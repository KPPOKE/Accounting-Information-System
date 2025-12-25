<?php
$pageTitle = 'Database Backup';
$breadcrumb = [['title' => 'Database Backup']];
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$user = getCurrentUser();
if ($user['role_id'] != 1) {
    setFlash('danger', 'Akses ditolak. Hanya Admin yang dapat mengakses fitur ini.');
    redirect(APP_URL . '/dashboard');
}

require_once __DIR__ . '/../../config/backup.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            $result = createDatabaseBackup();
            if ($result['success']) {
                $message = "Backup berhasil dibuat: {$result['filename']} ({$result['tables']} tables)";
                $messageType = 'success';
                logActivity('create', 'backup', null, 'Created database backup: ' . $result['filename']);
            } else {
                $message = 'Gagal membuat backup: ' . ($result['error'] ?? 'Unknown error');
                $messageType = 'danger';
            }
        } elseif ($action === 'delete') {
            $filename = $_POST['filename'] ?? '';
            if (deleteBackup($filename)) {
                $message = "Backup berhasil dihapus: $filename";
                $messageType = 'success';
                logActivity('delete', 'backup', null, 'Deleted database backup: ' . $filename);
            } else {
                $message = 'Gagal menghapus backup';
                $messageType = 'danger';
            }
        }
    }
}

if (isset($_GET['download'])) {
    $filename = $_GET['download'];
    downloadBackup($filename);
}

$backups = getBackupList();

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Database Backup</h3>
        <form method="POST" style="margin: 0;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="create">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-download"></i> Buat Backup Sekarang
            </button>
        </form>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>" style="margin: 16px; margin-bottom: 0;">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <div class="card-body">
        <div class="alert alert-info" style="margin-bottom: 24px;">
            <i class="fas fa-info-circle"></i>
            <strong>Info:</strong> Backup database disimpan di folder <code>/backups</code> yang dilindungi dari akses web.
        </div>

        <?php if (empty($backups)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-database"></i></div>
            <div class="empty-state-title">Belum ada backup</div>
            <div class="empty-state-text">Klik tombol "Buat Backup Sekarang" untuk membuat backup pertama</div>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 45%;">Nama File</th>
                        <th style="width: 15%; text-align: center;">Ukuran</th>
                        <th style="width: 25%; text-align: center;">Tanggal Dibuat</th>
                        <th style="width: 15%; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td>
                            <i class="fas fa-file-code" style="color: var(--primary); margin-right: 8px;"></i>
                            <span style="word-break: break-all;"><?php echo htmlspecialchars($backup['filename']); ?></span>
                        </td>
                        <td style="text-align: center;"><?php echo formatFileSize($backup['size']); ?></td>
                        <td style="text-align: center;"><?php echo date('d M Y H:i:s', $backup['created']); ?></td>
                        <td style="text-align: center;">
                            <div class="d-flex gap-2" style="justify-content: center;">
                                <a href="?download=<?php echo urlencode($backup['filename']); ?>" 
                                   class="btn btn-sm btn-success" title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                                <form method="POST" style="margin: 0; display: inline-block;" 
                                      onsubmit="return confirm('Yakin ingin menghapus backup ini?')">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="alert alert-warning" style="margin-top: 24px; display: flex; align-items: flex-start; gap: 12px;">
            <i class="fas fa-lightbulb" style="margin-top: 2px;"></i>
            <div>
                <strong>Tips:</strong> Sebaiknya download dan simpan backup di lokasi terpisah (cloud storage, external drive) untuk keamanan data.
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
