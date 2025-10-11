<?php
/**
 * RENDER PHPMailer SOLUTION
 * This system is specifically designed to work on Render.com
 */

require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/email_config.php';

/**
 * Send email specifically optimized for Render
 */
function sendEmailRender($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    $config = getEmailConfig();
    
    // Log the email attempt
    error_log("Render email attempt to: $to, Subject: $subject");
    
    // Method 1: Try PHPMailer with Render-optimized settings
    $result = tryPHPMailerRender($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName, $config);
    if ($result) {
        error_log("Email sent successfully via PHPMailer on Render to: $to");
        return true;
    }
    
    // Method 2: Try SendGrid API (highly recommended for Render)
    $result = trySendGridRender($to, $subject, $bodyHtml, $fromEmail, $fromName);
    if ($result) {
        error_log("Email sent successfully via SendGrid on Render to: $to");
        return true;
    }
    
    // Method 3: Try Mailgun API
    $result = tryMailgunRender($to, $subject, $bodyHtml, $fromEmail, $fromName);
    if ($result) {
        error_log("Email sent successfully via Mailgun on Render to: $to");
        return true;
    }
    
    // Method 4: Try Resend API
    $result = tryResendRender($to, $subject, $bodyHtml, $fromEmail, $fromName);
    if ($result) {
        error_log("Email sent successfully via Resend on Render to: $to");
        return true;
    }
    
    // Method 5: Try simple mail() function
    $result = trySimpleMailRender($to, $subject, $bodyHtml, $fromEmail, $fromName, $config);
    if ($result) {
        error_log("Email sent successfully via mail() on Render to: $to");
        return true;
    }
    
    // Method 6: Try webhook services
    $result = tryWebhookRender($to, $subject, $bodyHtml, $fromEmail, $fromName);
    if ($result) {
        error_log("Email sent successfully via webhook on Render to: $to");
        return true;
    }
    
    // If all methods fail, log the failure
    error_log("FAILED to send email to: $to - All Render methods failed");
    return false;
}

/**
 * Try PHPMailer with Render-optimized settings
 */
function tryPHPMailerRender($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName, $config) {
    $mail = new PHPMailer(true);
    
    try {
        // Render-optimized SMTP settings
        $mail->isSMTP();
        
        // Use environment variables for Render
        $mail->Host = $_ENV['SMTP_HOST'] ?? $config['smtp_host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser ?: $_ENV['SMTP_USERNAME'] ?? $config['smtp_username'];
        $mail->Password = $smtpPass ?: $_ENV['SMTP_PASSWORD'] ?? $config['smtp_password'];
        
        // Use TLS (port 587) - most reliable on Render
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'] ?? $config['smtp_port'] ?? 587;
        $mail->CharSet = $config['charset'];
        
        // Render-specific optimizations
        $mail->Timeout = 30; // Longer timeout for Render
        $mail->SMTPKeepAlive = false; // Disable keep-alive for Render
        $mail->SMTPAutoTLS = true; // Auto TLS negotiation
        $mail->SMTPDebug = 0; // Disable debug on Render
        
        // Render-specific SSL options
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT
            ]
        ];
        
        // Sender settings
        $finalFromName = $fromName ?: $_ENV['SMTP_FROM_NAME'] ?? $config['from_name'];
        $finalFromEmail = $fromEmail ?: $_ENV['SMTP_FROM_EMAIL'] ?? $config['from_address'];
        
        $mail->setFrom($finalFromEmail, $finalFromName);
        $mail->addReplyTo($finalFromEmail, $finalFromName);
        
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
        error_log("PHPMailer failed on Render: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Try SendGrid API (highly recommended for Render)
 */
function trySendGridRender($to, $subject, $bodyHtml, $fromEmail, $fromName) {
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Render-specific
    
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
function tryMailgunRender($to, $subject, $bodyHtml, $fromEmail, $fromName) {
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Render-specific
    
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
function tryResendRender($to, $subject, $bodyHtml, $fromEmail, $fromName) {
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Render-specific
    
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
function trySimpleMailRender($to, $subject, $bodyHtml, $fromEmail, $fromName, $config) {
    try {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . ($fromName ?: $_ENV['SMTP_FROM_NAME'] ?? $config['from_name']) . ' <' . ($fromEmail ?: $_ENV['SMTP_FROM_EMAIL'] ?? $config['from_address']) . '>',
            'Reply-To: ' . ($fromEmail ?: $_ENV['SMTP_FROM_EMAIL'] ?? $config['from_address']),
            'X-Mailer: SmartUnion Render Email System',
            'X-Priority: 3'
        ];
        
        $result = @mail($to, $subject, $bodyHtml, implode("\r\n", $headers));
        return $result;
        
    } catch (Exception $e) {
        error_log("mail() function failed on Render: " . $e->getMessage());
        return false;
    }
}

/**
 * Try webhook services
 */
function tryWebhookRender($to, $subject, $bodyHtml, $fromEmail, $fromName) {
    $webhookUrls = [
        $_ENV['EMAIL_WEBHOOK_URL'] ?? '',
        $_ENV['ZAPIER_WEBHOOK_URL'] ?? '',
        $_ENV['IFTTT_WEBHOOK_URL'] ?? '',
        $_ENV['MAKE_WEBHOOK_URL'] ?? '',
        $_ENV['WEBHOOK_SITE_URL'] ?? ''
    ];
    
    foreach ($webhookUrls as $webhookUrl) {
        if ($webhookUrl) {
            $result = sendViaWebhookRender($to, $subject, $bodyHtml, $fromEmail, $fromName, $webhookUrl);
            if ($result) return true;
        }
    }
    
    return false;
}

/**
 * Send via webhook
 */
function sendViaWebhookRender($to, $subject, $bodyHtml, $fromEmail, $fromName, $webhookUrl) {
    $data = [
        'to' => $to,
        'subject' => $subject,
        'body' => $bodyHtml,
        'from' => $fromEmail,
        'from_name' => $fromName,
        'timestamp' => time(),
        'source' => 'smartunion-render',
        'platform' => 'render'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: SmartUnion-Render-Email'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Render-specific
    
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
    return sendEmailRender($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName);
}

/**
 * Legacy function for backward compatibility
 */
function sendEmailReliable($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    return sendEmailRender($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName);
}

/**
 * Legacy function for backward compatibility
 */
function sendEmailUltraReliable($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    return sendEmailRender($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName);
}

/**
 * Legacy function for backward compatibility
 */
function sendEmailGuaranteed($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    return sendEmailRender($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName);
}

/**
 * Legacy function for backward compatibility
 */
function sendEmailActually($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    return sendEmailRender($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName);
}

/**
 * Legacy function for backward compatibility
 */
function sendEmailUltraReliableRender($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    return sendEmailRender($to, $subject, $bodyHtml, $smtpUser, $smtpPass, $fromEmail, $fromName);
}
?>
