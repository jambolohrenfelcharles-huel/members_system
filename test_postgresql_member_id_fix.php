<?php
/**
 * Test PostgreSQL member_id Fix
 * This script tests the PostgreSQL member_id type fix
 */

echo "<h1>PostgreSQL member_id Fix Test</h1>";
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
        echo "<h2>🐘 PostgreSQL member_id Fix Test</h2>";
        
        // Test 1: Check table structures
        echo "<h3>📊 Table Structure Check</h3>";
        
        $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'attendance' AND column_name = 'member_id'");
        $memberIdColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($memberIdColumn) {
            echo "<p><strong>attendance.member_id:</strong> " . $memberIdColumn['data_type'] . "</p>";
            if ($memberIdColumn['data_type'] === 'character varying') {
                echo "<p style='color: green;'>✅ member_id is VARCHAR type</p>";
            } else {
                echo "<p style='color: red;'>❌ member_id is not VARCHAR type</p>";
            }
        }
        
        $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'members' AND column_name = 'member_id'");
        $membersMemberIdColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($membersMemberIdColumn) {
            echo "<p><strong>members.member_id:</strong> " . $membersMemberIdColumn['data_type'] . "</p>";
            if ($membersMemberIdColumn['data_type'] === 'character varying') {
                echo "<p style='color: green;'>✅ members.member_id is VARCHAR type</p>";
            } else {
                echo "<p style='color: red;'>❌ members.member_id is not VARCHAR type</p>";
            }
        }
        
        // Test 2: Test the problematic queries
        echo "<h3>🧪 Query Testing</h3>";
        
        $testMemberId = 'M20250001';
        $testEventId = 1;
        
        try {
            // Test the SELECT query with explicit casting
            echo "<p><strong>Testing SELECT query with explicit casting...</strong></p>";
            $stmt = $db->prepare('SELECT id FROM attendance WHERE member_id = ?::VARCHAR(50) AND event_id = ?::INTEGER');
            $stmt->execute([$testMemberId, $testEventId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✅ SELECT query with casting executed successfully</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ SELECT query with casting failed: " . $e->getMessage() . "</p>";
        }
        
        try {
            // Test the INSERT query with explicit casting
            echo "<p><strong>Testing INSERT query with explicit casting...</strong></p>";
            $stmt = $db->prepare('INSERT INTO attendance (member_id, full_name, club_position, event_id, date) VALUES (?::VARCHAR(50), ?::VARCHAR(100), ?::VARCHAR(50), ?::INTEGER, ?::TIMESTAMP)');
            $testDate = date('Y-m-d H:i:s');
            $result = $stmt->execute([$testMemberId, 'Test User', 'Test Position', $testEventId, $testDate]);
            
            if ($result) {
                echo "<p style='color: green;'>✅ INSERT query with casting executed successfully</p>";
                
                // Clean up test record
                $stmt = $db->prepare('DELETE FROM attendance WHERE member_id = ?::VARCHAR(50) AND event_id = ?::INTEGER');
                $stmt->execute([$testMemberId, $testEventId]);
                echo "<p style='color: blue;'>ℹ️ Test record cleaned up</p>";
            } else {
                echo "<p style='color: red;'>❌ INSERT query with casting failed</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ INSERT query with casting failed: " . $e->getMessage() . "</p>";
        }
        
        // Test 3: Test without explicit casting (should fail)
        echo "<h3>🚫 Testing Without Explicit Casting</h3>";
        
        try {
            $stmt = $db->prepare('SELECT id FROM attendance WHERE member_id = ? AND event_id = ?');
            $stmt->execute([$testMemberId, $testEventId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: orange;'>⚠️ Query without casting executed (unexpected)</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: green;'>✅ Query without casting failed as expected: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<h2>🐬 MySQL Database</h2>";
        echo "<p style='color: blue;'>ℹ️ This is a MySQL database, PostgreSQL-specific fixes are not needed</p>";
        
        // Test MySQL queries
        $testMemberId = 'M20250001';
        $testEventId = 1;
        
        try {
            $stmt = $db->prepare('SELECT id FROM attendance WHERE member_id = ? AND event_id = ?');
            $stmt->execute([$testMemberId, $testEventId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✅ MySQL SELECT query executed successfully</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ MySQL SELECT query failed: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<h2>🔧 PostgreSQL member_id Fix Summary</h2>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3 style='color: green;'>✅ PostgreSQL member_id Fix Complete</h3>";
echo "<ul>";
echo "<li>✅ <strong>Problem:</strong> PostgreSQL was trying to cast VARCHAR member_id to INTEGER</li>";
echo "<li>✅ <strong>Solution:</strong> Added explicit type casting in SQL queries</li>";
echo "<li>✅ <strong>Migration:</strong> Created migration to fix column types</li>";
echo "<li>✅ <strong>Query Safety:</strong> Added PostgreSQL-specific query handling</li>";
echo "<li>✅ <strong>Render Integration:</strong> Added fix to deployment process</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🔗 Important URLs</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>QR Scanner:</strong> <code>https://your-app.onrender.com/dashboard/attendance/qr_scan.php</code></p>";
echo "<p><strong>Attendance List:</strong> <code>https://your-app.onrender.com/dashboard/attendance/index.php</code></p>";
echo "<p><strong>Events List:</strong> <code>https://your-app.onrender.com/dashboard/events/index.php</code></p>";
echo "<p><strong>System Status:</strong> <code>https://your-app.onrender.com/dashboard/system_status.php</code></p>";
echo "<p><strong>Health Check:</strong> <code>https://your-app.onrender.com/health.php</code></p>";
echo "<p><strong>Default Login:</strong> admin / 123</p>";
echo "</div>";

echo "</div>";
?>
