<?php
define('APP_NAME', 'Finacore');
define('APP_VERSION', '1.0.0');

// Dynamic APP_URL - works on localhost and production
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = '';

// Detect base path from script location
if (strpos($scriptPath, '/modules/') !== false) {
    $basePath = substr($scriptPath, 0, strpos($scriptPath, '/modules/'));
} elseif (strpos($scriptPath, '/api/') !== false) {
    $basePath = substr($scriptPath, 0, strpos($scriptPath, '/api/'));
} else {
    $basePath = rtrim($scriptPath, '/');
}

define('APP_URL', $protocol . '://' . $host . $basePath);

define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/assets/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);

date_default_timezone_set('Asia/Jakarta');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
