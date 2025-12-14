<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('cash_edit');

$pageTitle = 'Edit Transaksi Kas';
$breadcrumb = [
    ['title' => 'Kas Masuk/Keluar', 'url' => APP_URL . '/modules/cash/'],
    ['title' => 'Edit Transaksi']
];

$pdo = getDBConnection();
$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM cash_transactions WHERE id = ? AND status = 'pending'");
$stmt->execute([$id]);
$transaction = $stmt->fetch();

if (!$transaction) {
    setFlash('danger', 'Transaksi tidak ditemukan atau sudah diproses');
    redirect(APP_URL . '/modules/cash/');
}

$cashAccounts = $pdo->query("
    SELECT a.* FROM accounts a 
    LEFT JOIN account_categories ac ON a.category_id = ac.id 
    WHERE ac.type = 'aset' AND a.is_active = 1 
    ORDER BY a.code
")->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionDate = sanitize($_POST['transaction_date'] ?? '');
    $accountId = intval($_POST['account_id'] ?? 0);
    $amount = floatval(str_replace(['.', ','], ['', '.'], $_POST['amount'] ?? 0));
    $description = sanitize($_POST['description'] ?? '');
    $reference = sanitize($_POST['reference'] ?? '');

    if (empty($transactionDate)) $errors[] = 'Tanggal harus diisi';
    if ($accountId <= 0) $errors[] = 'Akun harus dipilih';
    if ($amount <= 0) $errors[] = 'Jumlah harus lebih dari 0';

    if (empty($errors)) {
        $oldData = $transaction;
        $stmt = $pdo->prepare("
            UPDATE cash_transactions SET transaction_date = ?, account_id = ?, amount = ?, description = ?, reference = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $result = $stmt->execute([$transactionDate, $accountId, $amount, $description, $reference, $id]);

        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['attachment'], 'cash');
            if ($upload['success']) {
                $attachStmt = $pdo->prepare("
                    INSERT INTO attachments (filename, original_name, filepath, filetype, filesize, cash_transaction_id, uploaded_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $attachStmt->execute([
                    $upload['filename'], $upload['original_name'], $upload['filepath'],
                    $upload['filetype'], $upload['filesize'], $id, $_SESSION['user_id']
                ]);
            }
        }

        if ($result) {
            logActivity('update', 'cash', $id, "Mengupdate transaksi: {$transaction['transaction_number']}", $oldData, $_POST);
            setFlash('success', 'Transaksi berhasil diupdate');
            redirect(APP_URL . '/modules/cash/');
        } else {
            $errors[] = 'Gagal menyimpan data';
        }
    }
}

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Form Edit Transaksi Kas</h3>
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
                    <label class="form-label">Tipe Transaksi</label>
                    <input type="text" class="form-control" value="<?php echo $transaction['type'] === 'masuk' ? 'Kas Masuk' : 'Kas Keluar'; ?>" readonly style="background: var(--gray-100);">
                </div>
                <div class="form-group">
                    <label class="form-label">No. Transaksi</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($transaction['transaction_number']); ?>" readonly style="background: var(--gray-100);">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" name="transaction_date" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['transaction_date'] ?? $transaction['transaction_date']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Akun Kas/Bank <span class="text-danger">*</span></label>
                    <select name="account_id" class="form-select" required>
                        <option value="">Pilih Akun</option>
                        <?php foreach ($cashAccounts as $acc): ?>
                        <option value="<?php echo $acc['id']; ?>" <?php echo (($_POST['account_id'] ?? $transaction['account_id']) == $acc['id']) ? 'selected' : ''; ?>>
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
                           value="<?php echo number_format($_POST['amount'] ?? $transaction['amount'], 0, ',', '.'); ?>"
                           onkeyup="formatNumber(this);" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Referensi</label>
                    <input type="text" name="reference" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['reference'] ?? $transaction['reference']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Keterangan</label>
                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? $transaction['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Tambah Lampiran</label>
                <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
            </div>

            <div class="d-flex gap-2" style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="<?php echo APP_URL; ?>/modules/cash/" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
