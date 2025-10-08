<?php
// AJAX endpoint for autocomplete comment suggestions
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$news_feed_id = isset($_GET['news_feed_id']) ? intval($_GET['news_feed_id']) : 0;

if ($query && $news_feed_id) {
    $stmt = $db->prepare("SELECT DISTINCT comment FROM news_feed_comments WHERE news_feed_id = ? AND comment LIKE ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$news_feed_id, "%$query%"]);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($results);
} else {
    echo json_encode([]);
}
