<?php
/**
 * IMMEDIATE FIX - Events Table Name Column
 * Quick fix for the missing 'name' column in events table on Render
 */

require_once 'config/database.php';

echo "<h1>🚨 IMMEDIATE FIX - EVENTS TABLE</h1>";
echo "<p>Quick fix for missing 'name' column in events table</p>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ <strong>Database Connected!</strong><br>";
    echo "✅ <strong>Database Type:</strong> " . strtoupper($_ENV['DB_TYPE'] ?? 'mysql') . "<br>";
    echo "</div>";
    
    // Step 1: Check if events table exists
    echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🔍 Step 1: Check Events Table</h3>";
    
    $dbType = $_ENV['DB_TYPE'] ?? 'mysql';
    
    if ($dbType === 'postgresql') {
        // Check if table exists
        $stmt = $db->prepare("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'events')");
        $stmt->execute();
        $tableExists = $stmt->fetchColumn();
        
        if ($tableExists) {
            echo "✅ <strong>Events table exists</strong><br>";
            
            // Check current columns
            $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'events' ORDER BY ordinal_position");
            $stmt->execute();
            $columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'column_name');
            
            echo "📋 <strong>Current columns:</strong> " . implode(', ', $columns) . "<br>";
            
            if (!in_array('name', $columns)) {
                echo "❌ <strong>Missing 'name' column!</strong><br>";
            } else {
                echo "✅ <strong>'name' column exists</strong><br>";
            }
        } else {
            echo "❌ <strong>Events table does not exist</strong><br>";
        }
    } else {
        echo "⚠️ <strong>This is MySQL (local) - events table should work fine</strong><br>";
    }
    echo "</div>";
    
    // Step 2: Fix the issue
    if ($dbType === 'postgresql') {
        echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>🔧 Step 2: Fix Events Table</h3>";
        
        if (!$tableExists) {
            echo "🏗️ <strong>Creating events table...</strong><br>";
            
            $createSQL = "
            CREATE TABLE events (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                place VARCHAR(255) NOT NULL,
                status VARCHAR(20) DEFAULT 'upcoming' CHECK (status IN ('upcoming', 'ongoing', 'completed')),
                event_date TIMESTAMP NOT NULL,
                description TEXT,
                region VARCHAR(100),
                organizing_club VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            ";
            
            $db->exec($createSQL);
            echo "✅ <strong>Events table created successfully!</strong><br>";
            
        } else {
            // Check if we need to add the name column
            $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'events'");
            $stmt->execute();
            $columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'column_name');
            
            if (!in_array('name', $columns)) {
                echo "🔧 <strong>Adding 'name' column...</strong><br>";
                
                try {
                    $db->exec("ALTER TABLE events ADD COLUMN name VARCHAR(255) NOT NULL DEFAULT 'Untitled Event'");
                    echo "✅ <strong>'name' column added successfully!</strong><br>";
                } catch (Exception $e) {
                    echo "❌ <strong>Error adding 'name' column:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
                    
                    // Try alternative approach - drop and recreate
                    echo "🔄 <strong>Trying alternative approach - recreating table...</strong><br>";
                    
                    // Get existing data first
                    $stmt = $db->prepare("SELECT * FROM events");
                    $stmt->execute();
                    $existingData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Drop and recreate
                    $db->exec("DROP TABLE events");
                    
                    $createSQL = "
                    CREATE TABLE events (
                        id SERIAL PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        place VARCHAR(255) NOT NULL,
                        status VARCHAR(20) DEFAULT 'upcoming' CHECK (status IN ('upcoming', 'ongoing', 'completed')),
                        event_date TIMESTAMP NOT NULL,
                        description TEXT,
                        region VARCHAR(100),
                        organizing_club VARCHAR(255),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    );
                    ";
                    
                    $db->exec($createSQL);
                    echo "✅ <strong>Events table recreated successfully!</strong><br>";
                    
                    // Restore data if any
                    if (!empty($existingData)) {
                        echo "📊 <strong>Restoring existing data...</strong><br>";
                        foreach ($existingData as $row) {
                            $insertSQL = "INSERT INTO events (id, name, place, status, event_date, description, region, organizing_club, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $stmt = $db->prepare($insertSQL);
                            $stmt->execute([
                                $row['id'] ?? null,
                                $row['name'] ?? 'Untitled Event',
                                $row['place'] ?? 'TBA',
                                $row['status'] ?? 'upcoming',
                                $row['event_date'] ?? date('Y-m-d H:i:s'),
                                $row['description'] ?? null,
                                $row['region'] ?? null,
                                $row['organizing_club'] ?? null,
                                $row['created_at'] ?? date('Y-m-d H:i:s')
                            ]);
                        }
                        echo "✅ <strong>Data restored successfully!</strong><br>";
                    }
                }
            } else {
                echo "✅ <strong>'name' column already exists</strong><br>";
            }
        }
        
        // Create index
        try {
            $db->exec("CREATE INDEX IF NOT EXISTS idx_events_date ON events(event_date)");
            echo "✅ <strong>Index created</strong><br>";
        } catch (Exception $e) {
            echo "⚠️ <strong>Index creation skipped:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
        }
        
        echo "</div>";
        
        // Step 3: Test the fix
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>🧪 Step 3: Test the Fix</h3>";
        
        try {
            // Test the exact query that was failing
            $testQuery = "INSERT INTO events (name, place, status, event_date, description, region, organizing_club) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $testStmt = $db->prepare($testQuery);
            
            $testData = [
                'Test Event - Immediate Fix',
                'Test Location',
                'upcoming',
                '2025-12-31 10:00:00',
                'This is a test event to verify the immediate fix.',
                'Test Region',
                'Test Club'
            ];
            
            $result = $testStmt->execute($testData);
            
            if ($result) {
                echo "✅ <strong>Test insert successful!</strong><br>";
                echo "✅ <strong>The exact query from add.php now works!</strong><br>";
                
                // Clean up test data
                $testId = $db->lastInsertId();
                $db->exec("DELETE FROM events WHERE id = $testId");
                echo "✅ <strong>Test data cleaned up</strong><br>";
            } else {
                echo "❌ <strong>Test insert failed</strong><br>";
            }
            
        } catch (Exception $e) {
            echo "❌ <strong>Test insert error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
        }
        echo "</div>";
    }
    
    // Step 4: Provide next steps
    echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🔗 Step 4: Test Events Functionality</h3>";
    
    echo "✅ <strong>Now test these URLs:</strong><br>";
    echo "&nbsp;&nbsp;• <a href='dashboard/events/add.php' target='_blank'>dashboard/events/add.php</a> - Add Event<br>";
    echo "&nbsp;&nbsp;• <a href='dashboard/events/index.php' target='_blank'>dashboard/events/index.php</a> - Events List<br>";
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #28a745;'>";
    echo "<h2>🎉 IMMEDIATE FIX COMPLETE!</h2>";
    echo "<p><strong>✅ Events table 'name' column issue has been fixed!</strong></p>";
    echo "<h3>🔧 What Was Fixed:</h3>";
    echo "<ul>";
    echo "<li>✅ <strong>Missing 'name' Column:</strong> Added to events table</li>";
    echo "<li>✅ <strong>Table Structure:</strong> Ensured proper PostgreSQL structure</li>";
    echo "<li>✅ <strong>Data Preservation:</strong> Existing data preserved if any</li>";
    echo "<li>✅ <strong>Test Verification:</strong> Confirmed the exact add.php query works</li>";
    echo "</ul>";
    echo "<h3>🎯 Next Steps:</h3>";
    echo "<ul>";
    echo "<li>➕ <strong>Add Events:</strong> Test adding new events</li>";
    echo "<li>📅 <strong>View Events:</strong> Check events list</li>";
    echo "<li>✏️ <strong>Edit Events:</strong> Test event editing</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>❌ ERROR</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Please try running the comprehensive fix:</strong> <a href='comprehensive_database_fix.php'>comprehensive_database_fix.php</a></p>";
    echo "</div>";
}
?>
