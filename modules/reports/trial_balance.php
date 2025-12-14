<?php
$pageTitle = 'Neraca Saldo';
$breadcrumb = [['title' => 'Laporan'], ['title' => 'Neraca Saldo']];
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('reports_view');

$pdo = getDBConnection();

$asOfDate = sanitize($_GET['as_of_date'] ?? date('Y-m-d'));

$stmt = $pdo->prepare("
    SELECT a.id, a.code, a.name, a.opening_balance, 
           ac.name as category_name, ac.type as category_type, ac.normal_balance,
           COALESCE(SUM(jd.debit), 0) as total_debit,
           COALESCE(SUM(jd.credit), 0) as total_credit
    FROM accounts a
    LEFT JOIN account_categories ac ON a.category_id = ac.id
    LEFT JOIN journal_details jd ON a.id = jd.account_id
    LEFT JOIN journal_entries je ON jd.journal_entry_id = je.id 
        AND je.status = 'approved' AND je.entry_date <= ?
    WHERE a.is_active = 1
    GROUP BY a.id, a.code, a.name, a.opening_balance, ac.name, ac.type, ac.normal_balance
    ORDER BY a.code
");
$stmt->execute([$asOfDate]);
$accounts = $stmt->fetchAll();

$trialBalance = [];
$totalDebit = 0;
$totalCredit = 0;

foreach ($accounts as $acc) {
    $saldo = $acc['opening_balance'] + $acc['total_debit'] - $acc['total_credit'];

    if ($acc['normal_balance'] === 'debit') {
        $debit = $saldo >= 0 ? $saldo : 0;
        $credit = $saldo < 0 ? abs($saldo) : 0;
    } else {
        $saldo = $acc['opening_balance'] - $acc['total_debit'] + $acc['total_credit'];
        $credit = $saldo >= 0 ? $saldo : 0;
        $debit = $saldo < 0 ? abs($saldo) : 0;
    }

    if ($debit != 0 || $credit != 0) {
        $trialBalance[] = [
            'code' => $acc['code'],
            'name' => $acc['name'],
            'category' => $acc['category_name'],
            'type' => $acc['category_type'],
            'debit' => $debit,
            'credit' => $credit
        ];
        $totalDebit += $debit;
        $totalCredit += $credit;
    }
}

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Neraca Saldo</h3>
        <?php if (hasPermission('reports_export')): ?>
        <a href="<?php echo APP_URL; ?>/api/export_excel.php?type=trial_balance&as_of_date=<?php echo $asOfDate; ?>" 
           class="btn btn-success btn-sm">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <?php endif; ?>
    </div>

    <div class="filter-bar">
        <form method="GET" class="d-flex gap-2" style="align-items: flex-end;">
            <div class="form-group mb-0">
                <label class="form-label">Per Tanggal</label>
                <input type="date" name="as_of_date" class="form-control" value="<?php echo $asOfDate; ?>">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Tampilkan
            </button>
        </form>
    </div>

    <div class="card-body">
        <div class="text-center" style="margin-bottom: 24px;">
            <h2 style="margin-bottom: 8px;">NERACA SALDO</h2>
            <p style="color: var(--gray-500);">Per Tanggal: <?php echo formatDate($asOfDate, 'd F Y'); ?></p>
        </div>

        <?php if (empty($trialBalance)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-balance-scale"></i></div>
            <div class="empty-state-title">Tidak ada data</div>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Akun</th>
                        <th>Kategori</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trialBalance as $row): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><span class="badge badge-secondary"><?php echo htmlspecialchars($row['category']); ?></span></td>
                        <td class="text-right"><?php echo $row['debit'] > 0 ? formatCurrency($row['debit']) : '-'; ?></td>
                        <td class="text-right"><?php echo $row['credit'] > 0 ? formatCurrency($row['credit']) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: var(--primary); color: white; font-weight: 600;">
                        <td colspan="3">TOTAL</td>
                        <td class="text-right"><?php echo formatCurrency($totalDebit); ?></td>
                        <td class="text-right"><?php echo formatCurrency($totalCredit); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="alert <?php echo abs($totalDebit - $totalCredit) < 0.01 ? 'alert-success' : 'alert-danger'; ?>" style="margin-top: 24px;">
            <i class="fas fa-<?php echo abs($totalDebit - $totalCredit) < 0.01 ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php if (abs($totalDebit - $totalCredit) < 0.01): ?>
            <span>Neraca Saldo SEIMBANG</span>
            <?php else: ?>
            <span>Neraca Saldo TIDAK SEIMBANG! Selisih: <?php echo formatCurrency(abs($totalDebit - $totalCredit)); ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
