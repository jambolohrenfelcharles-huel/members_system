<?php
/**
 * Health Check Endpoint for Render Auto-Deployment
 * This endpoint is used by Render to monitor application health
 */

header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '1.0.0',
    'environment' => $_ENV['DEPLOYMENT_ENV'] ?? 'development',
    'checks' => []
];

try {
    // Check database connection
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        $health['checks']['database'] = [
            'status' => 'healthy',
            'message' => 'Database connection successful'
        ];
        
        // Test a simple query
        $stmt = $db->query("SELECT 1 as test");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['test'] == 1) {
            $health['checks']['database_query'] = [
                'status' => 'healthy',
                'message' => 'Database query test successful'
            ];
        } else {
            $health['checks']['database_query'] = [
                'status' => 'unhealthy',
                'message' => 'Database query test failed'
            ];
            $health['status'] = 'unhealthy';
        }
        
        // Check if required tables exist
        $requiredTables = ['users', 'events', 'attendance', 'members'];
        $existingTables = [];
        
        foreach ($requiredTables as $table) {
            $stmt = $db->prepare("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = ?)");
            $stmt->execute([$table]);
            if ($stmt->fetchColumn()) {
                $existingTables[] = $table;
            }
        }
        
        $health['checks']['database_tables'] = [
            'status' => count($existingTables) === count($requiredTables) ? 'healthy' : 'unhealthy',
            'message' => 'Required tables check',
            'existing_tables' => $existingTables,
            'required_tables' => $requiredTables
        ];
        
        if (count($existingTables) !== count($requiredTables)) {
            $health['status'] = 'unhealthy';
        }
        
    } else {
        $health['checks']['database'] = [
            'status' => 'unhealthy',
            'message' => 'Database connection failed'
        ];
        $health['status'] = 'unhealthy';
    }
    
} catch (Exception $e) {
    $health['checks']['database'] = [
        'status' => 'unhealthy',
        'message' => 'Database error: ' . $e->getMessage()
    ];
    $health['status'] = 'unhealthy';
}

// Check file system permissions
$writableDirs = ['uploads', 'dashboard/uploads', 'dashboard/backups'];
$writableStatus = [];

foreach ($writableDirs as $dir) {
    if (is_dir($dir)) {
        $writableStatus[$dir] = is_writable($dir);
    }
}

$health['checks']['file_system'] = [
    'status' => array_reduce($writableStatus, function($carry, $item) { return $carry && $item; }, true) ? 'healthy' : 'unhealthy',
    'message' => 'File system permissions check',
    'writable_directories' => $writableStatus
];

if (!$health['checks']['file_system']['status'] === 'healthy') {
    $health['status'] = 'unhealthy';
}

// Check PHP extensions
$requiredExtensions = ['pdo', 'pdo_pgsql', 'gd', 'zip'];
$loadedExtensions = get_loaded_extensions();
$missingExtensions = array_diff($requiredExtensions, $loadedExtensions);

$health['checks']['php_extensions'] = [
    'status' => empty($missingExtensions) ? 'healthy' : 'unhealthy',
    'message' => 'PHP extensions check',
    'loaded_extensions' => array_intersect($requiredExtensions, $loadedExtensions),
    'missing_extensions' => $missingExtensions
];

if (!empty($missingExtensions)) {
    $health['status'] = 'unhealthy';
}

// Check environment variables
$requiredEnvVars = ['DB_TYPE', 'DB_HOST', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD'];
$missingEnvVars = [];

foreach ($requiredEnvVars as $envVar) {
    if (!isset($_ENV[$envVar]) || empty($_ENV[$envVar])) {
        $missingEnvVars[] = $envVar;
    }
}

$health['checks']['environment'] = [
    'status' => empty($missingEnvVars) ? 'healthy' : 'unhealthy',
    'message' => 'Environment variables check',
    'missing_variables' => $missingEnvVars
];

if (!empty($missingEnvVars)) {
    $health['status'] = 'unhealthy';
}

// Set HTTP status code based on health
http_response_code($health['status'] === 'healthy' ? 200 : 503);

// Add response time
$health['response_time'] = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];

echo json_encode($health, JSON_PRETTY_PRINT);
?>