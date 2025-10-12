<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Events Table Structure</h2>";
    
    // Check if events table exists
    $result = $db->query("SHOW TABLES LIKE 'events'");
    if ($result->rowCount() > 0) {
        echo "<p>✅ Events table exists</p>";
        
        // Get table structure
        $result = $db->query("DESCRIBE events");
        echo "<h3>Table Columns:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check for name/title columns
        $result = $db->query("SHOW COLUMNS FROM events LIKE 'name'");
        $hasName = $result->rowCount() > 0;
        
        $result = $db->query("SHOW COLUMNS FROM events LIKE 'title'");
        $hasTitle = $result->rowCount() > 0;
        
        echo "<h3>Event Name/Title Columns:</h3>";
        echo "<p>name column: " . ($hasName ? "✅ Exists" : "❌ Missing") . "</p>";
        echo "<p>title column: " . ($hasTitle ? "✅ Exists" : "❌ Missing") . "</p>";
        
        if (!$hasName && !$hasTitle) {
            echo "<h3>🔧 Fix Required:</h3>";
            echo "<p>No name or title column found. Need to add one:</p>";
            echo "<pre>";
            echo "ALTER TABLE events ADD COLUMN name VARCHAR(255) NOT NULL;\n";
            echo "ALTER TABLE events ADD COLUMN title VARCHAR(255) NOT NULL;\n";
            echo "</pre>";
        } elseif ($hasTitle && !$hasName) {
            echo "<h3>🔧 Fix Required:</h3>";
            echo "<p>Table uses 'title' column but dashboard expects 'name'. Update dashboard code.</p>";
        }
        
        // Show sample data
        $result = $db->query("SELECT * FROM events LIMIT 3");
        echo "<h3>Sample Data:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        $first = true;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $key) {
                    echo "<th>$key</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p>❌ Events table does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
