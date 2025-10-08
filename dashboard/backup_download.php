<?php
// backup_download.php
// Creates a ZIP of the entire Smart folder and downloads it

$rootDir = realpath(__DIR__ . '/../'); // c:/xampp/htdocs/Smart
$zipName = 'smart_union_backup.zip';
$zipPath = $rootDir . '/backups/' . $zipName;

function zipFolder($source, $zipFile) {
    if (!extension_loaded('zip') || !file_exists($source)) return false;
    $zip = new ZipArchive();
    if (!$zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) return false;
    $source = realpath($source);
    $baseFolder = basename($source);
    if (is_dir($source)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $baseFolder . '/' . substr($filePath, strlen($source) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    } else if (is_file($source)) {
        $zip->addFile($source, $baseFolder . '/' . basename($source));
    }
    return $zip->close();
}

// Create the ZIP
if (!zipFolder($rootDir, $zipPath)) {
    http_response_code(500);
    echo 'Failed to create backup.';
    exit;
}

// Download the ZIP
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);
exit;
