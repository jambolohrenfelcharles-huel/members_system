
<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/email_config.php';

function sendMailPHPMailer($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    $config = getEmailConfig();
    
    // Check if we're on Render and handle accordingly
    $isRender = isset($_ENV['RENDER']) || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'render.com') !== false);
    
    // For Render, use more aggressive timeout and retry settings
    if ($isRender) {
        return sendMailPHPMailerRender($to, $subject, $bodyHtml, $config, $smtpUser, $smtpPass, $fromEmail, $fromName);
    }
    
    // Original implementation for local development
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser ?: $config['smtp_username'];
        $mail->Password = $smtpPass ?: $config['smtp_password'];
        
        // Handle encryption properly for different ports
        if ($config['smtp_encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        $mail->Port = $config['smtp_port'];
        $mail->CharSet = $config['charset'];
        
        // Timeout settings
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = true;
        
        // Debug settings
        if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = 'error_log';
        }
        
        // Sender settings
        $finalFromName = $fromName ?: $config['from_name'];
        $mail->setFrom($fromEmail ?: $config['from_address'], $finalFromName);
        $mail->addReplyTo($config['reply_to'], $finalFromName);
        
        // Recipients
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $bodyHtml;
        $mail->AltBody = strip_tags($bodyHtml);
        $mail->Priority = $config['priority'];
        
        // Send the email
        $result = $mail->send();
        
        // Log successful send
        if (defined('EMAIL_LOG_ATTEMPTS') && EMAIL_LOG_ATTEMPTS) {
            error_log("Email sent successfully to: $to, Subject: $subject");
        }
        
        return true;
        
    } catch (Exception $e) {
        // Enhanced error logging
        $errorMsg = "PHPMailer Error: " . $mail->ErrorInfo;
        error_log($errorMsg);
        
        return false;
    }
}

/**
 * Render-specific email sending with enhanced reliability
 */
function sendMailPHPMailerRender($to, $subject, $bodyHtml, $config, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    $maxRetries = 3;
    $retryDelay = 2; // seconds
    
    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings optimized for Render
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser ?: $config['smtp_username'];
            $mail->Password = $smtpPass ?: $config['smtp_password'];
            
            // Render-optimized encryption settings
            if ($config['smtp_encryption'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $mail->Port = $config['smtp_port'];
            $mail->CharSet = $config['charset'];
            
            // Render-specific timeout settings
            $mail->Timeout = 15; // Shorter timeout for Render
            $mail->SMTPKeepAlive = false; // Disable keep-alive on Render
            $mail->SMTPAutoTLS = true; // Auto TLS negotiation
            
            // Disable debug on Render to prevent output issues
            $mail->SMTPDebug = 0;
            
            // Sender settings
            $finalFromName = $fromName ?: $config['from_name'];
            $mail->setFrom($fromEmail ?: $config['from_address'], $finalFromName);
            $mail->addReplyTo($config['reply_to'], $finalFromName);
            
            // Recipients
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->AltBody = strip_tags($bodyHtml);
            $mail->Priority = $config['priority'];
            
            // Suppress all output during sending
            ob_start();
            $result = $mail->send();
            ob_end_clean();
            
            // Log successful send
            if (defined('EMAIL_LOG_ATTEMPTS') && EMAIL_LOG_ATTEMPTS) {
                error_log("Render Email sent successfully (attempt $attempt) to: $to, Subject: $subject");
            }
            
            return true;
            
        } catch (Exception $e) {
            // Log the attempt
            $errorMsg = "Render PHPMailer Error (attempt $attempt): " . $mail->ErrorInfo;
            error_log($errorMsg);
            
            // If this is the last attempt, return false
            if ($attempt === $maxRetries) {
                error_log("All $maxRetries attempts failed for email to: $to");
                return false;
            }
            
            // Wait before retry
            sleep($retryDelay);
            $retryDelay *= 2; // Exponential backoff
        }
    }
    
    return false;
}

/**
 * Alternative email sending method using cURL for Render
 */
function sendMailViaCurl($to, $subject, $bodyHtml, $fromEmail = null, $fromName = null) {
    $config = getEmailConfig();
    
    // This is a fallback method using a webhook or API service
    // You can implement SendGrid, Mailgun, or other API-based email services here
    
    $data = [
        'to' => $to,
        'subject' => $subject,
        'body' => $bodyHtml,
        'from' => $fromEmail ?: $config['from_address'],
        'from_name' => $fromName ?: $config['from_name']
    ];
    
    // Example implementation for SendGrid (uncomment and configure if needed)
    /*
    $apiKey = $_ENV['SENDGRID_API_KEY'] ?? '';
    if ($apiKey) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 202) {
            error_log("Email sent via SendGrid to: $to");
            return true;
        }
    }
    */
    
    return false;
}
