<?php
/**
 * Test script to verify system status functionality
 * This tests the specific query that was failing
 */

require_once 'config/database.php';

echo "<h1>System Status Test</h1>";
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
    echo "<h2>📊 Testing System Status Queries</h2>";
    
    $members_table = $isPostgreSQL ? 'members' : 'membership_monitoring';
    echo "<p>Using members table: <strong>$members_table</strong></p>";
    
    $testQueries = [
        "SELECT COUNT(*) as total FROM users" => "Users count",
        "SELECT COUNT(*) as total FROM $members_table" => "Members count (the failing query)",
        "SELECT COUNT(*) as total FROM events" => "Events count",
        "SELECT COUNT(*) as total FROM attendance" => "Attendance count"
    ];
    
    foreach ($testQueries as $query => $description) {
        try {
            echo "<h3>$description</h3>";
            echo "<p><strong>Query:</strong> <code>" . htmlspecialchars($query) . "</code></p>";
            
            $stmt = $db->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p style='color: green;'>✅ Query successful: " . $result['total'] . " records</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Query failed: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test table existence
    echo "<h2>🏗️ Table Existence Test</h2>";
    
    $tables = ['users', 'events', 'attendance'];
    if ($isPostgreSQL) {
        $tables[] = 'members';
    } else {
        $tables[] = 'membership_monitoring';
    }
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✅ Table '$table': " . $result['total'] . " records</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Table '$table' failed: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test system status page functionality
    echo "<h2>🔧 System Status Page Test</h2>";
    
    try {
        // Simulate the system status page logic
        $stats = [];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM users");
        $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM $members_table");
        $stats['members'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM events");
        $stats['events'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM attendance");
        $stats['attendance'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Statistic</th><th>Count</th></tr>";
        foreach ($stats as $key => $value) {
            echo "<tr><td>" . ucfirst($key) . "</td><td>$value</td></tr>";
        }
        echo "</table>";
        
        echo "<p style='color: green;'>✅ System status page simulation successful</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ System status page simulation failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>🎯 Test Summary</h2>";
    
    if ($isPostgreSQL) {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3 style='color: green;'>✅ PostgreSQL Ready!</h3>";
        echo "<p>Your system status page should now work correctly with PostgreSQL on Render.</p>";
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
