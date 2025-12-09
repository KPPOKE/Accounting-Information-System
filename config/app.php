<?php
define('APP_NAME', 'Finacore');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/Sistem%20Informasi%20Akuntasi');
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/assets/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);

date_default_timezone_set('Asia/Jakarta');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
