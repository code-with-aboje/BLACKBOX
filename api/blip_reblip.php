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

$stmt = $pdo->prepare("SELECT id FROM blip_reblips WHERE blip_id = ? AND user_id = ?");
$stmt->execute([$blipId, $user['id']]);

if ($stmt->fetch()) {
    $pdo->prepare("DELETE FROM blip_reblips WHERE blip_id = ? AND user_id = ?")
        ->execute([$blipId, $user['id']]);
    $reblipped = false;
} else {
    $pdo->prepare("INSERT INTO blip_reblips (blip_id, user_id) VALUES (?, ?)")
        ->execute([$blipId, $user['id']]);
    $reblipped = true;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM blip_reblips WHERE blip_id = ?");
$countStmt->execute([$blipId]);
$count = $countStmt->fetchColumn();

echo json_encode(["success" => true, "reblipped" => $reblipped, "count" => (int) $count]);
