<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);
$myId = $user['id'];

$targetId = (int) ($_GET['user_id'] ?? 0);
if (!$targetId) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "user_id is required"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.username,
        u.avatar,
        u.about,
        u.role,
        (SELECT COUNT(*) FROM follows WHERE followed_id = u.id) AS follower_count,
        EXISTS(SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = u.id) AS followed_by_me
    FROM users u
    WHERE u.id = ?
");
$stmt->execute([$myId, $targetId]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$u) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

$u['follower_count'] = (int) $u['follower_count'];
$u['followed_by_me'] = (bool) $u['followed_by_me'];
$u['is_me'] = ($targetId === $myId);

echo json_encode(["success" => true, "user" => $u]);
