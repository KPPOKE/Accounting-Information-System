<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/permissions.php';
requirePermission('reports_export');

$type = sanitize($_GET['type'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? date('Y-m-01'));
$dateTo = sanitize($_GET['date_to'] ?? date('Y-m-d'));
$accountId = intval($_GET['account_id'] ?? 0);
$asOfDate = sanitize($_GET['as_of_date'] ?? date('Y-m-d'));

$pdo = getDBConnection();

header('Content-Type: text/html; charset=utf-8');

$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan - Finacore</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        h1 { text-align: center; margin-bottom: 5px; }
        h2 { text-align: center; font-size: 14px; margin-bottom: 20px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .total-row { background: #333; color: white; font-weight: bold; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>';

switch ($type) {
    case 'journal':
        $stmt = $pdo->prepare("
            SELECT je.*, u.full_name as creator_name FROM journal_entries je 
            LEFT JOIN users u ON je.created_by = u.id
            WHERE je.status = 'approved' AND je.entry_date BETWEEN ? AND ?
            ORDER BY je.entry_date ASC
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $journals = $stmt->fetchAll();

        $html .= "<h1>LAPORAN JURNAL UMUM</h1>";
        $html .= "<h2>Periode: " . date('d/m/Y', strtotime($dateFrom)) . " - " . date('d/m/Y', strtotime($dateTo)) . "</h2>";

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($journals as $j) {
            $detailStmt = $pdo->prepare("
                SELECT jd.*, a.code, a.name FROM journal_details jd 
                LEFT JOIN accounts a ON jd.account_id = a.id WHERE jd.journal_entry_id = ?
            ");
            $detailStmt->execute([$j['id']]);
            $details = $detailStmt->fetchAll();

            $html .= "<p><strong>{$j['entry_number']}</strong> - " . date('d/m/Y', strtotime($j['entry_date'])) . " - {$j['description']}</p>";
            $html .= "<table><tr><th>Kode</th><th>Nama Akun</th><th class='text-right'>Debit</th><th class='text-right'>Kredit</th></tr>";

            foreach ($details as $d) {
                $totalDebit += $d['debit'];
                $totalCredit += $d['credit'];
                $html .= "<tr><td>{$d['code']}</td><td>{$d['name']}</td>";
                $html .= "<td class='text-right'>" . ($d['debit'] > 0 ? number_format($d['debit'], 0, ',', '.') : '-') . "</td>";
                $html .= "<td class='text-right'>" . ($d['credit'] > 0 ? number_format($d['credit'], 0, ',', '.') : '-') . "</td></tr>";
            }
            $html .= "</table><br>";
        }

        $html .= "<table><tr class='total-row'><td colspan='2'>GRAND TOTAL</td>";
        $html .= "<td class='text-right'>" . number_format($totalDebit, 0, ',', '.') . "</td>";
        $html .= "<td class='text-right'>" . number_format($totalCredit, 0, ',', '.') . "</td></tr></table>";
        break;

    default:
        $html .= "<p>Tipe laporan tidak valid</p>";
}

$html .= "<script>window.print();</script></body></html>";
echo $html;
