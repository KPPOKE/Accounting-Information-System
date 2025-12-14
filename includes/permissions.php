<?php
require_once __DIR__ . '/../config/database.php';

function hasPermission($permissionName) {
    if (!isset($_SESSION['role_id'])) {
        return false;
    }

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM role_permissions rp 
        JOIN permissions p ON rp.permission_id = p.id 
        WHERE rp.role_id = ? AND p.name = ?
    ");
    $stmt->execute([$_SESSION['role_id'], $permissionName]);
    $result = $stmt->fetch();

    return $result['count'] > 0;
}

function hasAnyPermission($permissions) {
    foreach ($permissions as $permission) {
        if (hasPermission($permission)) {
            return true;
        }
    }
    return false;
}

function hasAllPermissions($permissions) {
    foreach ($permissions as $permission) {
        if (!hasPermission($permission)) {
            return false;
        }
    }
    return true;
}

function requirePermission($permissionName) {
    if (!hasPermission($permissionName)) {
        header('HTTP/1.1 403 Forbidden');
        include __DIR__ . '/../components/403.php';
        exit;
    }
}

function canAccessModule($module) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM role_permissions rp 
        JOIN permissions p ON rp.permission_id = p.id 
        WHERE rp.role_id = ? AND p.module = ?
    ");
    $stmt->execute([$_SESSION['role_id'], $module]);
    $result = $stmt->fetch();

    return $result['count'] > 0;
}

function getUserPermissions($roleId = null) {
    if ($roleId === null && isset($_SESSION['role_id'])) {
        $roleId = $_SESSION['role_id'];
    }

    if ($roleId === null) {
        return [];
    }

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM role_permissions rp 
        JOIN permissions p ON rp.permission_id = p.id 
        WHERE rp.role_id = ?
    ");
    $stmt->execute([$roleId]);
    return $stmt->fetchAll();
}

function isAdmin() {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Admin';
}

function isAkuntan() {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Akuntan';
}

function isStaffKeuangan() {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Staff Keuangan';
}

function isAuditor() {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Auditor';
}

function isManajer() {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Manajer Keuangan';
}

function canApprove() {
    return isAdmin() || isManajer();
}
