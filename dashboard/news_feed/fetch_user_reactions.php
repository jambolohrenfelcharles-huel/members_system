<?php
// AJAX endpoint to fetch the current user's reactions for a news feed post
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$news_feed_id = intval($_GET['news_feed_id'] ?? 0);
$user_id = intval($_SESSION['user_id']);
if ($news_feed_id && $user_id) {
    $stmt = $db->prepare("SELECT reaction_type FROM news_feed_reactions WHERE news_feed_id = ? AND user_id = ?");
    $stmt->execute([$news_feed_id, $user_id]);
    $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($types);
} else {
    echo json_encode([]);
}
