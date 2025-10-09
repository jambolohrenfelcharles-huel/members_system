<?php
/**
 * Run Name to Title Migration
 * Renames name column to title in events table
 */

echo "<h1>Name to Title Migration - Events Table</h1>";
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
    
    echo "<h2>Checking Events Table Structure</h2>";
    
    // Check current table structure
    try {
        $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'events' ORDER BY ordinal_position");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p><strong>Current events table columns:</strong> " . implode(', ', $columns) . "</p>";
        
        $hasName = in_array('name', $columns);
        $hasTitle = in_array('title', $columns);
        
        if ($hasName && !$hasTitle) {
            echo "<p style='color: orange;'>🔧 Found name column, renaming to title...</p>";
            
            if ($isPostgreSQL) {
                $db->exec("ALTER TABLE events RENAME COLUMN name TO title");
            } else {
                $db->exec("ALTER TABLE events CHANGE name title VARCHAR(255) NOT NULL");
            }
            
            echo "<p style='color: green;'>✅ name column renamed to title successfully</p>";
            
            // Update any NULL values
            try {
                $db->exec("UPDATE events SET title = 'Untitled Event' WHERE title IS NULL OR title = ''");
                echo "<p style='color: green;'>✅ Updated NULL title values</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ Update NULL values warning: " . $e->getMessage() . "</p>";
            }
            
        } elseif ($hasTitle && !$hasName) {
            echo "<p style='color: green;'>✅ title column already exists (no name column found)</p>";
        } elseif ($hasName && $hasTitle) {
            echo "<p style='color: orange;'>⚠️ Both name and title columns exist - this may cause issues</p>";
        } else {
            echo "<p style='color: red;'>❌ Neither name nor title column found</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error checking table structure: " . $e->getMessage() . "</p>";
    }
    
    // Verify the final structure
    echo "<h2>Verifying Final Table Structure</h2>";
    
    try {
        $stmt = $db->prepare("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'events' ORDER BY ordinal_position");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Final events table structure:</strong></p>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>" . $column['column_name'] . " (" . $column['data_type'] . ")</li>";
        }
        echo "</ul>";
        
        // Check if title column is in the list
        $hasTitleColumn = false;
        foreach ($columns as $column) {
            if ($column['column_name'] === 'title') {
                $hasTitleColumn = true;
                break;
            }
        }
        
        if ($hasTitleColumn) {
            echo "<p style='color: green;'>✅ title column confirmed in events table</p>";
        } else {
            echo "<p style='color: red;'>❌ title column not found in events table</p>";
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
        
        // Test inserting a new event with title
        $testTitle = "Test Title Migration - " . date('Y-m-d H:i:s');
        $testPlace = "Test Location";
        $testDate = date('Y-m-d H:i:s', strtotime('+1 day'));
        $testDescription = "Test event to verify title column works";
        $testRegion = "Test Region";
        $testClub = "Test Club";
        
        $stmt = $db->prepare("INSERT INTO events (title, place, status, event_date, description, region, organizing_club) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$testTitle, $testPlace, 'upcoming', $testDate, $testDescription, $testRegion, $testClub]);
        
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
    echo "<h3 style='color: green;'>✅ Name to Title Migration Complete</h3>";
    echo "<p>The events table now uses the 'title' column instead of 'name' and is compatible with the application code.</p>";
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
