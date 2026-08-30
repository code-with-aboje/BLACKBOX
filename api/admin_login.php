<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/admin_config.php';

$data = json_decode(file_get_contents("php://input"), true);
$password = $data['password'] ?? '';

if (!$password || !password_verify($password, ADMIN_PASSWORD_HASH)) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Wrong password"]);
    exit;
}

echo json_encode([
    "success" => true,
    "token" => getAdminToken()
]);
