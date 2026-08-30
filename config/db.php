<?php
// Database connection — currently pointed at InfinityFree.
// If this ever runs on Railway again, its env vars will override these
// automatically (MYSQLHOST etc.) — nothing to change either way.
$host   = getenv('MYSQLHOST')     ?: 'sql304.infinityfree.com';
$dbname = getenv('MYSQLDATABASE') ?: 'if0_42720492_blackbox';
$dbuser = getenv('MYSQLUSER')     ?: 'if0_42720492';
$dbpass = getenv('MYSQLPASSWORD') ?: 'jeTEyHLatPKIW1A';
$port   = getenv('MYSQLPORT')     ?: '3306';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(["success" => false, "message" => "DB connection failed: " . $e->getMessage()]));
}
