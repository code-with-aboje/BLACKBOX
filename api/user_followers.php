<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);

$targetId = (int) ($_GET['user_id'] ?? 0);
if (!$targetId) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "user_id is required"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.avatar
    FROM follows f
    JOIN users u ON u.id = f.follower_id
    WHERE f.followed_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$targetId]);
$followers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["success" => true, "followers" => $followers]);
