
<?php
$pageTitle = 'Laporan Arus Kas';
$breadcrumb = [['title' => 'Laporan'], ['title' => 'Arus Kas']];
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('reports_view');

$pdo = getDBConnection();

$dateFrom = sanitize($_GET['date_from'] ?? date('Y-m-01'));
$dateTo = sanitize($_GET['date_to'] ?? date('Y-m-d'));

$stmt = $pdo->prepare("
    SELECT ct.*, a.code as account_code, a.name as account_name, u.full_name as creator_name
    FROM cash_transactions ct
    LEFT JOIN accounts a ON ct.account_id = a.id
    LEFT JOIN users u ON ct.created_by = u.id
    WHERE ct.status = 'approved' AND DATE(ct.transaction_date) BETWEEN ? AND ?
    ORDER BY ct.transaction_date ASC, ct.id ASC
");
$stmt->execute([$dateFrom, $dateTo]);
$transactions = $stmt->fetchAll();

$totalIn = 0;
$totalOut = 0;

foreach ($transactions as $trx) {
    if ($trx['type'] === 'masuk') {
        $totalIn += $trx['amount'];
    } else {
        $totalOut += $trx['amount'];
    }
}

$netCashFlow = $totalIn - $totalOut;

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Laporan Arus Kas</h3>
        <?php if (hasPermission('reports_export')): ?>
        <a href="<?php echo APP_URL; ?>/api/export_excel.php?type=cash_flow&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>" 
           class="btn btn-success btn-sm">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
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
            <h2 style="margin-bottom: 8px;">LAPORAN ARUS KAS</h2>
            <p style="color: var(--gray-500);">Periode: <?php echo formatDate($dateFrom); ?> - <?php echo formatDate($dateTo); ?></p>
        </div>

        <div class="stats-grid" style="margin-bottom: 24px;">
            <div class="stat-card">
                <div class="stat-icon success"><i class="fas fa-arrow-down"></i></div>
                <div class="stat-content">
                    <div class="stat-value text-success"><?php echo formatCurrency($totalIn); ?></div>
                    <div class="stat-label">Total Kas Masuk</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon danger"><i class="fas fa-arrow-up"></i></div>
                <div class="stat-content">
                    <div class="stat-value text-danger"><?php echo formatCurrency($totalOut); ?></div>
                    <div class="stat-label">Total Kas Keluar</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon <?php echo $netCashFlow >= 0 ? 'primary' : 'warning'; ?>">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value <?php echo $netCashFlow >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo formatCurrency($netCashFlow); ?>
                    </div>
                    <div class="stat-label">Arus Kas Bersih</div>
                </div>
            </div>
        </div>

        <?php if (empty($transactions)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-exchange-alt"></i></div>
            <div class="empty-state-title">Tidak ada data</div>
            <div class="empty-state-text">Tidak ada transaksi kas pada periode ini</div>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Transaksi</th>
                        <th>Akun</th>
                        <th>Keterangan</th>
                        <th class="text-right">Kas Masuk</th>
                        <th class="text-right">Kas Keluar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $trx): ?>
                    <tr>
                        <td><?php echo formatDate($trx['transaction_date']); ?></td>
                        <td><strong><?php echo htmlspecialchars($trx['transaction_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($trx['account_name']); ?></td>
                        <td><?php echo htmlspecialchars($trx['description']); ?></td>
                        <td class="text-right text-success">
                            <?php echo $trx['type'] === 'masuk' ? formatCurrency($trx['amount']) : '-'; ?>
                        </td>
                        <td class="text-right text-danger">
                            <?php echo $trx['type'] === 'keluar' ? formatCurrency($trx['amount']) : '-'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background: var(--bg-tertiary); font-weight: 600;">
                    <tr>
                        <td colspan="4" class="text-right" style="color: var(--text-primary);">TOTAL</td>
                        <td class="text-right text-success"><?php echo formatCurrency($totalIn); ?></td>
                        <td class="text-right text-danger"><?php echo formatCurrency($totalOut); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
