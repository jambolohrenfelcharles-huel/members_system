<?php
/**
 * ACTUAL EMAIL DELIVERY TEST
 * This tests if emails are ACTUALLY being sent
 */

echo "<h1>📧 ACTUAL EMAIL DELIVERY TEST</h1>";
echo "<p>Testing if emails are ACTUALLY being sent...</p>";

// Step 1: Environment Check
echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🌍 Step 1: Environment Check</h3>";

$isRender = isset($_ENV['RENDER']) || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'render.com') !== false);
echo "<strong>Environment:</strong> " . ($isRender ? 'Render (Production)' : 'Local Development') . "<br>";
echo "<strong>Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "<br>";

echo "<h4>Actual Email Delivery Methods:</h4>";
echo "✅ <strong>PHPMailer:</strong> Proper SMTP authentication<br>";
echo "✅ <strong>SendGrid API:</strong> Real email service<br>";
echo "✅ <strong>Mailgun API:</strong> Real email service<br>";
echo "✅ <strong>Resend API:</strong> Real email service<br>";
echo "✅ <strong>mail() Function:</strong> Native PHP mail<br>";
echo "✅ <strong>Webhook Services:</strong> External webhook integration<br>";
echo "❌ <strong>No Fake Success:</strong> Returns false if all methods fail<br>";

echo "</div>";

// Step 2: Test Actual Email Delivery
echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📧 Step 2: Actual Email Delivery Test</h3>";

require_once 'config/actual_email_delivery.php';

$testEmail = 'charlesjambo3@gmail.com'; // Use your real email for testing
$testSubject = 'ACTUAL EMAIL DELIVERY TEST - ' . date('Y-m-d H:i:s');
$testMessage = '<h1>ACTUAL EMAIL DELIVERY TEST</h1>';
$testMessage .= '<p>This email tests if the system ACTUALLY sends emails.</p>';
$testMessage .= '<p><strong>Test Time:</strong> ' . date('Y-m-d H:i:s') . '</p>';
$testMessage .= '<p><strong>Test ID:</strong> ' . uniqid() . '</p>';
$testMessage .= '<p>If you receive this email, the system is working correctly!</p>';

echo "<strong>Testing ACTUAL email delivery...</strong><br>";
echo "<strong>To:</strong> $testEmail<br>";
echo "<strong>Subject:</strong> $testSubject<br>";

$startTime = microtime(true);
$result = sendEmailActually($testEmail, $testSubject, $testMessage);
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

if ($result) {
    echo "✅ <strong>Actual Email Delivery Test:</strong> SUCCESS ({$duration}ms)<br>";
    echo "<strong>Result:</strong> Email was ACTUALLY sent! Check your inbox.<br>";
    echo "<strong>Note:</strong> If you don't receive the email, check your spam folder or SMTP configuration.<br>";
} else {
    echo "❌ <strong>Actual Email Delivery Test:</strong> FAILED ({$duration}ms)<br>";
    echo "<strong>Result:</strong> Email was NOT sent. Check SMTP configuration and logs.<br>";
    echo "<strong>Action Required:</strong> Configure SMTP settings or external email service.<br>";
}

echo "</div>";

// Step 3: Test Contact Admin
echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📨 Step 3: Contact Admin Test</h3>";

$adminEmail = 'charlesjambo3@gmail.com';
$contactSubject = 'ACTUAL Contact Admin Test - ' . date('Y-m-d H:i:s');
$contactMessage = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
$contactMessage .= '<h2 style="color: #333;">ACTUAL Contact Admin Test</h2>';
$contactMessage .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">';
$contactMessage .= '<p><strong>From:</strong> test@example.com</p>';
$contactMessage .= '<p><strong>Test Time:</strong> ' . date('Y-m-d H:i:s') . '</p>';
$contactMessage .= '<p><strong>Message:</strong></p>';
$contactMessage .= '<div style="background: white; padding: 15px; border-left: 4px solid #667eea; margin: 10px 0;">';
$contactMessage .= 'This is an ACTUAL test message to verify email delivery is working.';
$contactMessage .= '</div>';
$contactMessage .= '</div>';
$contactMessage .= '<p style="color: #666; font-size: 14px;">This message was sent from the SmartUnion contact form.</p>';
$contactMessage .= '</div>';

echo "<strong>Testing contact admin functionality...</strong><br>";
echo "<strong>Admin Email:</strong> $adminEmail<br>";
echo "<strong>Subject:</strong> $contactSubject<br>";

$contactResult = sendEmailActually($adminEmail, $contactSubject, $contactMessage, null, null, 'test@example.com', 'Test User');

if ($contactResult) {
    echo "✅ <strong>Contact Admin Test:</strong> SUCCESS<br>";
    echo "<strong>Result:</strong> Contact admin email was ACTUALLY sent!<br>";
} else {
    echo "❌ <strong>Contact Admin Test:</strong> FAILED<br>";
    echo "<strong>Result:</strong> Contact admin email was NOT sent.<br>";
}

echo "</div>";

// Step 4: Test Individual Methods
echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔧 Step 4: Individual Method Tests</h3>";

require_once 'config/email_config.php';
$config = getEmailConfig();

echo "<h4>Method 1: PHPMailer</h4>";
$phpmailerResult = tryPHPMailerActual($testEmail, 'PHPMailer Test', '<p>PHPMailer test</p>', null, null, null, null, $config);
echo $phpmailerResult ? "✅ PHPMailer: SUCCESS<br>" : "❌ PHPMailer: FAILED<br>";

echo "<h4>Method 2: SendGrid</h4>";
$sendgridResult = trySendGridActual($testEmail, 'SendGrid Test', '<p>SendGrid test</p>', null, null);
echo $sendgridResult ? "✅ SendGrid: SUCCESS<br>" : "❌ SendGrid: FAILED<br>";

echo "<h4>Method 3: Mailgun</h4>";
$mailgunResult = tryMailgunActual($testEmail, 'Mailgun Test', '<p>Mailgun test</p>', null, null);
echo $mailgunResult ? "✅ Mailgun: SUCCESS<br>" : "❌ Mailgun: FAILED<br>";

echo "<h4>Method 4: Resend</h4>";
$resendResult = tryResendActual($testEmail, 'Resend Test', '<p>Resend test</p>', null, null);
echo $resendResult ? "✅ Resend: SUCCESS<br>" : "❌ Resend: FAILED<br>";

echo "<h4>Method 5: mail()</h4>";
$mailResult = trySimpleMailActual($testEmail, 'mail() Test', '<p>mail() test</p>', null, null, $config);
echo $mailResult ? "✅ mail(): SUCCESS<br>" : "❌ mail(): FAILED<br>";

echo "<h4>Method 6: Webhook</h4>";
$webhookResult = tryWebhookActual($testEmail, 'Webhook Test', '<p>Webhook test</p>', null, null);
echo $webhookResult ? "✅ Webhook: SUCCESS<br>" : "❌ Webhook: FAILED<br>";

echo "</div>";

// Step 5: Environment Variables Check
echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔧 Step 5: Environment Variables Check</h3>";

$envVars = [
    'SMTP_HOST' => $_ENV['SMTP_HOST'] ?? 'Not set',
    'SMTP_PORT' => $_ENV['SMTP_PORT'] ?? 'Not set',
    'SMTP_USERNAME' => $_ENV['SMTP_USERNAME'] ?? 'Not set',
    'SMTP_PASSWORD' => isset($_ENV['SMTP_PASSWORD']) && $_ENV['SMTP_PASSWORD'] ? 'Set' : 'Not set',
    'SMTP_FROM_EMAIL' => $_ENV['SMTP_FROM_EMAIL'] ?? 'Not set',
    'SMTP_FROM_NAME' => $_ENV['SMTP_FROM_NAME'] ?? 'Not set',
    'SENDGRID_API_KEY' => isset($_ENV['SENDGRID_API_KEY']) && $_ENV['SENDGRID_API_KEY'] ? 'Set' : 'Not set',
    'MAILGUN_API_KEY' => isset($_ENV['MAILGUN_API_KEY']) && $_ENV['MAILGUN_API_KEY'] ? 'Set' : 'Not set',
    'MAILGUN_DOMAIN' => $_ENV['MAILGUN_DOMAIN'] ?? 'Not set',
    'RESEND_API_KEY' => isset($_ENV['RESEND_API_KEY']) && $_ENV['RESEND_API_KEY'] ? 'Set' : 'Not set',
    'EMAIL_WEBHOOK_URL' => isset($_ENV['EMAIL_WEBHOOK_URL']) && $_ENV['EMAIL_WEBHOOK_URL'] ? 'Set' : 'Not set',
    'ZAPIER_WEBHOOK_URL' => isset($_ENV['ZAPIER_WEBHOOK_URL']) && $_ENV['ZAPIER_WEBHOOK_URL'] ? 'Set' : 'Not set',
    'IFTTT_WEBHOOK_URL' => isset($_ENV['IFTTT_WEBHOOK_URL']) && $_ENV['IFTTT_WEBHOOK_URL'] ? 'Set' : 'Not set',
    'MAKE_WEBHOOK_URL' => isset($_ENV['MAKE_WEBHOOK_URL']) && $_ENV['MAKE_WEBHOOK_URL'] ? 'Set' : 'Not set',
    'WEBHOOK_SITE_URL' => isset($_ENV['WEBHOOK_SITE_URL']) && $_ENV['WEBHOOK_SITE_URL'] ? 'Set' : 'Not set'
];

foreach ($envVars as $key => $value) {
    $status = $value === 'Not set' ? '❌' : '✅';
    echo "$status <strong>$key:</strong> $value<br>";
}

echo "</div>";

// Step 6: Configuration Instructions
echo "<div style='background: #e2e3e5; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #6c757d;'>";
echo "<h2>🔧 ACTUAL EMAIL DELIVERY CONFIGURATION</h2>";

echo "<h3>✅ Required Configuration:</h3>";
echo "<p>To ACTUALLY send emails, you need to configure at least one of these methods:</p>";

echo "<h4>1. SMTP Configuration (Required for PHPMailer):</h4>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
echo "SMTP_HOST=smtp.gmail.com\n";
echo "SMTP_PORT=587\n";
echo "SMTP_USERNAME=your-email@gmail.com\n";
echo "SMTP_PASSWORD=your-gmail-app-password\n";
echo "SMTP_FROM_EMAIL=your-email@gmail.com\n";
echo "SMTP_FROM_NAME=SmartUnion\n";
echo "</pre>";

echo "<h4>2. SendGrid API (Recommended for Render):</h4>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
echo "SENDGRID_API_KEY=your-sendgrid-api-key\n";
echo "</pre>";

echo "<h4>3. Mailgun API:</h4>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
echo "MAILGUN_API_KEY=your-mailgun-api-key\n";
echo "MAILGUN_DOMAIN=your-mailgun-domain\n";
echo "</pre>";

echo "<h4>4. Resend API:</h4>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
echo "RESEND_API_KEY=your-resend-api-key\n";
echo "</pre>";

echo "<h3>🧪 Test Your Email Functionality:</h3>";
echo "<ul>";
echo "<li>📧 <strong>Forgot Password:</strong> <a href='auth/forgot_password.php'>auth/forgot_password.php</a></li>";
echo "<li>📨 <strong>Contact Admin:</strong> <a href='auth/contact_admin.php'>auth/contact_admin.php</a></li>";
echo "<li>👤 <strong>Member Signup:</strong> <a href='auth/signup.php'>auth/signup.php</a></li>";
echo "</ul>";

echo "<h3>⚠️ Important Notes:</h3>";
echo "<ul>";
echo "<li>❌ <strong>No Fake Success:</strong> This system returns false if emails can't be sent</li>";
echo "<li>✅ <strong>Real Delivery:</strong> Only returns true if email is actually sent</li>";
echo "<li>🔧 <strong>Configuration Required:</strong> You must configure SMTP or external service</li>";
echo "<li>📧 <strong>Check Inbox:</strong> If test shows success, check your email inbox</li>";
echo "<li>📁 <strong>Check Spam:</strong> Also check your spam/junk folder</li>";
echo "</ul>";

echo "</div>";

echo "<div style='background: #d4edda; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #28a745;'>";
echo "<h2>🎉 ACTUAL EMAIL DELIVERY SYSTEM READY!</h2>";
echo "<p><strong>This system ACTUALLY sends emails - no fake success messages!</strong></p>";
echo "<p>Configure SMTP settings or external email service to start sending real emails.</p>";
echo "<p><strong>If you see SUCCESS above, check your email inbox!</strong></p>";
echo "</div>";
?>
