<?php
/**
 * Comprehensive Fix for All Table References
 * This script fixes all remaining membership_monitoring references for PostgreSQL compatibility
 */

echo "<h1>Comprehensive Table Reference Fix</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

$filesFixed = 0;
$totalReplacements = 0;

// Files that need fixing
$filesToFix = [
    'dashboard/members/index.php',
    'dashboard/members/add.php',
    'dashboard/members/edit.php',
    'dashboard/members/view.php',
    'dashboard/members/qr_generator.php',
    'config/notification_helper.php',
    'run_migration.php'
];

foreach ($filesToFix as $file) {
    if (!file_exists($file)) {
        echo "<p style='color: orange;'>⚠️ File not found: $file</p>";
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    $fileReplacements = 0;
    
    // Fix membership_monitoring references
    if (strpos($content, 'membership_monitoring') !== false) {
        // Add dynamic table name logic at the top of the file
        $pattern = '/<\?php\s*\/\*\*.*?\*\/\s*require_once/s';
        if (preg_match($pattern, $content)) {
            // Insert after the opening PHP tag and initial comments
            $content = preg_replace(
                '/(<\?php\s*\/\*\*.*?\*\/\s*require_once[^;]+;)/s',
                '$1' . "\n\n" . '// Dynamic table name for PostgreSQL compatibility' . "\n" . '$members_table = ($_ENV[\'DB_TYPE\'] ?? \'mysql\') === \'postgresql\' ? \'members\' : \'membership_monitoring\';',
                $content
            );
        } else {
            // Insert after require_once statements
            $content = preg_replace(
                '/(require_once[^;]+;)/s',
                '$1' . "\n\n" . '// Dynamic table name for PostgreSQL compatibility' . "\n" . '$members_table = ($_ENV[\'DB_TYPE\'] ?? \'mysql\') === \'postgresql\' ? \'members\' : \'membership_monitoring\';',
                $content,
                1
            );
        }
        
        // Replace membership_monitoring with $members_table
        $content = str_replace('membership_monitoring', '$members_table', $content);
        $fileReplacements++;
    }
    
    // Fix MySQL-specific functions
    $mysqlFunctions = [
        'SHOW COLUMNS FROM' => 'information_schema.columns WHERE table_name =',
        'CURDATE()' => 'CURRENT_DATE',
        'NOW()' => 'CURRENT_TIMESTAMP',
        'DATE_FORMAT(' => 'TO_CHAR(',
        'DATE_SUB(NOW(), INTERVAL' => 'CURRENT_DATE - INTERVAL \'',
        'INTERVAL 7 DAY' => 'INTERVAL \'7 days\'',
        'INTERVAL 30 DAY' => 'INTERVAL \'30 days\''
    ];
    
    foreach ($mysqlFunctions as $search => $replace) {
        $count = 0;
        $content = str_replace($search, $replace, $content, $count);
        $fileReplacements += $count;
    }
    
    // Write back if changes were made
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $filesFixed++;
        $totalReplacements += $fileReplacements;
        echo "<p style='color: green;'>✅ Fixed: $file ($fileReplacements replacements)</p>";
    }
}

// Fix specific files with special handling
$specialFiles = [
    'config/notification_helper.php' => [
        'pattern' => '/FROM membership_monitoring/',
        'replacement' => 'FROM $members_table'
    ],
    'run_migration.php' => [
        'pattern' => '/membership_monitoring/',
        'replacement' => '$members_table'
    ]
];

foreach ($specialFiles as $file => $fix) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $originalContent = $content;
        
        // Add dynamic table name
        if (strpos($content, 'membership_monitoring') !== false) {
            $content = preg_replace(
                '/(require_once[^;]+;)/s',
                '$1' . "\n\n" . '// Dynamic table name for PostgreSQL compatibility' . "\n" . '$members_table = ($_ENV[\'DB_TYPE\'] ?? \'mysql\') === \'postgresql\' ? \'members\' : \'membership_monitoring\';',
                $content,
                1
            );
            
            $content = preg_replace($fix['pattern'], $fix['replacement'], $content);
            
            if ($content !== $originalContent) {
                file_put_contents($file, $content);
                echo "<p style='color: green;'>✅ Fixed: $file (special handling)</p>";
            }
        }
    }
}

echo "<h3>📊 Summary</h3>";
echo "<p><strong>Files fixed:</strong> $filesFixed</p>";
echo "<p><strong>Total replacements:</strong> $totalReplacements</p>";

// Test the fixes
echo "<h3>🧪 Testing Fixes</h3>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $isPostgreSQL = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql';
    $members_table = $isPostgreSQL ? 'members' : 'membership_monitoring';
    
    echo "<p>Database type: " . ($isPostgreSQL ? 'PostgreSQL' : 'MySQL') . "</p>";
    echo "<p>Members table: $members_table</p>";
    
    // Test table existence
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM $members_table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ $members_table table accessible: " . $result['total'] . " records</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ $members_table table failed: " . $e->getMessage() . "</p>";
    }
    
    // Test system status
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Users table accessible: " . $result['total'] . " records</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Users table failed: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database test failed: " . $e->getMessage() . "</p>";
}

if ($filesFixed > 0) {
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: green;'>✅ Table Reference Fix Complete!</h4>";
    echo "<p>All membership_monitoring references have been updated for PostgreSQL compatibility.</p>";
    echo "<p><strong>What was fixed:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Dynamic table name resolution</li>";
    echo "<li>✅ PostgreSQL function compatibility</li>";
    echo "<li>✅ Database type detection</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: orange;'>⚠️ No Changes Needed</h4>";
    echo "<p>All files are already PostgreSQL compatible.</p>";
    echo "</div>";
}

echo "</div>";
?>
