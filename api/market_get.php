<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);
$myId = $user['id'];

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$toolId = (int) ($data['tool_id'] ?? 0);

if (!$toolId) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "tool_id is required"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Lock the tool row and the buyer's row so concurrent purchases can't race the balance check.
    $toolStmt = $pdo->prepare("SELECT id, creator_id, price_tokens, tool_url FROM market_tools WHERE id = ? FOR UPDATE");
    $toolStmt->execute([$toolId]);
    $tool = $toolStmt->fetch(PDO::FETCH_ASSOC);

    if (!$tool) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Tool not found"]);
        exit;
    }

    if ((int) $tool['creator_id'] === $myId) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "You can't buy your own tool"]);
        exit;
    }

    $existsStmt = $pdo->prepare("SELECT id FROM market_downloads WHERE tool_id = ? AND user_id = ?");
    $existsStmt->execute([$toolId, $myId]);
    if ($existsStmt->fetch()) {
        // Already owned — idempotent, no charge, just report current state.
        $pdo->commit();
        $balanceStmt = $pdo->prepare("SELECT tokens FROM users WHERE id = ?");
        $balanceStmt->execute([$myId]);
        $tokens = (int) $balanceStmt->fetchColumn();
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM market_downloads WHERE tool_id = ?");
        $countStmt->execute([$toolId]);
        echo json_encode([
            "success" => true,
            "already_owned" => true,
            "tokens" => $tokens,
            "downloads" => (int) $countStmt->fetchColumn(),
            "tool_url" => $tool['tool_url']
        ]);
        exit;
    }

    $price = (int) $tool['price_tokens'];

    $buyerStmt = $pdo->prepare("SELECT tokens FROM users WHERE id = ? FOR UPDATE");
    $buyerStmt->execute([$myId]);
    $buyerTokens = (int) $buyerStmt->fetchColumn();

    if ($buyerTokens < $price) {
        $pdo->rollBack();
        http_response_code(402);
        echo json_encode(["success" => false, "message" => "Not enough tokens"]);
        exit;
    }

    if ($price > 0) {
        $pdo->prepare("UPDATE users SET tokens = tokens - ? WHERE id = ?")->execute([$price, $myId]);
        $pdo->prepare("UPDATE users SET tokens = tokens + ? WHERE id = ?")->execute([$price, $tool['creator_id']]);
    }

    $pdo->prepare("INSERT INTO market_downloads (tool_id, user_id) VALUES (?, ?)")->execute([$toolId, $myId]);

    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, tool_id) VALUES (?, ?, 'tool_get', ?)");
    $notifStmt->execute([$tool['creator_id'], $myId, $toolId]);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM market_downloads WHERE tool_id = ?");
    $countStmt->execute([$toolId]);
    $downloads = (int) $countStmt->fetchColumn();

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "already_owned" => false,
        "tokens" => $buyerTokens - $price,
        "downloads" => $downloads,
        "tool_url" => $tool['tool_url']
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Could not complete purchase"]);
}
