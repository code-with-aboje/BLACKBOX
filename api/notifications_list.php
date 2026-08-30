<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);
$myId = $user['id'];

$stmt = $pdo->prepare("
    SELECT
        n.id,
        n.type,
        n.is_read,
        n.created_at,
        n.comment_text,
        a.id AS actor_id,
        a.username AS actor_username,
        a.avatar AS actor_avatar,
        n.blip_id,
        LEFT(b.text, 80) AS blip_snippet,
        n.tool_id,
        t.name AS tool_name,
        t.icon AS tool_icon,
        t.icon_image AS tool_icon_image
    FROM notifications n
    JOIN users a ON a.id = n.actor_id
    LEFT JOIN blips b ON b.id = n.blip_id
    LEFT JOIN market_tools t ON t.id = n.tool_id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 100
");
$stmt->execute([$myId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// NOTE: avatar is left as-is (same convention as blip_feed.php / users_list.php) —
// avatar upload isn't fully wired up yet elsewhere in the app.
$unreadCount = 0;
foreach ($notifications as &$n) {
    $n['is_read'] = (bool) $n['is_read'];
    if (!$n['is_read']) $unreadCount++;
    if ($n['tool_icon_image']) $n['tool_icon_image'] = '../' . $n['tool_icon_image'];
}

echo json_encode([
    "success" => true,
    "notifications" => $notifications,
    "unread_count" => $unreadCount
]);
