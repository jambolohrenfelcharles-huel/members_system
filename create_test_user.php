<?php
/**
 * CREATE TEST USER FOR RESET PASSWORD TESTING
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>👤 Create Test User</h2>";
    
    $testEmail = 'charlesjambo3@gmail.com';
    $testUsername = 'testuser';
    $testPassword = 'testpass123';
    $testName = 'Test User';
    
    // Check if user already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$testEmail, $testUsername]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        echo "<p>✅ <strong>User Already Exists:</strong> ID " . $existingUser['id'] . "</p>";
        echo "<p><strong>Email:</strong> $testEmail</p>";
        echo "<p><strong>Username:</strong> $testUsername</p>";
        echo "<p><strong>Password:</strong> $testPassword</p>";
    } else {
        // Create new user
        $hashedPassword = hash('sha256', $testPassword);
        $stmt = $db->prepare("INSERT INTO users (username, email, full_name, password, role) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$testUsername, $testEmail, $testName, $hashedPassword, 'member']);
        
        if ($result) {
            $userId = $db->lastInsertId();
            echo "<p>✅ <strong>Test User Created:</strong> ID $userId</p>";
            echo "<p><strong>Email:</strong> $testEmail</p>";
            echo "<p><strong>Username:</strong> $testUsername</p>";
            echo "<p><strong>Password:</strong> $testPassword</p>";
            echo "<p><strong>Role:</strong> member</p>";
        } else {
            echo "<p>❌ <strong>Failed to create user</strong></p>";
        }
    }
    
    echo "<h3>🔗 Test Links:</h3>";
    echo "<ul>";
    echo "<li><a href='auth/login.php' target='_blank'>🔐 Login Page</a></li>";
    echo "<li><a href='auth/forgot_password.php' target='_blank'>🔄 Forgot Password</a></li>";
    echo "<li><a href='test_reset_password.php' target='_blank'>🧪 Reset Password Test</a></li>";
    echo "</ul>";
    
    echo "<h3>📋 Test Steps:</h3>";
    echo "<ol>";
    echo "<li>Go to <a href='auth/login.php' target='_blank'>Login Page</a></li>";
    echo "<li>Login with username: <strong>$testUsername</strong> and password: <strong>$testPassword</strong></li>";
    echo "<li>Or go to <a href='auth/forgot_password.php' target='_blank'>Forgot Password</a></li>";
    echo "<li>Enter email: <strong>$testEmail</strong></li>";
    echo "<li>Click 'Send Reset Link'</li>";
    echo "<li>Check your email for the reset link</li>";
    echo "<li>Click the reset link and set a new password</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
