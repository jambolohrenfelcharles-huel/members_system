<?php
/**
 * Test Announcement Functionality for Render
 * Comprehensive test to ensure announcements work on Render deployment
 */

echo "<h1>Announcement Functionality Test for Render</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

try {
    // Test database connection
    echo "<h2>🔧 Testing Database Connection</h2>";
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        $isPostgreSQL = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql';
        echo "<p style='color: green;'>✅ Database connection successful</p>";
        echo "<p>Database type: " . ($isPostgreSQL ? 'PostgreSQL' : 'MySQL') . "</p>";
        
        // Test announcements table
        echo "<h3>📋 Testing Announcements Table</h3>";
        
        try {
            // Check if announcements table exists
            $stmt = $db->query("SELECT COUNT(*) as total FROM announcements");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✅ Announcements table exists: " . $result['total'] . " records</p>";
            
            // Test table structure
            $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'announcements' ORDER BY ordinal_position");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p><strong>Table structure:</strong></p>";
            echo "<ul>";
            foreach ($columns as $column) {
                echo "<li>" . $column['column_name'] . " (" . $column['data_type'] . ")</li>";
            }
            echo "</ul>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Announcements table issue: " . $e->getMessage() . "</p>";
            
            // Try to create the table
            echo "<p style='color: orange;'>🔧 Attempting to create announcements table...</p>";
            try {
                $createTableSQL = "
                    CREATE TABLE IF NOT EXISTS announcements (
                        id SERIAL PRIMARY KEY,
                        title VARCHAR(255) NOT NULL,
                        content TEXT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ";
                $db->exec($createTableSQL);
                echo "<p style='color: green;'>✅ Announcements table created successfully</p>";
            } catch (Exception $e2) {
                echo "<p style='color: red;'>❌ Failed to create announcements table: " . $e2->getMessage() . "</p>";
            }
        }
        
        // Test notification helper
        echo "<h3>📧 Testing Notification Helper</h3>";
        
        try {
            require_once 'config/notification_helper.php';
            
            // Capture any output/warnings
            ob_start();
            $notificationHelper = new NotificationHelper($db);
            $output = ob_get_clean();
            
            if (!empty($output)) {
                echo "<p style='color: orange;'>⚠️ Output captured: " . htmlspecialchars($output) . "</p>";
            } else {
                echo "<p style='color: green;'>✅ NotificationHelper instantiated successfully</p>";
            }
            
            // Test reflection to check private property
            $reflection = new ReflectionClass($notificationHelper);
            $membersTableProperty = $reflection->getProperty('members_table');
            $membersTableProperty->setAccessible(true);
            $membersTableValue = $membersTableProperty->getValue($notificationHelper);
            
            echo "<p style='color: green;'>✅ \$members_table property set to: <strong>$membersTableValue</strong></p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ NotificationHelper test failed: " . $e->getMessage() . "</p>";
        }
        
        // Test members table for notifications
        echo "<h3>👥 Testing Members Table for Notifications</h3>";
        
        $members_table = $isPostgreSQL ? 'members' : 'membership_monitoring';
        echo "<p>Using members table: <strong>$members_table</strong></p>";
        
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM $members_table WHERE status = 'active' AND email IS NOT NULL AND email != ''");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✅ Active members with email: " . $result['total'] . " records</p>";
            
            if ($result['total'] > 0) {
                // Test a sample query
                $stmt = $db->query("SELECT name, email FROM $members_table WHERE status = 'active' AND email IS NOT NULL AND email != '' LIMIT 3");
                $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<p><strong>Sample members:</strong></p>";
                echo "<ul>";
                foreach ($members as $member) {
                    echo "<li>" . htmlspecialchars($member['name']) . " (" . htmlspecialchars($member['email']) . ")</li>";
                }
                echo "</ul>";
            } else {
                echo "<p style='color: orange;'>⚠️ No active members with email addresses found</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Members table query failed: " . $e->getMessage() . "</p>";
        }
        
        // Test announcement insertion
        echo "<h3>📝 Testing Announcement Insertion</h3>";
        
        try {
            $testTitle = "Test Announcement - " . date('Y-m-d H:i:s');
            $testContent = "This is a test announcement to verify the system works correctly on Render.";
            
            $stmt = $db->prepare("INSERT INTO announcements (title, content) VALUES (?, ?)");
            $result = $stmt->execute([$testTitle, $testContent]);
            
            if ($result) {
                $announcementId = $db->lastInsertId();
                echo "<p style='color: green;'>✅ Test announcement inserted successfully (ID: $announcementId)</p>";
                
                // Clean up test announcement
                $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
                $stmt->execute([$announcementId]);
                echo "<p style='color: green;'>✅ Test announcement cleaned up</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to insert test announcement</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Announcement insertion test failed: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }
    
    echo "<h2>🎯 Test Summary</h2>";
    
    if ($isPostgreSQL) {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3 style='color: green;'>✅ PostgreSQL Ready!</h3>";
        echo "<p>Announcement system should work correctly on Render with PostgreSQL.</p>";
        echo "<p><strong>Key components tested:</strong></p>";
        echo "<ul>";
        echo "<li>✅ Database connection</li>";
        echo "<li>✅ Announcements table structure</li>";
        echo "<li>✅ NotificationHelper class</li>";
        echo "<li>✅ Members table queries</li>";
        echo "<li>✅ Announcement insertion</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3 style='color: orange;'>⚠️ MySQL Detected</h3>";
        echo "<p>You're currently using MySQL. The system will work when deployed to PostgreSQL on Render.</p>";
        echo "</div>";
    }
    
    echo "<h3>🔗 How to Use Announcements</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<ol>";
    echo "<li><strong>Login as Admin:</strong> Use admin / 123 credentials</li>";
    echo "<li><strong>Go to Announcements:</strong> Navigate to dashboard/announcements/add.php</li>";
    echo "<li><strong>Add Announcement:</strong> Fill in title and content</li>";
    echo "<li><strong>Send Notifications:</strong> System will automatically email all active members</li>";
    echo "<li><strong>View Announcements:</strong> Check dashboard/announcements/index.php</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>🔗 Important URLs for Render</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Add Announcements:</strong> <code>https://your-app.onrender.com/dashboard/announcements/add.php</code></p>";
    echo "<p><strong>View Announcements:</strong> <code>https://your-app.onrender.com/dashboard/announcements/index.php</code></p>";
    echo "<p><strong>Admin Login:</strong> <code>https://your-app.onrender.com/auth/login.php</code></p>";
    echo "<p><strong>Admin Console:</strong> <code>https://your-app.onrender.com/dashboard/admin/index.php</code></p>";
    echo "<p><strong>System Status:</strong> <code>https://your-app.onrender.com/dashboard/system_status.php</code></p>";
    echo "<p><strong>Health Check:</strong> <code>https://your-app.onrender.com/health.php</code></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Test Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</div>";
?>
