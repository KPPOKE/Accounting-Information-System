<?php
$pageTitle = 'Chart of Accounts';
$breadcrumb = [['title' => 'Chart of Accounts']];
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('accounts_view');

$pdo = getDBConnection();

$search = sanitize($_GET['search'] ?? '');
$categoryFilter = sanitize($_GET['category'] ?? '');

$sql = "
    SELECT a.*, ac.name as category_name, ac.type as category_type 
    FROM accounts a 
    LEFT JOIN account_categories ac ON a.category_id = ac.id 
    WHERE 1=1
";
$params = [];

if ($search) {
    $sql .= " AND (a.code LIKE ? OR a.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categoryFilter) {
    $sql .= " AND a.category_id = ?";
    $params[] = $categoryFilter;
}

$sql .= " ORDER BY a.code ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$accounts = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM account_categories ORDER BY code")->fetchAll();

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Akun Perkiraan</h3>
        <?php if (hasPermission('accounts_create')): ?>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Tambah Akun
        </a>
        <?php endif; ?>
    </div>
    
    <div class="filter-bar">
        <form method="GET" class="d-flex gap-2" style="width: 100%; flex-wrap: wrap;">
            <div class="filter-item search-box">
                <i class="fas fa-search search-box-icon"></i>
                <input type="text" name="search" class="form-control" 
                       placeholder="Cari kode atau nama akun..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-item">
                <select name="category" class="form-select">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-filter"></i>
                Filter
            </button>
            <a href="index.php" class="btn btn-outline">Reset</a>
        </form>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Akun</th>
                    <th>Kategori</th>
                    <th>Tipe</th>
                    <th>Saldo</th>
                    <th>Status</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accounts)): ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-folder-open"></i></div>
                            <div class="empty-state-title">Tidak ada data</div>
                            <div class="empty-state-text">Belum ada akun yang terdaftar</div>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($accounts as $account): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($account['code']); ?></strong></td>
                    <td><?php echo htmlspecialchars($account['name']); ?></td>
                    <td><?php echo htmlspecialchars($account['category_name']); ?></td>
                    <td>
                        <?php
                        $typeLabels = [
                            'aset' => ['Aset', 'primary'],
                            'liabilitas' => ['Liabilitas', 'warning'],
                            'ekuitas' => ['Ekuitas', 'info'],
                            'pendapatan' => ['Pendapatan', 'success'],
                            'beban' => ['Beban', 'danger']
                        ];
                        $type = $typeLabels[$account['category_type']] ?? ['Unknown', 'secondary'];
                        ?>
                        <span class="badge badge-<?php echo $type[1]; ?>"><?php echo $type[0]; ?></span>
                    </td>
                    <td class="<?php echo $account['current_balance'] < 0 ? 'text-danger' : ''; ?>">
                        <?php echo formatCurrency($account['current_balance']); ?>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $account['is_active'] ? 'success' : 'secondary'; ?>">
                            <?php echo $account['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <?php if (hasPermission('accounts_edit')): ?>
                            <a href="edit.php?id=<?php echo $account['id']; ?>" class="btn btn-sm btn-secondary btn-icon" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('accounts_delete')): ?>
                            <a href="#" 
                               class="btn btn-sm btn-danger btn-icon" title="Hapus"
                               onclick="confirmDelete('Yakin ingin menghapus akun ini? Data yang dihapus tidak dapat dikembalikan.', function() { window.location.href='delete.php?id=<?php echo $account['id']; ?>'; }); return false;">
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
