<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);
$myId = $user['id'];

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$markAll = !empty($data['mark_all']);
$notificationId = (int) ($data['notification_id'] ?? 0);

if (!$markAll && !$notificationId) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "notification_id or mark_all is required"]);
    exit;
}

if ($markAll) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$myId]);
} else {
    // Ownership check baked into the WHERE clause — can't mark someone else's notification.
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $myId]);
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$countStmt->execute([$myId]);
$unreadCount = (int) $countStmt->fetchColumn();

echo json_encode(["success" => true, "unread_count" => $unreadCount]);
