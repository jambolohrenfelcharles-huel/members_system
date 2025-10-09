<?php
/**
 * Fast QR Code Download Endpoint
 * Optimized server-side QR code download for Render deployment
 */

// Enable output buffering for faster response
ob_start();

try {
    // Get event ID from request
    $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
    
    if ($eventId <= 0) {
        throw new Exception('Invalid event ID');
    }
    
    // Connect to database
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Get event details
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        throw new Exception('Event not found');
    }
    
    // Generate QR code payload
    $qrPayload = [
        'type' => 'attendance',
        'event_id' => $eventId,
        'event_name' => $event['title'],
        'ts' => time()
    ];
    $qrText = json_encode($qrPayload);
    
    // Check for cached QR code first
    $cacheDir = __DIR__ . '/uploads/qr_cache/';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . 'event_' . $eventId . '_qr.png';
    $cacheKey = md5($qrText);
    $cacheFileWithKey = $cacheDir . 'event_' . $eventId . '_' . $cacheKey . '_qr.png';
    
    // Check if cached file exists and is recent (within 1 hour)
    if (file_exists($cacheFileWithKey) && (time() - filemtime($cacheFileWithKey)) < 3600) {
        // Serve cached file
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="event_' . $eventId . '_qr.png"');
        header('Content-Length: ' . filesize($cacheFileWithKey));
        header('Cache-Control: public, max-age=3600');
        header('ETag: "' . $cacheKey . '"');
        
        // Clear output buffer and send cached file
        ob_end_clean();
        readfile($cacheFileWithKey);
        exit;
    }
    
    // Generate QR code URL using QR Server API (smaller size for faster download)
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=' . urlencode($qrText);
    
    // Set response headers for download
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="event_' . $eventId . '_qr.png"');
    header('Cache-Control: public, max-age=3600');
    header('ETag: "' . $cacheKey . '"');
    
    // Fetch QR code image from API with optimized settings
    $context = stream_context_create([
        'http' => [
            'timeout' => 5, // Reduced timeout for faster response
            'user_agent' => 'SmartApp QR Downloader',
            'method' => 'GET',
            'header' => [
                'Accept: image/png',
                'Connection: close'
            ]
        ]
    ]);
    
    $qrImage = file_get_contents($qrUrl, false, $context);
    
    if ($qrImage === false) {
        throw new Exception('Failed to generate QR code');
    }
    
    // Cache the QR code for future requests
    file_put_contents($cacheFileWithKey, $qrImage);
    
    // Set content length
    header('Content-Length: ' . strlen($qrImage));
    
    // Clear output buffer and send image
    ob_end_clean();
    echo $qrImage;
    
} catch (Exception $e) {
    // Reset headers for error response
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(400);
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
