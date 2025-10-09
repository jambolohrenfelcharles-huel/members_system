<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "<h2>Attendance Table Structure</h2>";
    
    // Check if attendance table exists
    $db_type = $_ENV['DB_TYPE'] ?? 'mysql';
    if ($db_type === 'postgresql') {
        $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'attendance')");
    } else {
        $stmt = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = 'attendance'");
    }
    $tableExists = $stmt->fetchColumn();
    
    if (!$tableExists) {
        echo "<p style='color: red;'>❌ Attendance table does not exist</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Attendance table exists</p>";
    
    // Get all columns
    $stmt = $db->query("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name = 'attendance' ORDER BY ordinal_position");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Columns:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column Name</th><th>Data Type</th><th>Nullable</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['column_name'] . "</td>";
        echo "<td>" . $column['data_type'] . "</td>";
        echo "<td>" . $column['is_nullable'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if full_name column exists
    $fullNameExists = false;
    foreach ($columns as $column) {
        if ($column['column_name'] === 'full_name') {
            $fullNameExists = true;
            break;
        }
    }
    
    if ($fullNameExists) {
        echo "<p style='color: green;'>✅ full_name column exists</p>";
    } else {
        echo "<p style='color: red;'>❌ full_name column does not exist</p>";
        
        // Check what columns are available for member info
        $memberColumns = ['member_id', 'name', 'full_name', 'student_name', 'student_id'];
        echo "<h3>Available member-related columns:</h3>";
        foreach ($memberColumns as $col) {
            $exists = false;
            foreach ($columns as $column) {
                if ($column['column_name'] === $col) {
                    $exists = true;
                    break;
                }
            }
            if ($exists) {
                echo "<p style='color: green;'>✅ $col</p>";
            } else {
                echo "<p style='color: red;'>❌ $col</p>";
            }
        }
    }
    
    // Test the problematic query
    echo "<h3>Testing Queries:</h3>";
    
    $testQueries = [
        "SELECT COUNT(*) as total FROM attendance",
        "SELECT member_id, COUNT(*) as count FROM attendance GROUP BY member_id LIMIT 5"
    ];
    
    foreach ($testQueries as $i => $query) {
        try {
            $stmt = $db->query($query);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✅ Query " . ($i + 1) . ": " . count($result) . " results</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Query " . ($i + 1) . " failed: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
