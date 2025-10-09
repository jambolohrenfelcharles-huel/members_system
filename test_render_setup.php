<?php
/**
 * Test script to verify Render deployment setup
 * Run this locally to check if everything is configured correctly
 */

echo "<h1>SmartApp Render Setup Test</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

// Test 1: Check if required files exist
echo "<h2>📁 File Structure Test</h2>";

$requiredFiles = [
    'render.yaml' => 'Render configuration',
    'dockerfile' => 'Docker configuration',
    'start.sh' => 'Startup script',
    'render_deploy.php' => 'Deployment script',
    'config/database.php' => 'Database configuration',
    'db/members_system_postgresql.sql' => 'PostgreSQL schema',
    '.htaccess' => 'Apache configuration'
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $description ($file)</p>";
    } else {
        echo "<p style='color: red;'>❌ Missing: $description ($file)</p>";
    }
}

// Test 2: Check render.yaml syntax
echo "<h2>⚙️ Configuration Test</h2>";

if (file_exists('render.yaml')) {
    $yaml = file_get_contents('render.yaml');
    
    // Check for required services
    if (strpos($yaml, 'type: web') !== false) {
        echo "<p style='color: green;'>✅ Web service configured</p>";
    } else {
        echo "<p style='color: red;'>❌ Web service not found</p>";
    }
    
    if (strpos($yaml, 'type: pserv') !== false) {
        echo "<p style='color: green;'>✅ PostgreSQL service configured</p>";
    } else {
        echo "<p style='color: red;'>❌ PostgreSQL service not found</p>";
    }
    
    if (strpos($yaml, 'DB_TYPE') !== false) {
        echo "<p style='color: green;'>✅ Database type configured</p>";
    } else {
        echo "<p style='color: red;'>❌ Database type not configured</p>";
    }
    
    if (strpos($yaml, 'fromDatabase') !== false) {
        echo "<p style='color: green;'>✅ Database auto-linking configured</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Database auto-linking not configured (manual setup required)</p>";
    }
} else {
    echo "<p style='color: red;'>❌ render.yaml not found</p>";
}

// Test 3: Check Docker configuration
echo "<h2>🐳 Docker Configuration Test</h2>";

if (file_exists('dockerfile')) {
    $dockerfile = file_get_contents('dockerfile');
    
    if (strpos($dockerfile, 'php:8.2-apache') !== false) {
        echo "<p style='color: green;'>✅ PHP 8.2 with Apache</p>";
    } else {
        echo "<p style='color: red;'>❌ PHP version not specified</p>";
    }
    
    if (strpos($dockerfile, 'pdo_pgsql') !== false) {
        echo "<p style='color: green;'>✅ PostgreSQL extension included</p>";
    } else {
        echo "<p style='color: red;'>❌ PostgreSQL extension missing</p>";
    }
    
    if (strpos($dockerfile, 'start.sh') !== false) {
        echo "<p style='color: green;'>✅ Startup script configured</p>";
    } else {
        echo "<p style='color: red;'>❌ Startup script not configured</p>";
    }
} else {
    echo "<p style='color: red;'>❌ dockerfile not found</p>";
}

// Test 4: Check database configuration
echo "<h2>🗄️ Database Configuration Test</h2>";

if (file_exists('config/database.php')) {
    $dbConfig = file_get_contents('config/database.php');
    
    if (strpos($dbConfig, 'DB_TYPE') !== false) {
        echo "<p style='color: green;'>✅ Environment variable support</p>";
    } else {
        echo "<p style='color: red;'>❌ Environment variable support missing</p>";
    }
    
    if (strpos($dbConfig, 'postgresql') !== false) {
        echo "<p style='color: green;'>✅ PostgreSQL support</p>";
    } else {
        echo "<p style='color: red;'>❌ PostgreSQL support missing</p>";
    }
    
    if (strpos($dbConfig, 'parse_url') !== false) {
        echo "<p style='color: green;'>✅ Database URL parsing</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Database URL parsing not implemented</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Database configuration not found</p>";
}

// Test 5: Check PostgreSQL schema
echo "<h2>🐘 PostgreSQL Schema Test</h2>";

if (file_exists('db/members_system_postgresql.sql')) {
    $schema = file_get_contents('db/members_system_postgresql.sql');
    
    $requiredTables = ['users', 'events', 'attendance', 'members', 'news_feed'];
    foreach ($requiredTables as $table) {
        if (strpos($schema, "CREATE TABLE.*$table") !== false || strpos($schema, "TABLE.*$table") !== false) {
            echo "<p style='color: green;'>✅ Table '$table' defined</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' missing</p>";
        }
    }
    
    if (strpos($schema, 'event_id') !== false) {
        echo "<p style='color: green;'>✅ event_id column in attendance table</p>";
    } else {
        echo "<p style='color: red;'>❌ event_id column missing from attendance table</p>";
    }
} else {
    echo "<p style='color: red;'>❌ PostgreSQL schema not found</p>";
}

// Test 6: Deployment readiness
echo "<h2>🚀 Deployment Readiness</h2>";

$criticalIssues = 0;
$warnings = 0;

// Check for critical issues
if (!file_exists('render.yaml')) $criticalIssues++;
if (!file_exists('dockerfile')) $criticalIssues++;
if (!file_exists('start.sh')) $criticalIssues++;
if (!file_exists('config/database.php')) $criticalIssues++;

// Check for warnings
if (!file_exists('.htaccess')) $warnings++;
if (!file_exists('render_deploy.php')) $warnings++;

if ($criticalIssues === 0) {
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: green;'>🎉 Ready for Render Deployment!</h3>";
    echo "<p>Your SmartApp is configured correctly for Render deployment.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Push your code to GitHub</li>";
    echo "<li>Go to <a href='https://dashboard.render.com' target='_blank'>Render Dashboard</a></li>";
    echo "<li>Create a new Blueprint and connect your repository</li>";
    echo "<li>Set environment variables (POSTGRES_PASSWORD, SMTP credentials)</li>";
    echo "<li>Deploy!</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #ffeaea; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: red;'>❌ Critical Issues Found</h3>";
    echo "<p>Please fix the critical issues before deploying to Render.</p>";
    echo "</div>";
}

if ($warnings > 0) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: orange;'>⚠️ Warnings</h3>";
    echo "<p>There are $warnings warning(s) that should be addressed for optimal deployment.</p>";
    echo "</div>";
}

echo "</div>";
?>
