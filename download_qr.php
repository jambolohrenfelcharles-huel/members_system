<?php
/**
 * QR Code Download Endpoint
 * Server-side QR code download for Render deployment
 */

// Set headers for download
header('Content-Type: application/json');

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
    
    // Generate QR code URL using QR Server API
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=512x512&data=' . urlencode($qrText);
    
    // Set response headers for download
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="event_' . $eventId . '_qr.png"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Fetch QR code image from API
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'SmartApp QR Downloader'
        ]
    ]);
    
    $qrImage = file_get_contents($qrUrl, false, $context);
    
    if ($qrImage === false) {
        throw new Exception('Failed to generate QR code');
    }
    
    // Output QR code image
    echo $qrImage;
    
} catch (Exception $e) {
    // Reset headers for error response
    header('Content-Type: application/json');
    http_response_code(400);
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
