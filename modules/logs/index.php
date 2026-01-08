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
        <form method="GET" class="filter-form">
            <div class="filter-group-left">
                <div class="custom-dropdown" data-submit>
                    <input type="hidden" name="module" value="<?php echo $moduleFilter; ?>">
                    <button class="dropdown-trigger" type="button">
                        <span class="dropdown-value"><?php echo $moduleFilter ? ($modules[$moduleFilter] ?? $moduleFilter) : 'Semua Modul'; ?></span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="dropdown-menu">
                        <div class="dropdown-item<?php echo empty($moduleFilter) ? ' active' : ''; ?>" data-value="">Semua Modul</div>
                        <?php foreach ($modules as $key => $label): ?>
                        <div class="dropdown-item<?php echo $moduleFilter === $key ? ' active' : ''; ?>" data-value="<?php echo $key; ?>"><?php echo $label; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="custom-dropdown" data-submit>
                    <input type="hidden" name="user_id" value="<?php echo $userFilter; ?>">
                    <button class="dropdown-trigger" type="button">
                        <span class="dropdown-value"><?php 
                            if ($userFilter) {
                                foreach ($users as $user) {
                                    if ($user['id'] == $userFilter) {
                                        echo htmlspecialchars($user['full_name']);
                                        break;
                                    }
                                }
                            } else {
                                echo 'Semua User';
                            }
                        ?></span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="dropdown-menu">
                        <div class="dropdown-item<?php echo empty($userFilter) ? ' active' : ''; ?>" data-value="">Semua User</div>
                        <?php foreach ($users as $user): ?>
                        <div class="dropdown-item<?php echo $userFilter == $user['id'] ? ' active' : ''; ?>" data-value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="filter-group-right">
                <input type="date" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>" placeholder="Dari Tanggal">
                <input type="date" name="date_to" class="form-control" value="<?php echo $dateTo; ?>" placeholder="Sampai Tanggal">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Filter</button>
                <a href="<?php echo APP_URL; ?>/logs" class="btn btn-outline">Reset</a>
            </div>
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
