<?php
/**
 * Test script to verify login functionality on Render
 * This script tests database connection and admin user creation
 */

require_once 'config/database.php';

echo "<h1>SmartApp Login Test for Render</h1>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check database type
    $db_type = $_ENV['DB_TYPE'] ?? 'mysql';
    echo "<p>Database type: <strong>$db_type</strong></p>";
    
    // Check if users table exists and has admin user
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "<p style='color: green;'>✅ Admin user exists in database</p>";
        
        // Test password hash
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $test_password = '123';
        $hashed_password = hash('sha256', $test_password);
        
        if ($admin['password'] === $hashed_password) {
            echo "<p style='color: green;'>✅ Admin password hash is correct</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Password hash mismatch. Expected: $hashed_password, Got: " . $admin['password'] . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Admin user not found in database</p>";
        
        // Try to create admin user
        echo "<p>Attempting to create admin user...</p>";
        $hashed_password = hash('sha256', '123');
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
        $result = $stmt->execute([$hashed_password]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Admin user created successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create admin user</p>";
        }
    }
    
    // Test login simulation
    echo "<h2>Login Test</h2>";
    $test_username = 'admin';
    $test_password = '123';
    
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$test_username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && hash('sha256', $test_password) === $user['password']) {
        echo "<p style='color: green;'>✅ Login test successful!</p>";
        echo "<p>User ID: " . $user['id'] . "</p>";
        echo "<p>Username: " . $user['username'] . "</p>";
        echo "<p>Role: " . $user['role'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Login test failed</p>";
    }
    
    // Check tables
    echo "<h2>Database Tables</h2>";
    $tables = ['users', 'events', 'announcements', 'attendance', 'members', 'news_feed', 'comments'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>✅ Table '$table': " . $result['count'] . " records</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Table '$table': " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h2>Environment Variables</h2>";
echo "<p>DB_TYPE: " . ($_ENV['DB_TYPE'] ?? 'Not set') . "</p>";
echo "<p>DB_HOST: " . ($_ENV['DB_HOST'] ?? 'Not set') . "</p>";
echo "<p>DB_NAME: " . ($_ENV['DB_NAME'] ?? 'Not set') . "</p>";
echo "<p>DB_USERNAME: " . ($_ENV['DB_USERNAME'] ?? 'Not set') . "</p>";

echo "<hr>";
echo "<p><a href='auth/login.php'>Go to Login Page</a></p>";
echo "<p><a href='dashboard/index.php'>Go to Dashboard</a></p>";
?>
