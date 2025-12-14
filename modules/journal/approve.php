<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('journal_approve');

$pdo = getDBConnection();
$id = intval($_GET['id'] ?? 0);
$action = sanitize($_GET['action'] ?? '');

$stmt = $pdo->prepare("SELECT * FROM journal_entries WHERE id = ? AND status = 'pending'");
$stmt->execute([$id]);
$journal = $stmt->fetch();

if (!$journal) {
    setFlash('danger', 'Jurnal tidak ditemukan atau sudah diproses');
    redirect(APP_URL . '/modules/journal/');
}

if ($action === 'approve') {
    $stmt = $pdo->prepare("UPDATE journal_entries SET status = 'approved', approved_by = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$_SESSION['user_id'], $id]);

    if ($result) {
        logActivity('approve', 'journal', $id, "Menyetujui jurnal: {$journal['entry_number']}");
        setFlash('success', 'Jurnal berhasil disetujui');
    }
} elseif ($action === 'reject') {
    $note = sanitize($_GET['note'] ?? 'Ditolak');
    $stmt = $pdo->prepare("UPDATE journal_entries SET status = 'rejected', approved_by = ?, rejection_note = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$_SESSION['user_id'], $note, $id]);

    if ($result) {
        logActivity('reject', 'journal', $id, "Menolak jurnal: {$journal['entry_number']}");
        setFlash('warning', 'Jurnal telah ditolak');
    }
}

redirect(APP_URL . '/modules/journal/');
