<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);
$myId = $user['id'];

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$targetId = (int) ($data['user_id'] ?? 0);

if (!$targetId) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "user_id is required"]);
    exit;
}

if ($targetId === $myId) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "You can't message yourself"]);
    exit;
}

$targetStmt = $pdo->prepare("SELECT id, username, avatar FROM users WHERE id = ?");
$targetStmt->execute([$targetId]);
$target = $targetStmt->fetch(PDO::FETCH_ASSOC);

if (!$target) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

$userA = min($myId, $targetId);
$userB = max($myId, $targetId);

$findStmt = $pdo->prepare("SELECT id, status, requested_by FROM conversations WHERE user_a_id = ? AND user_b_id = ?");
$findStmt->execute([$userA, $userB]);
$conversation = $findStmt->fetch(PDO::FETCH_ASSOC);

if (!$conversation) {
    $insStmt = $pdo->prepare("INSERT INTO conversations (user_a_id, user_b_id, requested_by, status) VALUES (?, ?, ?, 'pending')");
    $insStmt->execute([$userA, $userB, $myId]);
    $conversationId = $pdo->lastInsertId();

    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type) VALUES (?, ?, 'chat_request')");
    $notifStmt->execute([$targetId, $myId]);

    $conversation = ["id" => $conversationId, "status" => "pending", "requested_by" => $myId];
}

echo json_encode([
    "success" => true,
    "conversation_id" => (int) $conversation['id'],
    "status" => $conversation['status'],
    "requested_by" => (int) $conversation['requested_by'],
    "other_user" => $target
]);
