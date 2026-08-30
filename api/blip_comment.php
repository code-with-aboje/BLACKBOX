<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);
$data = json_decode(file_get_contents("php://input"), true);
$blipId = (int) ($data['blip_id'] ?? 0);
$text = trim($data['text'] ?? '');
$parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;

if (!$blipId || !$text) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "blip_id and text required"]);
    exit;
}

if (strlen($text) > 500) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Comment too long (max 500 chars)"]);
    exit;
}

if ($parentId) {
    // a reply must point at a comment that actually belongs to this blip
    $checkStmt = $pdo->prepare("SELECT id FROM blip_comments WHERE id = ? AND blip_id = ?");
    $checkStmt->execute([$parentId, $blipId]);
    if (!$checkStmt->fetch()) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid comment to reply to"]);
        exit;
    }
}

$stmt = $pdo->prepare("INSERT INTO blip_comments (blip_id, parent_id, user_id, text) VALUES (?, ?, ?, ?)");
$stmt->execute([$blipId, $parentId, $user['id'], $text]);
$commentId = $pdo->lastInsertId();

$ownerStmt = $pdo->prepare("SELECT user_id FROM blips WHERE id = ?");
$ownerStmt->execute([$blipId]);
$blipOwnerId = $ownerStmt->fetchColumn();

if ($blipOwnerId && (int) $blipOwnerId !== (int) $user['id']) {
    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, blip_id, comment_text) VALUES (?, ?, 'comment', ?, ?)");
    $notifStmt->execute([$blipOwnerId, $user['id'], $blipId, mb_substr($text, 0, 160)]);
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM blip_comments WHERE blip_id = ?");
$countStmt->execute([$blipId]);
$count = $countStmt->fetchColumn();

echo json_encode([
    "success" => true,
    "id" => (int) $commentId,
    "parent_id" => $parentId,
    "user_id" => $user['id'],
    "username" => $user['username'],
    "avatar" => $user['avatar'],
    "text" => $text,
    "count" => (int) $count
]);
