<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/chart_data.php';

header('Content-Type: application/json');

requireLogin();

$pdo = getDBConnection();
$chartType = $_GET['chart'] ?? '';
$period = $_GET['period'] ?? '6';

$response = ['success' => false, 'data' => null];

try {
    switch ($chartType) {
        case 'revenue_expense':
            $months = intval($period);
            $data = getMonthlyRevenueExpenseTrend($pdo, $months);
            $response['data'] = prepareChartData($data, 'month_label', ['revenue', 'expense']);
            $response['success'] = true;
            break;
            
        case 'cash_flow':
            if (strpos($period, 'd') !== false) {
                $days = intval($period);
                $query = "
                    SELECT 
                        DATE_FORMAT(ct.transaction_date, '%d %b') as date_label,
                        COALESCE(SUM(CASE WHEN ct.type = 'masuk' THEN ct.amount ELSE 0 END), 0) as cash_in,
                        COALESCE(SUM(CASE WHEN ct.type = 'keluar' THEN ct.amount ELSE 0 END), 0) as cash_out
                    FROM cash_transactions ct
                    WHERE ct.status = 'approved'
                        AND ct.transaction_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                    GROUP BY ct.transaction_date, date_label
                    ORDER BY ct.transaction_date ASC
                ";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$days]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response['data'] = prepareChartData($data, 'date_label', ['cash_in', 'cash_out']);
            } else {
                $months = intval($period);
                $data = getCashFlowMonthlyTrend($pdo, $months);
                $response['data'] = prepareChartData($data, 'month_label', ['cash_in', 'cash_out']);
            }
            $response['success'] = true;
            break;
            
        default:
            $response['error'] = 'Invalid chart type';
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
