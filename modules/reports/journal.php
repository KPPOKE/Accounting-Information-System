<?php
$pageTitle = 'Laporan Jurnal Umum';
$breadcrumb = [['title' => 'Laporan'], ['title' => 'Jurnal Umum']];
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('reports_view');

$pdo = getDBConnection();

// Smart default: Use latest transaction date if no filter is set
if (!isset($_GET['date_from']) || !isset($_GET['date_to'])) {
    $latestStmt = $pdo->query("SELECT MIN(entry_date) as earliest, MAX(entry_date) as latest FROM journal_entries");
    $dateRange = $latestStmt->fetch();
    
    $dateFrom = sanitize($_GET['date_from'] ?? ($dateRange['earliest'] ?? date('Y-m-01')));
    $dateTo = sanitize($_GET['date_to'] ?? ($dateRange['latest'] ?? date('Y-m-d')));
} else {
    $dateFrom = sanitize($_GET['date_from']);
    $dateTo = sanitize($_GET['date_to']);
}

$status = sanitize($_GET['status'] ?? 'all');

$query = "SELECT je.*, u.full_name as creator_name 
          FROM journal_entries je 
          LEFT JOIN users u ON je.created_by = u.id 
          WHERE DATE(je.entry_date) BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];

if ($status !== 'all') {
    $query .= " AND je.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY je.entry_date ASC, je.entry_number ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
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
            <a href="<?php echo APP_URL; ?>/api/export_pdf.php?type=journal&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>&status=<?php echo $status; ?>" 
               class="btn btn-danger btn-sm" target="_blank">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
            <a href="<?php echo APP_URL; ?>/api/export_excel.php?type=journal&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>&status=<?php echo $status; ?>" 
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
            <div class="form-group mb-0" style="min-width: 150px;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control" style="height: 45px; padding: 8px 12px; padding-right: 35px; font-size: 14px; background-position: right 8px center;">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
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
        <?php
        // Get available date range to help user
        $rangeStmt = $pdo->query("SELECT MIN(entry_date) as earliest, MAX(entry_date) as latest FROM journal_entries");
        $dateRange = $rangeStmt->fetch();
        ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-book"></i></div>
            <div class="empty-state-title">Tidak ada data</div>
            <div class="empty-state-text">
                Tidak ada jurnal pada periode <?php echo formatDate($dateFrom); ?> - <?php echo formatDate($dateTo); ?>
                <?php if ($status !== 'all'): ?>
                    dengan status <strong><?php echo $status; ?></strong>
                <?php endif; ?>
            </div>
            <?php if ($dateRange && $dateRange['earliest']): ?>
            <div class="empty-state-text" style="margin-top: 12px; padding: 12px; background: var(--warning); border-radius: 6px;">
                <i class="fas fa-info-circle"></i> 
                <strong>Data tersedia:</strong> <?php echo formatDate($dateRange['earliest']); ?> sampai <?php echo formatDate($dateRange['latest']); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <?php 
        $grandTotalDebit = 0;
        $grandTotalCredit = 0;
        foreach ($journals as $journal): 
        ?>
        <div style="margin-bottom: 24px; padding: 16px; background: var(--card-bg); border-radius: 8px; border: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <div>
                    <strong>
                        <a href="<?php echo APP_URL; ?>/journal/view?id=<?php echo HashIdHelper::encode($journal['id']); ?>" style="text-decoration: none; color: var(--text-primary);">
                            <?php echo htmlspecialchars($journal['entry_number']); ?>
                        </a>
                    </strong>
                    <span style="color: var(--text-secondary); margin-left: 12px;"><?php echo formatDate($journal['entry_date']); ?></span>
                </div>
                <div style="color: var(--text-secondary);"><?php echo htmlspecialchars($journal['description']); ?></div>
            </div>

            <table style="width: 100%; background: var(--bg-secondary); border: 1px solid var(--border-color);">
                <thead>
                    <tr style="background: var(--table-header-bg);">
                        <th style="width: 15%; padding: 12px; color: var(--text-secondary); text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; border-bottom: 2px solid var(--border-color);">Kode Akun</th>
                        <th style="width: 35%; padding: 12px; color: var(--text-secondary); text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; border-bottom: 2px solid var(--border-color);">Nama Akun</th>
                        <th style="width: 25%; padding: 12px; color: var(--text-secondary); text-align: right; font-size: 12px; font-weight: 600; text-transform: uppercase; border-bottom: 2px solid var(--border-color);">Debit</th>
                        <th style="width: 25%; padding: 12px; color: var(--text-secondary); text-align: right; font-size: 12px; font-weight: 600; text-transform: uppercase; border-bottom: 2px solid var(--border-color);">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $journalDetails = $details[$journal['id']] ?? [];
                    foreach ($journalDetails as $d): 
                        $grandTotalDebit += $d['debit'];
                        $grandTotalCredit += $d['credit'];
                    ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px; color: var(--text-primary);"><?php echo htmlspecialchars($d['account_code']); ?></td>
                        <td style="padding: 12px; color: var(--text-primary);"><?php echo htmlspecialchars($d['account_name']); ?></td>
                        <td style="padding: 12px; color: var(--text-primary); text-align: right;"><?php echo $d['debit'] > 0 ? formatCurrency($d['debit']) : '-'; ?></td>
                        <td style="padding: 12px; color: var(--text-primary); text-align: right;"><?php echo $d['credit'] > 0 ? formatCurrency($d['credit']) : '-'; ?></td>
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
