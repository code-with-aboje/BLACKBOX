<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);
$myId = $user['id'];

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$conversationId = (int) ($data['conversation_id'] ?? 0);
$action = $data['action'] ?? '';

if (!$conversationId || !in_array($action, ['accept', 'decline'], true)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "conversation_id and a valid action ('accept' or 'decline') are required"]);
    exit;
}

$convStmt = $pdo->prepare("SELECT * FROM conversations WHERE id = ?");
$convStmt->execute([$conversationId]);
$conversation = $convStmt->fetch(PDO::FETCH_ASSOC);

if (!$conversation || ((int) $conversation['user_a_id'] !== $myId && (int) $conversation['user_b_id'] !== $myId)) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Conversation not found"]);
    exit;
}

if ($conversation['status'] !== 'pending' || (int) $conversation['requested_by'] === $myId) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Nothing to respond to"]);
    exit;
}

if ($action === 'accept') {
    $pdo->prepare("UPDATE conversations SET status = 'accepted' WHERE id = ?")->execute([$conversationId]);
    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type) VALUES (?, ?, 'chat_accept')");
    $notifStmt->execute([$conversation['requested_by'], $myId]);
    echo json_encode(["success" => true, "status" => "accepted"]);
} else {
    $pdo->prepare("DELETE FROM conversations WHERE id = ?")->execute([$conversationId]);
    echo json_encode(["success" => true, "status" => "declined"]);
}
