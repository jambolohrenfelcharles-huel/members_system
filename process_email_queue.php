<?php
/**
 * Email Queue Processor
 * Background job to process email queue asynchronously
 */

// Set execution time limit for background processing
set_time_limit(300); // 5 minutes
ini_set('memory_limit', '256M');

// Disable output buffering for better performance
if (ob_get_level()) {
    ob_end_clean();
}

// Set headers to prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    require_once 'config/database.php';
    require_once 'config/async_notification_helper.php';
    
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Initialize async notification helper
    $asyncHelper = new AsyncNotificationHelper($db);
    
    // Process email queue
    $result = $asyncHelper->processEmailQueue(20); // Process up to 20 emails
    
    // Log results
    if ($result['success']) {
        error_log("Email queue processed: " . $result['processed'] . " items, " . $result['sent'] . " sent, " . $result['failed'] . " failed");
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'processed' => $result['processed'],
            'sent' => $result['sent'],
            'failed' => $result['failed'],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        error_log("Email queue processing failed: " . $result['error']);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (Exception $e) {
    error_log("Email queue processor error: " . $e->getMessage());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
