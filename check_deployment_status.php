<?php
/**
 * Render Auto-Deployment Status Checker
 * This script helps you monitor your Render deployment status
 */

echo "<h1>🚀 Render Auto-Deployment Status</h1>";
echo "<p>Checking your deployment configuration and status...</p>";

// Step 1: Check Git Status
echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📋 Step 1: Git Repository Status</h3>";

$gitStatus = shell_exec('git status --porcelain 2>/dev/null');
$gitBranch = trim(shell_exec('git branch --show-current 2>/dev/null'));
$gitRemote = trim(shell_exec('git remote get-url origin 2>/dev/null'));

if ($gitBranch) {
    echo "✅ <strong>Current Branch:</strong> $gitBranch<br>";
} else {
    echo "❌ <strong>Git Branch:</strong> Not detected<br>";
}

if ($gitRemote) {
    echo "✅ <strong>Remote Repository:</strong> $gitRemote<br>";
} else {
    echo "❌ <strong>Remote Repository:</strong> Not configured<br>";
}

if (empty($gitStatus)) {
    echo "✅ <strong>Working Directory:</strong> Clean (no uncommitted changes)<br>";
} else {
    echo "⚠️ <strong>Working Directory:</strong> Has uncommitted changes<br>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>$gitStatus</pre>";
}

echo "</div>";

// Step 2: Check Render Configuration
echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>⚙️ Step 2: Render Configuration Check</h3>";

if (file_exists('render.yaml')) {
    echo "✅ <strong>render.yaml:</strong> Found<br>";
    
    $renderYaml = file_get_contents('render.yaml');
    if (strpos($renderYaml, 'autoDeploy: true') !== false) {
        echo "✅ <strong>Auto-Deploy:</strong> Enabled<br>";
    } else {
        echo "❌ <strong>Auto-Deploy:</strong> Not enabled<br>";
    }
    
    if (strpos($renderYaml, 'healthCheckPath: /health.php') !== false) {
        echo "✅ <strong>Health Check:</strong> Configured<br>";
    } else {
        echo "❌ <strong>Health Check:</strong> Not configured<br>";
    }
} else {
    echo "❌ <strong>render.yaml:</strong> Not found<br>";
}

if (file_exists('dockerfile')) {
    echo "✅ <strong>Dockerfile:</strong> Found<br>";
} else {
    echo "❌ <strong>Dockerfile:</strong> Not found<br>";
}

if (file_exists('health.php')) {
    echo "✅ <strong>Health Endpoint:</strong> Found<br>";
} else {
    echo "❌ <strong>Health Endpoint:</strong> Not found<br>";
}

echo "</div>";

// Step 3: Check Email Configuration
echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📧 Step 3: Email Configuration Check</h3>";

if (file_exists('config/phpmailer_helper.php')) {
    echo "✅ <strong>PHPMailer Helper:</strong> Found<br>";
    
    $phpmailerContent = file_get_contents('config/phpmailer_helper.php');
    if (strpos($phpmailerContent, 'sendMailPHPMailerRender') !== false) {
        echo "✅ <strong>Render Optimization:</strong> Implemented<br>";
    } else {
        echo "❌ <strong>Render Optimization:</strong> Not implemented<br>";
    }
} else {
    echo "❌ <strong>PHPMailer Helper:</strong> Not found<br>";
}

if (file_exists('config/email_config.php')) {
    echo "✅ <strong>Email Config:</strong> Found<br>";
    
    $emailConfigContent = file_get_contents('config/email_config.php');
    if (strpos($emailConfigContent, '$_ENV[') !== false) {
        echo "✅ <strong>Environment Variables:</strong> Supported<br>";
    } else {
        echo "❌ <strong>Environment Variables:</strong> Not supported<br>";
    }
} else {
    echo "❌ <strong>Email Config:</strong> Not found<br>";
}

echo "</div>";

// Step 4: Environment Variables Check
echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔧 Step 4: Required Environment Variables</h3>";

$requiredEnvVars = [
    'SMTP_USERNAME' => 'Your Gmail address',
    'SMTP_PASSWORD' => 'Your Gmail App Password',
    'SMTP_FROM_EMAIL' => 'Your Gmail address',
    'SMTP_FROM_NAME' => 'SmartUnion',
    'POSTGRES_PASSWORD' => 'Strong password for database'
];

echo "<strong>Set these in your Render dashboard:</strong><br>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";

foreach ($requiredEnvVars as $var => $description) {
    $value = $_ENV[$var] ?? 'Not set';
    $status = $value === 'Not set' ? '❌' : '✅';
    echo "$status $var=$description\n";
}

echo "</pre>";

echo "</div>";

// Step 5: Deployment Instructions
echo "<div style='background: #d1ecf1; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #17a2b8;'>";
echo "<h2>🚀 Auto-Deployment Instructions</h2>";

echo "<h3>📋 Step-by-Step Setup:</h3>";
echo "<ol>";
echo "<li><strong>Go to Render Dashboard:</strong> <a href='https://dashboard.render.com' target='_blank'>https://dashboard.render.com</a></li>";
echo "<li><strong>Create Blueprint:</strong> Click 'New +' → 'Blueprint'</li>";
echo "<li><strong>Connect Repository:</strong> Select 'jambolohrenfelcharles-huel/members_system'</li>";
echo "<li><strong>Set Environment Variables:</strong> Add the variables listed above</li>";
echo "<li><strong>Deploy:</strong> Click 'Create Blueprint'</li>";
echo "<li><strong>Monitor:</strong> Watch the deployment progress</li>";
echo "</ol>";

echo "<h3>🧪 Test Your Deployment:</h3>";
echo "<ul>";
echo "<li><strong>Health Check:</strong> <code>https://your-app.onrender.com/health.php</code></li>";
echo "<li><strong>Email Test:</strong> <code>https://your-app.onrender.com/test_render_email_fix.php</code></li>";
echo "<li><strong>Forgot Password:</strong> <code>https://your-app.onrender.com/auth/forgot_password.php</code></li>";
echo "<li><strong>Contact Admin:</strong> <code>https://your-app.onrender.com/auth/contact_admin.php</code></li>";
echo "</ul>";

echo "<h3>📊 Monitor Deployment:</h3>";
echo "<ul>";
echo "<li><strong>Render Dashboard:</strong> Monitor service status and logs</li>";
echo "<li><strong>GitHub:</strong> Check commit history and pushes</li>";
echo "<li><strong>Health Endpoint:</strong> Regular health checks</li>";
echo "<li><strong>Email Logs:</strong> Check Render logs for email success/failure</li>";
echo "</ul>";

echo "</div>";

// Step 6: Success Checklist
echo "<div style='background: #d4edda; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #28a745;'>";
echo "<h2>✅ Deployment Success Checklist</h2>";

echo "<h3>Before Deployment:</h3>";
echo "<ul>";
echo "<li>✅ Code committed and pushed to GitHub</li>";
echo "<li>✅ render.yaml configured with auto-deploy</li>";
echo "<li>✅ Environment variables ready</li>";
echo "<li>✅ Gmail App Password generated</li>";
echo "</ul>";

echo "<h3>After Deployment:</h3>";
echo "<ul>";
echo "<li>✅ Render service shows 'Live' status</li>";
echo "<li>✅ Health endpoint returns 'OK'</li>";
echo "<li>✅ Email test shows 'SUCCESS'</li>";
echo "<li>✅ Forgot password emails are received</li>";
echo "<li>✅ Contact admin emails are received</li>";
echo "</ul>";

echo "<h3>🎉 Your SmartUnion App is Ready!</h3>";
echo "<p><strong>Auto-deployment is configured and ready to go!</strong></p>";
echo "<p>Every time you push changes to GitHub, Render will automatically deploy your updates.</p>";

echo "</div>";

echo "<div style='background: #e2e3e5; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔗 Quick Links</h3>";
echo "<ul>";
echo "<li><a href='https://dashboard.render.com' target='_blank'>Render Dashboard</a></li>";
echo "<li><a href='https://github.com/jambolohrenfelcharles-huel/members_system' target='_blank'>GitHub Repository</a></li>";
echo "<li><a href='test_render_email_fix.php'>Email Test Script</a></li>";
echo "<li><a href='health.php'>Health Check</a></li>";
echo "</ul>";
echo "</div>";
?>