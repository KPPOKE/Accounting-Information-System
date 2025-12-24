<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('accounts_edit');

$pageTitle = 'Edit Akun';
$breadcrumb = [
    ['title' => 'Chart of Accounts', 'url' => APP_URL . '/accounts'],
    ['title' => 'Edit Akun']
];

$pdo = getDBConnection();
$decodedId = HashIdHelper::decode($_GET['id'] ?? '');
$id = $decodedId !== false ? $decodedId : 0;

$stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
$stmt->execute([$id]);
$account = $stmt->fetch();

if (!$account) {
    setFlash('danger', 'Akun tidak ditemukan');
    redirect(APP_URL . '/accounts');
}

$categories = $pdo->query("SELECT * FROM account_categories ORDER BY code")->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = sanitize($_POST['code'] ?? '');
    $name = sanitize($_POST['name'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $openingBalance = floatval(str_replace(['.', ','], ['', '.'], $_POST['opening_balance'] ?? 0));
    $description = sanitize($_POST['description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (empty($code)) $errors[] = 'Kode akun harus diisi';
    if (empty($name)) $errors[] = 'Nama akun harus diisi';
    if ($categoryId <= 0) $errors[] = 'Kategori harus dipilih';

    $existCheck = $pdo->prepare("SELECT id FROM accounts WHERE code = ? AND id != ?");
    $existCheck->execute([$code, $id]);
    if ($existCheck->fetch()) $errors[] = 'Kode akun sudah digunakan';

    if (empty($errors)) {
        $oldData = $account;
        $stmt = $pdo->prepare("
            UPDATE accounts SET code = ?, name = ?, category_id = ?, opening_balance = ?, description = ?, is_active = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $result = $stmt->execute([$code, $name, $categoryId, $openingBalance, $description, $isActive, $id]);

        if ($result) {
            logActivity('update', 'accounts', $id, "Mengupdate akun: $code - $name", $oldData, $_POST);
            setFlash('success', 'Akun berhasil diupdate');
            redirect(APP_URL . '/accounts');
        } else {
            $errors[] = 'Gagal menyimpan data';
        }
    }
}

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Form Edit Akun</h3>
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
            <?php echo csrfField(); ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Kode Akun <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['code'] ?? $account['code']); ?>"
                           placeholder="Contoh: 1-1001" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Akun <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? $account['name']); ?>"
                           placeholder="Contoh: Kas Kecil" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                                <?php echo (($_POST['category_id'] ?? $account['category_id']) == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['code'] . ' - ' . $cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Saldo Awal</label>
                    <input type="text" name="opening_balance" class="form-control" 
                           value="<?php echo number_format($_POST['opening_balance'] ?? $account['opening_balance'], 0, ',', '.'); ?>"
                           placeholder="0" onkeyup="formatNumber(this);">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3" 
                          placeholder="Keterangan tambahan"><?php echo htmlspecialchars($_POST['description'] ?? $account['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" 
                           <?php echo (($_POST['is_active'] ?? $account['is_active'])) ? 'checked' : ''; ?>>
                    <span>Akun Aktif</span>
                </label>
            </div>

            <div class="d-flex gap-2" style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update
                </button>
                <a href="<?php echo APP_URL; ?>/accounts" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
