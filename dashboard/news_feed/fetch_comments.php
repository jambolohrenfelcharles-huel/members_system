<?php
// AJAX endpoint to fetch comments for a news feed post
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$news_feed_id = intval($_GET['news_feed_id'] ?? 0);
if ($news_feed_id) {
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT c.id, c.comment, c.created_at, c.parent_id, c.user_id, u.username FROM news_feed_comments c JOIN users u ON c.user_id = u.id WHERE c.news_feed_id = ? ORDER BY c.created_at ASC");
    $stmt->execute([$news_feed_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($comments as &$comment) {
        $comment['is_owner'] = ($comment['user_id'] == $user_id);
        unset($comment['user_id']);
    }
    echo json_encode($comments);
} else {
    echo json_encode([]);
}
