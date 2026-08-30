<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);
$myId = $user['id'];

$q = trim($_GET['q'] ?? '');

$sql = "
    SELECT
        u.id,
        u.username,
        u.avatar,
        u.about,
        u.role,
        (SELECT COUNT(*) FROM follows WHERE followed_id = u.id) AS follower_count,
        EXISTS(SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = u.id) AS followed_by_me
    FROM users u
    WHERE u.id != ?
";
$params = [$myId, $myId];

if ($q !== '') {
    $sql .= " AND (u.username LIKE ? OR u.about LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY u.username ASC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as &$u) {
    $u['follower_count'] = (int) $u['follower_count'];
    $u['followed_by_me'] = (bool) $u['followed_by_me'];
}

echo json_encode(["success" => true, "users" => $users]);
