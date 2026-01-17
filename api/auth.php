<?php
// ===== CORS =====
$allowedOrigin = "https://kunzomat.de";
$origin = $_SERVER['HTTP_ORIGIN'] ?? "";

if ($origin === $allowedOrigin) {
    header("Access-Control-Allow-Origin: $allowedOrigin");
    header("Vary: Origin");
}

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-Key");
header("Content-Type: application/json; charset=UTF-8");

// ===== Preflight =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ===== API KEY =====
// Pfad ggf. anpassen!
require_once dirname(__DIR__, 3) . '/config.php';

$clientKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

if (!defined('API_KEY') || !hash_equals(API_KEY, $clientKey)) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
