<?php
$pageTitle = 'Laporan Pendapatan & Beban';
$breadcrumb = [['title' => 'Laporan'], ['title' => 'Pendapatan & Beban']];
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('reports_view');

$pdo = getDBConnection();

$dateFrom = sanitize($_GET['date_from'] ?? date('Y-m-01'));
$dateTo = sanitize($_GET['date_to'] ?? date('Y-m-d'));

$stmt = $pdo->prepare("
    SELECT a.id, a.code, a.name, ac.type as category_type, ac.name as category_name,
           COALESCE(SUM(trans.credit), 0) as total_credit,
           COALESCE(SUM(trans.debit), 0) as total_debit
    FROM accounts a
    LEFT JOIN account_categories ac ON a.category_id = ac.id
    LEFT JOIN (
        SELECT jd.account_id, jd.debit, jd.credit
        FROM journal_details jd
        JOIN journal_entries je ON jd.journal_entry_id = je.id
        WHERE je.status = 'approved' AND DATE(je.entry_date) BETWEEN ? AND ?
    ) trans ON a.id = trans.account_id
    WHERE ac.type IN ('pendapatan', 'beban')
    GROUP BY a.id, a.code, a.name, ac.type, ac.name
    HAVING total_credit > 0 OR total_debit > 0
    ORDER BY ac.type DESC, a.code
");
$stmt->execute([$dateFrom, $dateTo]);
$data = $stmt->fetchAll();

$pendapatan = [];
$beban = [];
$totalPendapatan = 0;
$totalBeban = 0;

foreach ($data as $row) {
    if ($row['category_type'] === 'pendapatan') {
        $amount = $row['total_credit'] - $row['total_debit'];
        if ($amount != 0) {
            $pendapatan[] = ['code' => $row['code'], 'name' => $row['name'], 'amount' => $amount];
            $totalPendapatan += $amount;
        }
    } else {
        $amount = $row['total_debit'] - $row['total_credit'];
        if ($amount != 0) {
            $beban[] = ['code' => $row['code'], 'name' => $row['name'], 'amount' => $amount];
            $totalBeban += $amount;
        }
    }
}

$labaRugi = $totalPendapatan - $totalBeban;

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Laporan Pendapatan & Beban</h3>
        <?php if (hasPermission('reports_export')): ?>
        <div class="export-btn-group">
            <a href="<?php echo APP_URL; ?>/api/export_excel.php?type=income_expense&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>" 
               class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
        </div>
        <?php endif; ?>
    </div>

    <div class="filter-bar">
        <form method="GET" class="report-filter-form">
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
        <div class="report-header text-center">
            <h2>LAPORAN LABA RUGI</h2>
            <p class="report-period">Periode: <?php echo formatDate($dateFrom); ?> - <?php echo formatDate($dateTo); ?></p>
        </div>

        <div class="income-expense-grid">
            <div class="report-section pendapatan-section">
                <h4 class="section-title text-success">
                    <i class="fas fa-arrow-down"></i> PENDAPATAN
                </h4>
                <div class="report-table-wrapper">
                    <table class="report-table">
                        <?php if (empty($pendapatan)): ?>
                        <tr><td colspan="2" class="text-muted text-center empty-row">Tidak ada pendapatan</td></tr>
                        <?php else: ?>
                        <?php foreach ($pendapatan as $p): ?>
                        <tr>
                            <td class="account-name"><?php echo htmlspecialchars($p['code'] . ' - ' . $p['name']); ?></td>
                            <td class="amount"><?php echo formatCurrency($p['amount']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
                <div class="total-row-mobile success">
                    <span class="total-label">Total Pendapatan</span>
                    <span class="total-amount"><?php echo formatCurrency($totalPendapatan); ?></span>
                </div>
            </div>

            <!-- BEBAN -->
            <div class="report-section beban-section">
                <h4 class="section-title text-danger">
                    <i class="fas fa-arrow-up"></i> BEBAN
                </h4>
                <div class="report-table-wrapper">
                    <table class="report-table">
                        <?php if (empty($beban)): ?>
                        <tr><td colspan="2" class="text-muted text-center empty-row">Tidak ada beban</td></tr>
                        <?php else: ?>
                        <?php foreach ($beban as $b): ?>
                        <tr>
                            <td class="account-name"><?php echo htmlspecialchars($b['code'] . ' - ' . $b['name']); ?></td>
                            <td class="amount"><?php echo formatCurrency($b['amount']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
                <div class="total-row-mobile danger">
                    <span class="total-label">Total Beban</span>
                    <span class="total-amount"><?php echo formatCurrency($totalBeban); ?></span>
                </div>
            </div>
        </div>

        <div class="laba-rugi-summary <?php echo $labaRugi >= 0 ? 'laba' : 'rugi'; ?>">
            <h3 class="summary-label"><?php echo $labaRugi >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH'; ?></h3>
            <h2 class="summary-value"><?php echo formatCurrency(abs($labaRugi)); ?></h2>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
