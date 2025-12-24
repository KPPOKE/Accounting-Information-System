<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('users_delete');

require_once __DIR__ . '/../../includes/HashIdHelper.php';
$id = HashIdHelper::decode($_GET['id'] ?? '');

if ($id === false) {
    setFlash('danger', 'ID Pengguna tidak valid');
    redirect(APP_URL . '/users');
}

if ($id == $_SESSION['user_id']) {
    setFlash('danger', 'Tidak dapat menghapus akun sendiri');
    redirect(APP_URL . '/users');
}

if (deleteUser($id)) {
    setFlash('success', 'Pengguna berhasil dihapus');
} else {
    setFlash('danger', 'Gagal menghapus pengguna');
}

redirect(APP_URL . '/users');
