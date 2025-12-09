<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('users_edit');

$pageTitle = 'Edit Pengguna';
$breadcrumb = [
    ['title' => 'Kelola Pengguna', 'url' => APP_URL . '/modules/users/'],
    ['title' => 'Edit Pengguna']
];

$id = intval($_GET['id'] ?? 0);
$user = getUserById($id);

if (!$user) {
    setFlash('danger', 'Pengguna tidak ditemukan');
    redirect(APP_URL . '/modules/users/');
}

$roles = getAllRoles();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => sanitize($_POST['username'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'full_name' => sanitize($_POST['full_name'] ?? ''),
        'role_id' => intval($_POST['role_id'] ?? 0),
        'status' => sanitize($_POST['status'] ?? 'active')
    ];
    
    if (empty($data['username'])) $errors[] = 'Username harus diisi';
    if (empty($data['email'])) $errors[] = 'Email harus diisi';
    if (!empty($data['password']) && strlen($data['password']) < 6) $errors[] = 'Password minimal 6 karakter';
    if (empty($data['full_name'])) $errors[] = 'Nama lengkap harus diisi';
    if ($data['role_id'] <= 0) $errors[] = 'Role harus dipilih';
    
    $pdo = getDBConnection();
    $check = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $check->execute([$data['username'], $data['email'], $id]);
    if ($check->fetch()) $errors[] = 'Username atau email sudah digunakan';
    
    if (empty($errors)) {
        if (updateUser($id, $data)) {
            setFlash('success', 'Pengguna berhasil diupdate');
            redirect(APP_URL . '/modules/users/');
        } else {
            $errors[] = 'Gagal menyimpan data';
        }
    }
}

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Form Edit Pengguna</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? $user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? $user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password" class="form-control">
                    <span class="form-text">Kosongkan jika tidak ingin mengubah password</span>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role_id" class="form-select" required>
                        <option value="">Pilih Role</option>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>" 
                                <?php echo (($_POST['role_id'] ?? $user['role_id']) == $role['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($role['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo (($_POST['status'] ?? $user['status']) === 'active') ? 'selected' : ''; ?>>Aktif</option>
                        <option value="inactive" <?php echo (($_POST['status'] ?? $user['status']) === 'inactive') ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                </div>
            </div>
            
            <div class="d-flex gap-2" style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="<?php echo APP_URL; ?>/modules/users/" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
