<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);
$data = json_decode(file_get_contents("php://input"), true);
$blipId = (int) ($data['blip_id'] ?? 0);

if (!$blipId) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "blip_id required"]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM blip_likes WHERE blip_id = ? AND user_id = ?");
$stmt->execute([$blipId, $user['id']]);

if ($stmt->fetch()) {
    // already liked -> unlike
    $pdo->prepare("DELETE FROM blip_likes WHERE blip_id = ? AND user_id = ?")
        ->execute([$blipId, $user['id']]);
    $liked = false;
} else {
    $pdo->prepare("INSERT INTO blip_likes (blip_id, user_id) VALUES (?, ?)")
        ->execute([$blipId, $user['id']]);
    $liked = true;

    $ownerStmt = $pdo->prepare("SELECT user_id FROM blips WHERE id = ?");
    $ownerStmt->execute([$blipId]);
    $blipOwnerId = $ownerStmt->fetchColumn();

    if ($blipOwnerId && (int) $blipOwnerId !== (int) $user['id']) {
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, blip_id) VALUES (?, ?, 'like', ?)");
        $notifStmt->execute([$blipOwnerId, $user['id'], $blipId]);
    }
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM blip_likes WHERE blip_id = ?");
$countStmt->execute([$blipId]);
$count = $countStmt->fetchColumn();

echo json_encode(["success" => true, "liked" => $liked, "count" => (int) $count]);
