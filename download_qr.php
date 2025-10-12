<?php
/**
 * Render-Optimized QR Code Download Endpoint
 * Specifically designed to work reliably on Render deployment
 */

// Disable any output buffering that might interfere
if (ob_get_level()) {
    ob_end_clean();
}

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    
    // Simple and reliable QR code generation
    $qrImage = null;
    
    // Primary QR code API - QR Server (most reliable)
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrText) . '&format=png&ecc=M';
    
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'SmartApp-QR-Downloader/1.0',
                'method' => 'GET',
                'ignore_errors' => true
            ]
        ]);
        
        $qrImage = file_get_contents($qrUrl, false, $context);
        
        if ($qrImage === false || strlen($qrImage) < 100) {
            // Fallback to Google Charts
            $fallbackUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($qrText) . '&choe=UTF-8';
            $qrImage = @file_get_contents($fallbackUrl);
        }
        
    } catch (Exception $e) {
        error_log("QR Code generation error: " . $e->getMessage());
        
        // Final fallback
        $fallbackUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($qrText) . '&choe=UTF-8';
        $qrImage = @file_get_contents($fallbackUrl);
    }
    
    if ($qrImage === false || strlen($qrImage) < 100) {
        throw new Exception('Failed to generate QR code from all available APIs');
    }
    
    // Set simple headers for reliable download
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="event_' . $eventId . '_qr_code.png"');
    header('Content-Length: ' . strlen($qrImage));
    header('Cache-Control: no-cache');
    header('Pragma: no-cache');
    
    // Output the generated QR code image
    echo $qrImage;
    
    // Log successful download of generated QR code
    error_log("Generated QR Code download successful for event $eventId, size: " . strlen($qrImage) . " bytes, payload: " . $qrText);
    
} catch (Exception $e) {
    // Clear any previous headers
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(400);
    }
    
    // Log the error
    error_log("QR Code download error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'event_id' => $eventId ?? 0
    ]);
}
?>

