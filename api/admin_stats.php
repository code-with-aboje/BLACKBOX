<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/admin_config.php';

$authHeader = '';
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? ($headers['authorization'] ?? '');
}
if (!$authHeader && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
}
if (!$authHeader && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}

if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $matches) || $matches[1] !== getAdminToken()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Not authorized"]);
    exit;
}

$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

$newToday = (int) $pdo->query("
    SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()
")->fetchColumn();

try {
    $totalBlips = (int) $pdo->query("SELECT COUNT(*) FROM blips")->fetchColumn();
} catch (PDOException $e) {
    $totalBlips = 0; // blips table might not exist yet on a fresh setup
}

$roleBreakdown = $pdo->query("
    SELECT role, COUNT(*) AS count FROM users GROUP BY role ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

$recentUsers = $pdo->query("
    SELECT id, username, email, role, created_at
    FROM users ORDER BY created_at DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "total_users" => $totalUsers,
    "new_today" => $newToday,
    "total_blips" => $totalBlips,
    "role_breakdown" => $roleBreakdown,
    "recent_users" => $recentUsers
]);
