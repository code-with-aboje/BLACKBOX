<?php
// Include this at the top of any protected API endpoint.
// Expects header: Authorization: Bearer <token>
require_once __DIR__ . '/db.php';

function getAuthUser($pdo) {
    $authHeader = '';

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? ($headers['authorization'] ?? '');
    }

    // fallback for hosts (common on shared hosting like InfinityFree) where
    // getallheaders() doesn't see it but PHP still received it under one of these
    if (!$authHeader && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }
    if (!$authHeader && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }

    if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "No token provided"]);
        exit;
    }

    $token = $matches[1];

    $stmt = $pdo->prepare("
        SELECT u.* FROM sessions s
        JOIN users u ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid or expired token"]);
        exit;
    }

    return $user;
}
