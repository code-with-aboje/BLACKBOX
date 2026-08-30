<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);
$myId = $user['id'];

$stmt = $pdo->prepare("
    SELECT
        t.id,
        t.name,
        t.icon,
        t.icon_image,
        t.description,
        t.price_tokens,
        t.version,
        t.size_label,
        t.tool_url,
        t.created_at,
        t.creator_id,
        u.username AS creator_username,
        (SELECT COUNT(*) FROM market_downloads WHERE tool_id = t.id) AS downloads,
        EXISTS(SELECT 1 FROM market_downloads WHERE tool_id = t.id AND user_id = ?) AS gotten_by_me
    FROM market_tools t
    JOIN users u ON u.id = t.creator_id
    ORDER BY t.created_at DESC
");
$stmt->execute([$myId]);
$tools = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tools as &$t) {
    $t['downloads'] = (int) $t['downloads'];
    $t['gotten_by_me'] = (bool) $t['gotten_by_me'];
    $t['is_mine'] = ((int) $t['creator_id'] === (int) $myId);
    $t['price_tokens'] = (int) $t['price_tokens'];
    // Only reveal the actual hosted link to the creator or someone who's already got it —
    // otherwise there'd be nothing stopping people from skipping the purchase.
    if (!$t['is_mine'] && !$t['gotten_by_me']) {
        $t['tool_url'] = null;
    }
}

echo json_encode(["success" => true, "tools" => $tools]);
