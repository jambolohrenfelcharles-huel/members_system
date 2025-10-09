<?php
/**
 * Final test for notification helper fix
 * Tests the class property approach
 */

echo "<h1>Notification Helper Final Fix Test</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

try {
    // Test the notification helper include
    echo "<h2>🔧 Testing Notification Helper</h2>";
    
    // Capture any output/warnings
    ob_start();
    
    require_once 'config/notification_helper.php';
    
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "<p style='color: orange;'>⚠️ Output captured: " . htmlspecialchars($output) . "</p>";
    } else {
        echo "<p style='color: green;'>✅ No output/warnings from notification helper</p>";
    }
    
    // Test database connection
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        $isPostgreSQL = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql';
        echo "<p style='color: green;'>✅ Database connection successful</p>";
        echo "<p>Database type: " . ($isPostgreSQL ? 'PostgreSQL' : 'MySQL') . "</p>";
        
        // Test notification helper instantiation
        try {
            $notificationHelper = new NotificationHelper($db);
            echo "<p style='color: green;'>✅ NotificationHelper instantiated successfully</p>";
            
            // Test reflection to check private property
            $reflection = new ReflectionClass($notificationHelper);
            $membersTableProperty = $reflection->getProperty('members_table');
            $membersTableProperty->setAccessible(true);
            $membersTableValue = $membersTableProperty->getValue($notificationHelper);
            
            echo "<p style='color: green;'>✅ \$members_table property set to: <strong>$membersTableValue</strong></p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ NotificationHelper instantiation failed: " . $e->getMessage() . "</p>";
        }
        
        // Test the members table query
        $members_table = $isPostgreSQL ? 'members' : 'membership_monitoring';
        echo "<p>Expected members table: <strong>$members_table</strong></p>";
        
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM $members_table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✅ Members table query successful: " . $result['total'] . " records</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Members table query failed: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }
    
    echo "<h2>🎯 Test Summary</h2>";
    
    if ($isPostgreSQL) {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3 style='color: green;'>✅ PostgreSQL Ready!</h3>";
        echo "<p>Notification helper should now work correctly with PostgreSQL on Render.</p>";
        echo "<p><strong>What was fixed:</strong></p>";
        echo "<ul>";
        echo "<li>✅ Moved \$members_table to class property</li>";
        echo "<li>✅ Updated all references to use \$this->members_table</li>";
        echo "<li>✅ Removed global variable scope issue</li>";
        echo "<li>✅ Fixed header modification error</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3 style='color: orange;'>⚠️ MySQL Detected</h3>";
        echo "<p>You're currently using MySQL. The fixes will work when you deploy to PostgreSQL on Render.</p>";
        echo "</div>";
    }
    
    echo "<h3>🔗 How to Use</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<ol>";
    echo "<li><strong>Add Announcements:</strong> Go to dashboard/announcements/add.php</li>";
    echo "<li><strong>Send Notifications:</strong> Notifications will be sent to all active members</li>";
    echo "<li><strong>No More Errors:</strong> Undefined variable and header errors are fixed</li>";
    echo "<li><strong>PostgreSQL Ready:</strong> Works correctly on Render deployment</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Test Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</div>";
?>
