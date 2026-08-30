<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);

// Text-only publishes arrive as JSON; publishes with an icon image arrive as multipart/form-data.
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'multipart/form-data') !== false) {
    $data = $_POST;
    $iconFile = $_FILES['icon_image'] ?? null;
} else {
    $data = json_decode(file_get_contents("php://input"), true) ?? [];
    $iconFile = null;
}

$name        = trim($data['name'] ?? '');
$icon        = trim($data['icon'] ?? '') ?: '🛠';
$description = trim($data['description'] ?? '');
$version     = trim($data['version'] ?? '') ?: '1.0';
$sizeLabel   = trim($data['size_label'] ?? '') ?: null;
$price       = $data['price_tokens'] ?? null;
$toolUrl     = trim($data['tool_url'] ?? '');

if (!$name || !$description) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Name and description are required"]);
    exit;
}

if (mb_strlen($name) > 100) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Name must be 100 characters or fewer"]);
    exit;
}

if (mb_strlen($description) > 500) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Description must be 500 characters or fewer"]);
    exit;
}

if (!is_numeric($price) || (int) $price < 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Price must be a non-negative number of tokens"]);
    exit;
}
$price = (int) $price;

if (!$toolUrl) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Add the link to where your tool is hosted"]);
    exit;
}
if (!filter_var($toolUrl, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $toolUrl)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Tool link must be a valid http(s) URL"]);
    exit;
}
if (mb_strlen($toolUrl) > 500) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Tool link is too long"]);
    exit;
}

// icon is expected to be a short emoji/glyph, not a paragraph
if (mb_strlen($icon) > 16) {
    $icon = mb_substr($icon, 0, 16);
}

$iconImagePath = null;
$hasIconFile = $iconFile && isset($iconFile['name']) && $iconFile['name'] !== '' && $iconFile['error'] !== UPLOAD_ERR_NO_FILE;

if ($hasIconFile) {
    $MAX_BYTES = 4 * 1024 * 1024; // 4MB
    $ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    if ($iconFile['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Icon image failed to upload"]);
        exit;
    }
    if ($iconFile['size'] > $MAX_BYTES) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Icon image is too large (max 4MB)"]);
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $iconFile['tmp_name']);
    finfo_close($finfo);

    if (!isset($ALLOWED_MIME[$mime])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Icon image must be JPG, PNG, GIF, or WEBP"]);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/market_icons/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = $ALLOWED_MIME[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($iconFile['tmp_name'], $destPath)) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Could not save icon image"]);
        exit;
    }

    $iconImagePath = 'uploads/market_icons/' . $filename;
}

$stmt = $pdo->prepare("
    INSERT INTO market_tools (creator_id, name, icon, icon_image, description, price_tokens, version, size_label, tool_url)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([$user['id'], $name, $icon, $iconImagePath, $description, $price, $version, $sizeLabel, $toolUrl]);

echo json_encode([
    "success" => true,
    "tool_id" => $pdo->lastInsertId()
]);
