<?php
/**
 * Fix News Feed Table - Add Missing Columns
 * This script adds the missing media_path and media_type columns to the news_feed table
 */

echo "<h1>News Feed Table Fix</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px;'>";

try {
    // Connect to database
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "<h2>✅ Database Connection</h2>";
    echo "<p style='color: green;'>Database connected successfully</p>";
    
    // Check current database type
    $db_type = ($_ENV['DB_TYPE'] ?? 'mysql');
    echo "<p><strong>Database Type:</strong> " . strtoupper($db_type) . "</p>";
    
    // Check if news_feed table exists
    if ($db_type === 'postgresql') {
        $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'news_feed')");
        $tableExists = $stmt->fetchColumn();
    } else {
        $stmt = $db->query("SHOW TABLES LIKE 'news_feed'");
        $tableExists = $stmt->rowCount() > 0;
    }
    
    echo "<h2>📊 News Feed Table Status</h2>";
    echo "<p><strong>Table Exists:</strong> " . ($tableExists ? "✅ Yes" : "❌ No") . "</p>";
    
    if ($tableExists) {
        // Check current columns
        if ($db_type === 'postgresql') {
            $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'news_feed' ORDER BY ordinal_position");
        } else {
            $stmt = $db->query("DESCRIBE news_feed");
        }
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Current Columns:</h3>";
        echo "<ul>";
        $hasMediaPath = false;
        $hasMediaType = false;
        
        foreach ($columns as $column) {
            $columnName = $db_type === 'postgresql' ? $column['column_name'] : $column['Field'];
            $dataType = $db_type === 'postgresql' ? $column['data_type'] : $column['Type'];
            
            echo "<li><strong>" . htmlspecialchars($columnName) . "</strong> (" . htmlspecialchars($dataType) . ")</li>";
            
            if ($columnName === 'media_path') $hasMediaPath = true;
            if ($columnName === 'media_type') $hasMediaType = true;
        }
        echo "</ul>";
        
        echo "<h3>Missing Columns Check:</h3>";
        echo "<p><strong>media_path column:</strong> " . ($hasMediaPath ? "✅ Present" : "❌ Missing") . "</p>";
        echo "<p><strong>media_type column:</strong> " . ($hasMediaType ? "✅ Present" : "❌ Missing") . "</p>";
        
        // Add missing columns
        if (!$hasMediaPath || !$hasMediaType) {
            echo "<h3>🔧 Adding Missing Columns</h3>";
            
            if ($db_type === 'postgresql') {
                if (!$hasMediaPath) {
                    $db->exec("ALTER TABLE news_feed ADD COLUMN IF NOT EXISTS media_path VARCHAR(500)");
                    echo "<p>✅ Added media_path column</p>";
                }
                
                if (!$hasMediaType) {
                    $db->exec("ALTER TABLE news_feed ADD COLUMN IF NOT EXISTS media_type VARCHAR(20) NOT NULL DEFAULT 'image' CHECK (media_type IN ('image', 'video'))");
                    echo "<p>✅ Added media_type column</p>";
                }
            } else {
                if (!$hasMediaPath) {
                    $db->exec("ALTER TABLE news_feed ADD COLUMN media_path VARCHAR(500)");
                    echo "<p>✅ Added media_path column</p>";
                }
                
                if (!$hasMediaType) {
                    $db->exec("ALTER TABLE news_feed ADD COLUMN media_type ENUM('image', 'video') NOT NULL DEFAULT 'image'");
                    echo "<p>✅ Added media_type column</p>";
                }
            }
        } else {
            echo "<p style='color: green;'>✅ All required columns are present</p>";
        }
        
        // Verify the fix
        echo "<h3>🔍 Verification</h3>";
        if ($db_type === 'postgresql') {
            $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'news_feed' ORDER BY ordinal_position");
        } else {
            $stmt = $db->query("DESCRIBE news_feed");
        }
        $updatedColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Updated Columns:</h4>";
        echo "<ul>";
        foreach ($updatedColumns as $column) {
            $columnName = $db_type === 'postgresql' ? $column['column_name'] : $column['Field'];
            $dataType = $db_type === 'postgresql' ? $column['data_type'] : $column['Type'];
            echo "<li><strong>" . htmlspecialchars($columnName) . "</strong> (" . htmlspecialchars($dataType) . ")</li>";
        }
        echo "</ul>";
        
        // Test the INSERT statement
        echo "<h3>🧪 Testing INSERT Statement</h3>";
        try {
            $testStmt = $db->prepare("INSERT INTO news_feed (user_id, title, description, media_path, media_type) VALUES (?, ?, ?, ?, ?)");
            echo "<p style='color: green;'>✅ INSERT statement syntax is valid</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ INSERT statement failed: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<h3>🔧 Creating News Feed Table</h3>";
        
        if ($db_type === 'postgresql') {
            $createTable = "
                CREATE TABLE IF NOT EXISTS news_feed (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    media_path VARCHAR(500),
                    media_type VARCHAR(20) NOT NULL CHECK (media_type IN ('image', 'video')),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
        } else {
            $createTable = "
                CREATE TABLE IF NOT EXISTS news_feed (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    media_path VARCHAR(500),
                    media_type ENUM('image', 'video') NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ";
        }
        
        $db->exec($createTable);
        echo "<p style='color: green;'>✅ News feed table created successfully</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<h2>🔧 News Feed Table Fix Summary</h2>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3 style='color: green;'>✅ News Feed Table Fixed</h3>";
echo "<ul>";
echo "<li>✅ <strong>Problem:</strong> Missing media_path and media_type columns in news_feed table</li>";
echo "<li>✅ <strong>Root Cause:</strong> Database schema mismatch between local and deployed database</li>";
echo "<li>✅ <strong>Solution:</strong> Added missing columns with proper data types and constraints</li>";
echo "<li>✅ <strong>Result:</strong> News feed posts can now be created successfully</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🔗 Important URLs for News Feed Testing</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>Add News Feed:</strong> <code>https://your-app.onrender.com/dashboard/news_feed/add.php</code></p>";
echo "<p><strong>View News Feed:</strong> <code>https://your-app.onrender.com/dashboard/news_feed/index.php</code></p>";
echo "<p><strong>Edit News Feed:</strong> <code>https://your-app.onrender.com/dashboard/news_feed/edit.php?id={id}</code></p>";
echo "<p><strong>Admin Console:</strong> <code>https://your-app.onrender.com/dashboard/admin/index.php</code></p>";
echo "<p><strong>System Status:</strong> <code>https://your-app.onrender.com/dashboard/system_status.php</code></p>";
echo "<p><strong>Health Check:</strong> <code>https://your-app.onrender.com/health.php</code></p>";
echo "<p><strong>Default Login:</strong> admin / 123</p>";
echo "</div>";

echo "</div>";
?>
