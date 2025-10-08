<?php
session_start();
require_once '../config/database.php';
require_once '../config/smtp_email.php';

header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Check if this is a POST request with correct action
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'test_email') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Get user information
    $stmt = $db->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Use fixed test email address
    $user_email = 'charlesjambo3@gmail.com';
    
    // Create test email
    $subject = "SmartUnion - Notification Test Email";
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Notification Test</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
            .success { color: #28a745; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔔 SmartUnion Notification Test</h1>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($user['username']) . "!</h2>
                <p>This is a test email to verify your notification settings are working correctly.</p>
                
                <div class='success'>
                    ✅ If you can see this email, your notification system is working properly!
                </div>
                
                <h3>Test Details:</h3>
                <ul>
                    <li><strong>Sent at:</strong> " . date('F j, Y \a\t g:i A T') . "</li>
                    <li><strong>User:</strong> " . htmlspecialchars($user['username']) . "</li>
                    <li><strong>Email:</strong> " . htmlspecialchars($user_email) . "</li>
                    <li><strong>User ID:</strong> #" . $_SESSION['user_id'] . "</li>
                </ul>
                
                <p>This test confirms that:</p>
                <ul>
                    <li>Your email address is valid and reachable</li>
                    <li>The SMTP server is configured correctly</li>
                    <li>Email notifications will work for future updates</li>
                </ul>
            </div>
            <div class='footer'>
                <p>This is an automated test email from SmartUnion Membership System.</p>
                <p>You can safely ignore this email - it's just a test!</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send email using SMTP
    require_once '../config/email_config.php';
    $result = sendEmailViaSMTP(
        $user_email,
        $subject,
        $message,
        'noreply@smartunion.com',
        'SmartUnion System'
    );
    
    if ($result) {
        // Log successful test email
        error_log("Test email sent successfully to: " . $user_email);
        echo json_encode([
            'success' => true, 
            'message' => 'Test email sent successfully to charlesjambo3@gmail.com',
            'email' => $user_email
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email via SMTP. Please check your email configuration.']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in test email: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in test email: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
