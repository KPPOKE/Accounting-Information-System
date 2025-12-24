<?php
$pageTitle = 'Buku Besar';
$breadcrumb = [['title' => 'Laporan'], ['title' => 'Buku Besar']];
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('reports_view');

$pdo = getDBConnection();

$accountId = HashIdHelper::decode($_GET['account_id'] ?? '') ?: 0;
$dateFrom = sanitize($_GET['date_from'] ?? date('Y-m-01'));
$dateTo = sanitize($_GET['date_to'] ?? date('Y-m-d'));

$accounts = $pdo->query("SELECT a.*, ac.name as category_name FROM accounts a LEFT JOIN account_categories ac ON a.category_id = ac.id WHERE a.is_active = 1 ORDER BY a.code")->fetchAll();

$ledgerData = [];
$selectedAccount = null;

if ($accountId > 0) {
    $stmt = $pdo->prepare("SELECT a.*, ac.normal_balance FROM accounts a LEFT JOIN account_categories ac ON a.category_id = ac.id WHERE a.id = ?");
    $stmt->execute([$accountId]);
    $selectedAccount = $stmt->fetch();

    if ($selectedAccount) {
        $stmt = $pdo->prepare("
            SELECT je.entry_number, je.entry_date, je.description as journal_desc, 
                   jd.debit, jd.credit, jd.description, je.id as journal_id
            FROM journal_details jd
            JOIN journal_entries je ON jd.journal_entry_id = je.id
            WHERE jd.account_id = ? AND je.status = 'approved' AND DATE(je.entry_date) BETWEEN ? AND ?
            ORDER BY je.entry_date ASC, je.id ASC
        ");
        $stmt->execute([$accountId, $dateFrom, $dateTo]);
        $ledgerData = $stmt->fetchAll();
    }
}

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Buku Besar</h3>
        <?php if (hasPermission('reports_export') && $selectedAccount): ?>
        <a href="<?php echo APP_URL; ?>/api/export_excel.php?type=ledger&account_id=<?php echo HashIdHelper::encode($accountId); ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>" 
           class="btn btn-success btn-sm">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <?php endif; ?>
    </div>

    <div class="filter-bar">
        <form method="GET" class="d-flex gap-2" style="align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group mb-0" style="min-width: 250px;">
                <label class="form-label">Pilih Akun</label>
                <select name="account_id" class="form-select" required>
                    <option value="">-- Pilih Akun --</option>
                    <?php foreach ($accounts as $acc): ?>
                    <option value="<?php echo HashIdHelper::encode($acc['id']); ?>" <?php echo $accountId == $acc['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($acc['code'] . ' - ' . $acc['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
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
        <?php if (!$selectedAccount): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-book-open"></i></div>
            <div class="empty-state-title">Pilih Akun</div>
            <div class="empty-state-text">Silakan pilih akun untuk melihat buku besar</div>
        </div>
        <?php else: ?>
        <div class="text-center" style="margin-bottom: 24px;">
            <h2 style="margin-bottom: 8px;">BUKU BESAR</h2>
            <h4><?php echo htmlspecialchars($selectedAccount['code'] . ' - ' . $selectedAccount['name']); ?></h4>
            <p style="color: var(--gray-500);">Periode: <?php echo formatDate($dateFrom); ?> - <?php echo formatDate($dateTo); ?></p>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Bukti</th>
                        <th>Keterangan</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Kredit</th>
                        <th class="text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background: var(--gray-50);">
                        <td colspan="5"><strong>Saldo Awal</strong></td>
                        <td class="text-right"><strong><?php echo formatCurrency($selectedAccount['opening_balance']); ?></strong></td>
                    </tr>
                    <?php 
                    $saldo = $selectedAccount['opening_balance'];
                    $isDebitNormal = $selectedAccount['normal_balance'] === 'debit';

                    foreach ($ledgerData as $row): 
                        if ($isDebitNormal) {
                            $saldo = $saldo + $row['debit'] - $row['credit'];
                        } else {
                            $saldo = $saldo - $row['debit'] + $row['credit'];
                        }
                    ?>
                    <tr>
                        <td><?php echo formatDate($row['entry_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['entry_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['description'] ?: $row['journal_desc']); ?></td>
                        <td class="text-right"><?php echo $row['debit'] > 0 ? formatCurrency($row['debit']) : '-'; ?></td>
                        <td class="text-right"><?php echo $row['credit'] > 0 ? formatCurrency($row['credit']) : '-'; ?></td>
                        <td class="text-right <?php echo $saldo < 0 ? 'text-danger' : ''; ?>"><?php echo formatCurrency($saldo); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background: var(--primary); color: white;">
                    <tr>
                        <td colspan="5"><strong>Saldo Akhir</strong></td>
                        <td class="text-right"><strong><?php echo formatCurrency($saldo); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
