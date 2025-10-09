<?php
/**
 * Render Deployment Script for SmartApp
 * This script initializes the database and runs necessary migrations
 */

require_once 'config/database.php';

echo "<h1>SmartApp Render Deployment</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "<h2>✅ Database Connection Successful</h2>";
    echo "<p>Connected to: " . ($_ENV['DB_TYPE'] ?? 'mysql') . " database</p>";
    
    // Check if we're using PostgreSQL
    $isPostgreSQL = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql';
    
    if ($isPostgreSQL) {
        echo "<h3>🐘 PostgreSQL Setup</h3>";
        
        // Read and execute PostgreSQL schema
        $schemaFile = 'db/members_system_postgresql.sql';
        if (file_exists($schemaFile)) {
            $schema = file_get_contents($schemaFile);
            
            // Split by semicolon and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $schema)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    try {
                        $db->exec($statement);
                    } catch (PDOException $e) {
                        // Ignore "already exists" errors
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            echo "<p style='color: orange;'>⚠️ Statement warning: " . substr($statement, 0, 50) . "... - " . $e->getMessage() . "</p>";
                        }
                    }
                }
            }
            
            echo "<p style='color: green;'>✅ PostgreSQL schema executed successfully</p>";
        }
        
        // Run attendance migration
        echo "<h3>🔧 Running Attendance Migration</h3>";
        
        // Check if event_id column exists
        $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'attendance' AND column_name = 'event_id'");
        $stmt->execute();
        $columnExists = $stmt->fetch();
        
        if ($columnExists) {
            echo "<p style='color: green;'>✅ event_id column already exists in attendance table</p>";
        } else {
            echo "<p style='color: orange;'>🔧 Adding event_id column to attendance table...</p>";
            
            // Add event_id column
            $db->exec("ALTER TABLE attendance ADD COLUMN event_id INTEGER REFERENCES events(id) ON DELETE CASCADE");
            echo "<p style='color: green;'>✅ event_id column added successfully</p>";
            
            // Create index
            $db->exec("CREATE INDEX IF NOT EXISTS idx_attendance_event_id ON attendance(event_id)");
            echo "<p style='color: green;'>✅ Index created successfully</p>";
        }
        
    } else {
        echo "<h3>🐬 MySQL Setup</h3>";
        
        // Read and execute MySQL schema
        $schemaFile = 'db/members_system.sql';
        if (file_exists($schemaFile)) {
            $schema = file_get_contents($schemaFile);
            
            // Split by semicolon and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $schema)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    try {
                        $db->exec($statement);
                    } catch (PDOException $e) {
                        // Ignore "already exists" errors
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            echo "<p style='color: orange;'>⚠️ Statement warning: " . substr($statement, 0, 50) . "... - " . $e->getMessage() . "</p>";
                        }
                    }
                }
            }
            
            echo "<p style='color: green;'>✅ MySQL schema executed successfully</p>";
        }
    }
    
    // Test attendance functionality
    echo "<h3>🧪 Testing Attendance Functionality</h3>";
    
    try {
        if ($isPostgreSQL) {
            $stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURRENT_DATE");
        } else {
            $stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURRENT_DATE");
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Attendance query works: " . $result['total'] . " records for today</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Attendance query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test events functionality
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM events");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Events table accessible: " . $result['total'] . " events</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Events query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test users functionality
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Users table accessible: " . $result['total'] . " users</p>";
        
        // Check if admin user exists
        $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE username = 'admin'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['total'] > 0) {
            echo "<p style='color: green;'>✅ Admin user exists</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Admin user not found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Users query failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>🎉 Deployment Complete!</h3>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Your SmartApp is ready!</strong></p>";
    echo "<p>Default Admin Login:</p>";
    echo "<ul>";
    echo "<li>Username: <code>admin</code></li>";
    echo "<li>Password: <code>123</code></li>";
    echo "</ul>";
    echo "<p style='color: red;'><strong>Important:</strong> Change the admin password after first login!</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Deployment Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
}

echo "</div>";
?>
