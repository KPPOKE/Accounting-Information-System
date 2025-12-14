<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('journal_view');

$pageTitle = 'Detail Jurnal';
$breadcrumb = [
    ['title' => 'Jurnal Umum', 'url' => APP_URL . '/modules/journal/'],
    ['title' => 'Detail Jurnal']
];

$pdo = getDBConnection();
$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT je.*, u.full_name as creator_name, ua.full_name as approver_name
    FROM journal_entries je 
    LEFT JOIN users u ON je.created_by = u.id 
    LEFT JOIN users ua ON je.approved_by = ua.id  
    WHERE je.id = ?
");
$stmt->execute([$id]);
$journal = $stmt->fetch();

if (!$journal) {
    setFlash('danger', 'Jurnal tidak ditemukan');
    redirect(APP_URL . '/modules/journal/');
}

$detailsStmt = $pdo->prepare("
    SELECT jd.*, a.code as account_code, a.name as account_name 
    FROM journal_details jd 
    LEFT JOIN accounts a ON jd.account_id = a.id 
    WHERE jd.journal_entry_id = ?
");
$detailsStmt->execute([$id]);
$details = $detailsStmt->fetchAll();

$attachmentStmt = $pdo->prepare("SELECT * FROM attachments WHERE journal_entry_id = ?");
$attachmentStmt->execute([$id]);
$attachments = $attachmentStmt->fetchAll();

require_once __DIR__ . '/../../components/header.php';
?>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informasi Jurnal</h3>
            <?php
            $badgeClass = $journal['status'] === 'approved' ? 'success' : 
                         ($journal['status'] === 'rejected' ? 'danger' : 'warning');
            ?>
            <span class="badge badge-<?php echo $badgeClass; ?>" style="font-size: 14px; padding: 8px 16px;">
                <?php echo ucfirst($journal['status']); ?>
            </span>
        </div>
        <div class="card-body">
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 8px 0; color: var(--gray-500); width: 40%;">No. Bukti</td>
                    <td style="padding: 8px 0; font-weight: 600;"><?php echo htmlspecialchars($journal['entry_number']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: var(--gray-500);">Tanggal</td>
                    <td style="padding: 8px 0;"><?php echo formatDate($journal['entry_date'], 'd F Y'); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: var(--gray-500);">Keterangan</td>
                    <td style="padding: 8px 0;"><?php echo htmlspecialchars($journal['description']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: var(--gray-500);">Total</td>
                    <td style="padding: 8px 0; font-weight: 600; font-size: 18px; color: var(--primary);">
                        <?php echo formatCurrency($journal['total_amount']); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: var(--gray-500);">Dibuat Oleh</td>
                    <td style="padding: 8px 0;"><?php echo htmlspecialchars($journal['creator_name']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: var(--gray-500);">Tanggal Dibuat</td>
                    <td style="padding: 8px 0;"><?php echo formatDateTime($journal['created_at']); ?></td>
                </tr>
                <?php if ($journal['approved_by']): ?>
                <tr>
                    <td style="padding: 8px 0; color: var(--gray-500);">Disetujui Oleh</td>
                    <td style="padding: 8px 0;"><?php echo htmlspecialchars($journal['approver_name']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($journal['rejection_note']): ?>
                <tr>
                    <td style="padding: 8px 0; color: var(--gray-500);">Catatan Penolakan</td>
                    <td style="padding: 8px 0; color: var(--danger);"><?php echo htmlspecialchars($journal['rejection_note']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Lampiran</h3>
        </div>
        <div class="card-body">
            <?php if (empty($attachments)): ?>
            <div class="text-center text-muted" style="padding: 40px;">
                <i class="fas fa-paperclip" style="font-size: 32px; opacity: 0.5;"></i>
                <p style="margin-top: 8px;">Tidak ada lampiran</p>
            </div>
            <?php else: ?>
            <?php foreach ($attachments as $att): ?>
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--gray-50); border-radius: 8px; margin-bottom: 8px;">
                <i class="fas fa-file" style="font-size: 24px; color: var(--primary);"></i>
                <div style="flex: 1;">
                    <div style="font-weight: 500;"><?php echo htmlspecialchars($att['original_name']); ?></div>
                    <div style="font-size: 12px; color: var(--gray-500);"><?php echo number_format($att['filesize'] / 1024, 1); ?> KB</div>
                </div>
                <a href="<?php echo APP_URL; ?>/assets/uploads/<?php echo $att['filepath']; ?>" 
                   target="_blank" class="btn btn-sm btn-secondary">
                    <i class="fas fa-download"></i>
                </a>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h3 class="card-title">Detail Transaksi</h3>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Kode Akun</th>
                    <th>Nama Akun</th>
                    <th>Keterangan</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Kredit</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalDebit = 0;
                $totalCredit = 0;
                foreach ($details as $detail): 
                    $totalDebit += $detail['debit'];
                    $totalCredit += $detail['credit'];
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($detail['account_code']); ?></strong></td>
                    <td><?php echo htmlspecialchars($detail['account_name']); ?></td>
                    <td><?php echo htmlspecialchars($detail['description']); ?></td>
                    <td class="text-right"><?php echo $detail['debit'] > 0 ? formatCurrency($detail['debit']) : '-'; ?></td>
                    <td class="text-right"><?php echo $detail['credit'] > 0 ? formatCurrency($detail['credit']) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot style="background: var(--gray-50); font-weight: 600;">
                <tr>
                    <td colspan="3" class="text-right">TOTAL</td>
                    <td class="text-right"><?php echo formatCurrency($totalDebit); ?></td>
                    <td class="text-right"><?php echo formatCurrency($totalCredit); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="d-flex gap-2" style="margin-top: 24px;">
    <a href="<?php echo APP_URL; ?>/modules/journal/" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
    <?php if (hasPermission('journal_edit') && $journal['status'] === 'pending'): ?>
    <a href="edit.php?id=<?php echo $journal['id']; ?>" class="btn btn-primary">
        <i class="fas fa-edit"></i> Edit
    </a>
    <?php endif; ?>
    <?php if (hasPermission('journal_approve') && $journal['status'] === 'pending'): ?>
    <a href="#" 
       class="btn btn-success" onclick="confirmAction('Approve Jurnal?', 'Jurnal yang sudah diapprove tidak bisa diedit lagi.', 'Ya, Approve!', function() { window.location.href='approve.php?id=<?php echo $journal['id']; ?>&action=approve'; }); return false;">
        <i class="fas fa-check"></i> Approve
    </a>
    <a href="#" 
       class="btn btn-danger" onclick="confirmAction('Reject Jurnal?', 'Berikan alasan penolakan kepada pembuat jurnal.', 'Ya, Reject!', function() { window.location.href='approve.php?id=<?php echo $journal['id']; ?>&action=reject'; }); return false;">
        <i class="fas fa-times"></i> Reject
    </a>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
