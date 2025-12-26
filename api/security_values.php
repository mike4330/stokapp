<?php
header('Content-Type: application/json');

try {
    $dir = 'sqlite:../portfolio.sqlite';
    $dbh = new PDO($dir);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $symbol = $_GET['symbol'] ?? '';
    $period = $_GET['period'] ?? '1y';

    if (empty($symbol)) {
        throw new Exception('Symbol parameter is required');
    }

    // Calculate date range based on period
    $date_limit = match($period) {
        '1y' => date('Y-m-d', strtotime('-1 year')),
        '6m' => date('Y-m-d', strtotime('-6 months')),
        '3m' => date('Y-m-d', strtotime('-3 months')),
        '1m' => date('Y-m-d', strtotime('-1 month')),
        default => date('Y-m-d', strtotime('-1 year'))
    };

    $query = "SELECT timestamp, close, cbps 
              FROM security_values 
              WHERE symbol = :symbol 
              AND timestamp >= :date_limit 
              ORDER BY timestamp ASC";

    $stmt = $dbh->prepare($query);
    $stmt->execute([
        ':symbol' => $symbol,
        ':date_limit' => $date_limit
    ]);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure numeric values are properly formatted
    foreach ($data as &$row) {
        $row['close'] = floatval($row['close']);
        $row['cbps'] = floatval($row['cbps']);
    }

    echo json_encode($data, JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 