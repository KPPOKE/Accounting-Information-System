<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
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
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_name'] = $user['role_name'];

        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        logActivity('login', 'auth', null, 'User ' . $username . ' berhasil login');

        return true;
    }
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
