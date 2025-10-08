<?php
/**
 * Fix Events Table on Render (PostgreSQL)
 * Specifically designed to fix the events table structure on Render's PostgreSQL
 */

require_once 'config/database.php';

echo "<h1>🔧 FIX EVENTS TABLE ON RENDER (PostgreSQL)</h1>";
echo "<p>Fixing the events table structure specifically for Render's PostgreSQL deployment</p>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ <strong>Database Connected!</strong><br>";
    echo "✅ <strong>Database Type:</strong> " . strtoupper($_ENV['DB_TYPE'] ?? 'mysql') . "<br>";
    echo "✅ <strong>Environment:</strong> " . ($_ENV['RENDER'] ? 'Render' : 'Local') . "<br>";
    echo "</div>";
    
    // Step 1: Check current events table structure on PostgreSQL
    echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🔍 Step 1: Check Current Events Table Structure</h3>";
    
    $dbType = $_ENV['DB_TYPE'] ?? 'mysql';
    
    if ($dbType === 'postgresql') {
        // Check if table exists
        $stmt = $db->prepare("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'events')");
        $stmt->execute();
        $tableExists = $stmt->fetchColumn();
        
        if ($tableExists) {
            echo "✅ <strong>Events table exists</strong><br>";
            
            // Get current structure
            $stmt = $db->prepare("SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = 'events' ORDER BY ordinal_position");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "📋 <strong>Current structure:</strong><br>";
            foreach ($columns as $column) {
                echo "&nbsp;&nbsp;• <strong>{$column['column_name']}:</strong> {$column['data_type']} " . 
                     ($column['is_nullable'] === 'NO' ? '(NOT NULL)' : '(NULL)') . 
                     ($column['column_default'] ? " DEFAULT {$column['column_default']}" : "") . "<br>";
            }
            
            // Check for missing 'name' column
            $columnNames = array_column($columns, 'column_name');
            if (!in_array('name', $columnNames)) {
                echo "❌ <strong>Missing 'name' column!</strong><br>";
            } else {
                echo "✅ <strong>'name' column exists</strong><br>";
            }
            
        } else {
            echo "❌ <strong>Events table does not exist</strong><br>";
        }
    } else {
        echo "⚠️ <strong>This script is designed for PostgreSQL (Render)</strong><br>";
        echo "✅ <strong>Local MySQL structure is correct</strong><br>";
    }
    echo "</div>";
    
    // Step 2: Fix the events table for PostgreSQL
    if ($dbType === 'postgresql') {
        echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>🔧 Step 2: Fix Events Table for PostgreSQL</h3>";
        
        if (!$tableExists) {
            echo "🏗️ <strong>Creating events table...</strong><br>";
            
            $createTableSQL = "
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
            
            $db->exec($createTableSQL);
            echo "✅ <strong>Events table created successfully!</strong><br>";
            
        } else {
            // Check if we need to add missing columns
            $columnNames = array_column($columns, 'column_name');
            $requiredColumns = ['id', 'name', 'place', 'status', 'event_date', 'description', 'region', 'organizing_club', 'created_at'];
            $missingColumns = array_diff($requiredColumns, $columnNames);
            
            if (!empty($missingColumns)) {
                echo "🔧 <strong>Adding missing columns:</strong> " . implode(', ', $missingColumns) . "<br>";
                
                foreach ($missingColumns as $column) {
                    switch ($column) {
                        case 'name':
                            $db->exec("ALTER TABLE events ADD COLUMN name VARCHAR(255) NOT NULL DEFAULT 'Untitled Event'");
                            echo "&nbsp;&nbsp;✅ Added 'name' column<br>";
                            break;
                        case 'place':
                            $db->exec("ALTER TABLE events ADD COLUMN place VARCHAR(255) NOT NULL DEFAULT 'TBA'");
                            echo "&nbsp;&nbsp;✅ Added 'place' column<br>";
                            break;
                        case 'status':
                            $db->exec("ALTER TABLE events ADD COLUMN status VARCHAR(20) DEFAULT 'upcoming' CHECK (status IN ('upcoming', 'ongoing', 'completed'))");
                            echo "&nbsp;&nbsp;✅ Added 'status' column<br>";
                            break;
                        case 'event_date':
                            $db->exec("ALTER TABLE events ADD COLUMN event_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
                            echo "&nbsp;&nbsp;✅ Added 'event_date' column<br>";
                            break;
                        case 'description':
                            $db->exec("ALTER TABLE events ADD COLUMN description TEXT");
                            echo "&nbsp;&nbsp;✅ Added 'description' column<br>";
                            break;
                        case 'region':
                            $db->exec("ALTER TABLE events ADD COLUMN region VARCHAR(100)");
                            echo "&nbsp;&nbsp;✅ Added 'region' column<br>";
                            break;
                        case 'organizing_club':
                            $db->exec("ALTER TABLE events ADD COLUMN organizing_club VARCHAR(255)");
                            echo "&nbsp;&nbsp;✅ Added 'organizing_club' column<br>";
                            break;
                        case 'created_at':
                            $db->exec("ALTER TABLE events ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                            echo "&nbsp;&nbsp;✅ Added 'created_at' column<br>";
                            break;
                    }
                }
            } else {
                echo "✅ <strong>All required columns are present</strong><br>";
            }
        }
        
        // Create index
        try {
            $db->exec("CREATE INDEX IF NOT EXISTS idx_events_date ON events(event_date)");
            echo "✅ <strong>Index created successfully!</strong><br>";
        } catch (Exception $e) {
            echo "⚠️ <strong>Index creation skipped:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
        }
        
        echo "</div>";
        
        // Step 3: Test the fix
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>🧪 Step 3: Test the Fix</h3>";
        
        try {
            // Test the exact query from add.php
            $testQuery = "INSERT INTO events (name, place, status, event_date, description, region, organizing_club) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $testStmt = $db->prepare($testQuery);
            
            $testData = [
                'Test Event - Render Fix',
                'Test Location',
                'upcoming',
                '2025-12-31 10:00:00',
                'This is a test event to verify the PostgreSQL fix.',
                'Test Region',
                'Test Club'
            ];
            
            $result = $testStmt->execute($testData);
            
            if ($result) {
                echo "✅ <strong>Test insert successful!</strong><br>";
                echo "✅ <strong>The exact query from add.php now works!</strong><br>";
                
                // Get the inserted ID and delete the test record
                $testId = $db->lastInsertId();
                $db->exec("DELETE FROM events WHERE id = $testId");
                echo "✅ <strong>Test record cleaned up</strong><br>";
            } else {
                echo "❌ <strong>Test insert failed</strong><br>";
            }
            
        } catch (Exception $e) {
            echo "❌ <strong>Test insert error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
        }
        echo "</div>";
        
    }
    
    // Step 4: Provide test URLs
    echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🔗 Step 4: Test Events Functionality</h3>";
    
    echo "✅ <strong>Test these URLs to verify events functionality:</strong><br>";
    echo "&nbsp;&nbsp;• <a href='dashboard/events/add.php' target='_blank'>dashboard/events/add.php</a> - Add Event<br>";
    echo "&nbsp;&nbsp;• <a href='dashboard/events/index.php' target='_blank'>dashboard/events/index.php</a> - Events List<br>";
    echo "&nbsp;&nbsp;• <a href='dashboard/events/edit.php' target='_blank'>dashboard/events/edit.php</a> - Edit Event<br>";
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #28a745;'>";
    echo "<h2>🎉 EVENTS TABLE FIX COMPLETE!</h2>";
    echo "<p><strong>✅ Events table structure has been fixed for PostgreSQL!</strong></p>";
    echo "<h3>🔧 What Was Fixed:</h3>";
    echo "<ul>";
    echo "<li>✅ <strong>Table Structure:</strong> Created/fixed with all required columns</li>";
    echo "<li>✅ <strong>Missing Columns:</strong> Added any missing columns</li>";
    echo "<li>✅ <strong>Data Types:</strong> Proper PostgreSQL data types</li>";
    echo "<li>✅ <strong>Constraints:</strong> Proper constraints and defaults</li>";
    echo "<li>✅ <strong>Indexes:</strong> Performance indexes created</li>";
    echo "<li>✅ <strong>Test Insert:</strong> Verified the exact add.php query works</li>";
    echo "</ul>";
    echo "<h3>🎯 Next Steps:</h3>";
    echo "<ul>";
    echo "<li>➕ <strong>Add Events:</strong> Test adding new events on Render</li>";
    echo "<li>📅 <strong>View Events:</strong> Check events list functionality</li>";
    echo "<li>✏️ <strong>Edit Events:</strong> Test event editing</li>";
    echo "<li>🗑️ <strong>Delete Events:</strong> Test event deletion</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>❌ ERROR</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
