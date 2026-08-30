<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data['username'] ?? '');
$email    = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$role     = trim($data['role'] ?? 'Member');
$avatar   = $data['avatar'] ?? null; // base64 or filename, wire up later if needed

if (!$username || !$email || !$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Username, email and password are required"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Password must be at least 6 characters"]);
    exit;
}

// check for existing user
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "Username or email already taken"]);
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, avatar) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$username, $email, $hash, $role, $avatar]);

echo json_encode([
    "success" => true,
    "message" => "Account created",
    "user_id" => $pdo->lastInsertId()
]);
