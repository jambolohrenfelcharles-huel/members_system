<?php
/**
 * News Feed Table Migration for Render
 * This script ensures the news_feed table has the required media_path and media_type columns
 */

// This will be included in render_deploy.php to fix the news_feed table on deployment

function fixNewsFeedTable($db, $db_type) {
    try {
        echo "🔧 Checking news_feed table structure...\n";
        
        // Check if news_feed table exists
        if ($db_type === 'postgresql') {
            $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'news_feed')");
            $tableExists = $stmt->fetchColumn();
        } else {
            $stmt = $db->query("SHOW TABLES LIKE 'news_feed'");
            $tableExists = $stmt->rowCount() > 0;
        }
        
        if (!$tableExists) {
            echo "📝 Creating news_feed table...\n";
            
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
            echo "✅ news_feed table created\n";
            return;
        }
        
        // Check current columns
        if ($db_type === 'postgresql') {
            $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'news_feed'");
        } else {
            $stmt = $db->query("SHOW COLUMNS FROM news_feed");
        }
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $hasMediaPath = in_array('media_path', $columns);
        $hasMediaType = in_array('media_type', $columns);
        
        // Add missing columns
        if (!$hasMediaPath) {
            echo "📝 Adding media_path column...\n";
            if ($db_type === 'postgresql') {
                $db->exec("ALTER TABLE news_feed ADD COLUMN IF NOT EXISTS media_path VARCHAR(500)");
            } else {
                $db->exec("ALTER TABLE news_feed ADD COLUMN media_path VARCHAR(500)");
            }
            echo "✅ media_path column added\n";
        }
        
        if (!$hasMediaType) {
            echo "📝 Adding media_type column...\n";
            if ($db_type === 'postgresql') {
                $db->exec("ALTER TABLE news_feed ADD COLUMN IF NOT EXISTS media_type VARCHAR(20) NOT NULL DEFAULT 'image' CHECK (media_type IN ('image', 'video'))");
            } else {
                $db->exec("ALTER TABLE news_feed ADD COLUMN media_type ENUM('image', 'video') NOT NULL DEFAULT 'image'");
            }
            echo "✅ media_type column added\n";
        }
        
        if ($hasMediaPath && $hasMediaType) {
            echo "✅ news_feed table structure is correct\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error fixing news_feed table: " . $e->getMessage() . "\n";
        throw $e;
    }
}

// If this file is run directly, execute the fix
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        require_once 'config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception("Database connection failed");
        }
        
        $db_type = ($_ENV['DB_TYPE'] ?? 'mysql');
        echo "🔧 Fixing news_feed table for " . strtoupper($db_type) . "...\n";
        
        fixNewsFeedTable($db, $db_type);
        
        echo "✅ News feed table fix completed successfully\n";
        
    } catch (Exception $e) {
        echo "❌ News feed table fix failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>
