<?php
// AJAX endpoint for autocomplete reply suggestions
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
$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;

if ($query && $parent_id) {
    $stmt = $db->prepare("SELECT DISTINCT comment FROM news_feed_comments WHERE parent_id = ? AND comment LIKE ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$parent_id, "%$query%"]);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($results);
} else {
    echo json_encode([]);
}
