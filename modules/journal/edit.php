<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/permissions.php';
requirePermission('journal_edit');

$pageTitle = 'Edit Jurnal';
$breadcrumb = [
    ['title' => 'Jurnal Umum', 'url' => APP_URL . '/modules/journal/'],
    ['title' => 'Edit Jurnal']
];

$pdo = getDBConnection();
$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM journal_entries WHERE id = ? AND status = 'pending'");
$stmt->execute([$id]);
$journal = $stmt->fetch();

if (!$journal) {
    setFlash('danger', 'Jurnal tidak ditemukan atau sudah diproses');
    redirect(APP_URL . '/modules/journal/');
}

$detailsStmt = $pdo->prepare("SELECT * FROM journal_details WHERE journal_entry_id = ?");
$detailsStmt->execute([$id]);
$existingDetails = $detailsStmt->fetchAll();

$accounts = $pdo->query("SELECT a.*, ac.name as category_name FROM accounts a LEFT JOIN account_categories ac ON a.category_id = ac.id WHERE a.is_active = 1 ORDER BY a.code")->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entryDate = sanitize($_POST['entry_date'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $details = $_POST['details'] ?? [];

    if (empty($entryDate)) $errors[] = 'Tanggal harus diisi';
    if (empty($description)) $errors[] = 'Keterangan harus diisi';

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
        $errors[] = 'Total debit dan kredit harus sama';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE journal_entries SET entry_date = ?, description = ?, total_amount = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$entryDate, $description, $totalDebit, $id]);

            $pdo->prepare("DELETE FROM journal_details WHERE journal_entry_id = ?")->execute([$id]);

            $detailStmt = $pdo->prepare("
                INSERT INTO journal_details (journal_entry_id, account_id, debit, credit, description) 
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($validDetails as $detail) {
                $detailStmt->execute([$id, $detail['account_id'], $detail['debit'], $detail['credit'], $detail['description']]);
            }

            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload = uploadFile($_FILES['attachment'], 'journal');
                if ($upload['success']) {
                    $attachStmt = $pdo->prepare("
                        INSERT INTO attachments (filename, original_name, filepath, filetype, filesize, journal_entry_id, uploaded_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $attachStmt->execute([
                        $upload['filename'], $upload['original_name'], $upload['filepath'],
                        $upload['filetype'], $upload['filesize'], $id, $_SESSION['user_id']
                    ]);
                }
            }

            $pdo->commit();
            logActivity('update', 'journal', $id, "Mengupdate jurnal: {$journal['entry_number']}");
            setFlash('success', 'Jurnal berhasil diupdate');
            redirect(APP_URL . '/modules/journal/');

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
        <h3 class="card-title">Form Edit Jurnal</h3>
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
                    <label class="form-label">No. Bukti</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($journal['entry_number']); ?>" readonly style="background: var(--gray-100);">
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" name="entry_date" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['entry_date'] ?? $journal['entry_date']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Keterangan <span class="text-danger">*</span></label>
                <textarea name="description" class="form-control" rows="2" required><?php echo htmlspecialchars($_POST['description'] ?? $journal['description']); ?></textarea>
            </div>

            <div id="balanceInfo" class="alert alert-success">
                <i class="fas fa-check-circle"></i> Balance: Debit dan Kredit seimbang
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
                        <?php foreach ($existingDetails as $idx => $detail): ?>
                        <tr>
                            <td>
                                <select name="details[<?php echo $idx; ?>][account_id]" class="form-select" required>
                                    <option value="">Pilih Akun</option>
                                    <?php foreach ($accounts as $acc): ?>
                                    <option value="<?php echo $acc['id']; ?>" <?php echo $detail['account_id'] == $acc['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($acc['code'] . ' - ' . $acc['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="details[<?php echo $idx; ?>][description]" class="form-control" 
                                       value="<?php echo htmlspecialchars($detail['description']); ?>">
                            </td>
                            <td>
                                <input type="text" name="details[<?php echo $idx; ?>][debit]" class="form-control debit-input" 
                                       value="<?php echo $detail['debit'] > 0 ? number_format($detail['debit'], 0, ',', '.') : ''; ?>"
                                       onkeyup="formatNumber(this); validateDoubleEntry();">
                            </td>
                            <td>
                                <input type="text" name="details[<?php echo $idx; ?>][credit]" class="form-control credit-input" 
                                       value="<?php echo $detail['credit'] > 0 ? number_format($detail['credit'], 0, ',', '.') : ''; ?>"
                                       onkeyup="formatNumber(this); validateDoubleEntry();">
                            </td>
                            <td>
                                <?php if ($idx > 1): ?>
                                <button type="button" class="btn btn-danger btn-icon btn-sm" onclick="removeJournalRow(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <button type="button" class="btn btn-secondary" onclick="addJournalRow()">
                <i class="fas fa-plus"></i> Tambah Baris
            </button>

            <div class="form-group" style="margin-top: 20px;">
                <label class="form-label">Tambah Lampiran</label>
                <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
            </div>

            <div class="d-flex gap-2" style="margin-top: 24px;">
                <button type="submit" id="submitBtn" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="<?php echo APP_URL; ?>/modules/journal/" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() { validateDoubleEntry(); });
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
