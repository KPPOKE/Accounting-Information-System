<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('cash_delete');

$pdo = getDBConnection();
$decodedId = HashIdHelper::decode($_GET['id'] ?? '');
$id = $decodedId !== false ? $decodedId : 0;

$stmt = $pdo->prepare("SELECT * FROM cash_transactions WHERE id = ? AND status = 'pending'");
$stmt->execute([$id]);
$transaction = $stmt->fetch();

if (!$transaction) {
    setFlash('danger', 'Transaksi tidak ditemukan atau sudah diproses');
    redirect(APP_URL . '/cash');
}

$attachStmt = $pdo->prepare("SELECT filepath FROM attachments WHERE cash_transaction_id = ?");
$attachStmt->execute([$id]);
$attachments = $attachStmt->fetchAll();

foreach ($attachments as $att) {
    deleteFile($att['filepath']);
}

$pdo->prepare("DELETE FROM attachments WHERE cash_transaction_id = ?")->execute([$id]);

$stmt = $pdo->prepare("DELETE FROM cash_transactions WHERE id = ?");
$result = $stmt->execute([$id]);

if ($result) {
    logActivity('delete', 'cash', $id, "Menghapus transaksi: {$transaction['transaction_number']}", $transaction);
    setFlash('success', 'Transaksi berhasil dihapus');
} else {
    setFlash('danger', 'Gagal menghapus transaksi');
}

redirect(APP_URL . '/cash');
