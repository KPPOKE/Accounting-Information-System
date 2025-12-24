<?php

function getMonthlyRevenueExpenseTrend($pdo, $months = 6) {
    $query = "
        SELECT 
            DATE_FORMAT(je.entry_date, '%Y-%m') as month,
            DATE_FORMAT(je.entry_date, '%b %Y') as month_label,
            COALESCE(SUM(CASE 
                WHEN ac.type = 'pendapatan' THEN jd.credit
                ELSE 0 
            END), 0) as revenue,
            COALESCE(SUM(CASE 
                WHEN ac.type = 'beban' THEN jd.debit
                ELSE 0 
            END), 0) as expense
        FROM journal_entries je
        JOIN journal_details jd ON je.id = jd.journal_entry_id
        JOIN accounts a ON jd.account_id = a.id
        JOIN account_categories ac ON a.category_id = ac.id
        WHERE je.status = 'approved'
            AND je.entry_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
        GROUP BY month, month_label
        ORDER BY month ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$months]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAccountCategoryDistribution($pdo) {
    $query = "
        SELECT 
            ac.name as category,
            COUNT(a.id) as account_count
        FROM account_categories ac
        LEFT JOIN accounts a ON ac.id = a.category_id AND a.is_active = 1
        GROUP BY ac.id, ac.name
        HAVING account_count > 0
        ORDER BY account_count DESC
    ";
    
    $stmt = $pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCashFlowMonthlyTrend($pdo, $months = 6) {
    $query = "
        SELECT 
            DATE_FORMAT(ct.transaction_date, '%Y-%m') as month,
            DATE_FORMAT(ct.transaction_date, '%b %Y') as month_label,
            COALESCE(SUM(CASE WHEN ct.type = 'masuk' THEN ct.amount ELSE 0 END), 0) as cash_in,
            COALESCE(SUM(CASE WHEN ct.type = 'keluar' THEN ct.amount ELSE 0 END), 0) as cash_out
        FROM cash_transactions ct
        WHERE ct.status = 'approved'
            AND ct.transaction_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
        GROUP BY month, month_label
        ORDER BY month ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$months]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function prepareChartData($data, $labelKey, $valueKeys) {
    $labels = [];
    $datasets = [];
    
    foreach ($valueKeys as $key) {
        $datasets[$key] = [];
    }
    
    foreach ($data as $row) {
        $labels[] = $row[$labelKey];
        foreach ($valueKeys as $key) {
            $datasets[$key][] = floatval($row[$key]);
        }
    }
    
    return [
        'labels' => $labels,
        'datasets' => $datasets
    ];
}
