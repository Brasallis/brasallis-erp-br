<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

if (!isset($_SESSION['empresa_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../bootstrap.php';

use App\Repository\DashboardRepository;

try {
    $dashboardRepo = resolve(DashboardRepository::class);
    
    $period = $_GET['period'] ?? 'month'; // day, month, year
    
    $data = $dashboardRepo->getSalesAndProfitOverTime($period);
    
    $totals = [
        'sales' => array_sum(array_column($data, 'sales')),
        'profit' => array_sum(array_column($data, 'profit'))
    ];

    // Get Forecast Data
    $forecast = $dashboardRepo->getSalesForecast(7);

    echo json_encode([
        'labels' => array_column($data, 'label'),
        'sales' => array_column($data, 'sales'),
        'profit' => array_column($data, 'profit'),
        'cost' => array_column($data, 'cost'),
        'totals' => $totals,
        'forecast' => $forecast
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
