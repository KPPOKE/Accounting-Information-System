<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('journal_delete');

$pdo = getDBConnection();
$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM journal_entries WHERE id = ? AND status = 'pending'");
$stmt->execute([$id]);
$journal = $stmt->fetch();

if (!$journal) {
    setFlash('danger', 'Jurnal tidak ditemukan atau sudah diproses');
    redirect(APP_URL . '/modules/journal/');
}

$attachStmt = $pdo->prepare("SELECT filepath FROM attachments WHERE journal_entry_id = ?");
$attachStmt->execute([$id]);
$attachments = $attachStmt->fetchAll();

foreach ($attachments as $att) {
    deleteFile($att['filepath']);
}

$stmt = $pdo->prepare("DELETE FROM journal_entries WHERE id = ?");
$result = $stmt->execute([$id]);

if ($result) {
    logActivity('delete', 'journal', $id, "Menghapus jurnal: {$journal['entry_number']}", $journal);
    setFlash('success', 'Jurnal berhasil dihapus');
} else {
    setFlash('danger', 'Gagal menghapus jurnal');
}

redirect(APP_URL . '/modules/journal/');
