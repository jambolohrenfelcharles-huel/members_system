<?php
/**
 * ACTUAL EMAIL DELIVERY SYSTEM
 * This system ACTUALLY sends emails, not just returns true
 */

require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/email_config.php';

/**
 * Actually send emails - this function REALLY sends emails
 */
function sendEmailActually($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    $config = getEmailConfig();
    
    // Log the email attempt
    error_log("ACTUAL EMAIL DELIVERY attempt to: $to, Subject: $subject");
    
    // Method 1: Try PHPMailer with proper SMTP authentication
    $result = tryPHPMailerActual($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName, $config);
    if ($result) {
        error_log("Email ACTUALLY sent via PHPMailer to: $to");
        return true;
    }
    
    // Method 2: Try SendGrid API (if configured)
    $result = trySendGridActual($to, $subject, $bodyHtml, $fromEmail, $fromName);
    if ($result) {
        error_log("Email ACTUALLY sent via SendGrid to: $to");
        return true;
    }
    
    // Method 3: Try Mailgun API (if configured)
    $result = tryMailgunActual($to, $subject, $bodyHtml, $fromEmail, $fromName);
    if ($result) {
        error_log("Email ACTUALLY sent via Mailgun to: $to");
        return true;
    }
    
    // Method 4: Try Resend API (if configured)
    $result = tryResendActual($to, $subject, $bodyHtml, $fromEmail, $fromName);
    if ($result) {
        error_log("Email ACTUALLY sent via Resend to: $to");
        return true;
    }
    
    // Method 5: Try simple mail() function
    $result = trySimpleMailActual($to, $subject, $bodyHtml, $fromEmail, $fromName, $config);
    if ($result) {
        error_log("Email ACTUALLY sent via mail() to: $to");
        return true;
    }
    
    // Method 6: Try webhook services
    $result = tryWebhookActual($to, $subject, $bodyHtml, $fromEmail, $fromName);
    if ($result) {
        error_log("Email ACTUALLY sent via webhook to: $to");
        return true;
    }
    
    // If all methods fail, log the failure and return false
    error_log("FAILED to send email to: $to - All methods failed");
    return false;
}

/**
 * Try PHPMailer with proper SMTP authentication
 */
function tryPHPMailerActual($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName, $config) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser ?: $config['smtp_username'];
        $mail->Password = $smtpPass ?: $config['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['smtp_port'];
        $mail->CharSet = $config['charset'];
        
        // Timeout settings
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = false;
        $mail->SMTPAutoTLS = true;
        $mail->SMTPDebug = 0; // Set to 2 for debugging
        
        // SSL options for Render
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
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
        
        // Suppress output
        ob_start();
        $result = $mail->send();
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        return $result;
        
    } catch (Exception $e) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        error_log("PHPMailer failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Try SendGrid API
 */
function trySendGridActual($to, $subject, $bodyHtml, $fromEmail, $fromName) {
    $apiKey = $_ENV['SENDGRID_API_KEY'] ?? '';
    if (!$apiKey) {
        return false;
    }
    
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
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("SendGrid cURL error: $error");
        return false;
    }
    
    if ($httpCode === 202) {
        return true;
    } else {
        error_log("SendGrid API error: HTTP $httpCode - $result");
        return false;
    }
}

/**
 * Try Mailgun API
 */
function tryMailgunActual($to, $subject, $bodyHtml, $fromEmail, $fromName) {
    $apiKey = $_ENV['MAILGUN_API_KEY'] ?? '';
    $domain = $_ENV['MAILGUN_DOMAIN'] ?? '';
    
    if (!$apiKey || !$domain) {
        return false;
    }
    
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
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Mailgun cURL error: $error");
        return false;
    }
    
    if ($httpCode === 200) {
        return true;
    } else {
        error_log("Mailgun API error: HTTP $httpCode - $result");
        return false;
    }
}

/**
 * Try Resend API
 */
function tryResendActual($to, $subject, $bodyHtml, $fromEmail, $fromName) {
    $apiKey = $_ENV['RESEND_API_KEY'] ?? '';
    if (!$apiKey) {
        return false;
    }
    
    $data = [
        'from' => "$fromName <$fromEmail>",
        'to' => [$to],
        'subject' => $subject,
        'html' => $bodyHtml
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Resend cURL error: $error");
        return false;
    }
    
    if ($httpCode === 200) {
        return true;
    } else {
        error_log("Resend API error: HTTP $httpCode - $result");
        return false;
    }
}

/**
 * Try simple mail() function
 */
function trySimpleMailActual($to, $subject, $bodyHtml, $fromEmail, $fromName, $config) {
    try {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . ($fromName ?: $config['from_name']) . ' <' . ($fromEmail ?: $config['from_address']) . '>',
            'Reply-To: ' . ($fromEmail ?: $config['from_address']),
            'X-Mailer: SmartUnion Actual Email System',
            'X-Priority: 3'
        ];
        
        $result = @mail($to, $subject, $bodyHtml, implode("\r\n", $headers));
        return $result;
        
    } catch (Exception $e) {
        error_log("mail() function failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Try webhook services
 */
function tryWebhookActual($to, $subject, $bodyHtml, $fromEmail, $fromName) {
    $webhookUrls = [
        $_ENV['EMAIL_WEBHOOK_URL'] ?? '',
        $_ENV['ZAPIER_WEBHOOK_URL'] ?? '',
        $_ENV['IFTTT_WEBHOOK_URL'] ?? '',
        $_ENV['MAKE_WEBHOOK_URL'] ?? '',
        $_ENV['WEBHOOK_SITE_URL'] ?? ''
    ];
    
    foreach ($webhookUrls as $webhookUrl) {
        if ($webhookUrl) {
            $result = sendViaWebhookActual($to, $subject, $bodyHtml, $fromEmail, $fromName, $webhookUrl);
            if ($result) return true;
        }
    }
    
    return false;
}

/**
 * Send via webhook
 */
function sendViaWebhookActual($to, $subject, $bodyHtml, $fromEmail, $fromName, $webhookUrl) {
    $data = [
        'to' => $to,
        'subject' => $subject,
        'body' => $bodyHtml,
        'from' => $fromEmail,
        'from_name' => $fromName,
        'timestamp' => time(),
        'source' => 'smartunion-actual-email',
        'platform' => 'render'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: SmartUnion-Actual-Email'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Webhook cURL error: $error");
        return false;
    }
    
    return ($httpCode >= 200 && $httpCode < 300);
}

/**
 * Legacy function for backward compatibility
 */
function sendMailPHPMailer($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    return sendEmailActually($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName);
}

/**
 * Legacy function for backward compatibility
 */
function sendEmailReliable($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    return sendEmailActually($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName);
}

/**
 * Legacy function for backward compatibility
 */
function sendEmailUltraReliable($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    return sendEmailActually($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName);
}

/**
 * Legacy function for backward compatibility
 */
function sendEmailGuaranteed($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    return sendEmailActually($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName);
}

/**
 * Legacy function for backward compatibility
 */
function sendEmailRender($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    return sendEmailActually($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName);
}

/**
 * Legacy function for backward compatibility
 */
function sendEmailUltraReliableRender($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    return sendEmailActually($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName);
}
?>
