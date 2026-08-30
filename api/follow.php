<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);
$myId = $user['id'];

$data = json_decode(file_get_contents("php://input"), true);
$targetId = (int) ($data['user_id'] ?? 0);

if (!$targetId) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "user_id is required"]);
    exit;
}

if ($targetId === $myId) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "You can't follow yourself"]);
    exit;
}

$checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$checkStmt->execute([$targetId]);
if (!$checkStmt->fetch()) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

$existsStmt = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND followed_id = ?");
$existsStmt->execute([$myId, $targetId]);
$existing = $existsStmt->fetch();

if ($existing) {
    $delStmt = $pdo->prepare("DELETE FROM follows WHERE id = ?");
    $delStmt->execute([$existing['id']]);
    $following = false;
} else {
    $insStmt = $pdo->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)");
    $insStmt->execute([$myId, $targetId]);
    $following = true;

    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type) VALUES (?, ?, 'follow')");
    $notifStmt->execute([$targetId, $myId]);
}

$countStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM follows WHERE followed_id = ?");
$countStmt->execute([$targetId]);
$count = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['c'];

echo json_encode([
    "success" => true,
    "following" => $following,
    "follower_count" => $count
]);
