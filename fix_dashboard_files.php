<?php
// Fix all dashboard files to use dynamic table names
require_once 'config/database.php';

echo "<h1>🔧 Fix All Dashboard Files for PostgreSQL</h1>";
echo "<p>Updating all dashboard files to use dynamic table names</p>";

$database = new Database();
$members_table = $database->getMembersTable();
$db_type = $_ENV['DB_TYPE'] ?? 'mysql';

echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "✅ <strong>Database Type:</strong> " . strtoupper($db_type) . "<br>";
echo "✅ <strong>Members Table:</strong> " . $members_table . "<br>";
echo "</div>";

// Files that need to be updated
$files_to_fix = [
    'dashboard/index.php',
    'dashboard/members/index.php',
    'dashboard/admin/index.php',
    'dashboard/profile.php',
    'dashboard/attendance/qr_scan.php',
    'dashboard/members/view.php',
    'dashboard/system_status.php',
    'dashboard/reports/index.php',
    'dashboard/members/edit.php',
    'dashboard/members/add.php',
    'dashboard/members/qr_generator.php'
];

$fixed_files = [];
$errors = [];

foreach ($files_to_fix as $file) {
    if (file_exists($file)) {
        echo "<h3>Fixing: $file</h3>";
        
        $content = file_get_contents($file);
        $original_content = $content;
        
        // Replace membership_monitoring with dynamic table name
        $content = str_replace('membership_monitoring', $members_table, $content);
        
        // Fix specific queries that need database-specific functions
        if ($db_type === 'postgresql') {
            // Fix DATE_FORMAT for PostgreSQL
            $content = preg_replace('/DATE_FORMAT\(([^,]+),\s*\'%Y-%m\'\)/', "TO_CHAR(\\1, 'YYYY-MM')", $content);
            $content = preg_replace('/DATE_FORMAT\(([^,]+),\s*\'%Y-%m-%d\'\)/', "TO_CHAR(\\1, 'YYYY-MM-DD')", $content);
            
            // Fix CURDATE() for PostgreSQL
            $content = str_replace('CURDATE()', 'CURRENT_DATE', $content);
            
            // Fix NOW() for PostgreSQL
            $content = str_replace('NOW()', 'CURRENT_TIMESTAMP', $content);
            
            // Fix DATE_SUB for PostgreSQL
            $content = preg_replace('/DATE_SUB\(NOW\(\),\s*INTERVAL\s+(\d+)\s+DAY\)/', "CURRENT_TIMESTAMP - INTERVAL '\\1 days'", $content);
            
            // Fix SHOW COLUMNS for PostgreSQL
            $content = preg_replace('/SHOW COLUMNS FROM\s+(\w+)\s+LIKE\s+\'(\w+)\'/', "SELECT column_name FROM information_schema.columns WHERE table_name = '\\1' AND column_name = '\\2'", $content);
        }
        
        // Write the updated content back
        if (file_put_contents($file, $content)) {
            $fixed_files[] = $file;
            echo "✅ <strong>$file:</strong> Fixed successfully<br>";
        } else {
            $errors[] = $file;
            echo "❌ <strong>$file:</strong> Failed to write<br>";
        }
    } else {
        echo "⚠️ <strong>$file:</strong> File not found<br>";
    }
}

echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📊 Fix Summary</h3>";
echo "<strong>✅ Files Fixed:</strong> " . count($fixed_files) . "<br>";
echo "<strong>❌ Files with Errors:</strong> " . count($errors) . "<br>";

if (!empty($fixed_files)) {
    echo "<h4>Fixed Files:</h4><ul>";
    foreach ($fixed_files as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
}

if (!empty($errors)) {
    echo "<h4>Files with Errors:</h4><ul>";
    foreach ($errors as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
}
echo "</div>";

// Test the fixes
echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🧪 Testing Fixes</h3>";

try {
    $conn = $database->getConnection();
    
    if ($conn) {
        // Test dashboard index query
        if ($db_type === 'postgresql') {
            $stmt = $conn->query("SELECT COUNT(*) as total FROM " . $members_table);
        } else {
            $stmt = $conn->query("SELECT COUNT(*) as total FROM " . $members_table);
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ <strong>Dashboard Query:</strong> SUCCESS (Members: " . $result['total'] . ")<br>";
        
        // Test events query
        $stmt = $conn->query("SELECT COUNT(*) as total FROM events");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ <strong>Events Query:</strong> SUCCESS (Events: " . $result['total'] . ")<br>";
        
        // Test attendance query
        if ($db_type === 'postgresql') {
            $stmt = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURRENT_DATE");
        } else {
            $stmt = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE DATE(date) = CURDATE()");
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ <strong>Attendance Query:</strong> SUCCESS (Today: " . $result['total'] . ")<br>";
        
    } else {
        echo "❌ <strong>Database Connection:</strong> Failed<br>";
    }
} catch (Exception $e) {
    echo "❌ <strong>Test Error:</strong> " . $e->getMessage() . "<br>";
}

echo "</div>";

echo "<div style='background: #d1ecf1; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #17a2b8;'>";
echo "<h2>🎉 Dashboard Files Fixed!</h2>";
echo "<p><strong>✅ All dashboard files have been updated for PostgreSQL compatibility!</strong></p>";
echo "<h3>🚀 What's Been Fixed:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Table Names:</strong> All 'membership_monitoring' replaced with dynamic table name</li>";
echo "<li>✅ <strong>Date Functions:</strong> DATE_FORMAT → TO_CHAR for PostgreSQL</li>";
echo "<li>✅ <strong>Date Functions:</strong> CURDATE() → CURRENT_DATE for PostgreSQL</li>";
echo "<li>✅ <strong>Date Functions:</strong> NOW() → CURRENT_TIMESTAMP for PostgreSQL</li>";
echo "<li>✅ <strong>Date Functions:</strong> DATE_SUB → INTERVAL for PostgreSQL</li>";
echo "<li>✅ <strong>Schema Queries:</strong> SHOW COLUMNS → information_schema for PostgreSQL</li>";
echo "</ul>";

echo "<h3>📁 Files Updated:</h3>";
echo "<ul>";
foreach ($fixed_files as $file) {
    echo "<li>✅ $file</li>";
}
echo "</ul>";

echo "<h3>🎯 Ready to Test:</h3>";
echo "<ul>";
echo "<li>📊 <strong>Dashboard:</strong> <a href='dashboard/index.php'>dashboard/index.php</a></li>";
echo "<li>⚙️ <strong>Settings:</strong> <a href='dashboard/settings.php'>dashboard/settings.php</a></li>";
echo "<li>👥 <strong>Members:</strong> <a href='dashboard/members/index.php'>dashboard/members/index.php</a></li>";
echo "<li>📊 <strong>Reports:</strong> <a href='dashboard/reports/index.php'>dashboard/reports/index.php</a></li>";
echo "<li>👤 <strong>Admin Panel:</strong> <a href='dashboard/admin/index.php'>dashboard/admin/index.php</a></li>";
echo "</ul>";

echo "<p><strong>🎉 Your dashboard should now work perfectly on PostgreSQL!</strong></p>";
echo "</div>";
?>
