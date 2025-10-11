<?php
/**
 * Enhanced Email Helper with Multiple Fallback Methods
 * This provides reliable email sending for Render deployment
 */

require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/email_config.php';

/**
 * Enhanced email sending with multiple fallback methods
 */
function sendEmailReliable($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    $config = getEmailConfig();
    $isRender = isset($_ENV['RENDER']) || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'render.com') !== false);
    
    // Method 1: Try PHPMailer with Render optimizations
    $result = tryPHPMailer($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName, $config, $isRender);
    if ($result) {
        return true;
    }
    
    // Method 2: Try cURL-based email service (if configured)
    $result = tryCurlEmail($to, $subject, $bodyHtml, $fromEmail, $fromName);
    if ($result) {
        return true;
    }
    
    // Method 3: Try webhook-based email (if configured)
    $result = tryWebhookEmail($to, $subject, $bodyHtml, $fromEmail, $fromName);
    if ($result) {
        return true;
    }
    
    // Method 4: Try simple mail() function as last resort
    $result = trySimpleMail($to, $subject, $bodyHtml, $fromEmail, $fromName);
    if ($result) {
        return true;
    }
    
    // All methods failed
    error_log("All email methods failed for: $to");
    return false;
}

/**
 * Method 1: Enhanced PHPMailer with Render optimizations
 */
function tryPHPMailer($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName, $config, $isRender) {
    $maxRetries = $isRender ? 2 : 1; // Fewer retries on Render
    
    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser ?: $config['smtp_username'];
            $mail->Password = $smtpPass ?: $config['smtp_password'];
            
            // Render-optimized settings
            if ($config['smtp_encryption'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $mail->Port = $config['smtp_port'];
            $mail->CharSet = $config['charset'];
            
            // Render-specific optimizations
            if ($isRender) {
                $mail->Timeout = 10; // Very short timeout for Render
                $mail->SMTPKeepAlive = false;
                $mail->SMTPAutoTLS = true;
                $mail->SMTPDebug = 0; // Disable debug on Render
            } else {
                $mail->Timeout = 30;
                $mail->SMTPKeepAlive = true;
                if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
                    $mail->SMTPDebug = 2;
                    $mail->Debugoutput = 'error_log';
                }
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
            
            // Suppress output during sending
            ob_start();
            $result = $mail->send();
            ob_end_clean();
            
            if ($result) {
                error_log("PHPMailer success (attempt $attempt) to: $to");
                return true;
            }
            
        } catch (Exception $e) {
            error_log("PHPMailer attempt $attempt failed: " . $mail->ErrorInfo);
            
            if ($attempt === $maxRetries) {
                return false;
            }
            
            // Short delay before retry
            usleep(500000); // 0.5 seconds
        }
    }
    
    return false;
}

/**
 * Method 2: cURL-based email service (SendGrid, Mailgun, etc.)
 */
function tryCurlEmail($to, $subject, $bodyHtml, $fromEmail, $fromName) {
    $config = getEmailConfig();
    
    // Try SendGrid if API key is available
    $sendgridKey = $_ENV['SENDGRID_API_KEY'] ?? '';
    if ($sendgridKey) {
        return sendViaSendGrid($to, $subject, $bodyHtml, $fromEmail, $fromName, $sendgridKey);
    }
    
    // Try Mailgun if API key is available
    $mailgunKey = $_ENV['MAILGUN_API_KEY'] ?? '';
    $mailgunDomain = $_ENV['MAILGUN_DOMAIN'] ?? '';
    if ($mailgunKey && $mailgunDomain) {
        return sendViaMailgun($to, $subject, $bodyHtml, $fromEmail, $fromName, $mailgunKey, $mailgunDomain);
    }
    
    return false;
}

/**
 * SendGrid implementation
 */
function sendViaSendGrid($to, $subject, $bodyHtml, $fromEmail, $fromName, $apiKey) {
    $data = [
        'personalizations' => [
            [
                'to' => [['email' => $to]]
            ]
        ],
        'from' => [
            'email' => $fromEmail,
            'name' => $fromName
        ],
        'subject' => $subject,
        'content' => [
            [
                'type' => 'text/html',
                'value' => $bodyHtml
            ]
        ]
    ];
    
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
    
    error_log("SendGrid failed with code: $httpCode");
    return false;
}

/**
 * Mailgun implementation
 */
function sendViaMailgun($to, $subject, $bodyHtml, $fromEmail, $fromName, $apiKey, $domain) {
    $data = [
        'from' => "$fromName <$fromEmail>",
        'to' => $to,
        'subject' => $subject,
        'html' => $bodyHtml
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mailgun.net/v3/$domain/messages");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_USERPWD, "api:$apiKey");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        error_log("Email sent via Mailgun to: $to");
        return true;
    }
    
    error_log("Mailgun failed with code: $httpCode");
    return false;
}

/**
 * Method 3: Webhook-based email service
 */
function tryWebhookEmail($to, $subject, $bodyHtml, $fromEmail, $fromName) {
    $webhookUrl = $_ENV['EMAIL_WEBHOOK_URL'] ?? '';
    if (!$webhookUrl) {
        return false;
    }
    
    $data = [
        'to' => $to,
        'subject' => $subject,
        'body' => $bodyHtml,
        'from' => $fromEmail,
        'from_name' => $fromName,
        'timestamp' => time()
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: SmartUnion-Email-System'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        error_log("Email sent via webhook to: $to");
        return true;
    }
    
    error_log("Webhook email failed with code: $httpCode");
    return false;
}

/**
 * Method 4: Simple mail() function as last resort
 */
function trySimpleMail($to, $subject, $bodyHtml, $fromEmail, $fromName) {
    $config = getEmailConfig();
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . ($fromName ?: $config['from_name']) . ' <' . ($fromEmail ?: $config['from_address']) . '>',
        'Reply-To: ' . ($fromEmail ?: $config['from_address']),
        'X-Mailer: SmartUnion Email System'
    ];
    
    $result = mail($to, $subject, $bodyHtml, implode("\r\n", $headers));
    
    if ($result) {
        error_log("Email sent via mail() to: $to");
        return true;
    }
    
    error_log("mail() function failed for: $to");
    return false;
}

/**
 * Legacy function for backward compatibility
 */
function sendMailPHPMailer($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    return sendEmailReliable($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName);
}
?>
