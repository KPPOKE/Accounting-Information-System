<?php
$pageTitle = 'Activity Log';
$breadcrumb = [['title' => 'Activity Log']];
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('logs_view');

$pdo = getDBConnection();

$moduleFilter = sanitize($_GET['module'] ?? '');
$userFilter = intval($_GET['user_id'] ?? 0);
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo = sanitize($_GET['date_to'] ?? '');

$filters = [];
if ($moduleFilter) $filters['module'] = $moduleFilter;
if ($userFilter) $filters['user_id'] = $userFilter;
if ($dateFrom) $filters['date_from'] = $dateFrom;
if ($dateTo) $filters['date_to'] = $dateTo;

$logs = getActivityLogs($filters, 100);
$users = getAllUsers();

$modules = [
    'auth' => 'Authentication',
    'users' => 'Pengguna',
    'accounts' => 'Chart of Accounts',
    'journal' => 'Jurnal Umum',
    'cash' => 'Transaksi Kas',
    'reports' => 'Laporan'
];

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Activity Log</h3>
    </div>
    
    <div class="filter-bar">
        <form method="GET" class="d-flex gap-2" style="width: 100%; flex-wrap: wrap;">
            <div class="filter-item">
                <select name="module" class="form-select">
                    <option value="">Semua Modul</option>
                    <?php foreach ($modules as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo $moduleFilter === $key ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <select name="user_id" class="form-select">
                    <option value="">Semua User</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo $userFilter == $user['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <input type="date" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>" placeholder="Dari Tanggal">
            </div>
            <div class="filter-item">
                <input type="date" name="date_to" class="form-control" value="<?php echo $dateTo; ?>" placeholder="Sampai Tanggal">
            </div>
            <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Filter</button>
            <a href="<?php echo APP_URL; ?>/modules/logs/" class="btn btn-outline">Reset</a>
        </form>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 160px;">Waktu</th>
                    <th>User</th>
                    <th>Aksi</th>
                    <th>Modul</th>
                    <th>Keterangan</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-history"></i></div>
                            <div class="empty-state-title">Tidak ada data</div>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td>
                        <div style="font-size: 13px;"><?php echo formatDateTime($log['created_at'], 'd M Y'); ?></div>
                        <div style="font-size: 12px; color: var(--gray-500);"><?php echo formatDateTime($log['created_at'], 'H:i:s'); ?></div>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></strong>
                        <div style="font-size: 12px; color: var(--gray-500);"><?php echo htmlspecialchars($log['username'] ?? '-'); ?></div>
                    </td>
                    <td>
                        <?php
                        $actionBadge = [
                            'login' => 'info',
                            'logout' => 'secondary',
                            'create' => 'success',
                            'update' => 'warning',
                            'delete' => 'danger',
                            'approve' => 'success',
                            'reject' => 'danger'
                        ];
                        $badge = $actionBadge[$log['action']] ?? 'secondary';
                        ?>
                        <span class="badge badge-<?php echo $badge; ?>"><?php echo strtoupper($log['action']); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($modules[$log['module']] ?? $log['module']); ?></td>
                    <td><?php echo htmlspecialchars($log['description']); ?></td>
                    <td style="font-size: 12px; font-family: monospace;"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
