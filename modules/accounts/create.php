<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('accounts_create');

$pageTitle = 'Tambah Akun';
$breadcrumb = [
    ['title' => 'Chart of Accounts', 'url' => APP_URL . '/accounts'],
    ['title' => 'Tambah Akun']
];

$pdo = getDBConnection();
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

    $existCheck = $pdo->prepare("SELECT id FROM accounts WHERE code = ?");
    $existCheck->execute([$code]);
    if ($existCheck->fetch()) $errors[] = 'Kode akun sudah digunakan';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO accounts (code, name, category_id, opening_balance, current_balance, description, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([$code, $name, $categoryId, $openingBalance, $openingBalance, $description, $isActive]);

        if ($result) {
            $accountId = $pdo->lastInsertId();
            logActivity('create', 'accounts', $accountId, "Membuat akun baru: $code - $name");
            setFlash('success', 'Akun berhasil ditambahkan');
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
        <h3 class="card-title">Form Tambah Akun Baru</h3>
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
                           value="<?php echo htmlspecialchars($_POST['code'] ?? ''); ?>"
                           placeholder="Contoh: 1-1001" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Akun <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
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
                                <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['code'] . ' - ' . $cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Saldo Awal</label>
                    <input type="text" name="opening_balance" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['opening_balance'] ?? '0'); ?>"
                           placeholder="0" onkeyup="formatNumber(this);">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3" 
                          placeholder="Keterangan tambahan tentang akun ini"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" 
                           <?php echo (!isset($_POST['is_active']) || $_POST['is_active']) ? 'checked' : ''; ?>>
                    <span>Akun Aktif</span>
                </label>
            </div>

            <div class="d-flex gap-2" style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Simpan
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
