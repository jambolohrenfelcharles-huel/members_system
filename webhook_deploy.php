<?php
/**
 * Webhook endpoint for triggering deployments
 * This can be used with GitHub webhooks or other CI/CD systems
 */

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Verify webhook signature (optional but recommended)
$webhookSecret = $_ENV['WEBHOOK_SECRET'] ?? '';
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if ($webhookSecret && $signature) {
    $payload = file_get_contents('php://input');
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $webhookSecret);
    
    if (!hash_equals($expectedSignature, $signature)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
}

// Parse webhook payload
$payload = json_decode(file_get_contents('php://input'), true);
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';

$response = [
    'status' => 'received',
    'timestamp' => date('Y-m-d H:i:s'),
    'event' => $event,
    'ref' => $payload['ref'] ?? null,
    'repository' => $payload['repository']['full_name'] ?? null
];

// Only trigger deployment for main/master branch pushes
$ref = $payload['ref'] ?? '';
$isMainBranch = in_array($ref, ['refs/heads/main', 'refs/heads/master']);

if ($event === 'push' && $isMainBranch) {
    $response['deployment'] = 'triggered';
    $response['branch'] = str_replace('refs/heads/', '', $ref);
    
    // Log deployment trigger
    error_log("Deployment triggered for branch: " . $response['branch']);
    
    // You can add additional deployment logic here
    // For example, trigger a deployment API call to Render
    
} else {
    $response['deployment'] = 'skipped';
    $response['reason'] = $event !== 'push' ? 'Not a push event' : 'Not main/master branch';
}

// Set appropriate HTTP status
http_response_code(200);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);

// Log the webhook for debugging
error_log("Webhook received: " . json_encode($response));
?>
