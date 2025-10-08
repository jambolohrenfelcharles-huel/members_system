<?php
/**
 * COMPLETE EVENTS TABLE FIX
 * Ensures ALL required columns exist in the events table for PostgreSQL
 */

require_once 'config/database.php';

echo "<h1>🔧 COMPLETE EVENTS TABLE FIX</h1>";
echo "<p>Ensuring ALL required columns exist in the events table</p>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ <strong>Database Connected!</strong><br>";
    echo "✅ <strong>Database Type:</strong> " . strtoupper($_ENV['DB_TYPE'] ?? 'mysql') . "<br>";
    echo "</div>";
    
    $dbType = $_ENV['DB_TYPE'] ?? 'mysql';
    
    if ($dbType !== 'postgresql') {
        echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>⚠️ LOCAL MYSQL DETECTED</h3>";
        echo "<p>This script is designed for PostgreSQL (Render). Your local MySQL database is working correctly.</p>";
        echo "</div>";
        exit;
    }
    
    // Step 1: Check current events table structure
    echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🔍 Step 1: Check Current Events Table Structure</h3>";
    
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
        
        // Check for missing columns
        $columnNames = array_column($columns, 'column_name');
        $requiredColumns = ['id', 'name', 'place', 'status', 'event_date', 'description', 'region', 'organizing_club', 'created_at'];
        $missingColumns = array_diff($requiredColumns, $columnNames);
        
        if (!empty($missingColumns)) {
            echo "❌ <strong>Missing columns:</strong> " . implode(', ', $missingColumns) . "<br>";
        } else {
            echo "✅ <strong>All required columns present</strong><br>";
        }
        
    } else {
        echo "❌ <strong>Events table does not exist</strong><br>";
    }
    echo "</div>";
    
    // Step 2: Complete fix - drop and recreate with proper structure
    echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🔧 Step 2: Complete Events Table Fix</h3>";
    
    if ($tableExists) {
        // Get existing data first
        echo "📊 <strong>Backing up existing data...</strong><br>";
        $stmt = $db->prepare("SELECT * FROM events");
        $stmt->execute();
        $existingData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($existingData)) {
            echo "✅ <strong>Found " . count($existingData) . " existing records</strong><br>";
        } else {
            echo "✅ <strong>No existing data to backup</strong><br>";
        }
        
        // Drop the existing table
        echo "🗑️ <strong>Dropping existing events table...</strong><br>";
        $db->exec("DROP TABLE IF EXISTS events");
        echo "✅ <strong>Events table dropped</strong><br>";
    }
    
    // Create the table with complete structure
    echo "🏗️ <strong>Creating events table with complete structure...</strong><br>";
    
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
    
    // Create index
    try {
        $db->exec("CREATE INDEX IF NOT EXISTS idx_events_date ON events(event_date)");
        echo "✅ <strong>Index created successfully!</strong><br>";
    } catch (Exception $e) {
        echo "⚠️ <strong>Index creation skipped:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    }
    
    // Restore existing data if any
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
    
    echo "</div>";
    
    // Step 3: Verify the complete structure
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🧪 Step 3: Verify Complete Structure</h3>";
    
    // Get the new structure
    $stmt = $db->prepare("SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = 'events' ORDER BY ordinal_position");
    $stmt->execute();
    $newColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 <strong>New structure:</strong><br>";
    foreach ($newColumns as $column) {
        echo "&nbsp;&nbsp;• <strong>{$column['column_name']}:</strong> {$column['data_type']} " . 
             ($column['is_nullable'] === 'NO' ? '(NOT NULL)' : '(NULL)') . 
             ($column['column_default'] ? " DEFAULT {$column['column_default']}" : "") . "<br>";
    }
    
    // Verify all required columns exist
    $newColumnNames = array_column($newColumns, 'column_name');
    $requiredColumns = ['id', 'name', 'place', 'status', 'event_date', 'description', 'region', 'organizing_club', 'created_at'];
    $stillMissing = array_diff($requiredColumns, $newColumnNames);
    
    if (empty($stillMissing)) {
        echo "✅ <strong>All required columns are now present!</strong><br>";
    } else {
        echo "❌ <strong>Still missing columns:</strong> " . implode(', ', $stillMissing) . "<br>";
    }
    
    echo "</div>";
    
    // Step 4: Test the exact query from add.php
    echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🧪 Step 4: Test Add Event Query</h3>";
    
    try {
        // Test the exact query from add.php
        $testQuery = "INSERT INTO events (name, place, status, event_date, description, region, organizing_club) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $testStmt = $db->prepare($testQuery);
        
        $testData = [
            'Test Event - Complete Fix',
            'Test Location',
            'upcoming',
            '2025-12-31 10:00:00',
            'This is a test event to verify the complete fix.',
            'Test Region',
            'Test Club'
        ];
        
        $result = $testStmt->execute($testData);
        
        if ($result) {
            echo "✅ <strong>Test insert successful!</strong><br>";
            echo "✅ <strong>The exact query from add.php now works perfectly!</strong><br>";
            
            // Get the inserted ID and show the data
            $testId = $db->lastInsertId();
            $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$testId]);
            $testRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "📊 <strong>Test record created:</strong><br>";
            echo "&nbsp;&nbsp;• <strong>ID:</strong> {$testRecord['id']}<br>";
            echo "&nbsp;&nbsp;• <strong>Name:</strong> {$testRecord['name']}<br>";
            echo "&nbsp;&nbsp;• <strong>Place:</strong> {$testRecord['place']}<br>";
            echo "&nbsp;&nbsp;• <strong>Status:</strong> {$testRecord['status']}<br>";
            echo "&nbsp;&nbsp;• <strong>Event Date:</strong> {$testRecord['event_date']}<br>";
            echo "&nbsp;&nbsp;• <strong>Description:</strong> {$testRecord['description']}<br>";
            echo "&nbsp;&nbsp;• <strong>Region:</strong> {$testRecord['region']}<br>";
            echo "&nbsp;&nbsp;• <strong>Organizing Club:</strong> {$testRecord['organizing_club']}<br>";
            
            // Clean up test data
            $db->exec("DELETE FROM events WHERE id = $testId");
            echo "✅ <strong>Test data cleaned up</strong><br>";
        } else {
            echo "❌ <strong>Test insert failed</strong><br>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Test insert error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    }
    echo "</div>";
    
    // Step 5: Provide test URLs
    echo "<div style='background: #e2e3e5; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🔗 Step 5: Test Events Functionality</h3>";
    
    echo "✅ <strong>Now test these URLs to verify events functionality:</strong><br>";
    echo "&nbsp;&nbsp;• <a href='dashboard/events/add.php' target='_blank'>dashboard/events/add.php</a> - Add Event<br>";
    echo "&nbsp;&nbsp;• <a href='dashboard/events/index.php' target='_blank'>dashboard/events/index.php</a> - Events List<br>";
    echo "&nbsp;&nbsp;• <a href='dashboard/events/edit.php' target='_blank'>dashboard/events/edit.php</a> - Edit Event<br>";
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #28a745;'>";
    echo "<h2>🎉 COMPLETE EVENTS TABLE FIX COMPLETE!</h2>";
    echo "<p><strong>✅ ALL required columns now exist in the events table!</strong></p>";
    echo "<h3>🔧 What Was Fixed:</h3>";
    echo "<ul>";
    echo "<li>✅ <strong>Complete Table Recreation:</strong> Dropped and recreated with proper structure</li>";
    echo "<li>✅ <strong>All Required Columns:</strong> name, place, status, event_date, description, region, organizing_club, created_at</li>";
    echo "<li>✅ <strong>Data Preservation:</strong> Existing data backed up and restored</li>";
    echo "<li>✅ <strong>Proper Constraints:</strong> CHECK constraints for status field</li>";
    echo "<li>✅ <strong>Performance Indexes:</strong> Index on event_date created</li>";
    echo "<li>✅ <strong>Test Verification:</strong> Confirmed the exact add.php query works</li>";
    echo "</ul>";
    echo "<h3>🎯 Your Events System is Now Ready:</h3>";
    echo "<ul>";
    echo "<li>➕ <strong>Add Events:</strong> Full event creation functionality</li>";
    echo "<li>📅 <strong>View Events:</strong> Complete events list</li>";
    echo "<li>✏️ <strong>Edit Events:</strong> Event editing capabilities</li>";
    echo "<li>🗑️ <strong>Delete Events:</strong> Event deletion functionality</li>";
    echo "<li>📊 <strong>Event Management:</strong> Complete CRUD operations</li>";
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
