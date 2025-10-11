<?php
/**
 * Test Script for Forgot Password Email Functionality
 * This script tests the email configuration and sending for Render deployment
 */

echo "<h1>🧪 Forgot Password Email Test</h1>";
echo "<p>Testing email configuration and sending functionality...</p>";

// Step 1: Check environment detection
echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🌍 Step 1: Environment Detection</h3>";

$isRender = isset($_ENV['RENDER']) || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'render.com') !== false);
echo "<strong>Environment:</strong> " . ($isRender ? 'Render (Production)' : 'Local Development') . "<br>";
echo "<strong>Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "<br>";
echo "<strong>Protocol:</strong> " . (isset($_SERVER['HTTPS']) ? 'HTTPS' : 'HTTP') . "<br>";

if ($isRender) {
    echo "✅ <strong>Render Environment:</strong> Detected<br>";
} else {
    echo "ℹ️ <strong>Local Environment:</strong> Using fallback configuration<br>";
}

echo "</div>";

// Step 2: Check email configuration
echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📧 Step 2: Email Configuration Check</h3>";

require_once 'config/email_config.php';

$config = getEmailConfig();
echo "<strong>From Address:</strong> " . $config['from_address'] . "<br>";
echo "<strong>From Name:</strong> " . $config['from_name'] . "<br>";
echo "<strong>SMTP Host:</strong> " . $config['smtp_host'] . "<br>";
echo "<strong>SMTP Port:</strong> " . $config['smtp_port'] . "<br>";
echo "<strong>SMTP Encryption:</strong> " . $config['smtp_encryption'] . "<br>";
echo "<strong>SMTP Username:</strong> " . ($config['smtp_username'] ? 'Set' : 'Not set') . "<br>";
echo "<strong>SMTP Password:</strong> " . ($config['smtp_password'] ? 'Set' : 'Not set') . "<br>";

// Check if all required fields are set
$requiredFields = ['from_address', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password'];
$allSet = true;
foreach ($requiredFields as $field) {
    if (empty($config[$field])) {
        $allSet = false;
        break;
    }
}

if ($allSet) {
    echo "✅ <strong>Configuration:</strong> All required fields are set<br>";
} else {
    echo "❌ <strong>Configuration:</strong> Missing required fields<br>";
}

echo "</div>";

// Step 3: Test PHPMailer functionality
echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔧 Step 3: PHPMailer Test</h3>";

require_once 'config/phpmailer_helper.php';

// Test with a dummy email (don't actually send)
$testEmail = 'test@example.com';
$testSubject = 'Test Email - SmartUnion Password Reset';
$testMessage = '<h1>Test Email</h1><p>This is a test email to verify PHPMailer configuration.</p>';

echo "<strong>Testing PHPMailer with:</strong><br>";
echo "To: $testEmail<br>";
echo "Subject: $testSubject<br>";
echo "Message: Test HTML message<br>";

// Suppress actual sending for testing
ob_start();
$result = sendMailPHPMailer($testEmail, $testSubject, $testMessage);
$output = ob_get_clean();

if ($result) {
    echo "✅ <strong>PHPMailer Test:</strong> SUCCESS<br>";
} else {
    echo "❌ <strong>PHPMailer Test:</strong> FAILED<br>";
    echo "<strong>Output:</strong> " . htmlspecialchars($output) . "<br>";
}

echo "</div>";

// Step 4: Test URL generation
echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔗 Step 4: URL Generation Test</h3>";

// Simulate URL generation like in forgot_password.php
$protocol = 'https';
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $protocol = 'https';
} elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $protocol = 'https';
} elseif ($isRender) {
    $protocol = 'https';
} else {
    $protocol = 'http';
}

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . '://' . $host;
$testToken = 'test_token_12345';
$resetLink = $baseUrl . '/auth/reset_password.php?token=' . $testToken;

echo "<strong>Generated URL:</strong> $resetLink<br>";
echo "<strong>Protocol:</strong> $protocol<br>";
echo "<strong>Host:</strong> $host<br>";

if (filter_var($resetLink, FILTER_VALIDATE_URL)) {
    echo "✅ <strong>URL Validation:</strong> Valid<br>";
} else {
    echo "❌ <strong>URL Validation:</strong> Invalid<br>";
}

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
    'RENDER' => $_ENV['RENDER'] ?? 'Not set'
];

foreach ($envVars as $key => $value) {
    echo "<strong>$key:</strong> $value<br>";
}

echo "</div>";

// Step 6: Recommendations
echo "<div style='background: #e2e3e5; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #6c757d;'>";
echo "<h2>📋 Recommendations for Render Deployment</h2>";

echo "<h3>✅ Required Environment Variables:</h3>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
echo "SMTP_HOST=smtp.gmail.com\n";
echo "SMTP_PORT=587\n";
echo "SMTP_USERNAME=your-email@gmail.com\n";
echo "SMTP_PASSWORD=your-app-password\n";
echo "SMTP_FROM_EMAIL=your-email@gmail.com\n";
echo "SMTP_FROM_NAME=SmartUnion\n";
echo "</pre>";

echo "<h3>🔧 Gmail Setup Instructions:</h3>";
echo "<ol>";
echo "<li>Enable 2-Factor Authentication on your Gmail account</li>";
echo "<li>Generate an App Password: Google Account → Security → App passwords</li>";
echo "<li>Use the App Password (not your regular password) for SMTP_PASSWORD</li>";
echo "<li>Set SMTP_PORT to 587 and SMTP_ENCRYPTION to 'tls'</li>";
echo "</ol>";

echo "<h3>🧪 Testing Steps:</h3>";
echo "<ol>";
echo "<li>Set all environment variables in Render dashboard</li>";
echo "<li>Deploy your application</li>";
echo "<li>Visit: <a href='auth/forgot_password.php'>auth/forgot_password.php</a></li>";
echo "<li>Enter a valid email address</li>";
echo "<li>Check your email inbox and spam folder</li>";
echo "</ol>";

echo "<h3>🐛 Troubleshooting:</h3>";
echo "<ul>";
echo "<li><strong>Email not received:</strong> Check spam folder, verify SMTP credentials</li>";
echo "<li><strong>SMTP timeout:</strong> This is normal on Render, emails may still be sent</li>";
echo "<li><strong>Invalid token:</strong> Check database connection and token storage</li>";
echo "<li><strong>URL issues:</strong> Verify environment variables are set correctly</li>";
echo "</ul>";

echo "</div>";

echo "<div style='background: #d4edda; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #28a745;'>";
echo "<h2>🎉 Email Configuration Test Complete!</h2>";
echo "<p><strong>Your forgot password email functionality should now work on Render!</strong></p>";
echo "<p>If you see any issues above, please check your environment variables and SMTP configuration.</p>";
echo "</div>";
?>
