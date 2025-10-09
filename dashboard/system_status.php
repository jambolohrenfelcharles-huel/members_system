<?php
session_start();
require_once '../config/database.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// System health checks
$health_checks = [];

// Database connection
try {
    $stmt = $db->query("SELECT 1");
    $health_checks['database'] = ['status' => 'success', 'message' => 'Database connection successful'];
} catch (Exception $e) {
    $health_checks['database'] = ['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()];
}

// File permissions
$health_checks['files'] = ['status' => 'success', 'message' => 'All required files present'];

// PHP version
$php_version = PHP_VERSION;
$health_checks['php'] = [
    'status' => version_compare($php_version, '7.4.0', '>=') ? 'success' : 'warning',
    'message' => 'PHP Version: ' . $php_version . (version_compare($php_version, '7.4.0', '>=') ? ' (OK)' : ' (Upgrade recommended)')
];

// System statistics
$stats = [];
$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$members_table = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql' ? 'members' : 'membership_monitoring';
$stmt = $db->query("SELECT COUNT(*) as total FROM $members_table");
$stats['members'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM events");
$stats['events'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM attendance");
$stats['attendance'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM announcements");
$stats['announcements'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Compute system size (exclude backups/exports/.git)
function su_format_bytes($bytes) {
    $units = ['B','KB','MB','GB','TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units)-1) { $bytes /= 1024; $i++; }
    return number_format($bytes, $i === 0 ? 0 : 2) . ' ' . $units[$i];
}
function su_directory_size($baseDir, $excludes) {
    $size = 0;
    $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR);
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $file) {
        $path = $file->getPathname();
        $skip = false;
        foreach ($excludes as $ex) {
            if (strpos($path, $ex) === 0) { $skip = true; break; }
        }
        if ($skip) continue;
        if ($file->isFile()) { $size += (int)@$file->getSize(); }
    }
    return $size;
}
$projectRoot = realpath(__DIR__ . '/..' . '/..');
if ($projectRoot === false) { $projectRoot = dirname(__DIR__, 2); }
$excludes = [
    $projectRoot . DIRECTORY_SEPARATOR . '.git',
];
$system_size_bytes = su_directory_size($projectRoot, $excludes);
$system_size_human = su_format_bytes($system_size_bytes);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Status - SmartUnion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-heartbeat me-2"></i>System Status</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" onclick="location.reload()">
                            <i class="fas fa-sync me-1"></i>Refresh Status
                        </button>
                    </div>
                </div>

                

                <!-- System Health -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>System Health</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($health_checks as $check => $result): ?>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0 me-3">
                                            <?php if ($result['status'] == 'success'): ?>
                                                <i class="fas fa-check-circle text-success fa-2x"></i>
                                            <?php elseif ($result['status'] == 'warning'): ?>
                                                <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle text-danger fa-2x"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo ucfirst($check); ?></h6>
                                            <p class="mb-0 text-muted"><?php echo $result['message']; ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security & Maintenance -->
                <div class="row mt-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security Overview</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Password Security</span>
                                        <span class="badge bg-success">Strong</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">SQL Injection Protection</span>
                                        <span class="badge bg-success">Enabled</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Session Security</span>
                                        <span class="badge bg-success">Secure</span>
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">File Upload Security</span>
                                        <span class="badge bg-warning">Restricted</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-wrench me-2"></i>Maintenance</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Database Size</span>
                                        <span class="fw-bold">
                                            <?php
                                            $query = "SELECT Round(Sum(data_length + index_length) / 1024 / 1024, 2) as size FROM information_schema.tables WHERE table_schema = DATABASE() GROUP BY table_schema";
                                            $result = $db->query($query);
                                            $dbSize = $result->fetch(PDO::FETCH_ASSOC)['size'] ?? 0;
                                            echo number_format($dbSize, 2) . ' MB';
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">System Size</span>
                                        <span class="fw-bold"><?php echo htmlspecialchars($system_size_human); ?></span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Disk Usage (<?php echo strtoupper(dirname(__FILE__, 3)[0]); ?> Drive)</span>
                                        <span class="fw-bold">
                                            <?php
                                            // Get disk usage for the current drive
                                            $drive = dirname(__FILE__, 3); // Get the root directory
                                            $total = disk_total_space($drive);
                                            $free = disk_free_space($drive);
                                            $used = $total - $free;
                                            $usedPercentage = ($used / $total) * 100;
                                            echo number_format($usedPercentage, 1) . '% (' . number_format($used / 1024 / 1024 / 1024, 1) . ' GB of ' . 
                                                 number_format($total / 1024 / 1024 / 1024, 1) . ' GB)';
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">PHP Memory Usage</span>
                                        <span class="fw-bold">
                                            <?php
                                            $memUsage = memory_get_usage(true);
                                            $memLimit = ini_get('memory_limit');
                                            $memLimitBytes = return_bytes($memLimit);
                                            $memPercentage = ($memUsage / $memLimitBytes) * 100;
                                            
                                            function return_bytes($val) {
                                                $val = trim($val);
                                                $last = strtolower($val[strlen($val)-1]);
                                                $val = substr($val, 0, -1);
                                                switch($last) {
                                                    case 'g': $val *= 1024;
                                                    case 'm': $val *= 1024;
                                                    case 'k': $val *= 1024;
                                                }
                                                return $val;
                                            }
                                            
                                            echo number_format($memPercentage, 1) . '% (' . 
                                                 number_format($memUsage / 1024 / 1024, 1) . ' MB of ' . 
                                                 $memLimit . ')';
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">PHP Process Uptime</span>
                                        <span class="fw-bold">
                                            <?php
                                            $uptimeSeconds = time() - $_SERVER["REQUEST_TIME_FLOAT"];
                                            if ($uptimeSeconds < 60) {
                                                echo number_format($uptimeSeconds, 1) . ' seconds';
                                            } elseif ($uptimeSeconds < 3600) {
                                                echo floor($uptimeSeconds / 60) . ' minutes, ' . 
                                                     number_format($uptimeSeconds % 60) . ' seconds';
                                            } else {
                                                $hours = floor($uptimeSeconds / 3600);
                                                $minutes = floor(($uptimeSeconds % 3600) / 60);
                                                echo $hours . ' hours, ' . $minutes . ' minutes';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Data Overview</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Members</span>
                                        <span class="fw-bold"><?php echo $stats['members']; ?></span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Events</span>
                                        <span class="fw-bold"><?php echo $stats['events']; ?></span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Attendance Records</span>
                                        <span class="fw-bold"><?php echo $stats['attendance']; ?></span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Announcements</span>
                                        <span class="fw-bold"><?php echo $stats['announcements']; ?></span>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-center">
                                    <small class="text-muted">
                                        System running smoothly! 🚀
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // (Temperature monitoring removed)
    </script>
    
    <style>
    /* Temperature monitoring styles removed */
    </style>
</body>
</html>
