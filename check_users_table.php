<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Users Table Structure</h2>";
    
    // Check if users table exists
    $result = $db->query("SHOW TABLES LIKE 'users'");
    if ($result->rowCount() > 0) {
        echo "<p>✅ Users table exists</p>";
        
        // Get table structure
        $result = $db->query("DESCRIBE users");
        echo "<h3>Table Columns:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check for reset token columns
        $result = $db->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
        $hasResetToken = $result->rowCount() > 0;
        
        $result = $db->query("SHOW COLUMNS FROM users LIKE 'reset_expires'");
        $hasResetExpires = $result->rowCount() > 0;
        
        echo "<h3>Reset Password Columns:</h3>";
        echo "<p>reset_token: " . ($hasResetToken ? "✅ Exists" : "❌ Missing") . "</p>";
        echo "<p>reset_expires: " . ($hasResetExpires ? "✅ Exists" : "❌ Missing") . "</p>";
        
        if (!$hasResetToken || !$hasResetExpires) {
            echo "<h3>🔧 Fix Required:</h3>";
            echo "<p>Missing reset password columns. Run the following SQL:</p>";
            echo "<pre>";
            if (!$hasResetToken) {
                echo "ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL;\n";
            }
            if (!$hasResetExpires) {
                echo "ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL;\n";
            }
            echo "</pre>";
        }
        
    } else {
        echo "<p>❌ Users table does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
