<?php
// AJAX endpoint to fetch reaction counts for a news feed post
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
    $stmt = $db->prepare("SELECT reaction_type, COUNT(*) as count FROM news_feed_reactions WHERE news_feed_id = ? GROUP BY reaction_type");
    $stmt->execute([$news_feed_id]);
    $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    echo json_encode($counts);
} else {
    echo json_encode([]);
}
