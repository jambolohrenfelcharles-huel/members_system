<?php
/**
 * Login Verification Script for Render
 * This script tests the login functionality to ensure it works on Render
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Login Verification</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;}";
echo ".container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:#28a745;background:#d4edda;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".info{color:#0c5460;background:#d1ecf1;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".btn{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;}";
echo ".btn:hover{background:#0056b3;}";
echo ".test-box{border:1px solid #ddd;padding:15px;margin:10px 0;border-radius:5px;}";
echo "</style></head><body><div class='container'>";

echo "<h1>🔐 Login Verification for Render</h1>";
echo "<p>Testing login functionality to ensure successful dashboard access...</p>";

$allTestsPassed = true;

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    echo "<div class='success'>✅ Database connection successful</div>";
    
    // Test 1: Check database type
    echo "<div class='test-box'>";
    echo "<h3>Test 1: Database Type</h3>";
    $db_type = $_ENV['DB_TYPE'] ?? 'mysql';
    echo "<p>Database type: <strong>$db_type</strong></p>";
    if ($db_type === 'postgresql') {
        echo "<div class='success'>✅ PostgreSQL detected (Render environment)</div>";
    } else {
        echo "<div class='info'>ℹ️ MySQL detected (local environment)</div>";
    }
    echo "</div>";
    
    // Test 2: Check users table
    echo "<div class='test-box'>";
    echo "<h3>Test 2: Users Table</h3>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<div class='success'>✅ Users table exists with " . $result['count'] . " records</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ Users table error: " . htmlspecialchars($e->getMessage()) . "</div>";
        $allTestsPassed = false;
    }
    echo "</div>";
    
    // Test 3: Check admin user
    echo "<div class='test-box'>";
    echo "<h3>Test 3: Admin User</h3>";
    try {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = 'admin'");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<div class='success'>✅ Admin user found</div>";
            echo "<p>User ID: " . $user['id'] . "</p>";
            echo "<p>Username: " . $user['username'] . "</p>";
            echo "<p>Role: " . $user['role'] . "</p>";
        } else {
            echo "<div class='error'>❌ Admin user not found</div>";
            $allTestsPassed = false;
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Admin user check error: " . htmlspecialchars($e->getMessage()) . "</div>";
        $allTestsPassed = false;
    }
    echo "</div>";
    
    // Test 4: Password verification
    echo "<div class='test-box'>";
    echo "<h3>Test 4: Password Verification</h3>";
    try {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            $test_password = '123';
            $hashed_password = hash('sha256', $test_password);
            
            if ($admin['password'] === $hashed_password) {
                echo "<div class='success'>✅ Password hash verification successful</div>";
                echo "<p>Expected hash: " . substr($hashed_password, 0, 20) . "...</p>";
                echo "<p>Stored hash: " . substr($admin['password'], 0, 20) . "...</p>";
            } else {
                echo "<div class='error'>❌ Password hash mismatch</div>";
                echo "<p>Expected: " . substr($hashed_password, 0, 20) . "...</p>";
                echo "<p>Stored: " . substr($admin['password'], 0, 20) . "...</p>";
                $allTestsPassed = false;
            }
        } else {
            echo "<div class='error'>❌ Could not retrieve admin password</div>";
            $allTestsPassed = false;
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Password verification error: " . htmlspecialchars($e->getMessage()) . "</div>";
        $allTestsPassed = false;
    }
    echo "</div>";
    
    // Test 5: Complete login simulation
    echo "<div class='test-box'>";
    echo "<h3>Test 5: Complete Login Simulation</h3>";
    try {
        $username = 'admin';
        $password = '123';
        
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && hash('sha256', $password) === $user['password']) {
            echo "<div class='success'>✅ Complete login simulation successful!</div>";
            echo "<p>Login would create session with:</p>";
            echo "<ul>";
            echo "<li>User ID: " . $user['id'] . "</li>";
            echo "<li>Username: " . $user['username'] . "</li>";
            echo "<li>Role: " . $user['role'] . "</li>";
            echo "</ul>";
        } else {
            echo "<div class='error'>❌ Complete login simulation failed</div>";
            $allTestsPassed = false;
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Login simulation error: " . htmlspecialchars($e->getMessage()) . "</div>";
        $allTestsPassed = false;
    }
    echo "</div>";
    
    // Test 6: Dashboard access check
    echo "<div class='test-box'>";
    echo "<h3>Test 6: Dashboard Access Check</h3>";
    try {
        // Check if dashboard files exist
        $dashboard_file = 'dashboard/index.php';
        if (file_exists($dashboard_file)) {
            echo "<div class='success'>✅ Dashboard file exists</div>";
        } else {
            echo "<div class='error'>❌ Dashboard file not found</div>";
            $allTestsPassed = false;
        }
        
        // Check if dashboard can connect to database
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM events");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<div class='success'>✅ Dashboard database queries work (Events: " . $result['count'] . ")</div>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM announcements");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<div class='success'>✅ Dashboard database queries work (Announcements: " . $result['count'] . ")</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Dashboard access check error: " . htmlspecialchars($e->getMessage()) . "</div>";
        $allTestsPassed = false;
    }
    echo "</div>";
    
    // Final result
    echo "<div class='test-box'>";
    echo "<h3>Final Result</h3>";
    if ($allTestsPassed) {
        echo "<div class='success'>";
        echo "<h3>🎉 All Tests Passed! Login Ready for Render</h3>";
        echo "<p>Your SmartApp login is working correctly and ready for deployment on Render.</p>";
        echo "<p><strong>Login Credentials:</strong></p>";
        echo "<p>Username: <code>admin</code></p>";
        echo "<p>Password: <code>123</code></p>";
        echo "<p>After login, you'll be redirected to the dashboard successfully.</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>⚠️ Some Tests Failed</h3>";
        echo "<p>Please run the database setup first:</p>";
        echo "<p><a href='setup_render_login.php' class='btn'>Run Database Setup</a></p>";
        echo "</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database connection error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>Please check your database configuration and try again.</p>";
}

echo "<div style='text-align:center;margin:20px 0;'>";
echo "<a href='auth/login.php' class='btn'>Try Login Now</a>";
echo "<a href='setup_render_login.php' class='btn'>Setup Database</a>";
echo "<a href='index.php' class='btn'>Go to Home</a>";
echo "</div>";

echo "</div></body></html>";
?>
