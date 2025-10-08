<?php
// AJAX endpoint to delete a comment or reply
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
    $comment_id = intval($_POST['comment_id'] ?? 0);
    $user_id = intval($_SESSION['user_id']);
    if ($comment_id && $user_id) {
        // Only allow delete if user owns the comment
        $stmt = $db->prepare('SELECT user_id FROM news_feed_comments WHERE id = ?');
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($comment && $comment['user_id'] == $user_id) {
            // Delete all child replies first
            $stmt = $db->prepare('DELETE FROM news_feed_comments WHERE parent_id = ?');
            $stmt->execute([$comment_id]);
            // Delete the comment itself
            $stmt = $db->prepare('DELETE FROM news_feed_comments WHERE id = ?');
            $stmt->execute([$comment_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Not allowed']);
        }
    } else {
        echo json_encode(['error' => 'Missing data']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request']);
}
