<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('journal_create');

$pageTitle = 'Tambah Jurnal';
$breadcrumb = [
    ['title' => 'Jurnal Umum', 'url' => APP_URL . '/journal'],
    ['title' => 'Tambah Jurnal']
];

$pdo = getDBConnection();
$accounts = $pdo->query("SELECT a.*, ac.name as category_name FROM accounts a LEFT JOIN account_categories ac ON a.category_id = ac.id WHERE a.is_active = 1 ORDER BY a.code")->fetchAll();
$entryNumber = generateNumber('JU', 'journal_entries', 'entry_number');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entryDate = sanitize($_POST['entry_date'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $details = $_POST['details'] ?? [];

    if (empty($entryDate)) $errors[] = 'Tanggal harus diisi';
    if (empty($description)) $errors[] = 'Keterangan harus diisi';
    if (count($details) < 2) $errors[] = 'Minimal harus ada 2 baris detail';

    $totalDebit = 0;
    $totalCredit = 0;
    $validDetails = [];

    foreach ($details as $detail) {
        $accountId = intval($detail['account_id'] ?? 0);
        $debit = floatval(str_replace(['.', ','], ['', '.'], $detail['debit'] ?? 0));
        $credit = floatval(str_replace(['.', ','], ['', '.'], $detail['credit'] ?? 0));

        if ($accountId > 0 && ($debit > 0 || $credit > 0)) {
            $validDetails[] = [
                'account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'description' => sanitize($detail['description'] ?? '')
            ];
            $totalDebit += $debit;
            $totalCredit += $credit;
        }
    }

    if (count($validDetails) < 2) {
        $errors[] = 'Minimal harus ada 2 baris detail yang valid';
    }

    if ($totalDebit != $totalCredit) {
        $errors[] = 'Total debit dan kredit harus sama (Debit: ' . formatCurrency($totalDebit) . ', Kredit: ' . formatCurrency($totalCredit) . ')';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO journal_entries (entry_number, entry_date, description, total_amount, created_by, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$entryNumber, $entryDate, $description, $totalDebit, $_SESSION['user_id']]);
            $journalId = $pdo->lastInsertId();

            $detailStmt = $pdo->prepare("
                INSERT INTO journal_details (journal_entry_id, account_id, debit, credit, description) 
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($validDetails as $detail) {
                $detailStmt->execute([
                    $journalId, 
                    $detail['account_id'], 
                    $detail['debit'], 
                    $detail['credit'], 
                    $detail['description']
                ]);
            }

            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload = uploadFile($_FILES['attachment'], 'journal');
                if ($upload['success']) {
                    $attachStmt = $pdo->prepare("
                        INSERT INTO attachments (filename, original_name, filepath, filetype, filesize, journal_entry_id, uploaded_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $attachStmt->execute([
                        $upload['filename'],
                        $upload['original_name'],
                        $upload['filepath'],
                        $upload['filetype'],
                        $upload['filesize'],
                        $journalId,
                        $_SESSION['user_id']
                    ]);
                }
            }

            $pdo->commit();
            logActivity('create', 'journal', $journalId, "Membuat jurnal baru: $entryNumber");
            setFlash('success', 'Jurnal berhasil dibuat');
            redirect(APP_URL . '/journal');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan data: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../components/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Form Tambah Jurnal Umum</h3>
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

        <form method="POST" action="" enctype="multipart/form-data" id="journalForm">
            <?php echo csrfField(); ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">No. Bukti</label>
                    <input type="text" class="form-control" value="<?php echo $entryNumber; ?>" readonly 
                           style="background: var(--gray-100);">
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" name="entry_date" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['entry_date'] ?? date('Y-m-d')); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Keterangan <span class="text-danger">*</span></label>
                <textarea name="description" class="form-control" rows="2" required
                          placeholder="Deskripsi transaksi"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div id="balanceInfo" class="alert alert-info">
                <i class="fas fa-info-circle"></i> Masukkan detail jurnal (minimal 2 baris)
            </div>

            <div class="table-container" style="margin-bottom: 20px;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 35%;">Akun</th>
                            <th style="width: 20%;">Keterangan</th>
                            <th style="width: 18%;">Debit</th>
                            <th style="width: 18%;">Kredit</th>
                            <th style="width: 9%;"></th>
                        </tr>
                    </thead>
                    <tbody id="journalDetailsBody">
                        <tr>
                            <td>
                                <select name="details[0][account_id]" class="form-select" required>
                                    <option value="">Pilih Akun</option>
                                    <?php foreach ($accounts as $acc): ?>
                                    <option value="<?php echo $acc['id']; ?>">
                                        <?php echo htmlspecialchars($acc['code'] . ' - ' . $acc['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="details[0][description]" class="form-control" placeholder="Keterangan">
                            </td>
                            <td>
                                <input type="text" name="details[0][debit]" class="form-control debit-input" 
                                       placeholder="0" onkeyup="formatNumber(this); validateDoubleEntry();">
                            </td>
                            <td>
                                <input type="text" name="details[0][credit]" class="form-control credit-input" 
                                       placeholder="0" onkeyup="formatNumber(this); validateDoubleEntry();">
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>
                                <select name="details[1][account_id]" class="form-select" required>
                                    <option value="">Pilih Akun</option>
                                    <?php foreach ($accounts as $acc): ?>
                                    <option value="<?php echo $acc['id']; ?>">
                                        <?php echo htmlspecialchars($acc['code'] . ' - ' . $acc['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="details[1][description]" class="form-control" placeholder="Keterangan">
                            </td>
                            <td>
                                <input type="text" name="details[1][debit]" class="form-control debit-input" 
                                       placeholder="0" onkeyup="formatNumber(this); validateDoubleEntry();">
                            </td>
                            <td>
                                <input type="text" name="details[1][credit]" class="form-control credit-input" 
                                       placeholder="0" onkeyup="formatNumber(this); validateDoubleEntry();">
                            </td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="button" class="btn btn-secondary" onclick="addJournalRow()">
                <i class="fas fa-plus"></i> Tambah Baris
            </button>

            <div class="form-group" style="margin-top: 20px;">
                <label class="form-label">Lampiran Bukti Transaksi</label>
                <input type="file" name="attachment" class="form-control" 
                       accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
                <span class="form-text">Format: JPG, PNG, PDF, DOC, XLS (Maks. 5MB)</span>
            </div>

            <div class="d-flex gap-2" style="margin-top: 24px;">
                <button type="submit" id="submitBtn" class="btn btn-primary" disabled>
                    <i class="fas fa-save"></i>
                    Simpan Jurnal
                </button>
                <a href="<?php echo APP_URL; ?>/journal" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
