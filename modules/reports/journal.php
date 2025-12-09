<?php
$pageTitle = 'Laporan Jurnal Umum';
$breadcrumb = [['title' => 'Laporan'], ['title' => 'Jurnal Umum']];
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('reports_view');

$pdo = getDBConnection();

$dateFrom = sanitize($_GET['date_from'] ?? date('Y-m-01'));
$dateTo = sanitize($_GET['date_to'] ?? date('Y-m-d'));

$stmt = $pdo->prepare("
    SELECT je.*, u.full_name as creator_name
    FROM journal_entries je 
    LEFT JOIN users u ON je.created_by = u.id
    WHERE je.status = 'approved' AND je.entry_date BETWEEN ? AND ?
    ORDER BY je.entry_date ASC, je.entry_number ASC
");
$stmt->execute([$dateFrom, $dateTo]);
$journals = $stmt->fetchAll();

$journalIds = array_column($journals, 'id');
$details = [];
if (!empty($journalIds)) {
    $placeholders = implode(',', array_fill(0, count($journalIds), '?'));
    $detailStmt = $pdo->prepare("
        SELECT jd.*, a.code as account_code, a.name as account_name 
        FROM journal_details jd 
        LEFT JOIN accounts a ON jd.account_id = a.id 
        WHERE jd.journal_entry_id IN ($placeholders)
        ORDER BY jd.id
    ");
    $detailStmt->execute($journalIds);
    foreach ($detailStmt->fetchAll() as $d) {
        $details[$d['journal_entry_id']][] = $d;
    }
}

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Laporan Jurnal Umum</h3>
        <?php if (hasPermission('reports_export')): ?>
        <div class="btn-group">
            <a href="<?php echo APP_URL; ?>/api/export_pdf.php?type=journal&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>" 
               class="btn btn-danger btn-sm" target="_blank">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
            <a href="<?php echo APP_URL; ?>/api/export_excel.php?type=journal&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>" 
               class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="filter-bar">
        <form method="GET" class="d-flex gap-2" style="align-items: flex-end;">
            <div class="form-group mb-0">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $dateTo; ?>">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Tampilkan
            </button>
        </form>
    </div>
    
    <div class="card-body">
        <div class="text-center" style="margin-bottom: 24px;">
            <h2 style="margin-bottom: 8px;">JURNAL UMUM</h2>
            <p style="color: var(--gray-500);">Periode: <?php echo formatDate($dateFrom); ?> - <?php echo formatDate($dateTo); ?></p>
        </div>
        
        <?php if (empty($journals)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-book"></i></div>
            <div class="empty-state-title">Tidak ada data</div>
            <div class="empty-state-text">Tidak ada jurnal pada periode ini</div>
        </div>
        <?php else: ?>
        <?php 
        $grandTotalDebit = 0;
        $grandTotalCredit = 0;
        foreach ($journals as $journal): 
        ?>
        <div style="margin-bottom: 24px; padding: 16px; background: var(--gray-50); border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <div>
                    <strong><?php echo htmlspecialchars($journal['entry_number']); ?></strong>
                    <span style="color: var(--gray-500); margin-left: 12px;"><?php echo formatDate($journal['entry_date']); ?></span>
                </div>
                <div style="color: var(--gray-500);"><?php echo htmlspecialchars($journal['description']); ?></div>
            </div>
            
            <table style="width: 100%; background: white;">
                <thead>
                    <tr>
                        <th style="width: 15%;">Kode Akun</th>
                        <th style="width: 35%;">Nama Akun</th>
                        <th style="width: 25%;" class="text-right">Debit</th>
                        <th style="width: 25%;" class="text-right">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $journalDetails = $details[$journal['id']] ?? [];
                    foreach ($journalDetails as $d): 
                        $grandTotalDebit += $d['debit'];
                        $grandTotalCredit += $d['credit'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($d['account_code']); ?></td>
                        <td><?php echo htmlspecialchars($d['account_name']); ?></td>
                        <td class="text-right"><?php echo $d['debit'] > 0 ? formatCurrency($d['debit']) : '-'; ?></td>
                        <td class="text-right"><?php echo $d['credit'] > 0 ? formatCurrency($d['credit']) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
        
        <div style="background: var(--primary); color: white; padding: 16px; border-radius: 8px; display: flex; justify-content: space-between;">
            <strong>GRAND TOTAL</strong>
            <div>
                <span style="margin-right: 48px;">Debit: <?php echo formatCurrency($grandTotalDebit); ?></span>
                <span>Kredit: <?php echo formatCurrency($grandTotalCredit); ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
