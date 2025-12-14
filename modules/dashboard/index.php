<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDBConnection();

$statsTotalAccounts = $pdo->query("SELECT COUNT(*) as count FROM accounts WHERE is_active = 1")->fetch()['count'];
$statsPendingJournals = $pdo->query("SELECT COUNT(*) as count FROM journal_entries WHERE status = 'pending'")->fetch()['count'];
$statsTotalCashIn = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM cash_transactions WHERE type = 'masuk' AND status = 'approved'")->fetch()['total'];
$statsTotalCashOut = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM cash_transactions WHERE type = 'keluar' AND status = 'approved'")->fetch()['total'];

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

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-book" style="color: var(--primary); margin-right: 8px;"></i>
                Jurnal Terbaru
            </h3>
            <?php if (hasPermission('journal_view')): ?>
            <a href="<?php echo APP_URL; ?>/modules/journal/" class="btn btn-sm btn-outline">
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
            <a href="<?php echo APP_URL; ?>/modules/cash/" class="btn btn-sm btn-outline">
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
