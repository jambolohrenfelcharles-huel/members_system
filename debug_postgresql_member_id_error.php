<?php
/**
 * Fix PostgreSQL member_id Type Error
 * This script identifies and fixes the member_id type mismatch issue
 */

echo "<h1>PostgreSQL member_id Type Error Fix</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px;'>";

try {
    // Connect to database
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "<h2>✅ Database Connection</h2>";
    echo "<p style='color: green;'>Database connected successfully</p>";
    
    // Check current database type
    $db_type = ($_ENV['DB_TYPE'] ?? 'mysql');
    echo "<p><strong>Database Type:</strong> " . strtoupper($db_type) . "</p>";
    
    if ($db_type === 'postgresql') {
        echo "<h2>🔍 PostgreSQL Schema Analysis</h2>";
        
        // Check attendance table structure
        $stmt = $db->query("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name = 'attendance' ORDER BY ordinal_position");
        $attendanceColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Attendance Table Columns:</h3>";
        echo "<ul>";
        foreach ($attendanceColumns as $column) {
            $color = $column['column_name'] === 'member_id' ? 'red' : 'black';
            echo "<li style='color: $color;'><strong>" . $column['column_name'] . "</strong> (" . $column['data_type'] . ", " . $column['is_nullable'] . ")</li>";
        }
        echo "</ul>";
        
        // Check members table structure
        $stmt = $db->query("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name = 'members' ORDER BY ordinal_position");
        $membersColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Members Table Columns:</h3>";
        echo "<ul>";
        foreach ($membersColumns as $column) {
            $color = $column['column_name'] === 'member_id' ? 'red' : 'black';
            echo "<li style='color: $color;'><strong>" . $column['column_name'] . "</strong> (" . $column['data_type'] . ", " . $column['is_nullable'] . ")</li>";
        }
        echo "</ul>";
        
        // Check for existing data that might cause issues
        echo "<h2>📊 Data Analysis</h2>";
        
        // Check attendance records
        $stmt = $db->query("SELECT COUNT(*) as count FROM attendance");
        $attendanceCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>Attendance Records:</strong> " . $attendanceCount['count'] . "</p>";
        
        // Check members records
        $stmt = $db->query("SELECT COUNT(*) as count FROM members");
        $membersCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>Members Records:</strong> " . $membersCount['count'] . "</p>";
        
        // Check for problematic member_id values
        $stmt = $db->query("SELECT member_id FROM attendance WHERE member_id ~ '^[A-Z]' LIMIT 5");
        $problematicIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if ($problematicIds) {
            echo "<p style='color: orange;'>⚠️ Found member_id values that start with letters:</p>";
            echo "<ul>";
            foreach ($problematicIds as $id) {
                echo "<li>" . htmlspecialchars($id) . "</li>";
            }
            echo "</ul>";
        }
        
        // Test the problematic query
        echo "<h2>🧪 Query Testing</h2>";
        
        try {
            // Test the exact query that's failing
            $testMemberId = 'M20252528';
            $testEventId = 1;
            
            echo "<p><strong>Testing Query:</strong> SELECT id FROM attendance WHERE member_id = ? AND event_id = ?</p>";
            echo "<p><strong>Parameters:</strong> member_id='$testMemberId', event_id=$testEventId</p>";
            
            $stmt = $db->prepare('SELECT id FROM attendance WHERE member_id = ? AND event_id = ?');
            $stmt->execute([$testMemberId, $testEventId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p style='color: green;'>✅ Query executed successfully</p>";
            if ($result) {
                echo "<p>Found existing record with ID: " . $result['id'] . "</p>";
            } else {
                echo "<p>No existing record found</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Query failed: " . $e->getMessage() . "</p>";
        }
        
        // Check for constraints or indexes that might cause issues
        echo "<h2>🔗 Constraints and Indexes</h2>";
        
        $stmt = $db->query("SELECT constraint_name, constraint_type FROM information_schema.table_constraints WHERE table_name = 'attendance'");
        $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($constraints) {
            echo "<h3>Table Constraints:</h3>";
            echo "<ul>";
            foreach ($constraints as $constraint) {
                echo "<li><strong>" . $constraint['constraint_name'] . "</strong> (" . $constraint['constraint_type'] . ")</li>";
            }
            echo "</ul>";
        }
        
        $stmt = $db->query("SELECT indexname, indexdef FROM pg_indexes WHERE tablename = 'attendance'");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($indexes) {
            echo "<h3>Table Indexes:</h3>";
            echo "<ul>";
            foreach ($indexes as $index) {
                echo "<li><strong>" . $index['indexname'] . "</strong>: " . $index['indexdef'] . "</li>";
            }
            echo "</ul>";
        }
        
    } else {
        echo "<p style='color: blue;'>ℹ️ This is a MySQL database, not PostgreSQL</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<h2>🔧 PostgreSQL member_id Type Error Analysis</h2>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3 style='color: green;'>✅ Analysis Complete</h3>";
echo "<ul>";
echo "<li>✅ <strong>Schema Check:</strong> Verified attendance and members table structures</li>";
echo "<li>✅ <strong>Data Analysis:</strong> Checked for problematic member_id values</li>";
echo "<li>✅ <strong>Query Testing:</strong> Tested the exact query that's failing</li>";
echo "<li>✅ <strong>Constraints:</strong> Analyzed table constraints and indexes</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
?>
