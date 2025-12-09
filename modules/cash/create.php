<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('cash_create');

$pageTitle = 'Tambah Transaksi Kas';
$breadcrumb = [
    ['title' => 'Kas Masuk/Keluar', 'url' => APP_URL . '/modules/cash/'],
    ['title' => 'Tambah Transaksi']
];

$pdo = getDBConnection();
$cashAccounts = $pdo->query("
    SELECT a.* FROM accounts a 
    LEFT JOIN account_categories ac ON a.category_id = ac.id 
    WHERE ac.type = 'aset' AND a.is_active = 1 
    ORDER BY a.code
")->fetchAll();

$type = sanitize($_GET['type'] ?? 'masuk');
$prefix = $type === 'masuk' ? 'KM' : 'KK';
$transactionNumber = generateNumber($prefix, 'cash_transactions', 'transaction_number');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = sanitize($_POST['type'] ?? 'masuk');
    $transactionDate = sanitize($_POST['transaction_date'] ?? '');
    $accountId = intval($_POST['account_id'] ?? 0);
    $amount = floatval(str_replace(['.', ','], ['', '.'], $_POST['amount'] ?? 0));
    $description = sanitize($_POST['description'] ?? '');
    $reference = sanitize($_POST['reference'] ?? '');
    
    if (empty($transactionDate)) $errors[] = 'Tanggal harus diisi';
    if ($accountId <= 0) $errors[] = 'Akun harus dipilih';
    if ($amount <= 0) $errors[] = 'Jumlah harus lebih dari 0';
    
    $prefix = $type === 'masuk' ? 'KM' : 'KK';
    $transactionNumber = generateNumber($prefix, 'cash_transactions', 'transaction_number');
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO cash_transactions (transaction_number, transaction_date, type, account_id, amount, description, reference, created_by, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $result = $stmt->execute([
                $transactionNumber, $transactionDate, $type, $accountId, 
                $amount, $description, $reference, $_SESSION['user_id']
            ]);
            $transactionId = $pdo->lastInsertId();
            
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload = uploadFile($_FILES['attachment'], 'cash');
                if ($upload['success']) {
                    $attachStmt = $pdo->prepare("
                        INSERT INTO attachments (filename, original_name, filepath, filetype, filesize, cash_transaction_id, uploaded_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $attachStmt->execute([
                        $upload['filename'], $upload['original_name'], $upload['filepath'],
                        $upload['filetype'], $upload['filesize'], $transactionId, $_SESSION['user_id']
                    ]);
                }
            }
            
            $pdo->commit();
            logActivity('create', 'cash', $transactionId, "Membuat transaksi kas: $transactionNumber");
            setFlash('success', 'Transaksi kas berhasil dibuat');
            redirect(APP_URL . '/modules/cash/');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan data';
        }
    }
}

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Form Tambah Transaksi Kas</h3>
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
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tipe Transaksi <span class="text-danger">*</span></label>
                    <select name="type" class="form-select" required onchange="window.location.href='create.php?type='+this.value">
                        <option value="masuk" <?php echo $type === 'masuk' ? 'selected' : ''; ?>>Kas Masuk</option>
                        <option value="keluar" <?php echo $type === 'keluar' ? 'selected' : ''; ?>>Kas Keluar</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">No. Transaksi</label>
                    <input type="text" class="form-control" value="<?php echo $transactionNumber; ?>" readonly style="background: var(--gray-100);">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" name="transaction_date" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['transaction_date'] ?? date('Y-m-d')); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Akun Kas/Bank <span class="text-danger">*</span></label>
                    <select name="account_id" class="form-select" required>
                        <option value="">Pilih Akun</option>
                        <?php foreach ($cashAccounts as $acc): ?>
                        <option value="<?php echo $acc['id']; ?>" <?php echo (isset($_POST['account_id']) && $_POST['account_id'] == $acc['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($acc['code'] . ' - ' . $acc['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                    <input type="text" name="amount" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>"
                           placeholder="0" onkeyup="formatNumber(this);" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Referensi</label>
                    <input type="text" name="reference" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['reference'] ?? ''); ?>"
                           placeholder="No. Invoice / No. Kwitansi">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Keterangan</label>
                <textarea name="description" class="form-control" rows="3" 
                          placeholder="Deskripsi transaksi"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Lampiran Bukti</label>
                <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                <span class="form-text">Format: JPG, PNG, PDF (Maks. 5MB)</span>
            </div>
            
            <div class="d-flex gap-2" style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="<?php echo APP_URL; ?>/modules/cash/" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
