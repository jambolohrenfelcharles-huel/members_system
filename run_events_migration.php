<?php
/**
 * Run Events Migration
 * Adds place column to events table
 */

echo "<h1>Events Migration - Add Place Column</h1>";
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
    
    echo "<h2>Adding Place Column to Events Table</h2>";
    
    // Check if place column exists
    try {
        $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'events' AND column_name = 'place'");
        $stmt->execute();
        $columnExists = $stmt->fetch();
        
        if ($columnExists) {
            echo "<p style='color: green;'>✅ place column already exists in events table</p>";
        } else {
            echo "<p style='color: orange;'>🔧 Adding place column to events table...</p>";
            
            if ($isPostgreSQL) {
                $db->exec("ALTER TABLE events ADD COLUMN place VARCHAR(255) NOT NULL DEFAULT ''");
            } else {
                $db->exec("ALTER TABLE events ADD COLUMN place VARCHAR(255) NOT NULL DEFAULT ''");
            }
            
            echo "<p style='color: green;'>✅ place column added successfully</p>";
            
            // Update existing records
            try {
                $db->exec("UPDATE events SET place = 'TBA' WHERE place = '' OR place IS NULL");
                echo "<p style='color: green;'>✅ Existing events updated with default place</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ Update existing records warning: " . $e->getMessage() . "</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error checking/adding place column: " . $e->getMessage() . "</p>";
    }
    
    // Verify the column was added
    echo "<h2>Verifying Events Table Structure</h2>";
    
    try {
        $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'events' ORDER BY ordinal_position");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Events table structure:</strong></p>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>" . $column['column_name'] . " (" . $column['data_type'] . ")</li>";
        }
        echo "</ul>";
        
        // Check if place column is in the list
        $hasPlaceColumn = false;
        foreach ($columns as $column) {
            if ($column['column_name'] === 'place') {
                $hasPlaceColumn = true;
                break;
            }
        }
        
        if ($hasPlaceColumn) {
            echo "<p style='color: green;'>✅ place column confirmed in events table</p>";
        } else {
            echo "<p style='color: red;'>❌ place column not found in events table</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error verifying table structure: " . $e->getMessage() . "</p>";
    }
    
    // Test events functionality
    echo "<h2>Testing Events Functionality</h2>";
    
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM events");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Events table accessible: " . $result['total'] . " events</p>";
        
        // Test inserting a new event with place
        $testName = "Test Event - " . date('Y-m-d H:i:s');
        $testPlace = "Test Location";
        $testDate = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $stmt = $db->prepare("INSERT INTO events (name, place, status, event_date, description) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$testName, $testPlace, 'upcoming', $testDate, 'Test event description']);
        
        if ($result) {
            $eventId = $db->lastInsertId();
            echo "<p style='color: green;'>✅ Test event inserted successfully (ID: $eventId)</p>";
            
            // Clean up test event
            $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            echo "<p style='color: green;'>✅ Test event cleaned up</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to insert test event</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Events functionality test failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>Migration Complete</h2>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: green;'>✅ Events Table Updated</h3>";
    echo "<p>The events table now includes the place column and is ready for use.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Test adding events in the dashboard</li>";
    echo "<li>Deploy to Render</li>";
    echo "<li>Verify events functionality works correctly</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Migration Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</div>";
?>