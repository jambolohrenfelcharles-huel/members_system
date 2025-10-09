<?php
/**
 * Run Email Queue Migration
 * Creates email queue tables for async email processing
 */

echo "<h1>Email Queue Migration</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $isPostgreSQL = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql';
    echo "<p>Database type: " . ($isPostgreSQL ? 'PostgreSQL' : 'MySQL') . "</p>";
    
    echo "<h2>Creating Email Queue Tables</h2>";
    
    // Create email_queue table
    $createEmailQueueSQL = "
        CREATE TABLE IF NOT EXISTS email_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL DEFAULT NULL
        )
    ";
    
    try {
        $db->exec($createEmailQueueSQL);
        echo "<p style='color: green;'>✅ email_queue table created successfully</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || 
            strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "<p style='color: orange;'>⚠️ email_queue table already exists</p>";
        } else {
            echo "<p style='color: red;'>❌ email_queue table error: " . $e->getMessage() . "</p>";
        }
    }
    
    // Create email_queue_items table
    $createEmailQueueItemsSQL = "
        CREATE TABLE IF NOT EXISTS email_queue_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            queue_id INT NOT NULL,
            member_id VARCHAR(50),
            member_name VARCHAR(255),
            member_email VARCHAR(255) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL DEFAULT NULL,
            error_message TEXT DEFAULT NULL,
            FOREIGN KEY (queue_id) REFERENCES email_queue(id) ON DELETE CASCADE
        )
    ";
    
    try {
        $db->exec($createEmailQueueItemsSQL);
        echo "<p style='color: green;'>✅ email_queue_items table created successfully</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || 
            strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "<p style='color: orange;'>⚠️ email_queue_items table already exists</p>";
        } else {
            echo "<p style='color: red;'>❌ email_queue_items table error: " . $e->getMessage() . "</p>";
        }
    }
    
    // Create indexes
    $indexes = [
        'idx_email_queue_status' => 'CREATE INDEX IF NOT EXISTS idx_email_queue_status ON email_queue(status)',
        'idx_email_queue_items_status' => 'CREATE INDEX IF NOT EXISTS idx_email_queue_items_status ON email_queue_items(status)',
        'idx_email_queue_items_queue_id' => 'CREATE INDEX IF NOT EXISTS idx_email_queue_items_queue_id ON email_queue_items(queue_id)'
    ];
    
    foreach ($indexes as $indexName => $indexSQL) {
        try {
            $db->exec($indexSQL);
            echo "<p style='color: green;'>✅ $indexName index created</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ $indexName index warning: " . $e->getMessage() . "</p>";
        }
    }
    
    // Verify tables were created
    echo "<h2>Verifying Tables</h2>";
    
    $tables = ['email_queue', 'email_queue_items'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✅ $table table: " . $result['total'] . " records</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ $table table issue: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>Migration Complete</h2>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: green;'>✅ Email Queue Tables Created</h3>";
    echo "<p>The email queue system is now ready for async email processing.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Test the optimized announcement system</li>";
    echo "<li>Deploy to Render</li>";
    echo "<li>Verify async email processing works</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Migration Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</div>";
?>
