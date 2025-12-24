<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/chart_data.php';

$pdo = getDBConnection();

$statsTotalAccounts = $pdo->query("SELECT COUNT(*) as count FROM accounts WHERE is_active = 1")->fetch()['count'];
$statsPendingJournals = $pdo->query("SELECT COUNT(*) as count FROM journal_entries WHERE status = 'pending'")->fetch()['count'];
$statsTotalCashIn = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM cash_transactions WHERE type = 'masuk' AND status = 'approved'")->fetch()['total'];
$statsTotalCashOut = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM cash_transactions WHERE type = 'keluar' AND status = 'approved'")->fetch()['total'];

$revenueExpenseTrend = getMonthlyRevenueExpenseTrend($pdo, 12);
$categoryDistribution = getAccountCategoryDistribution($pdo);
$cashFlowTrend = getCashFlowMonthlyTrend($pdo, 12);

$revenueExpenseData = prepareChartData($revenueExpenseTrend, 'month_label', ['revenue', 'expense']);
$categoryData = [
    'labels' => array_column($categoryDistribution, 'category'),
    'values' => array_column($categoryDistribution, 'account_count')
];
$cashFlowData = prepareChartData($cashFlowTrend, 'month_label', ['cash_in', 'cash_out']);


$recentJournals = $pdo->query("
    SELECT je.*, u.full_name as creator_name 
    FROM journal_entries je 
    LEFT JOIN users u ON je.created_by = u.id 
    ORDER BY je.created_at DESC LIMIT 5
")->fetchAll();

$recentCash = $pdo->query("
    SELECT ct.*, a.name as account_name, u.full_name as creator_name 
    FROM cash_transactions ct 
    LEFT JOIN accounts a ON ct.account_id = a.id 
    LEFT JOIN users u ON ct.created_by = u.id 
    ORDER BY ct.created_at DESC LIMIT 5
")->fetchAll();

require_once __DIR__ . '/../../components/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-list-alt"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($statsTotalAccounts); ?></div>
            <div class="stat-label">Total Akun Aktif</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($statsPendingJournals); ?></div>
            <div class="stat-label">Jurnal Pending</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-arrow-down"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatCurrency($statsTotalCashIn); ?></div>
            <div class="stat-label">Total Kas Masuk</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon danger">
            <i class="fas fa-arrow-up"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatCurrency($statsTotalCashOut); ?></div>
            <div class="stat-label">Total Kas Keluar</div>
        </div>
    </div>
</div>

<div class="charts-section" style="margin-bottom: 32px;">
    <div class="grid-3">
        <div class="card">
            <div class="card-header chart-header-with-filter">
                <h3 class="card-title">
                    <i class="fas fa-chart-line" style="color: var(--primary); margin-right: 8px;"></i>
                    Trend Pendapatan & Beban
                </h3>
                <div class="custom-dropdown" id="revenueDropdown" data-chart="revenue_expense">
                    <button class="dropdown-trigger" type="button">
                        <span class="dropdown-value">6 Bulan</span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="dropdown-menu">
                        <div class="dropdown-item" data-value="3">3 Bulan</div>
                        <div class="dropdown-item active" data-value="6">6 Bulan</div>
                        <div class="dropdown-item" data-value="12">12 Bulan</div>
                        <div class="dropdown-item" data-value="24">24 Bulan</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 300px;">
                    <canvas id="revenueExpenseChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie" style="color: var(--info); margin-right: 8px;"></i>
                    Distribusi Kategori Akun
                </h3>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 300px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header chart-header-with-filter">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar" style="color: var(--success); margin-right: 8px;"></i>
                    Arus Kas Bulanan
                </h3>
                <div class="custom-dropdown" id="cashFlowDropdown" data-chart="cash_flow">
                    <button class="dropdown-trigger" type="button">
                        <span class="dropdown-value">6 Bulan</span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="dropdown-menu">
                        <div class="dropdown-item" data-value="7">7 Hari</div>
                        <div class="dropdown-item" data-value="30">30 Hari</div>
                        <div class="dropdown-item" data-value="3">3 Bulan</div>
                        <div class="dropdown-item active" data-value="6">6 Bulan</div>
                        <div class="dropdown-item" data-value="12">12 Bulan</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 300px;">
                    <canvas id="cashFlowChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="revenueExpenseData">
<?php echo json_encode($revenueExpenseData); ?>
</script>
<script type="application/json" id="categoryData">
<?php echo json_encode($categoryData); ?>
</script>
<script type="application/json" id="cashFlowData">
<?php echo json_encode($cashFlowData); ?>
</script>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-book" style="color: var(--primary); margin-right: 8px;"></i>
                Jurnal Terbaru
            </h3>
            <?php if (hasPermission('journal_view')): ?>
            <a href="<?php echo APP_URL; ?>/journal" class="btn btn-sm btn-outline">
                Lihat Semua
            </a>
            <?php endif; ?>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No. Bukti</th>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentJournals)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted" style="padding: 40px;">
                                Belum ada data jurnal
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recentJournals as $journal): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($journal['entry_number']); ?></strong></td>
                            <td><?php echo formatDate($journal['entry_date']); ?></td>
                            <td><?php echo formatCurrency($journal['total_amount']); ?></td>
                            <td>
                                <?php
                                $badgeClass = $journal['status'] === 'approved' ? 'success' : 
                                             ($journal['status'] === 'rejected' ? 'danger' : 'warning');
                                ?>
                                <span class="badge badge-<?php echo $badgeClass; ?>">
                                    <?php echo ucfirst($journal['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-money-bill-wave" style="color: var(--success); margin-right: 8px;"></i>
                Transaksi Kas Terbaru
            </h3>
            <?php if (hasPermission('cash_view')): ?>
            <a href="<?php echo APP_URL; ?>/cash" class="btn btn-sm btn-outline">
                Lihat Semua
            </a>
            <?php endif; ?>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Tipe</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentCash)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted" style="padding: 40px;">
                                Belum ada transaksi kas
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recentCash as $cash): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($cash['transaction_number']); ?></strong></td>
                            <td>
                                <span class="badge badge-<?php echo $cash['type'] === 'masuk' ? 'success' : 'danger'; ?>">
                                    <?php echo $cash['type'] === 'masuk' ? 'Kas Masuk' : 'Kas Keluar'; ?>
                                </span>
                            </td>
                            <td><?php echo formatCurrency($cash['amount']); ?></td>
                            <td>
                                <?php
                                $badgeClass = $cash['status'] === 'approved' ? 'success' : 
                                             ($cash['status'] === 'rejected' ? 'danger' : 'warning');
                                ?>
                                <span class="badge badge-<?php echo $badgeClass; ?>">
                                    <?php echo ucfirst($cash['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
