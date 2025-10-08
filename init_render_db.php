<?php
/**
 * Database initialization script specifically for Render deployment
 * This ensures the database is properly set up with all required tables and data
 */

require_once 'config/database.php';

echo "<h1>Render Database Initialization</h1>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Read the appropriate SQL file
    $db_type = $_ENV['DB_TYPE'] ?? 'mysql';
    $sqlFile = ($db_type === 'postgresql') ? 'db/members_system_postgresql.sql' : 'db/members_system.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    echo "<p>Using SQL file: <strong>$sqlFile</strong></p>";
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $pdo->beginTransaction();
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                $successCount++;
            } catch (Exception $e) {
                // Some statements might fail if tables already exist, which is OK
                if (strpos($e->getMessage(), 'already exists') !== false || 
                    strpos($e->getMessage(), 'duplicate key') !== false) {
                    $successCount++;
                } else {
                    echo "<p style='color: orange;'>⚠️ Statement warning: " . htmlspecialchars($e->getMessage()) . "</p>";
                    $errorCount++;
                }
            }
        }
    }
    
    $pdo->commit();
    
    echo "<p style='color: green;'>✅ Database initialization completed!</p>";
    echo "<p>Successful statements: $successCount</p>";
    if ($errorCount > 0) {
        echo "<p style='color: orange;'>Warnings: $errorCount</p>";
    }
    
    // Verify admin user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "<p style='color: green;'>✅ Admin user verified</p>";
    } else {
        echo "<p style='color: red;'>❌ Admin user not found - creating now...</p>";
        
        // Create admin user manually
        $hashed_password = hash('sha256', '123');
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
        $stmt->execute([$hashed_password]);
        echo "<p style='color: green;'>✅ Admin user created</p>";
    }
    
    // Test login
    echo "<h2>Login Test</h2>";
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && hash('sha256', '123') === $user['password']) {
        echo "<p style='color: green;'>✅ Login test successful!</p>";
        echo "<p><strong>Login credentials:</strong></p>";
        echo "<p>Username: <code>admin</code></p>";
        echo "<p>Password: <code>123</code></p>";
    } else {
        echo "<p style='color: red;'>❌ Login test failed</p>";
    }
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='auth/login.php'>Go to Login Page</a></p>";
echo "<p><a href='dashboard/index.php'>Go to Dashboard</a></p>";
echo "<p><a href='test_login_render.php'>Run Login Test</a></p>";
?>
