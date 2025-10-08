<?php
// AJAX endpoint to fetch reactions for a comment
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$comment_id = intval($_GET['comment_id'] ?? 0);
if ($comment_id) {
    $stmt = $db->prepare("SELECT reaction_type, COUNT(*) as count FROM news_feed_comment_reactions WHERE comment_id = ? GROUP BY reaction_type");
    $stmt->execute([$comment_id]);
    $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    echo json_encode($counts);
} else {
    echo json_encode([]);
}
