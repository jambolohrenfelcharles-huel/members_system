<?php
/**
 * Migration script to add user blocking functionality
 * This script adds the required columns to the users table
 */

require_once 'config/database.php';

echo "<h1>User Blocking Migration</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $isPostgreSQL = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql';
    echo "<p>Database type: " . ($isPostgreSQL ? 'PostgreSQL' : 'MySQL') . "</p>";
    
    echo "<h2>🔧 Adding User Blocking Columns</h2>";
    
    $columns = [
        'blocked' => $isPostgreSQL ? 'BOOLEAN DEFAULT FALSE' : 'TINYINT(1) DEFAULT 0',
        'blocked_reason' => 'TEXT DEFAULT NULL',
        'blocked_at' => $isPostgreSQL ? 'TIMESTAMP DEFAULT NULL' : 'DATETIME DEFAULT NULL'
    ];
    
    foreach ($columns as $column => $definition) {
        try {
            // Check if column exists
            if ($isPostgreSQL) {
                $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'users' AND column_name = ?");
            } else {
                $stmt = $db->prepare("SHOW COLUMNS FROM users LIKE ?");
            }
            $stmt->execute([$column]);
            $columnExists = $stmt->fetch();
            
            if ($columnExists) {
                echo "<p style='color: green;'>✅ Column '$column' already exists</p>";
            } else {
                echo "<p style='color: orange;'>🔧 Adding column '$column'...</p>";
                
                $sql = "ALTER TABLE users ADD COLUMN $column $definition";
                $db->exec($sql);
                
                echo "<p style='color: green;'>✅ Column '$column' added successfully</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error with column '$column': " . $e->getMessage() . "</p>";
        }
    }
    
    // Create index for better performance
    try {
        if ($isPostgreSQL) {
            $db->exec("CREATE INDEX IF NOT EXISTS idx_users_blocked ON users(blocked)");
        } else {
            $db->exec("CREATE INDEX idx_users_blocked ON users(blocked)");
        }
        echo "<p style='color: green;'>✅ Index created for blocked column</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Index creation skipped: " . $e->getMessage() . "</p>";
    }
    
    // Update existing users to have blocked = false
    try {
        $db->exec("UPDATE users SET blocked = " . ($isPostgreSQL ? 'FALSE' : '0') . " WHERE blocked IS NULL");
        echo "<p style='color: green;'>✅ Existing users updated with default blocked status</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ User update skipped: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>🧪 Testing Migration</h2>";
    
    // Test the new columns
    try {
        $stmt = $db->query("SELECT id, username, blocked, blocked_reason, blocked_at FROM users LIMIT 3");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p style='color: green;'>✅ Test query successful: " . count($users) . " users</p>";
        
        if (!empty($users)) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Username</th><th>Blocked</th><th>Reason</th><th>Blocked At</th></tr>";
            
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                echo "<td>" . ($user['blocked'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . htmlspecialchars($user['blocked_reason'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($user['blocked_at'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Test query failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>🎯 Migration Complete</h2>";
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: green;'>✅ User Blocking Migration Successful!</h3>";
    echo "<p>User blocking functionality has been added to your database.</p>";
    echo "<p><strong>What was added:</strong></p>";
    echo "<ul>";
    echo "<li>✅ <strong>blocked</strong> column - Boolean flag for user status</li>";
    echo "<li>✅ <strong>blocked_reason</strong> column - Text field for blocking reason</li>";
    echo "<li>✅ <strong>blocked_at</strong> column - Timestamp when user was blocked</li>";
    echo "<li>✅ <strong>Index</strong> - Performance optimization for blocked queries</li>";
    echo "</ul>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Test the admin console at dashboard/admin/index.php</li>";
    echo "<li>Try blocking and unblocking users</li>";
    echo "<li>Verify login prevention for blocked users</li>";
    echo "<li>Deploy to Render when ready</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Migration Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</div>";
?>
