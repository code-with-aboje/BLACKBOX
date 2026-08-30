<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);
$myId = $user['id'];

$stmt = $pdo->prepare("
    SELECT
        b.id,
        b.text,
        b.created_at,
        u.id AS user_id,
        u.username,
        u.avatar,
        (SELECT COUNT(*) FROM blip_likes WHERE blip_id = b.id) AS like_count,
        (SELECT COUNT(*) FROM blip_reblips WHERE blip_id = b.id) AS reblip_count,
        (SELECT COUNT(*) FROM blip_comments WHERE blip_id = b.id) AS comment_count,
        EXISTS(SELECT 1 FROM blip_likes WHERE blip_id = b.id AND user_id = ?) AS liked_by_me,
        EXISTS(SELECT 1 FROM blip_reblips WHERE blip_id = b.id AND user_id = ?) AS reblipped_by_me
    FROM blips b
    JOIN users u ON u.id = b.user_id
    ORDER BY b.created_at DESC
    LIMIT 50
");
$stmt->execute([$myId, $myId]);
$blips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// attach comments for each blip
$commentStmt = $pdo->prepare("
    SELECT c.id, c.parent_id, c.text, c.created_at, u.id AS user_id, u.username, u.avatar
    FROM blip_comments c
    JOIN users u ON u.id = c.user_id
    WHERE c.blip_id = ?
    ORDER BY c.created_at ASC
");

// attach images for each blip
$imageStmt = $pdo->prepare("
    SELECT image_path
    FROM blip_images
    WHERE blip_id = ?
    ORDER BY position ASC
");

foreach ($blips as &$blip) {
    $blip['liked_by_me'] = (bool) $blip['liked_by_me'];
    $blip['reblipped_by_me'] = (bool) $blip['reblipped_by_me'];
    $commentStmt->execute([$blip['id']]);
    $blip['comments'] = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

    $imageStmt->execute([$blip['id']]);
    $blip['images'] = array_map(
        fn($row) => '../' . $row['image_path'],
        $imageStmt->fetchAll(PDO::FETCH_ASSOC)
    );
}

echo json_encode(["success" => true, "blips" => $blips]);
