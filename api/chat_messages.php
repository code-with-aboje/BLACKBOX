<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);
$myId = $user['id'];

$conversationId = (int) ($_GET['conversation_id'] ?? 0);
if (!$conversationId) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "conversation_id is required"]);
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

$otherUserId = ((int) $conversation['user_a_id'] === $myId) ? $conversation['user_b_id'] : $conversation['user_a_id'];
$userStmt = $pdo->prepare("SELECT id, username, avatar FROM users WHERE id = ?");
$userStmt->execute([$otherUserId]);
$otherUser = $userStmt->fetch(PDO::FETCH_ASSOC);

$msgStmt = $pdo->prepare("
    SELECT id, sender_id, text, created_at
    FROM chat_messages
    WHERE conversation_id = ?
    ORDER BY created_at ASC
");
$msgStmt->execute([$conversationId]);
$messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($messages as &$m) {
    $m['id'] = (int) $m['id'];
    $m['sender_id'] = (int) $m['sender_id'];
}

echo json_encode([
    "success" => true,
    "status" => $conversation['status'],
    "requested_by" => (int) $conversation['requested_by'],
    "other_user" => $otherUser,
    "messages" => $messages
]);
