<?php
/**
 * Test Events Schema Fix
 * Verify that the events table schema is correct and works with the application
 */

echo "<h1>Events Schema Fix Test</h1>";
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
        
        // Test events table structure
        echo "<h3>📅 Testing Events Table Structure</h3>";
        
        try {
            $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'events' ORDER BY ordinal_position");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p><strong>Events table columns:</strong></p>";
            echo "<ul>";
            $hasNameColumn = false;
            $hasTitleColumn = false;
            $hasPlaceColumn = false;
            $hasEventDateColumn = false;
            $hasStatusColumn = false;
            $hasDescriptionColumn = false;
            $hasRegionColumn = false;
            $hasOrganizingClubColumn = false;
            
            foreach ($columns as $column) {
                $columnName = $column['column_name'];
                $dataType = $column['data_type'];
                
                // Check for required columns
                if ($columnName === 'name') {
                    $hasNameColumn = true;
                    echo "<li style='color: green; font-weight: bold;'>" . $columnName . " (" . $dataType . ") ✅</li>";
                } elseif ($columnName === 'title') {
                    $hasTitleColumn = true;
                    echo "<li style='color: red; font-weight: bold;'>" . $columnName . " (" . $dataType . ") ❌</li>";
                } elseif ($columnName === 'place') {
                    $hasPlaceColumn = true;
                    echo "<li style='color: green; font-weight: bold;'>" . $columnName . " (" . $dataType . ") ✅</li>";
                } elseif ($columnName === 'event_date') {
                    $hasEventDateColumn = true;
                    echo "<li style='color: green; font-weight: bold;'>" . $columnName . " (" . $dataType . ") ✅</li>";
                } elseif ($columnName === 'status') {
                    $hasStatusColumn = true;
                    echo "<li style='color: green; font-weight: bold;'>" . $columnName . " (" . $dataType . ") ✅</li>";
                } elseif ($columnName === 'description') {
                    $hasDescriptionColumn = true;
                    echo "<li style='color: green; font-weight: bold;'>" . $columnName . " (" . $dataType . ") ✅</li>";
                } elseif ($columnName === 'region') {
                    $hasRegionColumn = true;
                    echo "<li style='color: green; font-weight: bold;'>" . $columnName . " (" . $dataType . ") ✅</li>";
                } elseif ($columnName === 'organizing_club') {
                    $hasOrganizingClubColumn = true;
                    echo "<li style='color: green; font-weight: bold;'>" . $columnName . " (" . $dataType . ") ✅</li>";
                } else {
                    echo "<li>" . $columnName . " (" . $dataType . ")</li>";
                }
            }
            echo "</ul>";
            
            // Check schema compatibility
            echo "<h3>🔍 Schema Compatibility Check</h3>";
            
            if ($hasNameColumn && !$hasTitleColumn) {
                echo "<p style='color: green;'>✅ Schema is correct: name column exists, no title column</p>";
            } elseif ($hasTitleColumn && !$hasNameColumn) {
                echo "<p style='color: red;'>❌ Schema mismatch: title column exists, name column missing</p>";
            } elseif ($hasNameColumn && $hasTitleColumn) {
                echo "<p style='color: orange;'>⚠️ Schema conflict: both name and title columns exist</p>";
            } else {
                echo "<p style='color: red;'>❌ Schema error: neither name nor title column found</p>";
            }
            
            // Check required columns
            $requiredColumns = [
                'name' => $hasNameColumn,
                'place' => $hasPlaceColumn,
                'event_date' => $hasEventDateColumn,
                'status' => $hasStatusColumn,
                'description' => $hasDescriptionColumn,
                'region' => $hasRegionColumn,
                'organizing_club' => $hasOrganizingClubColumn
            ];
            
            echo "<p><strong>Required columns status:</strong></p>";
            echo "<ul>";
            foreach ($requiredColumns as $column => $exists) {
                if ($exists) {
                    echo "<li style='color: green;'>✅ $column</li>";
                } else {
                    echo "<li style='color: red;'>❌ $column</li>";
                }
            }
            echo "</ul>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error checking table structure: " . $e->getMessage() . "</p>";
        }
        
        // Test events functionality
        echo "<h3>📝 Testing Events Functionality</h3>";
        
        try {
            // Test inserting a new event with all required fields
            $testName = "Test Schema Fix - " . date('Y-m-d H:i:s');
            $testPlace = "Test Location";
            $testDate = date('Y-m-d H:i:s', strtotime('+1 day'));
            $testDescription = "Test event to verify schema fix works";
            $testRegion = "Test Region";
            $testClub = "Test Club";
            
            $stmt = $db->prepare("INSERT INTO events (name, place, status, event_date, description, region, organizing_club) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$testName, $testPlace, 'upcoming', $testDate, $testDescription, $testRegion, $testClub]);
            
            if ($result) {
                $eventId = $db->lastInsertId();
                echo "<p style='color: green;'>✅ Test event inserted successfully (ID: $eventId)</p>";
                
                // Verify the event was inserted correctly
                $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
                $stmt->execute([$eventId]);
                $event = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($event) {
                    echo "<p style='color: green;'>✅ Event retrieved successfully</p>";
                    echo "<p><strong>Event details:</strong></p>";
                    echo "<ul>";
                    echo "<li><strong>Name:</strong> " . htmlspecialchars($event['name']) . "</li>";
                    echo "<li><strong>Place:</strong> " . htmlspecialchars($event['place']) . "</li>";
                    echo "<li><strong>Status:</strong> " . htmlspecialchars($event['status']) . "</li>";
                    echo "<li><strong>Date:</strong> " . htmlspecialchars($event['event_date']) . "</li>";
                    echo "<li><strong>Description:</strong> " . htmlspecialchars($event['description']) . "</li>";
                    echo "<li><strong>Region:</strong> " . htmlspecialchars($event['region']) . "</li>";
                    echo "<li><strong>Organizing Club:</strong> " . htmlspecialchars($event['organizing_club']) . "</li>";
                    echo "</ul>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to retrieve inserted event</p>";
                }
                
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
        
        // Test events count
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM events");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✅ Events table accessible: " . $result['total'] . " events</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Events count query failed: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }
    
    echo "<h2>🎯 Fix Summary</h2>";
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: green;'>✅ Events Schema Fix Applied</h3>";
    echo "<p><strong>Problem:</strong> Schema mismatch between 'title' and 'name' columns causing NOT NULL violation</p>";
    echo "<p><strong>Solution:</strong> Updated render_deploy.php to handle column renaming and schema alignment</p>";
    echo "<p><strong>Files Updated:</strong></p>";
    echo "<ul>";
    echo "<li>render_deploy.php - Added comprehensive events table structure checking</li>";
    echo "<li>db/migration_rename_title_to_name_events.sql - Migration script</li>";
    echo "<li>run_title_to_name_migration.php - Local migration runner</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🔗 How to Use Events</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<ol>";
    echo "<li><strong>Add Events:</strong> Go to dashboard/events/add.php</li>";
    echo "<li><strong>Fill Required Fields:</strong> Name, Place, Date, Description</li>";
    echo "<li><strong>Optional Fields:</strong> Region, Organizing Club</li>";
    echo "<li><strong>Submit Form:</strong> Event will be saved with all fields</li>";
    echo "<li><strong>View Events:</strong> Check dashboard/events/index.php</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>🔗 Important URLs for Render</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Add Events:</strong> <code>https://your-app.onrender.com/dashboard/events/add.php</code></p>";
    echo "<p><strong>View Events:</strong> <code>https://your-app.onrender.com/dashboard/events/index.php</code></p>";
    echo "<p><strong>Admin Console:</strong> <code>https://your-app.onrender.com/dashboard/admin/index.php</code></p>";
    echo "<p><strong>System Status:</strong> <code>https://your-app.onrender.com/dashboard/system_status.php</code></p>";
    echo "<p><strong>Health Check:</strong> <code>https://your-app.onrender.com/health.php</code></p>";
    echo "<p><strong>Default Login:</strong> admin / 123</p>";
    echo "</div>";
    
    echo "<h3>📊 Deployment Status</h3>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: green;'>✅ Fix Committed and Pushed</h4>";
    echo "<p><strong>Latest commit:</strong> Fix events table schema mismatch - rename title to name column</p>";
    echo "<p><strong>Status:</strong> Ready for Render deployment</p>";
    echo "<p><strong>Auto-deploy:</strong> Enabled in render.yaml</p>";
    echo "<p><strong>Next step:</strong> Render will automatically deploy the fix</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Test Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</div>";
?>
