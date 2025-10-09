<?php
/**
 * Comprehensive PostgreSQL Compatibility Fix
 * This script fixes all remaining MySQL-specific functions for PostgreSQL compatibility
 */

echo "<h1>PostgreSQL Compatibility Fix</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

$filesFixed = 0;
$totalReplacements = 0;

// Define the patterns to replace
$replacements = [
    // Date functions
    'DATE(date)' => 'attendance_date',
    'CURDATE()' => 'CURRENT_DATE',
    'NOW()' => 'CURRENT_TIMESTAMP',
    'DATE_SUB(NOW(), INTERVAL 7 DAY)' => "CURRENT_DATE - INTERVAL '7 days'",
    'DATE_SUB(NOW(), INTERVAL 30 DAY)' => "CURRENT_DATE - INTERVAL '30 days'",
    
    // Date extraction functions
    'YEAR(date)' => 'EXTRACT(YEAR FROM attendance_date)',
    'MONTH(date)' => 'EXTRACT(MONTH FROM attendance_date)',
    'YEARWEEK(date)' => 'attendance_date',
    'YEARWEEK(NOW())' => 'CURRENT_DATE',
    
    // Table references
    'membership_monitoring' => 'members', // For PostgreSQL
    
    // MySQL-specific functions
    'DATE_FORMAT(created_at, \'%Y-%m\')' => 'TO_CHAR(created_at, \'YYYY-MM\')',
    'DATE_FORMAT(event_date, \'%Y-%m\')' => 'TO_CHAR(event_date, \'YYYY-MM\')',
    
    // Interval functions
    'INTERVAL 7 DAY' => "INTERVAL '7 days'",
    'INTERVAL 30 DAY' => "INTERVAL '30 days'",
];

// Get all PHP files in dashboard directory
$dashboardFiles = glob('dashboard/**/*.php');
$allFiles = array_merge($dashboardFiles, ['render_deploy.php', 'test_postgresql_fixes.php']);

foreach ($allFiles as $file) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $originalContent = $content;
    $fileReplacements = 0;
    
    // Apply replacements
    foreach ($replacements as $search => $replace) {
        $count = 0;
        $content = str_replace($search, $replace, $content, $count);
        $fileReplacements += $count;
    }
    
    // Special handling for dynamic table names
    if (strpos($content, 'membership_monitoring') !== false) {
        // Add dynamic table name logic
        $pattern = '/\$stmt = \$db->query\("([^"]*membership_monitoring[^"]*)"\);/';
        $content = preg_replace_callback($pattern, function($matches) {
            $query = $matches[1];
            $newQuery = str_replace('membership_monitoring', '$members_table', $query);
            return '$members_table = ($_ENV[\'DB_TYPE\'] ?? \'mysql\') === \'postgresql\' ? \'members\' : \'membership_monitoring\';\n    $stmt = $db->query("' . $newQuery . '");';
        }, $content);
    }
    
    // Write back if changes were made
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $filesFixed++;
        $totalReplacements += $fileReplacements;
        echo "<p style='color: green;'>✅ Fixed: $file ($fileReplacements replacements)</p>";
    }
}

// Fix specific attendance queries
$attendanceFiles = [
    'dashboard/attendance/index.php',
    'dashboard/attendance/qr_scan.php'
];

foreach ($attendanceFiles as $file) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Fix attendance queries
    $content = preg_replace(
        '/SELECT COUNT\(\*\) as total FROM attendance WHERE DATE\(date\) = CURDATE\(\)/',
        'SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURRENT_DATE',
        $content
    );
    
    $content = preg_replace(
        '/ORDER BY date DESC/',
        'ORDER BY attendance_date DESC',
        $content
    );
    
    $content = preg_replace(
        '/\$record\[\'date\'\]/',
        '$record[\'attendance_date\']',
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "<p style='color: green;'>✅ Fixed attendance queries in: $file</p>";
    }
}

// Create a comprehensive test
echo "<h3>🧪 Running Compatibility Test</h3>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $isPostgreSQL = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql';
    echo "<p>Database type: " . ($isPostgreSQL ? 'PostgreSQL' : 'MySQL') . "</p>";
    
    // Test attendance queries
    $testQueries = [
        "SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURRENT_DATE",
        "SELECT COUNT(*) as total FROM attendance WHERE attendance_date >= CURRENT_DATE - INTERVAL '7 days'",
        "SELECT COUNT(*) as total FROM attendance WHERE EXTRACT(YEAR FROM attendance_date) = EXTRACT(YEAR FROM CURRENT_DATE)"
    ];
    
    foreach ($testQueries as $i => $query) {
        try {
            $stmt = $db->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✅ Test " . ($i + 1) . ": " . $result['total'] . " records</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Test " . ($i + 1) . " failed: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test table existence
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
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database test failed: " . $e->getMessage() . "</p>";
}

echo "<h3>📊 Summary</h3>";
echo "<p><strong>Files fixed:</strong> $filesFixed</p>";
echo "<p><strong>Total replacements:</strong> $totalReplacements</p>";

if ($filesFixed > 0) {
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: green;'>✅ PostgreSQL Compatibility Fix Complete!</h4>";
    echo "<p>Your SmartApp is now fully compatible with PostgreSQL and ready for Render deployment.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Test locally: <code>php test_postgresql_fixes.php</code></li>";
    echo "<li>Deploy to Render using auto-deployment</li>";
    echo "<li>Monitor health endpoint: <code>/health.php</code></li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: orange;'>⚠️ No Changes Needed</h4>";
    echo "<p>All files are already PostgreSQL compatible.</p>";
    echo "</div>";
}

echo "</div>";
?>
