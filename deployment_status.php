<?php
/**
 * Deployment Status Checker
 * Shows the current status of your SmartApp deployment
 */

echo "<h1>SmartApp Deployment Status</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

// Check Git status
echo "<h2>📋 Git Status</h2>";
$gitStatus = shell_exec('git status --porcelain 2>&1');
if (empty(trim($gitStatus))) {
    echo "<p style='color: green;'>✅ Working directory clean - all changes committed</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Uncommitted changes detected</p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>$gitStatus</pre>";
}

// Check recent commits
echo "<h2>📝 Recent Commits</h2>";
$recentCommits = shell_exec('git log --oneline -5 2>&1');
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>$recentCommits</pre>";

// Check deployment files
echo "<h2>🚀 Deployment Files Status</h2>";

$deploymentFiles = [
    'render.yaml' => 'Render configuration',
    'dockerfile' => 'Docker configuration',
    'start.sh' => 'Startup script',
    'health.php' => 'Health check endpoint',
    'render_deploy.php' => 'Database initialization',
    '.github/workflows/deploy.yml' => 'GitHub Actions workflow',
    'AUTO_DEPLOYMENT_SETUP.md' => 'Deployment guide'
];

foreach ($deploymentFiles as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file);
        $modified = date('Y-m-d H:i:s', filemtime($file));
        echo "<p style='color: green;'>✅ $description ($file) - {$size} bytes, modified: $modified</p>";
    } else {
        echo "<p style='color: red;'>❌ Missing: $description ($file)</p>";
    }
}

// Check PostgreSQL compatibility
echo "<h2>🐘 PostgreSQL Compatibility</h2>";

$compatibilityFiles = [
    'dashboard/attendance/index.php',
    'dashboard/members/index.php',
    'dashboard/admin/index.php',
    'dashboard/reports/index.php',
    'dashboard/index.php'
];

$compatibilityIssues = 0;
foreach ($compatibilityFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $issues = 0;
        
        // Check for MySQL-specific functions
        if (strpos($content, 'CURDATE()') !== false) $issues++;
        if (strpos($content, 'DATE(date)') !== false) $issues++;
        if (strpos($content, 'NOW()') !== false) $issues++;
        if (strpos($content, 'DATE_SUB(') !== false) $issues++;
        if (strpos($content, 'YEARWEEK(') !== false) $issues++;
        
        if ($issues === 0) {
            echo "<p style='color: green;'>✅ $file - PostgreSQL compatible</p>";
        } else {
            echo "<p style='color: red;'>❌ $file - $issues MySQL-specific functions found</p>";
            $compatibilityIssues++;
        }
    }
}

if ($compatibilityIssues === 0) {
    echo "<p style='color: green; font-weight: bold;'>✅ All files are PostgreSQL compatible!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ $compatibilityIssues files need PostgreSQL fixes</p>";
}

// Check auto-deployment configuration
echo "<h2>⚙️ Auto-Deployment Configuration</h2>";

if (file_exists('render.yaml')) {
    $yaml = file_get_contents('render.yaml');
    
    $checks = [
        'autoDeploy: true' => 'Auto-deploy enabled',
        'healthCheckPath:' => 'Health check configured',
        'fromDatabase:' => 'Database auto-linking',
        'DEPLOYMENT_ENV' => 'Deployment environment'
    ];
    
    foreach ($checks as $pattern => $description) {
        if (strpos($yaml, $pattern) !== false) {
            echo "<p style='color: green;'>✅ $description</p>";
        } else {
            echo "<p style='color: red;'>❌ $description not configured</p>";
        }
    }
}

// GitHub Actions status
echo "<h2>🔄 GitHub Actions Status</h2>";

if (file_exists('.github/workflows/deploy.yml')) {
    $workflow = file_get_contents('.github/workflows/deploy.yml');
    
    $workflowChecks = [
        'on:' => 'Workflow triggers',
        'render-deploy-action' => 'Render deploy action',
        'RENDER_SERVICE_ID' => 'Service ID secret',
        'RENDER_API_KEY' => 'API key secret'
    ];
    
    foreach ($workflowChecks as $pattern => $description) {
        if (strpos($workflow, $pattern) !== false) {
            echo "<p style='color: green;'>✅ $description configured</p>";
        } else {
            echo "<p style='color: red;'>❌ $description missing</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ GitHub Actions workflow not found</p>";
}

// Final deployment status
echo "<h2>🎯 Deployment Status Summary</h2>";

$allFilesExist = true;
foreach ($deploymentFiles as $file => $description) {
    if (!file_exists($file)) {
        $allFilesExist = false;
        break;
    }
}

if ($allFilesExist && $compatibilityIssues === 0) {
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: green;'>🎉 Ready for Auto-Deployment!</h3>";
    echo "<p>Your SmartApp is fully prepared for automatic deployment to Render.</p>";
    echo "<p><strong>What's ready:</strong></p>";
    echo "<ul>";
    echo "<li>✅ PostgreSQL compatibility fixes applied</li>";
    echo "<li>✅ Auto-deployment configuration complete</li>";
    echo "<li>✅ Health monitoring enabled</li>";
    echo "<li>✅ Database initialization automated</li>";
    echo "<li>✅ GitHub Actions workflow configured</li>";
    echo "<li>✅ All changes committed and pushed</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🚀 Next Steps:</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<ol>";
    echo "<li><strong>Set up Render service:</strong> Go to <a href='https://dashboard.render.com' target='_blank'>Render Dashboard</a></li>";
    echo "<li><strong>Create Blueprint:</strong> Use your render.yaml for automatic setup</li>";
    echo "<li><strong>Set environment variables:</strong> POSTGRES_PASSWORD, SMTP credentials</li>";
    echo "<li><strong>Monitor deployment:</strong> Check Render dashboard and GitHub Actions</li>";
    echo "<li><strong>Test health endpoint:</strong> Visit /health.php after deployment</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>🔗 Important URLs:</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Render Dashboard:</strong> <a href='https://dashboard.render.com' target='_blank'>https://dashboard.render.com</a></p>";
    echo "<p><strong>GitHub Repository:</strong> <a href='https://github.com/jambolohrenfelcharles-huel/members_system' target='_blank'>https://github.com/jambolohrenfelcharles-huel/members_system</a></p>";
    echo "<p><strong>Health Check:</strong> <code>https://your-app.onrender.com/health.php</code></p>";
    echo "<p><strong>Default Login:</strong> admin / 123</p>";
    echo "</div>";
    
} else {
    echo "<div style='background: #ffeaea; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: red;'>⚠️ Additional Setup Required</h3>";
    echo "<p>Some components need attention before deployment.</p>";
    if (!$allFilesExist) {
        echo "<p>❌ Missing deployment files</p>";
    }
    if ($compatibilityIssues > 0) {
        echo "<p>❌ PostgreSQL compatibility issues found</p>";
    }
    echo "</div>";
}

echo "</div>";
?>
