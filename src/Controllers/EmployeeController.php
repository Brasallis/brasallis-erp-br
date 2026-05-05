<?php

namespace App\Controllers;

use PDO;
use Exception;

class EmployeeController
{
    private $pdo;
    private $empresa_id;
    private $user_id;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->checkAuth();
        $this->empresa_id = $_SESSION['empresa_id'];
        $this->user_id = $_SESSION['user_id'];
    }

    private function checkAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['empresa_id']) || !isset($_SESSION['user_id'])) {
            header('Location: ../login.php');
            exit;
        }
    }

    public function index()
    {
        $empresa_id = $this->empresa_id;
        $user_id = $this->user_id;
        $conn = $this->pdo;

        // --- FETCH DATA ---

        $total_products_stmt = $conn->prepare("SELECT COUNT(*) FROM produtos WHERE empresa_id = ?");
        $total_products_stmt->execute([$empresa_id]);
        $total_products = $total_products_stmt->fetchColumn();

        $low_stock_items_stmt = $conn->prepare("SELECT COUNT(*) FROM produtos WHERE quantity <= minimum_stock AND empresa_id = ?");
        $low_stock_items_stmt->execute([$empresa_id]);
        $low_stock_items = $low_stock_items_stmt->fetchColumn();

        $movements_today_stmt = $conn->prepare("SELECT COUNT(*) FROM historico_estoque WHERE DATE(created_at) = CURDATE() AND empresa_id = ?");
        $movements_today_stmt->execute([$empresa_id]);
        $movements_today = $movements_today_stmt->fetchColumn();

        $low_stock_stmt = $conn->prepare("SELECT id, name, quantity, minimum_stock FROM produtos WHERE quantity <= minimum_stock AND empresa_id = ? ORDER BY quantity ASC LIMIT 10");
        $low_stock_stmt->execute([$empresa_id]);
        $low_stock_products = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);

        $latest_movements_stmt = $conn->prepare("SELECT h.action, h.quantity, h.created_at, p.name as product_name, u.username as user_name FROM historico_estoque h JOIN produtos p ON h.product_id = p.id JOIN usuarios u ON h.user_id = u.id WHERE h.empresa_id = ? ORDER BY h.created_at DESC LIMIT 10");
        $latest_movements_stmt->execute([$empresa_id]);
        $latest_movements = $latest_movements_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Chart Data
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
            $date_key = date('Y-m-d', strtotime("-$i days"));
            $user_chart_dates[$date_key] = ['entrada' => 0, 'saida' => 0];
        }
        foreach ($user_movements as $movement) {
            if (isset($user_chart_dates[$movement['date']])) {
                $user_chart_dates[$movement['date']][$movement['action']] = (int)$movement['total_quantity'];
            }
        }
        $user_chart_labels = array_map(function($date) { return date('d/m', strtotime($date)); }, array_keys($user_chart_dates));
        $user_chart_entradas = array_column(array_values($user_chart_dates), 'entrada');
        $user_chart_saidas = array_column(array_values($user_chart_dates), 'saida');

        // --- RENDER VIEW ---
        require __DIR__ . '/../../views/employee/dashboard.php';
    }
}
