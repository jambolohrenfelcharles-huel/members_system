<?php
// AJAX endpoint to post a reaction to a comment
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
    $reaction_type = trim($_POST['reaction_type'] ?? '');
    $user_id = intval($_SESSION['user_id']);
    if ($comment_id && $reaction_type) {
        // Remove any previous reaction by this user for this comment
        $stmt = $db->prepare("DELETE FROM news_feed_comment_reactions WHERE comment_id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $user_id]);
        // Insert the new reaction
        $stmt = $db->prepare("INSERT INTO news_feed_comment_reactions (comment_id, user_id, reaction_type) VALUES (?, ?, ?)");
        $stmt->execute([$comment_id, $user_id, $reaction_type]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Missing data']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request']);
}
