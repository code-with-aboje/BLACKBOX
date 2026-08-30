<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$identifier = trim($data['username'] ?? $data['email'] ?? '');
$password   = $data['password'] ?? '';

if (!$identifier || !$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Username/email and password are required"]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Invalid credentials"]);
    exit;
}

// generate a simple session token
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+7 days'));

$stmt = $pdo->prepare("INSERT INTO sessions (user_id, token, expires_at) VALUES (?, ?, ?)");
$stmt->execute([$user['id'], $token, $expires]);

$pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

echo json_encode([
    "success" => true,
    "message" => "Login successful",
    "token" => $token,
    "user" => [
        "id" => $user['id'],
        "username" => $user['username'],
        "email" => $user['email'],
        "role" => $user['role'],
        "avatar" => $user['avatar']
    ]
]);
