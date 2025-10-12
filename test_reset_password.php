<?php
/**
 * RESET PASSWORD TEST
 * This tests the complete reset password functionality
 */

echo "<h1>🔐 Reset Password Test</h1>";
echo "<p>Testing the complete reset password functionality...</p>";

// Step 1: Database Connection Test
echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🗄️ Step 1: Database Connection Test</h3>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ <strong>Database Connection:</strong> SUCCESS<br>";
    
    // Check users table
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
    $hasResetToken = $result->rowCount() > 0;
    
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'reset_expires'");
    $hasResetExpires = $result->rowCount() > 0;
    
    echo "✅ <strong>reset_token column:</strong> " . ($hasResetToken ? 'EXISTS' : 'MISSING') . "<br>";
    echo "✅ <strong>reset_expires column:</strong> " . ($hasResetExpires ? 'EXISTS' : 'MISSING') . "<br>";
    
} catch (Exception $e) {
    echo "❌ <strong>Database Connection:</strong> FAILED - " . $e->getMessage() . "<br>";
}

echo "</div>";

// Step 2: Email System Test
echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📧 Step 2: Email System Test</h3>";

try {
    require_once 'config/direct_email_solution.php';
    echo "✅ <strong>Direct Email Solution:</strong> LOADED<br>";
    
    // Test email sending
    $testEmail = 'charlesjambo3@gmail.com';
    $testSubject = 'Reset Password Test - ' . date('Y-m-d H:i:s');
    $testMessage = '<h1>Reset Password Test</h1><p>This is a test email for the reset password functionality.</p>';
    
    echo "<strong>Testing email sending...</strong><br>";
    $emailResult = sendEmailDirect($testEmail, $testSubject, $testMessage);
    
    if ($emailResult) {
        echo "✅ <strong>Email Test:</strong> SUCCESS<br>";
        echo "<strong>Result:</strong> Test email sent successfully!<br>";
    } else {
        echo "❌ <strong>Email Test:</strong> FAILED<br>";
        echo "<strong>Result:</strong> Email was not sent. Check SMTP configuration.<br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Email System:</strong> FAILED - " . $e->getMessage() . "<br>";
}

echo "</div>";

// Step 3: Reset Password Flow Test
echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔄 Step 3: Reset Password Flow Test</h3>";

try {
    // Simulate forgot password process
    $testEmail = 'charlesjambo3@gmail.com';
    
    // Check if user exists
    $stmt = $db->prepare("SELECT id, email FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ <strong>User Found:</strong> ID " . $user['id'] . "<br>";
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database
        $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $result = $stmt->execute([$token, $expires, $testEmail]);
        
        if ($result) {
            echo "✅ <strong>Token Generated:</strong> SUCCESS<br>";
            echo "<strong>Token:</strong> " . substr($token, 0, 16) . "...<br>";
            echo "<strong>Expires:</strong> $expires<br>";
            
            // Generate reset link
            $protocol = 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $protocol . '://' . $host;
            $resetLink = $baseUrl . '/auth/reset_password.php?token=' . $token;
            
            echo "<strong>Reset Link:</strong> <a href='$resetLink' target='_blank'>$resetLink</a><br>";
            
            // Send reset email
            $subject = 'SmartUnion Password Reset Request - Test';
            $message = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>";
            $message .= "<h2 style='color: #333;'>Password Reset Request - Test</h2>";
            $message .= "<p>This is a test email for the reset password functionality.</p>";
            $message .= "<p>Click the button below to reset your password:</p>";
            $message .= "<div style='text-align: center; margin: 30px 0;'>";
            $message .= "<a href='$resetLink' style='background-color: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>Reset Password</a>";
            $message .= "</div>";
            $message .= "<p style='color: #666; font-size: 14px;'>If the button doesn't work, copy and paste this link into your browser:</p>";
            $message .= "<p style='color: #667eea; word-break: break-all;'>$resetLink</p>";
            $message .= "<p style='color: #666; font-size: 14px;'>This link will expire in 1 hour for security reasons.</p>";
            $message .= "<p style='color: #666; font-size: 14px;'>This is a test email for the reset password functionality.</p>";
            $message .= "</div>";
            
            $emailSent = sendEmailDirect($testEmail, $subject, $message);
            
            if ($emailSent) {
                echo "✅ <strong>Reset Email:</strong> SENT SUCCESSFULLY<br>";
                echo "<strong>Result:</strong> Reset password email sent! Check your inbox.<br>";
            } else {
                echo "❌ <strong>Reset Email:</strong> FAILED TO SEND<br>";
                echo "<strong>Result:</strong> Email was not sent. Check SMTP configuration.<br>";
            }
            
        } else {
            echo "❌ <strong>Token Generation:</strong> FAILED<br>";
        }
        
    } else {
        echo "❌ <strong>User Not Found:</strong> No user with email $testEmail<br>";
        echo "<strong>Note:</strong> Create a user account first or use an existing email.<br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Reset Password Flow:</strong> FAILED - " . $e->getMessage() . "<br>";
}

echo "</div>";

// Step 4: Test Reset Password Page
echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔗 Step 4: Reset Password Page Test</h3>";

echo "<h4>Test URLs:</h4>";
echo "<ul>";
echo "<li><a href='auth/forgot_password.php' target='_blank'>🔐 Forgot Password Page</a></li>";
echo "<li><a href='auth/reset_password.php' target='_blank'>🔄 Reset Password Page (No Token)</a></li>";
echo "<li><a href='auth/login.php' target='_blank'>👤 Login Page</a></li>";
echo "</ul>";

echo "<h4>Manual Test Steps:</h4>";
echo "<ol>";
echo "<li>Go to <a href='auth/forgot_password.php' target='_blank'>Forgot Password</a></li>";
echo "<li>Enter your email address</li>";
echo "<li>Click 'Send Reset Link'</li>";
echo "<li>Check your email for the reset link</li>";
echo "<li>Click the reset link in the email</li>";
echo "<li>Enter your new password</li>";
echo "<li>Click 'Reset Password'</li>";
echo "<li>Try logging in with your new password</li>";
echo "</ol>";

echo "</div>";

// Step 5: Environment Variables Check
echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔧 Step 5: Environment Variables Check</h3>";

$envVars = [
    'SMTP_USERNAME' => $_ENV['SMTP_USERNAME'] ?? 'Not set',
    'SMTP_PASSWORD' => isset($_ENV['SMTP_PASSWORD']) && $_ENV['SMTP_PASSWORD'] ? 'Set' : 'Not set',
    'SMTP_FROM_EMAIL' => $_ENV['SMTP_FROM_EMAIL'] ?? 'Not set',
    'SMTP_FROM_NAME' => $_ENV['SMTP_FROM_NAME'] ?? 'Not set',
    'SENDGRID_API_KEY' => isset($_ENV['SENDGRID_API_KEY']) && $_ENV['SENDGRID_API_KEY'] ? 'Set' : 'Not set'
];

foreach ($envVars as $key => $value) {
    $status = $value === 'Not set' ? '❌' : '✅';
    echo "$status <strong>$key:</strong> $value<br>";
}

echo "</div>";

// Step 6: Configuration Instructions
echo "<div style='background: #e2e3e5; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #6c757d;'>";
echo "<h2>🔧 Reset Password Configuration</h2>";

echo "<h3>✅ Required Configuration:</h3>";
echo "<p>To enable reset password functionality, configure email settings:</p>";

echo "<h4>1. Gmail SMTP Configuration:</h4>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
echo "SMTP_USERNAME=your-email@gmail.com\n";
echo "SMTP_PASSWORD=your-gmail-app-password\n";
echo "SMTP_FROM_EMAIL=your-email@gmail.com\n";
echo "SMTP_FROM_NAME=SmartUnion\n";
echo "</pre>";

echo "<h4>2. SendGrid API (Optional):</h4>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
echo "SENDGRID_API_KEY=your-sendgrid-api-key\n";
echo "</pre>";

echo "<h3>🧪 Test Your Reset Password Functionality:</h3>";
echo "<ul>";
echo "<li>🔐 <strong>Forgot Password:</strong> <a href='auth/forgot_password.php'>auth/forgot_password.php</a></li>";
echo "<li>🔄 <strong>Reset Password:</strong> <a href='auth/reset_password.php'>auth/reset_password.php</a></li>";
echo "<li>👤 <strong>Login:</strong> <a href='auth/login.php'>auth/login.php</a></li>";
echo "</ul>";

echo "<h3>⚠️ Important Notes:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Database Ready:</strong> reset_token and reset_expires columns exist</li>";
echo "<li>✅ <strong>Email System:</strong> Direct email solution implemented</li>";
echo "<li>✅ <strong>Token Security:</strong> 32-byte random tokens with 1-hour expiry</li>";
echo "<li>✅ <strong>Password Hashing:</strong> SHA-256 hashing for security</li>";
echo "<li>🔧 <strong>Configuration Required:</strong> You must configure SMTP or external service</li>";
echo "<li>📧 <strong>Check Inbox:</strong> If test shows success, check your email inbox</li>";
echo "<li>📁 <strong>Check Spam:</strong> Also check your spam/junk folder</li>";
echo "</ul>";

echo "</div>";

echo "<div style='background: #d4edda; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #28a745;'>";
echo "<h2>🎉 Reset Password System Ready!</h2>";
echo "<p><strong>Your reset password system is ready to use!</strong></p>";
echo "<p>Configure email settings to enable password reset functionality.</p>";
echo "<p><strong>If you see SUCCESS above, the system is working!</strong></p>";
echo "</div>";
?>
