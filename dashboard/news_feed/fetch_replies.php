<?php
// AJAX endpoint to fetch replies for a comment
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$parent_id = intval($_GET['parent_id'] ?? 0);
if ($parent_id) {
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT c.id, c.comment, c.created_at, c.user_id, u.username FROM news_feed_comments c JOIN users u ON c.user_id = u.id WHERE c.parent_id = ? ORDER BY c.created_at ASC");
    $stmt->execute([$parent_id]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($replies as &$reply) {
        $reply['is_owner'] = ($reply['user_id'] == $user_id);
        unset($reply['user_id']);
    }
    echo json_encode($replies);
} else {
    echo json_encode([]);
}
