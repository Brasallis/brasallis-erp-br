<?php
// api/dashboard_metrics.php

header('Content-Type: application/json');

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

// Verificar autenticação
if (!isset($_SESSION['empresa_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

require_once '../bootstrap.php';

use App\Controllers\EmployeeController;

try {
    $empresa_id = $_SESSION['empresa_id'];
    $user_id = $_SESSION['user_id'];
    /** @var \PDO $conn */
    $conn = resolve('db');

    // --- 1. Itens com Estoque Baixo (Contagem) ---
    $low_stock_items_stmt = $conn->prepare("SELECT COUNT(*) FROM produtos WHERE quantity <= minimum_stock AND empresa_id = ?");
    $low_stock_items_stmt->execute([$empresa_id]);
    $low_stock_items = $low_stock_items_stmt->fetchColumn();

    // --- 1.1 Lista de Itens com Estoque Baixo (Top 10) ---
    $low_stock_list_stmt = $conn->prepare("SELECT id, name, quantity, minimum_stock FROM produtos WHERE quantity <= minimum_stock AND empresa_id = ? ORDER BY quantity ASC LIMIT 10");
    $low_stock_list_stmt->execute([$empresa_id]);
    $low_stock_list = $low_stock_list_stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- 2. Movimentações Hoje ---
    $movements_today_stmt = $conn->prepare("SELECT COUNT(*) FROM historico_estoque WHERE DATE(created_at) = CURDATE() AND empresa_id = ?");
    $movements_today_stmt->execute([$empresa_id]);
    $movements_today = $movements_today_stmt->fetchColumn();

    // --- 3. Total de Produtos ---
    $total_products_stmt = $conn->prepare("SELECT COUNT(*) FROM produtos WHERE empresa_id = ?");
    $total_products_stmt->execute([$empresa_id]);
    $total_products = $total_products_stmt->fetchColumn();

    // --- 4. Últimas Movimentações (Lista) ---
    $latest_movements_stmt = $conn->prepare("
        SELECT h.action, h.quantity, h.created_at, p.name as product_name, u.username as user_name 
        FROM historico_estoque h 
        JOIN produtos p ON h.product_id = p.id 
        JOIN usuarios u ON h.user_id = u.id 
        WHERE h.empresa_id = ? 
        ORDER BY h.created_at DESC 
        LIMIT 10
    ");
    $latest_movements_stmt->execute([$empresa_id]);
    $latest_movements = $latest_movements_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatar datas para exibição
    foreach ($latest_movements as &$movement) {
        $movement['formatted_date'] = date('d/m/Y H:i', strtotime($movement['created_at']));
        $movement['action_label'] = ucfirst($movement['action']);
    }

    // --- 5. Dados do Gráfico (Minhas Movimentações - 7 dias) ---
    $chart_start_date = date('Y-m-d', strtotime('-6 days'));
    $chart_end_date = date('Y-m-d 23:59:59');

    $user_movements_sql = "SELECT DATE(created_at) as date, action, SUM(quantity) as total_quantity 
                           FROM historico_estoque 
                           WHERE user_id = ? AND created_at BETWEEN ? AND ? AND empresa_id = ?
                           GROUP BY DATE(created_at), action 
                           ORDER BY date ASC";
    $stmt_user_chart = $conn->prepare($user_movements_sql);
    $stmt_user_chart->execute([$user_id, $chart_start_date, $chart_end_date, $empresa_id]);
    $user_movements = $stmt_user_chart->fetchAll(PDO::FETCH_ASSOC);

    $user_chart_dates = [];
    for ($i = 6; $i >= 0; $i--) {
        // Corrigido para incluir o dia atual
        $date_key = date('Y-m-d', strtotime("-$i days"));
        $user_chart_dates[$date_key] = ['entrada' => 0, 'saida' => 0];
    }
    // Preenchendo com dados reais
    foreach ($user_movements as $movement) {
        if (isset($user_chart_dates[$movement['date']])) {
            $user_chart_dates[$movement['date']][$movement['action']] = (int)$movement['total_quantity'];
        }
    }
    
    $chart_data = [
        'labels' => array_map(function($date) { return date('d/m', strtotime($date)); }, array_keys($user_chart_dates)),
        'entradas' => array_column(array_values($user_chart_dates), 'entrada'),
        'saidas' => array_column(array_values($user_chart_dates), 'saida')
    ];
    
    // --- Retornar JSON ---
    echo json_encode([
        'metrics' => [
            'low_stock' => $low_stock_items,
            'movements_today' => $movements_today,
            'total_products' => $total_products
        ],
        'low_stock_list' => $low_stock_list,
        'latest_movements' => $latest_movements,
        'chart_data' => $chart_data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
