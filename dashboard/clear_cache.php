<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo 'Forbidden';
    exit();
}

// Recursively delete contents of a directory but keep the directory itself
function purgeDir(string $dir): void {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    if ($items === false) return;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            purgeDir($path);
            @rmdir($path);
        } else {
            @unlink($path);
        }
    }
}

$projectRoot = realpath(__DIR__ . '/..' . '/..');
if ($projectRoot === false) { $projectRoot = dirname(__DIR__, 2); }

// Candidate cache/temp directories to purge if present
$targets = [
    $projectRoot . DIRECTORY_SEPARATOR . 'dashboard' . DIRECTORY_SEPARATOR . 'cache',
    $projectRoot . DIRECTORY_SEPARATOR . 'dashboard' . DIRECTORY_SEPARATOR . 'tmp',
    $projectRoot . DIRECTORY_SEPARATOR . 'dashboard' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'cache',
    $projectRoot . DIRECTORY_SEPARATOR . 'dashboard' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tmp',
    $projectRoot . DIRECTORY_SEPARATOR . 'dashboard' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'cache',
];

foreach ($targets as $dir) {
    if (is_dir($dir)) {
        purgeDir($dir);
    }
}

// Reset PHP opcode cache if enabled
if (function_exists('opcache_reset')) {
    @opcache_reset();
}

header('Location: index.php?cache_cleared=1');
exit();
?>
