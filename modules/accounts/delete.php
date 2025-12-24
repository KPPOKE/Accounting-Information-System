<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('accounts_delete');

$pdo = getDBConnection();
$decodedId = HashIdHelper::decode($_GET['id'] ?? '');
$id = $decodedId !== false ? $decodedId : 0;

$stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
$stmt->execute([$id]);
$account = $stmt->fetch();

if (!$account) {
    setFlash('danger', 'Akun tidak ditemukan');
    redirect(APP_URL . '/accounts');
}

$usageCheck = $pdo->prepare("SELECT COUNT(*) as count FROM journal_details WHERE account_id = ?");
$usageCheck->execute([$id]);
$usage = $usageCheck->fetch();

if ($usage['count'] > 0) {
    setFlash('danger', 'Akun tidak dapat dihapus karena sudah digunakan dalam transaksi');
    redirect(APP_URL . '/accounts');
}

$stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ?");
$result = $stmt->execute([$id]);

if ($result) {
    logActivity('delete', 'accounts', $id, "Menghapus akun: {$account['code']} - {$account['name']}", $account);
    setFlash('success', 'Akun berhasil dihapus');
} else {
    setFlash('danger', 'Gagal menghapus akun');
}

redirect(APP_URL . '/accounts');
