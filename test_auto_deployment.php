<?php
/**
 * Test script for auto-deployment setup
 * Run this to verify all auto-deployment components are working
 */

echo "<h1>SmartApp Auto-Deployment Test</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

// Test 1: Check auto-deployment files
echo "<h2>📁 Auto-Deployment Files Test</h2>";

$autoDeployFiles = [
    '.github/workflows/deploy.yml' => 'GitHub Actions workflow',
    'health.php' => 'Health check endpoint',
    'webhook_deploy.php' => 'Webhook deployment endpoint',
    'AUTO_DEPLOYMENT_SETUP.md' => 'Auto-deployment guide'
];

foreach ($autoDeployFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $description ($file)</p>";
    } else {
        echo "<p style='color: red;'>❌ Missing: $description ($file)</p>";
    }
}

// Test 2: Check render.yaml configuration
echo "<h2>⚙️ Render Configuration Test</h2>";

if (file_exists('render.yaml')) {
    $yaml = file_get_contents('render.yaml');
    
    if (strpos($yaml, 'autoDeploy: true') !== false) {
        echo "<p style='color: green;'>✅ Auto-deploy enabled</p>";
    } else {
        echo "<p style='color: red;'>❌ Auto-deploy not enabled</p>";
    }
    
    if (strpos($yaml, 'healthCheckPath:') !== false) {
        echo "<p style='color: green;'>✅ Health check path configured</p>";
    } else {
        echo "<p style='color: red;'>❌ Health check path not configured</p>";
    }
    
    if (strpos($yaml, 'DEPLOYMENT_ENV') !== false) {
        echo "<p style='color: green;'>✅ Deployment environment configured</p>";
    } else {
        echo "<p style='color: red;'>❌ Deployment environment not configured</p>";
    }
} else {
    echo "<p style='color: red;'>❌ render.yaml not found</p>";
}

// Test 3: Check GitHub Actions workflow
echo "<h2>🚀 GitHub Actions Test</h2>";

if (file_exists('.github/workflows/deploy.yml')) {
    $workflow = file_get_contents('.github/workflows/deploy.yml');
    
    if (strpos($workflow, 'on:') !== false) {
        echo "<p style='color: green;'>✅ Workflow triggers configured</p>";
    } else {
        echo "<p style='color: red;'>❌ Workflow triggers not configured</p>";
    }
    
    if (strpos($workflow, 'render-deploy-action') !== false) {
        echo "<p style='color: green;'>✅ Render deploy action configured</p>";
    } else {
        echo "<p style='color: red;'>❌ Render deploy action not configured</p>";
    }
    
    if (strpos($workflow, 'RENDER_SERVICE_ID') !== false) {
        echo "<p style='color: green;'>✅ Service ID secret configured</p>";
    } else {
        echo "<p style='color: red;'>❌ Service ID secret not configured</p>";
    }
    
    if (strpos($workflow, 'RENDER_API_KEY') !== false) {
        echo "<p style='color: green;'>✅ API key secret configured</p>";
    } else {
        echo "<p style='color: red;'>❌ API key secret not configured</p>";
    }
} else {
    echo "<p style='color: red;'>❌ GitHub Actions workflow not found</p>";
}

// Test 4: Check health endpoint
echo "<h2>🏥 Health Check Test</h2>";

if (file_exists('health.php')) {
    $health = file_get_contents('health.php');
    
    if (strpos($health, 'Content-Type: application/json') !== false) {
        echo "<p style='color: green;'>✅ JSON response format</p>";
    } else {
        echo "<p style='color: red;'>❌ JSON response format not configured</p>";
    }
    
    if (strpos($health, 'database') !== false) {
        echo "<p style='color: green;'>✅ Database health check</p>";
    } else {
        echo "<p style='color: red;'>❌ Database health check missing</p>";
    }
    
    if (strpos($health, 'file_system') !== false) {
        echo "<p style='color: green;'>✅ File system health check</p>";
    } else {
        echo "<p style='color: red;'>❌ File system health check missing</p>";
    }
    
    if (strpos($health, 'php_extensions') !== false) {
        echo "<p style='color: green;'>✅ PHP extensions health check</p>";
    } else {
        echo "<p style='color: red;'>❌ PHP extensions health check missing</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Health check endpoint not found</p>";
}

// Test 5: Check webhook endpoint
echo "<h2>🔗 Webhook Test</h2>";

if (file_exists('webhook_deploy.php')) {
    $webhook = file_get_contents('webhook_deploy.php');
    
    if (strpos($webhook, 'POST') !== false) {
        echo "<p style='color: green;'>✅ POST method check</p>";
    } else {
        echo "<p style='color: red;'>❌ POST method check missing</p>";
    }
    
    if (strpos($webhook, 'signature') !== false) {
        echo "<p style='color: green;'>✅ Signature verification</p>";
    } else {
        echo "<p style='color: red;'>❌ Signature verification missing</p>";
    }
    
    if (strpos($webhook, 'push') !== false) {
        echo "<p style='color: green;'>✅ Push event handling</p>";
    } else {
        echo "<p style='color: red;'>❌ Push event handling missing</p>";
    }
    
    if (strpos($webhook, 'main') !== false || strpos($webhook, 'master') !== false) {
        echo "<p style='color: green;'>✅ Branch filtering</p>";
    } else {
        echo "<p style='color: red;'>❌ Branch filtering missing</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Webhook endpoint not found</p>";
}

// Test 6: Environment readiness
echo "<h2>🌍 Environment Test</h2>";

$requiredEnvVars = ['DB_TYPE', 'DB_HOST', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD'];
$missingEnvVars = [];

foreach ($requiredEnvVars as $envVar) {
    if (!isset($_ENV[$envVar]) || empty($_ENV[$envVar])) {
        $missingEnvVars[] = $envVar;
    }
}

if (empty($missingEnvVars)) {
    echo "<p style='color: green;'>✅ All required environment variables set</p>";
} else {
    echo "<p style='color: red;'>❌ Missing environment variables: " . implode(', ', $missingEnvVars) . "</p>";
}

// Test 7: Database connectivity
echo "<h2>🗄️ Database Test</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<p style='color: green;'>✅ Database connection successful</p>";
        
        // Test health endpoint functionality
        $stmt = $db->query("SELECT 1 as test");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['test'] == 1) {
            echo "<p style='color: green;'>✅ Database query test successful</p>";
        } else {
            echo "<p style='color: red;'>❌ Database query test failed</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 8: Auto-deployment readiness
echo "<h2>🎯 Auto-Deployment Readiness</h2>";

$criticalIssues = 0;
$warnings = 0;

// Check for critical issues
if (!file_exists('.github/workflows/deploy.yml')) $criticalIssues++;
if (!file_exists('health.php')) $criticalIssues++;
if (!file_exists('webhook_deploy.php')) $criticalIssues++;

// Check for warnings
if (!file_exists('AUTO_DEPLOYMENT_SETUP.md')) $warnings++;

if ($criticalIssues === 0) {
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: green;'>🎉 Auto-Deployment Ready!</h3>";
    echo "<p>Your SmartApp is configured for auto-deployment.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Push your code to GitHub</li>";
    echo "<li>Set up Render service with auto-deploy enabled</li>";
    echo "<li>Configure GitHub secrets (RENDER_API_KEY, RENDER_SERVICE_ID)</li>";
    echo "<li>Set up webhook in GitHub repository</li>";
    echo "<li>Test deployment by pushing to main/master branch</li>";
    echo "</ol>";
    echo "<p><strong>Health Check URL:</strong> <code>https://your-app.onrender.com/health.php</code></p>";
    echo "<p><strong>Webhook URL:</strong> <code>https://your-app.onrender.com/webhook_deploy.php</code></p>";
    echo "</div>";
} else {
    echo "<div style='background: #ffeaea; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: red;'>❌ Critical Issues Found</h3>";
    echo "<p>Please fix the critical issues before setting up auto-deployment.</p>";
    echo "</div>";
}

if ($warnings > 0) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: orange;'>⚠️ Warnings</h3>";
    echo "<p>There are $warnings warning(s) that should be addressed for optimal auto-deployment.</p>";
    echo "</div>";
}

echo "</div>";
?>
