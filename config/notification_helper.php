<?php
// config/notification_helper.php

require_once 'email_config.php';

// Dynamic table name for PostgreSQL compatibility
$members_table = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql' ? 'members' : 'membership_monitoring';
require_once 'smtp_email.php';

class NotificationHelper {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Send email notification to all active members
     */
    public function sendToAllMembers($subject, $message, $type = 'general') {
        try {
            // Get all active members with email addresses
            $stmt = $this->db->prepare("
                SELECT name, email 
                FROM $members_table 
                WHERE status = 'active' 
                AND email IS NOT NULL 
                AND email != ''
            ");
            $stmt->execute();
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sentCount = 0;
            $failedCount = 0;
            
            foreach ($members as $member) {
                $personalizedMessage = $this->personalizeMessage($message, $member['name'], $type);
                $result = $this->sendEmail($member['email'], $subject, $personalizedMessage);
                
                if ($result) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            }
            
            return [
                'success' => true,
                'sent' => $sentCount,
                'failed' => $failedCount,
                'total' => count($members)
            ];
            
        } catch (Exception $e) {
            error_log("NotificationHelper Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send email notification to members in a specific region
     */
    public function sendToRegion($region, $subject, $message, $type = 'general') {
        try {
            $stmt = $this->db->prepare("
                SELECT name, email 
                FROM $members_table 
                WHERE status = 'active' 
                AND region = ? 
                AND email IS NOT NULL 
                AND email != ''
            ");
            $stmt->execute([$region]);
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sentCount = 0;
            $failedCount = 0;
            
            foreach ($members as $member) {
                $personalizedMessage = $this->personalizeMessage($message, $member['name'], $type);
                $result = $this->sendEmail($member['email'], $subject, $personalizedMessage);
                
                if ($result) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            }
            
            return [
                'success' => true,
                'sent' => $sentCount,
                'failed' => $failedCount,
                'total' => count($members)
            ];
            
        } catch (Exception $e) {
            error_log("NotificationHelper Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send email notification to specific members
     */
    public function sendToMembers($memberIds, $subject, $message, $type = 'general') {
        try {
            if (empty($memberIds)) {
                return ['success' => false, 'error' => 'No member IDs provided'];
            }
            
            $placeholders = str_repeat('?,', count($memberIds) - 1) . '?';
            $stmt = $this->db->prepare("
                SELECT name, email 
                FROM $members_table 
                WHERE id IN ($placeholders) 
                AND status = 'active' 
                AND email IS NOT NULL 
                AND email != ''
            ");
            $stmt->execute($memberIds);
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sentCount = 0;
            $failedCount = 0;
            
            foreach ($members as $member) {
                $personalizedMessage = $this->personalizeMessage($message, $member['name'], $type);
                $result = $this->sendEmail($member['email'], $subject, $personalizedMessage);
                
                if ($result) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            }
            
            return [
                'success' => true,
                'sent' => $sentCount,
                'failed' => $failedCount,
                'total' => count($members)
            ];
            
        } catch (Exception $e) {
            error_log("NotificationHelper Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Personalize message with member name and type-specific content
     */
    private function personalizeMessage($message, $memberName, $type) {
        $personalizedMessage = str_replace('{MEMBER_NAME}', $memberName, $message);
        
        // Add type-specific styling
        switch ($type) {
            case 'event':
                $personalizedMessage = $this->wrapInEventTemplate($personalizedMessage);
                break;
            case 'announcement':
                $personalizedMessage = $this->wrapInAnnouncementTemplate($personalizedMessage);
                break;
            default:
                $personalizedMessage = $this->wrapInGeneralTemplate($personalizedMessage);
        }
        
        return $personalizedMessage;
    }
    
    /**
     * Send email using the existing email system
     */
    private function sendEmail($to, $subject, $message) {
        // Use PHPMailer for sending emails
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
        $config = getEmailConfig();
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
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
            if ($config['log_attempts']) {
                error_log("PHPMailer: To=$to, Subject=$subject, From=" . $config['from_address'] . ", Result=Success");
            }
            return true;
        } catch (PHPMailer\PHPMailer\Exception $e) {
            if ($config['log_attempts']) {
                error_log("PHPMailer: To=$to, Subject=$subject, From=" . $config['from_address'] . ", Result=Failed, Error=" . $mail->ErrorInfo);
            }
            return false;
        }
    }
    
    /**
     * Wrap message in event notification template
     */
    private function wrapInEventTemplate($message) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Event Notification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .event-icon { font-size: 24px; margin-bottom: 10px; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="event-icon">📅</div>
                    <h2>New Event Notification</h2>
                    <p>SmartUnion</p>
                </div>
                <div class="content">
                    ' . $message . '
                </div>
                <div class="footer">
                    <p>This is an automated notification from SmartUnion.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Wrap message in announcement notification template
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
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .announcement-icon { font-size: 24px; margin-bottom: 10px; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="announcement-icon">📢</div>
                    <h2>Important Announcement</h2>
                    <p>SmartUnion</p>
                </div>
                <div class="content">
                    ' . $message . '
                </div>
                <div class="footer">
                    <p>This is an automated notification from SmartUnion.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Wrap message in general notification template
     */
    private function wrapInGeneralTemplate($message) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Notification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #6c757d; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .notification-icon { font-size: 24px; margin-bottom: 10px; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="notification-icon">🔔</div>
                    <h2>System Notification</h2>
                    <p>SmartUnion System</p>
                </div>
                <div class="content">
                    ' . $message . '
                </div>
                <div class="footer">
                    <p>This is an automated notification from SmartUnion.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>';
    }
}
?>
