<?php

require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/permissions.php';
requirePermission('reports_export');
require_once __DIR__ . '/../includes/HashIdHelper.php';

$type = sanitize($_GET['type'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? date('Y-m-01'));
$dateTo = sanitize($_GET['date_to'] ?? date('Y-m-d'));
$decodedId = HashIdHelper::decode($_GET['account_id'] ?? '');
$accountId = $decodedId !== false ? $decodedId : 0;
$asOfDate = sanitize($_GET['as_of_date'] ?? date('Y-m-d'));

$pdo = getDBConnection();

$reportNames = [
    'journal' => 'Laporan_Jurnal',
    'ledger' => 'Buku_Besar',
    'trial_balance' => 'Neraca_Saldo',
    'cash_flow' => 'Arus_Kas',
    'income_expense' => 'Laba_Rugi'
];
$reportName = $reportNames[$type] ?? 'Export';

switch ($type) {
    case 'trial_balance':
        $filename = "Finacore_{$reportName}_{$asOfDate}.xls";
        break;
    case 'ledger':
        $accountCode = '';
        if ($accountId > 0) {
            $accStmt = $pdo->prepare("SELECT code FROM accounts WHERE id = ?");
            $accStmt->execute([$accountId]);
            $acc = $accStmt->fetch();
            $accountCode = $acc ? "_{$acc['code']}" : '';
        }
        $filename = "Finacore_{$reportName}{$accountCode}_{$dateFrom}_to_{$dateTo}.xls";
        break;
    default:
        $filename = "Finacore_{$reportName}_{$dateFrom}_to_{$dateTo}.xls";
        break;
}

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head><meta charset="UTF-8"><style>td{mso-number-format:\@;}</style></head><body>';
echo '<table border="1">';

switch ($type) {
    case 'journal':
        $status = sanitize($_GET['status'] ?? 'all');
        
        $query = "SELECT je.*, u.full_name as creator_name FROM journal_entries je 
                  LEFT JOIN users u ON je.created_by = u.id 
                  WHERE DATE(je.entry_date) BETWEEN ? AND ?";
        $params = [$dateFrom, $dateTo];

        if ($status !== 'all') {
            $query .= " AND je.status = ?";
            $params[] = $status;
        }

        $query .= " ORDER BY je.entry_date ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $journals = $stmt->fetchAll();

        echo "<tr><th colspan='5'>LAPORAN JURNAL UMUM</th></tr>";
        echo "<tr><th colspan='5'>Periode: " . date('d/m/Y', strtotime($dateFrom)) . " - " . date('d/m/Y', strtotime($dateTo)) . "</th></tr>";
        echo "<tr><th>No Bukti</th><th>Tanggal</th><th>Akun</th><th>Debit</th><th>Kredit</th></tr>";

        foreach ($journals as $j) {
            $detailStmt = $pdo->prepare("
                SELECT jd.*, a.code, a.name FROM journal_details jd 
                LEFT JOIN accounts a ON jd.account_id = a.id WHERE jd.journal_entry_id = ?
            ");
            $detailStmt->execute([$j['id']]);
            $details = $detailStmt->fetchAll();

            foreach ($details as $d) {
                echo "<tr>";
                echo "<td>{$j['entry_number']}</td>";
                echo "<td>" . date('d/m/Y', strtotime($j['entry_date'])) . "</td>";
                echo "<td>{$d['code']} - {$d['name']}</td>";
                echo "<td>" . ($d['debit'] > 0 ? $d['debit'] : '') . "</td>";
                echo "<td>" . ($d['credit'] > 0 ? $d['credit'] : '') . "</td>";
                echo "</tr>";
            }
        }
        break;

    case 'ledger':
        $stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
        $stmt->execute([$accountId]);
        $account = $stmt->fetch();

        if ($account) {
            $stmt = $pdo->prepare("
                SELECT je.entry_number, je.entry_date, jd.debit, jd.credit, jd.description
                FROM journal_details jd
                JOIN journal_entries je ON jd.journal_entry_id = je.id
                WHERE jd.account_id = ? AND je.status = 'approved' AND DATE(je.entry_date) BETWEEN ? AND ?
                ORDER BY je.entry_date ASC
            ");
            $stmt->execute([$accountId, $dateFrom, $dateTo]);
            $data = $stmt->fetchAll();

            echo "<tr><th colspan='5'>BUKU BESAR - {$account['code']} - {$account['name']}</th></tr>";
            echo "<tr><th>Tanggal</th><th>No Bukti</th><th>Debit</th><th>Kredit</th><th>Saldo</th></tr>";

            $saldo = $account['opening_balance'];
            echo "<tr><td colspan='4'>Saldo Awal</td><td>{$saldo}</td></tr>";

            foreach ($data as $row) {
                $saldo = $saldo + $row['debit'] - $row['credit'];
                echo "<tr>";
                echo "<td>" . date('d/m/Y', strtotime($row['entry_date'])) . "</td>";
                echo "<td>{$row['entry_number']}</td>";
                echo "<td>" . ($row['debit'] > 0 ? $row['debit'] : '') . "</td>";
                echo "<td>" . ($row['credit'] > 0 ? $row['credit'] : '') . "</td>";
                echo "<td>{$saldo}</td>";
                echo "</tr>";
            }
        }
        break;

    case 'trial_balance':
        $stmt = $pdo->prepare("
            SELECT a.code, a.name, a.opening_balance, ac.normal_balance,
                   COALESCE(SUM(trans.debit), 0) as total_debit, 
                   COALESCE(SUM(trans.credit), 0) as total_credit
            FROM accounts a
            LEFT JOIN account_categories ac ON a.category_id = ac.id
            LEFT JOIN (
                SELECT jd.account_id, jd.debit, jd.credit
                FROM journal_details jd
                JOIN journal_entries je ON jd.journal_entry_id = je.id
                WHERE je.status = 'approved' AND DATE(je.entry_date) <= ?
            ) trans ON a.id = trans.account_id
            WHERE a.is_active = 1
            GROUP BY a.id, a.code, a.name, a.opening_balance, ac.normal_balance
            ORDER BY a.code
        ");
        $stmt->execute([$asOfDate]);
        $accounts = $stmt->fetchAll();

        echo "<tr><th colspan='4'>NERACA SALDO per " . date('d/m/Y', strtotime($asOfDate)) . "</th></tr>";
        echo "<tr><th>Kode</th><th>Nama Akun</th><th>Debit</th><th>Kredit</th></tr>";

        foreach ($accounts as $acc) {
            $saldo = $acc['opening_balance'] + $acc['total_debit'] - $acc['total_credit'];

            if ($acc['normal_balance'] === 'debit') {
                $debit = $saldo >= 0 ? $saldo : 0;
                $credit = $saldo < 0 ? abs($saldo) : 0;
            } else {
                $saldo = $acc['opening_balance'] - $acc['total_debit'] + $acc['total_credit'];
                $credit = $saldo >= 0 ? $saldo : 0;
                $debit = $saldo < 0 ? abs($saldo) : 0;
            }

            if ($debit != 0 || $credit != 0) {
                $debitDisplay = $debit > 0 ? number_format($debit, 0, ',', '.') : '';
                $creditDisplay = $credit > 0 ? number_format($credit, 0, ',', '.') : '';
                echo "<tr><td>{$acc['code']}</td><td>{$acc['name']}</td><td>{$debitDisplay}</td><td>{$creditDisplay}</td></tr>";
            }
        }
        break;

    case 'cash_flow':
        $stmt = $pdo->prepare("
            SELECT ct.*, a.name as account_name FROM cash_transactions ct
            LEFT JOIN accounts a ON ct.account_id = a.id
            WHERE ct.status = 'approved' AND DATE(ct.transaction_date) BETWEEN ? AND ?
            ORDER BY ct.transaction_date ASC
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $transactions = $stmt->fetchAll();

        echo "<tr><th colspan='5'>LAPORAN ARUS KAS</th></tr>";
        echo "<tr><th>Tanggal</th><th>No Transaksi</th><th>Akun</th><th>Kas Masuk</th><th>Kas Keluar</th></tr>";

        foreach ($transactions as $trx) {
            echo "<tr>";
            echo "<td>" . date('d/m/Y', strtotime($trx['transaction_date'])) . "</td>";
            echo "<td>{$trx['transaction_number']}</td>";
            echo "<td>{$trx['account_name']}</td>";
            echo "<td>" . ($trx['type'] === 'masuk' ? $trx['amount'] : '') . "</td>";
            echo "<td>" . ($trx['type'] === 'keluar' ? $trx['amount'] : '') . "</td>";
            echo "</tr>";
        }
        break;

    case 'income_expense':
        $stmt = $pdo->prepare("
            SELECT a.code, a.name, ac.type,
                   COALESCE(SUM(trans.credit), 0) as total_credit, 
                   COALESCE(SUM(trans.debit), 0) as total_debit
            FROM accounts a
            LEFT JOIN account_categories ac ON a.category_id = ac.id
            LEFT JOIN (
                SELECT jd.account_id, jd.debit, jd.credit
                FROM journal_details jd
                JOIN journal_entries je ON jd.journal_entry_id = je.id
                WHERE je.status = 'approved' AND DATE(je.entry_date) BETWEEN ? AND ?
            ) trans ON a.id = trans.account_id
            WHERE ac.type IN ('pendapatan', 'beban')
            GROUP BY a.id, a.code, a.name, ac.type
            HAVING total_credit > 0 OR total_debit > 0
            ORDER BY ac.type DESC, a.code
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $data = $stmt->fetchAll();

        echo "<tr><th colspan='3'>LAPORAN LABA RUGI</th></tr>";
        echo "<tr><th>Kode</th><th>Nama</th><th>Jumlah</th></tr>";
        echo "<tr><td colspan='3'><b>PENDAPATAN</b></td></tr>";

        $totalPendapatan = 0;
        $totalBeban = 0;

        foreach ($data as $row) {
            if ($row['type'] === 'pendapatan') {
                $amount = $row['total_credit'] - $row['total_debit'];
                $totalPendapatan += $amount;
                echo "<tr><td>{$row['code']}</td><td>{$row['name']}</td><td>{$amount}</td></tr>";
            }
        }

        echo "<tr><td colspan='2'><b>Total Pendapatan</b></td><td><b>{$totalPendapatan}</b></td></tr>";
        echo "<tr><td colspan='3'><b>BEBAN</b></td></tr>";

        foreach ($data as $row) {
            if ($row['type'] === 'beban') {
                $amount = $row['total_debit'] - $row['total_credit'];
                $totalBeban += $amount;
                echo "<tr><td>{$row['code']}</td><td>{$row['name']}</td><td>{$amount}</td></tr>";
            }
        }

        echo "<tr><td colspan='2'><b>Total Beban</b></td><td><b>{$totalBeban}</b></td></tr>";
        $labaRugi = $totalPendapatan - $totalBeban;
        echo "<tr><td colspan='2'><b>" . ($labaRugi >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH') . "</b></td><td><b>" . abs($labaRugi) . "</b></td></tr>";
        break;
}

echo '</table></body></html>';
