<?php
/**
 * Email Configuration Test Script
 * Use this to test your SMTP settings before deploying
 */

require_once 'config/phpmailer_helper.php';

// Test email configuration
function testEmailConfig() {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'] ?? '';
        $mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'] ?? 587;
        
        // Recipients
        $mail->setFrom($_ENV['SMTP_FROM_EMAIL'] ?? '', $_ENV['SMTP_FROM_NAME'] ?? 'SmartApp');
        $mail->addAddress($_ENV['SMTP_FROM_EMAIL'] ?? '', 'Test User');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'SmartApp Email Test';
        $mail->Body = '<h1>Email Test Successful!</h1><p>Your SmartApp email configuration is working correctly.</p>';
        $mail->AltBody = 'Email Test Successful! Your SmartApp email configuration is working correctly.';
        
        $mail->send();
        echo "✅ Email test successful! Check your inbox.\n";
        return true;
        
    } catch (Exception $e) {
        echo "❌ Email test failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run the test
echo "Testing email configuration...\n";
testEmailConfig();
?>
