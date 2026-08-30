<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);
$myId = $user['id'];

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$conversationId = (int) ($data['conversation_id'] ?? 0);
$text = trim($data['text'] ?? '');

if (!$conversationId || !$text) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "conversation_id and text are required"]);
    exit;
}

if (mb_strlen($text) > 1000) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Message is too long (max 1000 characters)"]);
    exit;
}

try {
    $pdo->beginTransaction();

    $convStmt = $pdo->prepare("SELECT * FROM conversations WHERE id = ? FOR UPDATE");
    $convStmt->execute([$conversationId]);
    $conversation = $convStmt->fetch(PDO::FETCH_ASSOC);

    if (!$conversation || ((int) $conversation['user_a_id'] !== $myId && (int) $conversation['user_b_id'] !== $myId)) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Conversation not found"]);
        exit;
    }

    $isRequester = ((int) $conversation['requested_by'] === $myId);

    if ($conversation['status'] === 'pending' && $isRequester) {
        // The requester only gets ONE message until the other side responds.
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE conversation_id = ? AND sender_id = ?");
        $countStmt->execute([$conversationId, $myId]);
        if ((int) $countStmt->fetchColumn() > 0) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "You've already sent your message. Waiting for them to accept."]);
            exit;
        }
    }

    $insStmt = $pdo->prepare("INSERT INTO chat_messages (conversation_id, sender_id, text) VALUES (?, ?, ?)");
    $insStmt->execute([$conversationId, $myId, $text]);
    $messageId = $pdo->lastInsertId();

    $newStatus = $conversation['status'];
    if ($conversation['status'] === 'pending' && !$isRequester) {
        // Replying as the recipient of a pending request accepts it.
        $pdo->prepare("UPDATE conversations SET status = 'accepted' WHERE id = ?")->execute([$conversationId]);
        $newStatus = 'accepted';

        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type) VALUES (?, ?, 'chat_accept')");
        $notifStmt->execute([$conversation['requested_by'], $myId]);
    } else {
        $pdo->prepare("UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$conversationId]);
    }

    $msgStmt = $pdo->prepare("SELECT id, sender_id, text, created_at FROM chat_messages WHERE id = ?");
    $msgStmt->execute([$messageId]);
    $message = $msgStmt->fetch(PDO::FETCH_ASSOC);
    $message['id'] = (int) $message['id'];
    $message['sender_id'] = (int) $message['sender_id'];

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "status" => $newStatus,
        "message" => $message
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Could not send message"]);
}
