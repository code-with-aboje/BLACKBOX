<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);

echo json_encode([
    "success" => true,
    "tokens" => (int) $user['tokens']
]);
