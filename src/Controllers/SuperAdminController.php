<?php
namespace App\Controllers;

use App\Repository\SuperDashboardRepository;
use App\Core\Database;
use Exception;
use Throwable;

class SuperAdminController
{
    private $repo;

    public function __construct()
    {
        $this->checkAuth();
        $this->repo = new SuperDashboardRepository(Database::getInstance());
    }

    private function checkAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'super_admin') {
            header('Location: ../login.php');
            exit;
        }
    }

    public function index()
    {
        try {
            $stats = $this->repo->getGlobalStats();
            $recent_companies = $this->repo->getRecentCompanies(10);
            $revenue_data = $this->repo->getPlatformRevenueChart();
            
            // Novos Dados de Gestão
            $all_companies = $this->repo->getAllCompanies();
            $billing = $this->repo->getBillingOverview();
            $logs = $this->repo->getSystemLogs(20);

            // Formatação para gráficos
            $chart_labels = json_encode(array_column($revenue_data, 'label'));
            $chart_values = json_encode(array_column($revenue_data, 'value'));

            require __DIR__ . '/../../views/admin/super_dashboard.php';
        } catch (Throwable $e) {
            echo "Erro no Super Dashboard: " . $e->getMessage();
        }
    }
}
