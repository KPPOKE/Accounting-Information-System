<?php
$pageTitle = 'Jurnal Umum';
$breadcrumb = [['title' => 'Jurnal Umum']];
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('journal_view');

$pdo = getDBConnection();

$search = sanitize($_GET['search'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo = sanitize($_GET['date_to'] ?? '');

$sql = "
    SELECT je.*, u.full_name as creator_name, ua.full_name as approver_name
    FROM journal_entries je 
    LEFT JOIN users u ON je.created_by = u.id 
    LEFT JOIN users ua ON je.approved_by = ua.id
    WHERE 1=1
";
$params = [];

if ($search) {
    $sql .= " AND (je.entry_number LIKE ? OR je.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statusFilter) {
    $sql .= " AND je.status = ?";
    $params[] = $statusFilter;
}

if ($dateFrom) {
    $sql .= " AND je.entry_date >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $sql .= " AND je.entry_date <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY je.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$journals = $stmt->fetchAll();

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Jurnal Umum</h3>
        <?php if (hasPermission('journal_create')): ?>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Tambah Jurnal
        </a>
        <?php endif; ?>
    </div>
    
    <div class="filter-bar">
        <form method="GET" class="d-flex gap-2" style="width: 100%; flex-wrap: wrap;">
            <div class="filter-item search-box">
                <i class="fas fa-search search-box-icon"></i>
                <input type="text" name="search" class="form-control" 
                       placeholder="Cari no. bukti atau keterangan..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-item">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="filter-item">
                <input type="date" name="date_from" class="form-control" 
                       value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="Dari Tanggal">
            </div>
            <div class="filter-item">
                <input type="date" name="date_to" class="form-control" 
                       value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="Sampai Tanggal">
            </div>
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="index.php" class="btn btn-outline">Reset</a>
        </form>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>No. Bukti</th>
                    <th>Tanggal</th>
                    <th>Keterangan</th>
                    <th>Jumlah</th>
                    <th>Dibuat Oleh</th>
                    <th>Status</th>
                    <th style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($journals)): ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-book"></i></div>
                            <div class="empty-state-title">Tidak ada data</div>
                            <div class="empty-state-text">Belum ada jurnal yang terdaftar</div>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($journals as $journal): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($journal['entry_number']); ?></strong></td>
                    <td><?php echo formatDate($journal['entry_date']); ?></td>
                    <td><?php echo htmlspecialchars(substr($journal['description'], 0, 50)); ?><?php echo strlen($journal['description']) > 50 ? '...' : ''; ?></td>
                    <td><strong><?php echo formatCurrency($journal['total_amount']); ?></strong></td>
                    <td><?php echo htmlspecialchars($journal['creator_name']); ?></td>
                    <td>
                        <?php
                        $badgeClass = $journal['status'] === 'approved' ? 'success' : 
                                     ($journal['status'] === 'rejected' ? 'danger' : 'warning');
                        ?>
                        <span class="badge badge-<?php echo $badgeClass; ?>">
                            <?php echo ucfirst($journal['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="view.php?id=<?php echo $journal['id']; ?>" class="btn btn-sm btn-secondary btn-icon" title="Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (hasPermission('journal_edit') && $journal['status'] === 'pending'): ?>
                            <a href="edit.php?id=<?php echo $journal['id']; ?>" class="btn btn-sm btn-secondary btn-icon" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('journal_approve') && $journal['status'] === 'pending'): ?>
                            <a href="approve.php?id=<?php echo $journal['id']; ?>&action=approve" 
                               class="btn btn-sm btn-success btn-icon" title="Approve"
                               onclick="return confirm('Setujui jurnal ini?');">
                                <i class="fas fa-check"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('journal_delete') && $journal['status'] === 'pending'): ?>
                            <a href="delete.php?id=<?php echo $journal['id']; ?>" 
                               class="btn btn-sm btn-danger btn-icon" title="Hapus"
                               onclick="return confirm('Yakin ingin menghapus jurnal ini?');">
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
