<?php
$pageTitle = 'Kelola Pengguna';
$breadcrumb = [['title' => 'Kelola Pengguna']];
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('users_view');

$users = getAllUsers();
$roles = getAllRoles();

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Pengguna</h3>
        <?php if (hasPermission('users_create')): ?>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Tambah Pengguna
        </a>
        <?php endif; ?>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Login Terakhir</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-users"></i></div>
                            <div class="empty-state-title">Tidak ada data</div>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><span class="badge badge-primary"><?php echo htmlspecialchars($user['role_name']); ?></span></td>
                    <td>
                        <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo $user['status'] === 'active' ? 'Aktif' : 'Nonaktif'; ?>
                        </span>
                    </td>
                    <td><?php echo $user['last_login'] ? formatDateTime($user['last_login']) : '-'; ?></td>
                    <td>
                        <div class="btn-group">
                            <?php if (hasPermission('users_edit')): ?>
                            <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary btn-icon" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('users_delete') && $user['id'] != $_SESSION['user_id']): ?>
                            <a href="delete.php?id=<?php echo $user['id']; ?>" 
                               class="btn btn-sm btn-danger btn-icon" title="Hapus"
                               onclick="return confirm('Yakin ingin menghapus pengguna ini?');">
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
