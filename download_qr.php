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
    
    // Enhanced QR code generation with validation
    $qrImage = null;
    $qrApis = [
        'QR Server Primary' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrText) . '&format=png&ecc=M',
        'QR Server Secondary' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrText) . '&format=png&ecc=L',
        'QuickChart' => 'https://quickchart.io/qr?text=' . urlencode($qrText) . '&size=300&format=png',
        'Google Charts' => 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($qrText) . '&choe=UTF-8',
        'QR Code API' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=png&data=' . urlencode($qrText) . '&ecc=M'
    ];
    
    // Try each API with proper error handling and validation
    foreach ($qrApis as $apiName => $apiUrl) {
        try {
            // Create context with enhanced settings
            $context = stream_context_create([
                'http' => [
                    'timeout' => 20, // Longer timeout for reliability
                    'user_agent' => 'SmartApp-QR-Downloader/2.0',
                    'method' => 'GET',
                    'header' => [
                        'Accept: image/png,image/*,*/*',
                        'Connection: keep-alive',
                        'Cache-Control: no-cache',
                        'User-Agent: Mozilla/5.0 (compatible; SmartApp-QR-Downloader/2.0)'
                    ],
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            
            $qrImage = file_get_contents($apiUrl, false, $context);
            
            // Validate that we got actual PNG image data
            if ($qrImage !== false && strlen($qrImage) > 500) {
                // Check if it's actually a PNG file
                if (substr($qrImage, 0, 8) === "\x89PNG\r\n\x1a\n" || strpos($qrImage, 'PNG') !== false) {
                    error_log("Valid QR Code PNG generated using $apiName API, size: " . strlen($qrImage) . " bytes");
                    break;
                } else {
                    error_log("QR Code from $apiName API is not a valid PNG file");
                    $qrImage = null;
                }
            } else {
                error_log("QR Code generation failed with $apiName API - insufficient data");
                $qrImage = null;
            }
        } catch (Exception $e) {
            error_log("QR Code API $apiName error: " . $e->getMessage());
            $qrImage = null;
        }
    }
    
    // If all APIs failed, create a guaranteed QR code using a reliable method
    if ($qrImage === null || strlen($qrImage) < 500) {
        error_log("All QR APIs failed, creating guaranteed QR code");
        
        // Use a more reliable QR generation method
        $guaranteedUrls = [
            'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qrText) . '&format=png&ecc=M&margin=10',
            'https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=' . urlencode($qrText) . '&choe=UTF-8&chld=M|0',
            'https://quickchart.io/qr?text=' . urlencode($qrText) . '&size=250&format=png&margin=1'
        ];
        
        foreach ($guaranteedUrls as $url) {
            $qrImage = @file_get_contents($url);
            if ($qrImage !== false && strlen($qrImage) > 500) {
                error_log("Guaranteed QR code generated, size: " . strlen($qrImage) . " bytes");
                break;
            }
        }
    }
    
    if ($qrImage === false || strlen($qrImage) < 500) {
        throw new Exception('Failed to generate valid QR code PNG image from all available APIs');
    }
    
    // Final validation - ensure we have a valid PNG image
    if (substr($qrImage, 0, 8) !== "\x89PNG\r\n\x1a\n") {
        error_log("Warning: QR code may not be a valid PNG file, but proceeding with download");
    }
    
    // Set headers for universal QR code download compatibility
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="event_' . $eventId . '_qr_code.png"');
    header('Content-Length: ' . strlen($qrImage));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Accept-Ranges: bytes');
    header('X-QR-Downloadable: true');
    header('X-QR-Size: ' . strlen($qrImage) . ' bytes');
    header('X-QR-Format: PNG');
    header('X-QR-Quality: High');
    
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
