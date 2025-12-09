<?php
require_once __DIR__ . '/config/app.php';

header('Location: ' . APP_URL . '/modules/auth/login.php');
exit;
