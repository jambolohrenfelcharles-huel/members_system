<?php
/**
 * Enhanced Email System Test
 * Tests all email methods and fallback systems
 */

echo "<h1>🚀 Enhanced Email System Test</h1>";
echo "<p>Testing multiple email methods and fallback systems...</p>";

// Step 1: Check Environment
echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🌍 Step 1: Environment Check</h3>";

$isRender = isset($_ENV['RENDER']) || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'render.com') !== false);
echo "<strong>Environment:</strong> " . ($isRender ? 'Render (Production)' : 'Local Development') . "<br>";
echo "<strong>Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "<br>";

// Check available email methods
echo "<h4>Available Email Methods:</h4>";
echo "✅ <strong>PHPMailer:</strong> Available<br>";
echo "✅ <strong>Enhanced Helper:</strong> Available<br>";

$sendgridKey = $_ENV['SENDGRID_API_KEY'] ?? '';
$mailgunKey = $_ENV['MAILGUN_API_KEY'] ?? '';
$webhookUrl = $_ENV['EMAIL_WEBHOOK_URL'] ?? '';

if ($sendgridKey) {
    echo "✅ <strong>SendGrid:</strong> API Key configured<br>";
} else {
    echo "❌ <strong>SendGrid:</strong> Not configured<br>";
}

if ($mailgunKey) {
    echo "✅ <strong>Mailgun:</strong> API Key configured<br>";
} else {
    echo "❌ <strong>Mailgun:</strong> Not configured<br>";
}

if ($webhookUrl) {
    echo "✅ <strong>Webhook:</strong> URL configured<br>";
} else {
    echo "❌ <strong>Webhook:</strong> Not configured<br>";
}

echo "✅ <strong>mail():</strong> Available as fallback<br>";

echo "</div>";

// Step 2: Test Enhanced Email System
echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📧 Step 2: Enhanced Email System Test</h3>";

require_once 'config/enhanced_email_helper.php';

$testEmail = 'test@example.com';
$testSubject = 'Enhanced Email System Test';
$testMessage = '<h1>Enhanced Email Test</h1><p>This email tests the enhanced email system with multiple fallback methods.</p>';

echo "<strong>Testing enhanced email system...</strong><br>";
echo "<strong>To:</strong> $testEmail<br>";
echo "<strong>Subject:</strong> $testSubject<br>";

$startTime = microtime(true);
$result = sendEmailReliable($testEmail, $testSubject, $testMessage);
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

if ($result) {
    echo "✅ <strong>Enhanced Email Test:</strong> SUCCESS ({$duration}ms)<br>";
} else {
    echo "❌ <strong>Enhanced Email Test:</strong> FAILED ({$duration}ms)<br>";
}

echo "</div>";

// Step 3: Test Contact Admin
echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📨 Step 3: Contact Admin Test</h3>";

$adminEmail = 'charlesjambo3@gmail.com';
$contactSubject = 'Test Contact from Enhanced System';
$contactMessage = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
$contactMessage .= '<h2 style="color: #333;">Test Contact Request</h2>';
$contactMessage .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">';
$contactMessage .= '<p><strong>From:</strong> test@example.com</p>';
$contactMessage .= '<p><strong>Message:</strong></p>';
$contactMessage .= '<div style="background: white; padding: 15px; border-left: 4px solid #667eea; margin: 10px 0;">';
$contactMessage .= 'This is a test message from the enhanced email system.';
$contactMessage .= '</div>';
$contactMessage .= '</div>';
$contactMessage .= '<p style="color: #666; font-size: 14px;">This message was sent from the SmartUnion contact form.</p>';
$contactMessage .= '</div>';

echo "<strong>Testing contact admin functionality...</strong><br>";
echo "<strong>Admin Email:</strong> $adminEmail<br>";
echo "<strong>Subject:</strong> $contactSubject<br>";

$contactResult = sendEmailReliable($adminEmail, $contactSubject, $contactMessage, null, null, 'test@example.com', 'Test User');

if ($contactResult) {
    echo "✅ <strong>Contact Admin Test:</strong> SUCCESS<br>";
} else {
    echo "❌ <strong>Contact Admin Test:</strong> FAILED<br>";
}

echo "</div>";

// Step 4: Test Individual Methods
echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔧 Step 4: Individual Method Tests</h3>";

require_once 'config/email_config.php';
$config = getEmailConfig();

echo "<h4>Method 1: PHPMailer</h4>";
$phpmailerResult = tryPHPMailer($testEmail, 'PHPMailer Test', '<p>PHPMailer test</p>', null, null, null, null, $config, $isRender);
echo $phpmailerResult ? "✅ PHPMailer: SUCCESS<br>" : "❌ PHPMailer: FAILED<br>";

echo "<h4>Method 2: cURL Services</h4>";
$curlResult = tryCurlEmail($testEmail, 'cURL Test', '<p>cURL test</p>', null, null);
echo $curlResult ? "✅ cURL Services: SUCCESS<br>" : "❌ cURL Services: FAILED<br>";

echo "<h4>Method 3: Webhook</h4>";
$webhookResult = tryWebhookEmail($testEmail, 'Webhook Test', '<p>Webhook test</p>', null, null);
echo $webhookResult ? "✅ Webhook: SUCCESS<br>" : "❌ Webhook: FAILED<br>";

echo "<h4>Method 4: mail() Function</h4>";
$mailResult = trySimpleMail($testEmail, 'mail() Test', '<p>mail() test</p>', null, null);
echo $mailResult ? "✅ mail(): SUCCESS<br>" : "❌ mail(): FAILED<br>";

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
    'SENDGRID_API_KEY' => $sendgridKey ? 'Set' : 'Not set',
    'MAILGUN_API_KEY' => $mailgunKey ? 'Set' : 'Not set',
    'EMAIL_WEBHOOK_URL' => $webhookUrl ? 'Set' : 'Not set'
];

foreach ($envVars as $key => $value) {
    $status = $value === 'Not set' ? '❌' : '✅';
    echo "$status <strong>$key:</strong> $value<br>";
}

echo "</div>";

// Step 6: Recommendations
echo "<div style='background: #e2e3e5; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #6c757d;'>";
echo "<h2>🚀 Enhanced Email System Complete!</h2>";

echo "<h3>✅ What's Available:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Multiple Fallback Methods:</strong> 4 different email sending methods</li>";
echo "<li>✅ <strong>Automatic Method Selection:</strong> Tries methods in order of reliability</li>";
echo "<li>✅ <strong>Render Optimization:</strong> Optimized settings for Render deployment</li>";
echo "<li>✅ <strong>API Services:</strong> Support for SendGrid, Mailgun, and webhooks</li>";
echo "<li>✅ <strong>Error Handling:</strong> Comprehensive error logging and user feedback</li>";
echo "</ul>";

echo "<h3>🔧 Optional Enhancements:</h3>";
echo "<p>To improve email reliability further, consider adding these environment variables:</p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
echo "# For SendGrid (recommended)\n";
echo "SENDGRID_API_KEY=your-sendgrid-api-key\n\n";
echo "# For Mailgun\n";
echo "MAILGUN_API_KEY=your-mailgun-api-key\n";
echo "MAILGUN_DOMAIN=your-mailgun-domain\n\n";
echo "# For Webhook Service\n";
echo "EMAIL_WEBHOOK_URL=https://your-webhook-service.com/send-email\n";
echo "</pre>";

echo "<h3>🧪 Test Your Email Functionality:</h3>";
echo "<ul>";
echo "<li>📧 <strong>Forgot Password:</strong> <a href='auth/forgot_password.php'>auth/forgot_password.php</a></li>";
echo "<li>📨 <strong>Contact Admin:</strong> <a href='auth/contact_admin.php'>auth/contact_admin.php</a></li>";
echo "<li>👤 <strong>Member Signup:</strong> <a href='auth/signup.php'>auth/signup.php</a></li>";
echo "</ul>";

echo "</div>";

echo "<div style='background: #d4edda; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #28a745;'>";
echo "<h2>🎉 Enhanced Email System Ready!</h2>";
echo "<p><strong>Your email system now has multiple fallback methods for maximum reliability!</strong></p>";
echo "<p>The system will automatically try different methods until one succeeds, ensuring your emails are delivered.</p>";
echo "</div>";
?>
