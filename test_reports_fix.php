<?php
/**
 * Test script to verify reports functionality
 * This tests the specific query that was failing
 */

require_once 'config/database.php';

echo "<h1>Reports Fix Test</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $isPostgreSQL = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql';
    echo "<p>Database type: " . ($isPostgreSQL ? 'PostgreSQL' : 'MySQL') . "</p>";
    
    // Test the specific query that was failing
    echo "<h2>📊 Testing Reports Queries</h2>";
    
    $testQueries = [
        "SELECT COUNT(*) as total FROM attendance" => "Basic attendance count",
        $isPostgreSQL ? 
            "SELECT attendance_date, COUNT(*) as count FROM attendance WHERE attendance_date >= CURRENT_DATE - INTERVAL '7 days' GROUP BY attendance_date ORDER BY attendance_date DESC" :
            "SELECT DATE(date) as attendance_date, COUNT(*) as count FROM attendance WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(date) ORDER BY attendance_date DESC" => "Recent attendance (7 days)",
        "SELECT full_name, club_position, COUNT(*) as attendance_count FROM attendance GROUP BY full_name, club_position ORDER BY attendance_count DESC LIMIT 10" => "Top attending members (the failing query)"
    ];
    
    foreach ($testQueries as $query => $description) {
        try {
            echo "<h3>$description</h3>";
            echo "<p><strong>Query:</strong> <code>" . htmlspecialchars($query) . "</code></p>";
            
            $stmt = $db->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p style='color: green;'>✅ Query successful: " . count($results) . " results</p>";
            
            if (!empty($results)) {
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr>";
                foreach (array_keys($results[0]) as $column) {
                    echo "<th>" . htmlspecialchars($column) . "</th>";
                }
                echo "</tr>";
                
                foreach (array_slice($results, 0, 5) as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
                
                if (count($results) > 5) {
                    echo "<p><em>Showing first 5 of " . count($results) . " results</em></p>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Query failed: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test attendance table structure
    echo "<h2>🏗️ Attendance Table Structure</h2>";
    
    try {
        $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'attendance' ORDER BY column_name");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Column Name</th><th>Data Type</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['column_name']) . "</td>";
            echo "<td>" . htmlspecialchars($column['data_type']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check for required columns
        $requiredColumns = ['full_name', 'club_position', 'attendance_date'];
        $missingColumns = [];
        
        $existingColumns = array_column($columns, 'column_name');
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $existingColumns)) {
                $missingColumns[] = $col;
            }
        }
        
        if (empty($missingColumns)) {
            echo "<p style='color: green;'>✅ All required columns exist</p>";
        } else {
            echo "<p style='color: red;'>❌ Missing columns: " . implode(', ', $missingColumns) . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Column check failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>🎯 Test Summary</h2>";
    
    if ($isPostgreSQL) {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3 style='color: green;'>✅ PostgreSQL Ready!</h3>";
        echo "<p>Your reports should now work correctly with PostgreSQL on Render.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3 style='color: orange;'>⚠️ MySQL Detected</h3>";
        echo "<p>You're currently using MySQL. The fixes will work when you deploy to PostgreSQL on Render.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Test Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</div>";
?>
