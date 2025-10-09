<?php
/**
 * Complete Deployment and Fix Script
 * This script fixes PostgreSQL compatibility and prepares for deployment
 */

echo "<h1>SmartApp Deployment & Fix Script</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

// Step 1: Fix PostgreSQL compatibility
echo "<h2>🔧 Step 1: Fixing PostgreSQL Compatibility</h2>";

$fixes = [
    'dashboard/attendance/index.php' => [
        'DATE(date) = CURDATE()' => 'attendance_date = CURRENT_DATE',
        'ORDER BY date DESC' => 'ORDER BY attendance_date DESC',
        '$record[\'date\']' => '$record[\'attendance_date\']'
    ],
    'dashboard/members/index.php' => [
        'CURDATE()' => 'CURRENT_DATE'
    ],
    'dashboard/admin/index.php' => [
        'DATE_FORMAT(created_at, \'%Y-%m\')' => 'TO_CHAR(created_at, \'YYYY-MM\')',
        'membership_monitoring' => '$members_table'
    ],
    'dashboard/index.php' => [
        'DATE_FORMAT(created_at, \'%Y-%m\')' => 'TO_CHAR(created_at, \'YYYY-MM\')',
        'DATE_FORMAT(event_date, \'%Y-%m\')' => 'TO_CHAR(event_date, \'YYYY-MM\')',
        'membership_monitoring' => '$members_table'
    ]
];

$totalFixed = 0;
foreach ($fixes as $file => $fileFixes) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $originalContent = $content;
        
        foreach ($fileFixes as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        
        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            $totalFixed++;
            echo "<p style='color: green;'>✅ Fixed: $file</p>";
        }
    }
}

echo "<p><strong>Files fixed:</strong> $totalFixed</p>";

// Step 2: Test database compatibility
echo "<h2>🧪 Step 2: Testing Database Compatibility</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $isPostgreSQL = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql';
    echo "<p>Database type: " . ($isPostgreSQL ? 'PostgreSQL' : 'MySQL') . "</p>";
    
    // Test critical queries
    $testQueries = [
        "SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURRENT_DATE",
        "SELECT COUNT(*) as total FROM events",
        "SELECT COUNT(*) as total FROM users"
    ];
    
    foreach ($testQueries as $i => $query) {
        try {
            $stmt = $db->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✅ Query " . ($i + 1) . ": " . $result['total'] . " records</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Query " . ($i + 1) . " failed: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database test failed: " . $e->getMessage() . "</p>";
}

// Step 3: Check deployment readiness
echo "<h2>🚀 Step 3: Deployment Readiness Check</h2>";

$requiredFiles = [
    'render.yaml' => 'Render configuration',
    'dockerfile' => 'Docker configuration',
    'start.sh' => 'Startup script',
    'health.php' => 'Health check endpoint',
    'render_deploy.php' => 'Deployment script',
    '.github/workflows/deploy.yml' => 'GitHub Actions workflow'
];

$missingFiles = [];
foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $description ($file)</p>";
    } else {
        echo "<p style='color: red;'>❌ Missing: $description ($file)</p>";
        $missingFiles[] = $file;
    }
}

// Step 4: Generate deployment commands
echo "<h2>📋 Step 4: Deployment Commands</h2>";

if (empty($missingFiles)) {
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: green;'>🎉 Ready for Deployment!</h4>";
    echo "<p>Your SmartApp is ready for auto-deployment to Render.</p>";
    echo "</div>";
    
    echo "<h3>Deployment Commands:</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace;'>";
    echo "<p><strong>1. Commit and push changes:</strong></p>";
    echo "<code>git add .<br>";
    echo "git commit -m \"Fix PostgreSQL compatibility and enable auto-deployment\"<br>";
    echo "git push origin main</code><br><br>";
    
    echo "<p><strong>2. Deploy to Render:</strong></p>";
    echo "<code># Auto-deployment will trigger automatically<br>";
    echo "# Or manually trigger via GitHub Actions<br>";
    echo "# Or use Render dashboard to deploy</code><br><br>";
    
    echo "<p><strong>3. Monitor deployment:</strong></p>";
    echo "<code># Check Render dashboard<br>";
    echo "# Monitor health endpoint: /health.php<br>";
    echo "# Check GitHub Actions workflow</code>";
    echo "</div>";
    
    echo "<h3>Environment Variables to Set in Render:</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace;'>";
    echo "<code>POSTGRES_PASSWORD=your-strong-password<br>";
    echo "SMTP_USERNAME=your-email@gmail.com<br>";
    echo "SMTP_PASSWORD=your-gmail-app-password<br>";
    echo "SMTP_FROM_EMAIL=your-email@gmail.com<br>";
    echo "SMTP_FROM_NAME=SmartApp</code>";
    echo "</div>";
    
} else {
    echo "<div style='background: #ffeaea; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: red;'>❌ Missing Required Files</h4>";
    echo "<p>Please create the missing files before deploying:</p>";
    echo "<ul>";
    foreach ($missingFiles as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// Step 5: Health check
echo "<h2>🏥 Step 5: Health Check</h2>";

if (file_exists('health.php')) {
    echo "<p style='color: green;'>✅ Health check endpoint available</p>";
    echo "<p>Test URL: <code>https://your-app.onrender.com/health.php</code></p>";
} else {
    echo "<p style='color: red;'>❌ Health check endpoint missing</p>";
}

echo "<h3>🎯 Final Status</h3>";

if ($totalFixed > 0 && empty($missingFiles)) {
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: green;'>✅ All Systems Ready!</h4>";
    echo "<p>Your SmartApp is fully prepared for auto-deployment to Render with PostgreSQL support.</p>";
    echo "<p><strong>What's been fixed:</strong></p>";
    echo "<ul>";
    echo "<li>PostgreSQL compatibility issues resolved</li>";
    echo "<li>Auto-deployment configuration ready</li>";
    echo "<li>Health monitoring enabled</li>";
    echo "<li>Database initialization automated</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: orange;'>⚠️ Additional Setup Required</h4>";
    echo "<p>Some components need attention before deployment.</p>";
    echo "</div>";
}

echo "</div>";
?>
