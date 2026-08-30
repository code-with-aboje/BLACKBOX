<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);
$myId = $user['id'];

$stmt = $pdo->prepare("
    SELECT
        c.id AS conversation_id,
        c.status,
        c.requested_by,
        c.updated_at,
        IF(c.user_a_id = ?, c.user_b_id, c.user_a_id) AS other_user_id
    FROM conversations c
    WHERE c.user_a_id = ? OR c.user_b_id = ?
    ORDER BY c.updated_at DESC
");
$stmt->execute([$myId, $myId, $myId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$userStmt = $pdo->prepare("SELECT id, username, avatar FROM users WHERE id = ?");
$lastMsgStmt = $pdo->prepare("
    SELECT sender_id, text, created_at
    FROM chat_messages
    WHERE conversation_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");

$chats = [];
$requestsReceived = [];
$requestsSent = [];

foreach ($rows as $row) {
    $userStmt->execute([$row['other_user_id']]);
    $otherUser = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$otherUser) continue; // other user was deleted

    $lastMsgStmt->execute([$row['conversation_id']]);
    $lastMessage = $lastMsgStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    $item = [
        "conversation_id" => (int) $row['conversation_id'],
        "status" => $row['status'],
        "requested_by" => (int) $row['requested_by'],
        "updated_at" => $row['updated_at'],
        "other_user" => $otherUser,
        "last_message" => $lastMessage
    ];

    if ($row['status'] === 'accepted') {
        $chats[] = $item;
    } elseif ((int) $row['requested_by'] === $myId) {
        $requestsSent[] = $item;
    } else {
        $requestsReceived[] = $item;
    }
}

echo json_encode([
    "success" => true,
    "chats" => $chats,
    "requests_received" => $requestsReceived,
    "requests_sent" => $requestsSent
]);
