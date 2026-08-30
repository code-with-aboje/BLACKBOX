<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo); // dies with 401 if token missing/invalid

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update bio ("about")
    $data = json_decode(file_get_contents("php://input"), true);

    if (!array_key_exists('about', $data ?? [])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "about is required"]);
        exit;
    }

    $about = trim((string) $data['about']);
    if ($about === '') {
        $about = null; // treat empty submission as "no bio"
    } elseif (mb_strlen($about) > 280) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Bio must be 280 characters or fewer"]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET about = ? WHERE id = ?");
    $stmt->execute([$about, $user['id']]);

    echo json_encode([
        "success" => true,
        "about" => $about
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "user" => [
        "id" => $user['id'],
        "username" => $user['username'],
        "email" => $user['email'],
        "role" => $user['role'],
        "avatar" => $user['avatar'],
        "about" => $user['about'],
        "created_at" => $user['created_at'],
        "last_login" => $user['last_login']
    ]
]);
