<?php

$pageTitle = 'Kas Masuk/Keluar';
$breadcrumb = [['title' => 'Kas Masuk/Keluar']];
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('cash_view');

$pdo = getDBConnection();

$search = sanitize($_GET['search'] ?? '');
$typeFilter = sanitize($_GET['type'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo = sanitize($_GET['date_to'] ?? '');

$sql = "
    SELECT ct.*, a.code as account_code, a.name as account_name, u.full_name as creator_name
    FROM cash_transactions ct 
    LEFT JOIN accounts a ON ct.account_id = a.id 
    LEFT JOIN users u ON ct.created_by = u.id
    WHERE 1=1
";
$params = [];

if ($search) {
    $sql .= " AND (ct.transaction_number LIKE ? OR ct.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($typeFilter) {
    $sql .= " AND ct.type = ?";
    $params[] = $typeFilter;
}

if ($statusFilter) {
    $sql .= " AND ct.status = ?";
    $params[] = $statusFilter;
}

if ($dateFrom) {
    $sql .= " AND DATE(ct.transaction_date) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $sql .= " AND DATE(ct.transaction_date) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY ct.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Transaksi Kas</h3>
        <?php if (hasPermission('cash_create')): ?>
        <a href="<?php echo APP_URL; ?>/cash/create" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Tambah Transaksi
        </a>
        <?php endif; ?>
    </div>

    <div class="filter-bar">
        <form method="GET" class="d-flex gap-2" style="width: 100%; flex-wrap: wrap;">
            <div class="filter-item search-box">
                <i class="fas fa-search search-box-icon"></i>
                <input type="text" name="search" class="form-control" 
                       placeholder="Cari no. transaksi atau keterangan..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-item">
                <select name="type" class="form-select">
                    <option value="">Semua Tipe</option>
                    <option value="masuk" <?php echo $typeFilter === 'masuk' ? 'selected' : ''; ?>>Kas Masuk</option>
                    <option value="keluar" <?php echo $typeFilter === 'keluar' ? 'selected' : ''; ?>>Kas Keluar</option>
                </select>
            </div>
            <div class="filter-item">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                </select>
            </div>
            <div class="filter-item">
                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            <div class="filter-item">
                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>
            <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Filter</button>
            <a href="<?php echo APP_URL; ?>/cash" class="btn btn-outline">Reset</a>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>No. Transaksi</th>
                    <th>Tanggal</th>
                    <th>Tipe</th>
                    <th>Akun</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="empty-state-title">Tidak ada data</div>
                            <div class="empty-state-text">Belum ada transaksi kas</div>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($transactions as $trx): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($trx['transaction_number']); ?></strong></td>
                    <td><?php echo formatDate($trx['transaction_date']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $trx['type'] === 'masuk' ? 'success' : 'danger'; ?>">
                            <?php echo $trx['type'] === 'masuk' ? 'Kas Masuk' : 'Kas Keluar'; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($trx['account_code'] . ' - ' . $trx['account_name']); ?></td>
                    <td><strong><?php echo formatCurrency($trx['amount']); ?></strong></td>
                    <td>
                        <?php $badgeClass = $trx['status'] === 'approved' ? 'success' : 'warning'; ?>
                        <span class="badge badge-<?php echo $badgeClass; ?>"><?php echo ucfirst($trx['status']); ?></span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <?php if (hasPermission('cash_edit') && $trx['status'] === 'pending'): ?>
                            <a href="<?php echo APP_URL; ?>/cash/edit?id=<?php echo HashIdHelper::encode($trx['id']); ?>" class="btn btn-sm btn-secondary btn-icon" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('cash_delete') && $trx['status'] === 'pending'): ?>
                            <a href="#" 
                               class="btn btn-sm btn-danger btn-icon" title="Hapus"
                               onclick="confirmDelete('Yakin ingin menghapus transaksi kas ini? Data yang dihapus tidak dapat dikembalikan.', function() { window.location.href='<?php echo APP_URL; ?>/cash/delete?id=<?php echo HashIdHelper::encode($trx['id']); ?>'; }); return false;">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
