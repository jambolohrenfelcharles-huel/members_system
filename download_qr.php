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
    
    // Render-specific QR code generation - use multiple APIs with better error handling
    $qrImage = null;
    $qrApis = [
        'QR Server' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrText),
        'QuickChart' => 'https://quickchart.io/qr?text=' . urlencode($qrText) . '&size=300',
        'Google Charts' => 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($qrText),
        'QR Code API' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=png&data=' . urlencode($qrText)
    ];
    
    // Try each API with proper error handling
    foreach ($qrApis as $apiName => $apiUrl) {
        try {
            // Create context with Render-optimized settings
            $context = stream_context_create([
                'http' => [
                    'timeout' => 15, // Longer timeout for Render
                    'user_agent' => 'SmartApp-Render-QR-Downloader/1.0',
                    'method' => 'GET',
                    'header' => [
                        'Accept: image/png,image/*,*/*',
                        'Connection: keep-alive',
                        'Cache-Control: no-cache'
                    ],
                    'ignore_errors' => true // Don't fail on HTTP errors
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            $qrImage = file_get_contents($apiUrl, false, $context);
            
            if ($qrImage !== false && strlen($qrImage) > 100) { // Ensure we got actual image data
                error_log("QR Code generated successfully using $apiName API");
                break;
            } else {
                error_log("QR Code generation failed with $apiName API");
                $qrImage = null;
            }
        } catch (Exception $e) {
            error_log("QR Code API $apiName error: " . $e->getMessage());
            $qrImage = null;
        }
    }
    
    // If all APIs failed, create a simple QR code using a different approach
    if ($qrImage === null || strlen($qrImage) < 100) {
        error_log("All QR APIs failed, creating fallback QR code");
        
        // Create a simple QR code using a different method
        $fallbackUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrText) . '&format=png&ecc=M';
        $qrImage = @file_get_contents($fallbackUrl);
        
        if ($qrImage === false || strlen($qrImage) < 100) {
            // Create a minimal QR code as last resort
            $minimalUrl = 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($qrText) . '&choe=UTF-8';
            $qrImage = @file_get_contents($minimalUrl);
        }
    }
    
    if ($qrImage === false || strlen($qrImage) < 100) {
        throw new Exception('Failed to generate QR code from all available APIs');
    }
    
    // Set headers for Render-optimized download of generated QR code
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="event_' . $eventId . '_generated_qr.png"');
    header('Content-Length: ' . strlen($qrImage));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-Render-QR-Download: success');
    header('X-QR-Generated: true');
    header('X-QR-Size: ' . strlen($qrImage) . ' bytes');
    
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
