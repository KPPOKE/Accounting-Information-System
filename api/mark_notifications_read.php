<?php
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$_SESSION['last_seen_notification'] = date('Y-m-d H:i:s');

echo json_encode(['success' => true]);
