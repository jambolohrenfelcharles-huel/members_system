<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "Checking image paths in database:\n";
$stmt = $conn->prepare('SELECT id, name, image_path FROM membership_monitoring WHERE image_path IS NOT NULL AND image_path != "" ORDER BY id DESC LIMIT 5');
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($results as $row) {
    echo "ID: {$row['id']}, Name: {$row['name']}, Image: {$row['image_path']}\n";
    
    // Check if file exists
    $fullPath = realpath(__DIR__ . '/uploads/members/' . $row['image_path']);
    echo "  Full path: " . ($fullPath ?: 'Not found') . "\n";
    echo "  Exists: " . ($fullPath && file_exists($fullPath) ? 'Yes' : 'No') . "\n\n";
}
?>
