<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 300);
define('SESSION_TIMEOUT', 1800);

set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');

function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $logDir = __DIR__ . '/../storage';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/error_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] Error [$errno]: $errstr in $errfile on line $errline\n";
    
    error_log($message, 3, $logFile);
    
    if (in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        return false;
    }
    return true;
}

function customExceptionHandler($exception) {
    $logDir = __DIR__ . '/../storage';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/error_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] Exception: {$exception->getMessage()} in {$exception->getFile()} on line {$exception->getLine()}\n";
    $message .= "Stack trace:\n{$exception->getTraceAsString()}\n\n";
    
    error_log($message, 3, $logFile);
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    session_start();
}

function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function isLoginLocked() {
    if (!isset($_SESSION['login_attempts'])) {
        return false;
    }
    
    $attempts = $_SESSION['login_attempts'];
    $lastAttempt = $_SESSION['last_login_attempt'] ?? 0;
    
    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $lockoutRemaining = LOGIN_LOCKOUT_TIME - (time() - $lastAttempt);
        if ($lockoutRemaining > 0) {
            return $lockoutRemaining;
        }
        resetLoginAttempts();
    }
    return false;
}

function recordFailedLogin() {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['last_login_attempt'] = time();
}

function resetLoginAttempts() {
    unset($_SESSION['login_attempts']);
    unset($_SESSION['last_login_attempt']);
}

function getRemainingAttempts() {
    $attempts = $_SESSION['login_attempts'] ?? 0;
    return max(0, MAX_LOGIN_ATTEMPTS - $attempts);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn() || !checkSessionTimeout()) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        header('Location: ' . APP_URL . '/login');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'email' => $_SESSION['email'],
        'role_id' => $_SESSION['role_id'],
        'role_name' => $_SESSION['role_name']
    ];
}

function login($username, $password) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.username = ? AND u.status = 'active'
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['last_activity'] = time();

        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        resetLoginAttempts();
        logActivity('login', 'auth', null, 'User ' . $username . ' berhasil login');

        return true;
    }
    
    recordFailedLogin();
    return false;
}

function logout() {
    if (isLoggedIn()) {
        logActivity('logout', 'auth', null, 'User logout');
    }
    session_destroy();
    header('Location: ' . APP_URL . '/login');
    exit;
}

function getUserById($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAllUsers() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        ORDER BY u.created_at DESC
    ");
    return $stmt->fetchAll();
}

function createUser($data) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, full_name, role_id, status) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $result = $stmt->execute([
        $data['username'],
        $data['email'],
        password_hash($data['password'], PASSWORD_DEFAULT),
        $data['full_name'],
        $data['role_id'],
        $data['status'] ?? 'active'
    ]);

    if ($result) {
        $userId = $pdo->lastInsertId();
        logActivity('create', 'users', $userId, 'Membuat user baru: ' . $data['username']);
        return $userId;
    }
    return false;
}

function updateUser($id, $data) {
    $pdo = getDBConnection();
    $oldData = getUserById($id);

    $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, role_id = ?, status = ?, updated_at = NOW()";
    $params = [$data['username'], $data['email'], $data['full_name'], $data['role_id'], $data['status']];

    if (!empty($data['password'])) {
        $sql .= ", password = ?";
        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        logActivity('update', 'users', $id, 'Mengupdate user: ' . $data['username'], $oldData, $data);
    }
    return $result;
}

function deleteUser($id) {
    $pdo = getDBConnection();
    $oldData = getUserById($id);

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $result = $stmt->execute([$id]);

    if ($result) {
        logActivity('delete', 'users', $id, 'Menghapus user: ' . $oldData['username'], $oldData);
    }
    return $result;
}

function getAllRoles() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY id");
    return $stmt->fetchAll();
}
