<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('users_delete');

$id = intval($_GET['id'] ?? 0);

if ($id == $_SESSION['user_id']) {
    setFlash('danger', 'Tidak dapat menghapus akun sendiri');
    redirect(APP_URL . '/modules/users/');
}

if (deleteUser($id)) {
    setFlash('success', 'Pengguna berhasil dihapus');
} else {
    setFlash('danger', 'Gagal menghapus pengguna');
}

redirect(APP_URL . '/modules/users/');
