<?php
/**
 * Async Notification Helper
 * Optimized notification system for Render deployment without lag
 */

require_once 'email_config.php';
require_once 'smtp_email.php';

class AsyncNotificationHelper {
    private $db;
    private $members_table;
    
    public function __construct($database) {
        $this->db = $database;
        $this->members_table = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql' ? 'members' : 'membership_monitoring';
    }
    
    /**
     * Queue announcement for async email processing
     * Returns immediately without sending emails synchronously
     */
    public function queueAnnouncementNotification($announcementId, $title, $content) {
        try {
            // Create email queue entry
            $stmt = $this->db->prepare("
                INSERT INTO email_queue (type, subject, message, status, created_at) 
                VALUES (?, ?, ?, 'pending', CURRENT_TIMESTAMP)
            ");
            
            $subject = "Important Announcement: " . $title;
            $message = $this->createAnnouncementMessage($title, $content);
            
            $result = $stmt->execute(['announcement', $subject, $message]);
            
            if ($result) {
                $queueId = $this->db->lastInsertId();
                
                // Get all active members and create queue items
                $stmt = $this->db->prepare("
                    SELECT id, name, email 
                    FROM {$this->members_table} 
                    WHERE status = 'active' 
                    AND email IS NOT NULL 
                    AND email != ''
                ");
                $stmt->execute();
                $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Create individual queue items for each member
                $stmt = $this->db->prepare("
                    INSERT INTO email_queue_items (queue_id, member_id, member_name, member_email, status, created_at) 
                    VALUES (?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
                ");
                
                foreach ($members as $member) {
                    $stmt->execute([
                        $queueId,
                        $member['id'],
                        $member['name'],
                        $member['email']
                    ]);
                }
                
                // Trigger async processing
                $this->triggerAsyncProcessing();
                
                return [
                    'success' => true,
                    'queue_id' => $queueId,
                    'total_members' => count($members),
                    'message' => 'Announcement queued for email processing'
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Failed to queue announcement'
            ];
            
        } catch (Exception $e) {
            error_log("AsyncNotificationHelper Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create announcement message template
     */
    private function createAnnouncementMessage($title, $content) {
        return "
            <h3>Hello {MEMBER_NAME}!</h3>
            <p>We have an important announcement for you:</p>
            
            <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;'>
                <h4 style='color: #28a745; margin-top: 0;'>" . htmlspecialchars($title) . "</h4>
                <div style='margin-top: 15px;'>
                    " . nl2br(htmlspecialchars($content)) . "
                </div>
            </div>
            
            <p>Please take note of this important information.</p>
            <p>Best regards,<br>SmartUnion</p>
        ";
    }
    
    /**
     * Trigger async processing using background job
     */
    private function triggerAsyncProcessing() {
        // Use a simple background job approach
        // In a real production environment, you might use Redis, RabbitMQ, or similar
        
        // For Render, we'll use a simple approach with a separate endpoint
        $baseUrl = $this->getBaseUrl();
        $this->makeAsyncRequest($baseUrl . '/process_email_queue.php');
    }
    
    /**
     * Get base URL for async requests
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
    
    /**
     * Make async HTTP request
     */
    private function makeAsyncRequest($url) {
        // Use cURL for async request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1); // 1 second timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // Execute async
        curl_exec($ch);
        curl_close($ch);
    }
    
    /**
     * Process email queue (called by background job)
     */
    public function processEmailQueue($limit = 10) {
        try {
            // Get pending queue items
            $stmt = $this->db->prepare("
                SELECT eqi.*, eq.subject, eq.message 
                FROM email_queue_items eqi
                JOIN email_queue eq ON eqi.queue_id = eq.id
                WHERE eqi.status = 'pending'
                ORDER BY eqi.created_at ASC
                LIMIT " . (int)$limit
            );
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $processed = 0;
            $sent = 0;
            $failed = 0;
            
            foreach ($items as $item) {
                // Mark as processing
                $stmt = $this->db->prepare("UPDATE email_queue_items SET status = 'processing' WHERE id = ?");
                $stmt->execute([$item['id']]);
                
                // Personalize message
                $personalizedMessage = str_replace('{MEMBER_NAME}', $item['member_name'], $item['message']);
                $personalizedMessage = $this->wrapInAnnouncementTemplate($personalizedMessage);
                
                // Send email
                $result = $this->sendEmail($item['member_email'], $item['subject'], $personalizedMessage);
                
                if ($result) {
                    $sent++;
                    $status = 'sent';
                } else {
                    $failed++;
                    $status = 'failed';
                }
                
                // Update status
                $stmt = $this->db->prepare("UPDATE email_queue_items SET status = ?, processed_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$status, $item['id']]);
                
                $processed++;
                
                // Small delay to prevent overwhelming SMTP server
                usleep(100000); // 0.1 second
            }
            
            return [
                'success' => true,
                'processed' => $processed,
                'sent' => $sent,
                'failed' => $failed
            ];
            
        } catch (Exception $e) {
            error_log("Email queue processing error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send email using PHPMailer
     */
    private function sendEmail($to, $subject, $message) {
        try {
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
            
            $config = getEmailConfig();
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_username'];
            $mail->Password = $config['smtp_password'];
            $mail->SMTPSecure = $config['smtp_encryption'] ?? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $config['smtp_port'];
            $mail->CharSet = $config['charset'];
            
            // Recipients
            $mail->setFrom($config['from_address'], $config['from_name']);
            $mail->addAddress($to);
            $mail->addReplyTo($config['reply_to']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);
            $mail->Priority = $config['priority'];
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Wrap message in announcement template
     */
    private function wrapInAnnouncementTemplate($message) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Announcement</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>📢 SmartUnion Announcement</h2>
                </div>
                <div class="content">
                    ' . $message . '
                </div>
                <div class="footer">
                    <p>This is an automated message from SmartUnion</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Get queue status
     */
    public function getQueueStatus() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM email_queue_items 
                GROUP BY status
            ");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $status = [
                'pending' => 0,
                'processing' => 0,
                'sent' => 0,
                'failed' => 0
            ];
            
            foreach ($results as $result) {
                $status[$result['status']] = (int)$result['count'];
            }
            
            return $status;
            
        } catch (Exception $e) {
            error_log("Queue status error: " . $e->getMessage());
            return null;
        }
    }
}
?>
