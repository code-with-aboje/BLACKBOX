<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/auth_check.php';

$user = getAuthUser($pdo);

// Text can arrive as JSON (no images) or as multipart/form-data (images present).
// Detect which one we got.
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'multipart/form-data') !== false) {
    $text = trim($_POST['text'] ?? '');
    $files = $_FILES['images'] ?? null;
} else {
    $data = json_decode(file_get_contents("php://input"), true) ?? [];
    $text = trim($data['text'] ?? '');
    $files = null;
}

$hasImages = $files && isset($files['name']) && is_array($files['name']) && count(array_filter($files['name'])) > 0;

if (!$text && !$hasImages) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Blip needs text or at least one image"]);
    exit;
}

if (strlen($text) > 500) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Blip too long (max 500 chars)"]);
    exit;
}

$MAX_IMAGES = 4;
$MAX_BYTES = 8 * 1024 * 1024; // 8MB per image
$ALLOWED_MIME = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];

$savedPaths = [];

if ($hasImages) {
    $count = count($files['name']);
    if ($count > $MAX_IMAGES) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Max $MAX_IMAGES images per blip"]);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/blips/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;

        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Upload failed for image " . ($i + 1)]);
            exit;
        }

        if ($files['size'][$i] > $MAX_BYTES) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Image " . ($i + 1) . " is too large (max 8MB)"]);
            exit;
        }

        $tmpPath = $files['tmp_name'][$i];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        if (!isset($ALLOWED_MIME[$mime])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Image " . ($i + 1) . " must be JPG, PNG, GIF, or WEBP"]);
            exit;
        }

        $ext = $ALLOWED_MIME[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Could not save image " . ($i + 1)]);
            exit;
        }

        $savedPaths[] = 'uploads/blips/' . $filename;
    }
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO blips (user_id, text) VALUES (?, ?)");
    $stmt->execute([$user['id'], $text]);
    $blipId = $pdo->lastInsertId();

    if (!empty($savedPaths)) {
        $imgStmt = $pdo->prepare("INSERT INTO blip_images (blip_id, image_path, position) VALUES (?, ?, ?)");
        foreach ($savedPaths as $pos => $path) {
            $imgStmt->execute([$blipId, $path, $pos]);
        }
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    // clean up any files we already saved on disk since the DB write failed
    foreach ($savedPaths as $path) {
        $full = __DIR__ . '/../' . $path;
        if (file_exists($full)) unlink($full);
    }
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Could not save blip"]);
    exit;
}

echo json_encode([
    "success" => true,
    "blip_id" => $blipId,
    "images" => $savedPaths
]);
