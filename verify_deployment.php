<?php
/**
 * Render Deployment Verification Script
 * This script verifies that everything is working correctly on Render
 */

echo "<h1>🚀 Render Deployment Verification</h1>";
echo "<p>Checking if your SmartApp is ready for login...</p>";

// Check environment
echo "<h2>Environment Check</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";

// Check database connection
echo "<h2>Database Connection</h2>";
try {
    require_once 'config/database.php';
    $db = new Database();
    $pdo = $db->getConnection();
    
    if ($pdo) {
        echo "<p style='color: green;'>✅ Database connection successful!</p>";
        
        // Check database type
        $db_type = $_ENV['DB_TYPE'] ?? 'mysql';
        echo "<p>Database type: <strong>$db_type</strong></p>";
        
        // Check if tables exist
        echo "<h3>Table Check</h3>";
        $tables = ['users', 'events', 'announcements', 'attendance', 'members', 'news_feed', 'comments'];
        
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p>✅ Table '$table': " . $result['count'] . " records</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Table '$table': Missing or error</p>";
            }
        }
        
        // Check admin user
        echo "<h3>Admin User Check</h3>";
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo "<p style='color: green;'>✅ Admin user exists</p>";
            
            // Test password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin'");
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $test_password = '123';
            $hashed_password = hash('sha256', $test_password);
            
            if ($admin['password'] === $hashed_password) {
                echo "<p style='color: green;'>✅ Admin password is correct</p>";
            } else {
                echo "<p style='color: red;'>❌ Admin password is incorrect</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Admin user not found</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check environment variables
echo "<h2>Environment Variables</h2>";
$env_vars = ['DB_TYPE', 'DB_HOST', 'DB_NAME', 'DB_USERNAME'];
foreach ($env_vars as $var) {
    $value = $_ENV[$var] ?? 'Not set';
    if ($var === 'DB_PASSWORD') {
        $value = $value !== 'Not set' ? '***hidden***' : 'Not set';
    }
    echo "<p><strong>$var:</strong> $value</p>";
}

// Test login simulation
echo "<h2>Login Test</h2>";
try {
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = 'admin'");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && hash('sha256', '123') === $user['password']) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>";
            echo "<h3 style='color: #155724; margin-top: 0;'>🎉 Login Test Successful!</h3>";
            echo "<p><strong>Username:</strong> admin</p>";
            echo "<p><strong>Password:</strong> 123</p>";
            echo "<p><strong>User ID:</strong> " . $user['id'] . "</p>";
            echo "<p><strong>Role:</strong> " . $user['role'] . "</p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Login test failed</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Login test error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Final status
echo "<h2>Deployment Status</h2>";
if (isset($pdo) && $user && hash('sha256', '123') === $user['password']) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; border: 1px solid #c3e6cb;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ Ready for Login!</h3>";
    echo "<p>Your SmartApp is successfully deployed and ready to use.</p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li><a href='auth/login.php' style='color: #155724;'>Go to Login Page</a></li>";
    echo "<li>Use username: <code>admin</code> and password: <code>123</code></li>";
    echo "<li>Access your dashboard after login</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>⚠️ Setup Required</h3>";
    echo "<p>Please run the database setup first:</p>";
    echo "<p><a href='setup_render_db.php' style='color: #721c24;'>Run Database Setup</a></p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='index.php'>Home</a> | ";
echo "<a href='auth/login.php'>Login</a> | ";
echo "<a href='setup_render_db.php'>Setup Database</a></p>";
?>
