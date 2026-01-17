<?php
require_once __DIR__ . '/bootstrap.php';

$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'dust1947';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  http_response_code(500);
  die("DB connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
