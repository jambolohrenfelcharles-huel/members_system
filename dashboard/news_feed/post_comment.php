<?php
// AJAX endpoint for posting a comment
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $news_feed_id = intval($_POST['news_feed_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $user_id = intval($_SESSION['user_id']);
    if ($news_feed_id && $comment) {
        $stmt = $db->prepare("INSERT INTO news_feed_comments (news_feed_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$news_feed_id, $user_id, $comment]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Missing data']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request']);
}
