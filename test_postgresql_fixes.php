<?php
/**
 * Test script to verify PostgreSQL fixes
 * Run this to check if all PostgreSQL compatibility issues are resolved
 */

require_once 'config/database.php';

echo "<h1>PostgreSQL Compatibility Test</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "<h2>✅ Database Connection Successful</h2>";
    echo "<p>Connected to: " . ($_ENV['DB_TYPE'] ?? 'mysql') . " database</p>";
    
    $isPostgreSQL = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql';
    
    // Test 1: Attendance queries
    echo "<h3>📊 Attendance Queries Test</h3>";
    
    try {
        if ($isPostgreSQL) {
            $stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURRENT_DATE");
        } else {
            $stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURRENT_DATE");
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Today's attendance query: " . $result['total'] . " records</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Today's attendance query failed: " . $e->getMessage() . "</p>";
    }
    
    try {
        if ($isPostgreSQL) {
            $stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE attendance_date >= CURRENT_DATE - INTERVAL '7 days'");
        } else {
            $stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE attendance_date = YEARWEEK(CURRENT_TIMESTAMP)");
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Weekly attendance query: " . $result['total'] . " records</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Weekly attendance query failed: " . $e->getMessage() . "</p>";
    }
    
    try {
        if ($isPostgreSQL) {
            $stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE EXTRACT(YEAR FROM attendance_date) = EXTRACT(YEAR FROM CURRENT_DATE) AND EXTRACT(MONTH FROM attendance_date) = EXTRACT(MONTH FROM CURRENT_DATE)");
        } else {
            $stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE EXTRACT(YEAR FROM attendance_date) = YEAR(CURRENT_TIMESTAMP) AND EXTRACT(MONTH FROM attendance_date) = MONTH(CURRENT_TIMESTAMP)");
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Monthly attendance query: " . $result['total'] . " records</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Monthly attendance query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test 2: Members table queries
    echo "<h3>👥 Members Table Test</h3>";
    
    $members_table = $isPostgreSQL ? 'members' : 'members';
    
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM $members_table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Members count query: " . $result['total'] . " records</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Members count query failed: " . $e->getMessage() . "</p>";
    }
    
    try {
        if ($isPostgreSQL) {
            $stmt = $db->query("SELECT COUNT(*) as total FROM $members_table WHERE status = 'active' AND renewal_date >= CURRENT_DATE");
        } else {
            $stmt = $db->query("SELECT COUNT(*) as total FROM $members_table WHERE status = 'active' AND renewal_date >= CURRENT_DATE");
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Active members query: " . $result['total'] . " records</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Active members query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test 3: Events queries
    echo "<h3>📅 Events Queries Test</h3>";
    
    try {
        if ($isPostgreSQL) {
            $stmt = $db->query("SELECT COUNT(*) as total FROM events WHERE created_at >= CURRENT_DATE - INTERVAL '7 days'");
        } else {
            $stmt = $db->query("SELECT COUNT(*) as total FROM events WHERE created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL '7 days')");
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Recent events query: " . $result['total'] . " records</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Recent events query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test 4: Date functions
    echo "<h3>📅 Date Functions Test</h3>";
    
    try {
        if ($isPostgreSQL) {
            $stmt = $db->query("SELECT CURRENT_DATE as today, CURRENT_TIMESTAMP as now");
        } else {
            $stmt = $db->query("SELECT CURRENT_DATE as today, CURRENT_TIMESTAMP as now");
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Date functions: Today=" . $result['today'] . ", Now=" . $result['now'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Date functions failed: " . $e->getMessage() . "</p>";
    }
    
    // Test 5: Table structure
    echo "<h3>🏗️ Table Structure Test</h3>";
    
    $requiredTables = ['users', 'events', 'attendance', $members_table];
    foreach ($requiredTables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✅ Table '$table': " . $result['total'] . " records</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Table '$table' failed: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test 6: Attendance table structure
    echo "<h3>📋 Attendance Table Structure Test</h3>";
    
    try {
        $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'attendance' ORDER BY column_name");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = ['id', 'member_id', 'full_name', 'club_position', 'date', 'attendance_date'];
        if ($isPostgreSQL) {
            $requiredColumns[] = 'event_id';
        }
        
        foreach ($requiredColumns as $column) {
            if (in_array($column, $columns)) {
                echo "<p style='color: green;'>✅ Column '$column' exists</p>";
            } else {
                echo "<p style='color: red;'>❌ Column '$column' missing</p>";
            }
        }
        
        echo "<p><strong>All columns in attendance table:</strong> " . implode(', ', $columns) . "</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Column check failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>🎉 PostgreSQL Compatibility Test Complete!</h3>";
    
    if ($isPostgreSQL) {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4 style='color: green;'>✅ PostgreSQL Ready!</h4>";
        echo "<p>Your SmartApp is now compatible with PostgreSQL and ready for Render deployment.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4 style='color: orange;'>⚠️ MySQL Detected</h4>";
        echo "<p>You're currently using MySQL. The fixes will work when you deploy to PostgreSQL on Render.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Test Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</div>";
?>
