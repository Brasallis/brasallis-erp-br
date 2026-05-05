<?php

namespace App\Controllers;

use App\Repository\DashboardRepository;
use App\Core\Database;
use Exception;
use Throwable;

class AdminController
{
    private $dashboardRepo;
    private $empresa_id;

    public function __construct(DashboardRepository $dashboardRepo = null)
    {
        // Require authentication
        $this->checkAuth();
        
        $this->empresa_id = $_SESSION['empresa_id'];
        
        // Use injected repo or create new one (fallback for legacy/direct usage)
        $this->dashboardRepo = $dashboardRepo ?? new DashboardRepository(Database::getInstance(), $this->empresa_id);
    }

    private function checkAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['empresa_id'])) {
            header('Location: ../login.php');
            exit;
        }
    }

    public function index()
    {
        // --- 0. ONBOARDING CHECK FOR ADMINS ---
        if ($_SESSION['user_type'] === 'admin') {
            $stmtOnb = $this->dashboardRepo->getConnection()->prepare("SELECT onboarding_completed FROM empresas WHERE id = ?");
            $stmtOnb->execute([$this->empresa_id]);
            $onboarding = $stmtOnb->fetchColumn();

            if ($onboarding == 0) {
                header('Location: onboarding.php');
                exit();
            }
        }

        // --- 1. SMART REDIRECT FOR EMPLOYEES ---
        // Se for funcionário, não vê o Dashboard Executivo por padrão.
        if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
            $moduleUrl = $this->getRecommendedModule();
            if ($moduleUrl) {
                header("Location: $moduleUrl");
                exit;
            } else {
                // Fallback para página de "Acesso Pendente" se não tiver nenhuma permissão
                header("Location: /admin/dashboard_funcionario.php");
                exit;
            }
        }

        // Run automations loop for Admins
        if (file_exists(__DIR__ . '/../../admin/run_automations.php')) {
            require_once __DIR__ . '/../../admin/run_automations.php';
            runNotificationAutomations($this->dashboardRepo->getConnection(), $this->empresa_id);
        }

        try {
            $dashboardRepo = $this->dashboardRepo;
            $empresa_id = $this->empresa_id;

            // Fetch Data
            $kpis = $dashboardRepo->getDashboardKPIs();
            $crm_kpis = $dashboardRepo->getCRMKPIs();
            $fin_kpis = $dashboardRepo->getFinancialKPIs();
            
            $exec_health = $dashboardRepo->getExecutiveHealth();
            $metas_exec = $dashboardRepo->getMetasExecutivas();
            $crm_projects = $dashboardRepo->getProjectCompletionBoard();
            $operacoes = $dashboardRepo->getOperationsLogistics();
            
            $avisos = $dashboardRepo->getConnection()->query("SELECT * FROM avisos_globais WHERE active = 1 ORDER BY created_at DESC")->fetchAll(\PDO::FETCH_ASSOC);
            $insights = $dashboardRepo->getDashboardInsights();
            
            $produtos_validade = $dashboardRepo->getProdutosProximosValidade(5);
            $ultimas_compras = $dashboardRepo->getUltimasCompras(5);
            $chart_data = $dashboardRepo->getSalesAndProfitOverTime('month');
            $weekly_data = $dashboardRepo->getWeeklyPerformance();
            $cross_data = $dashboardRepo->getCrossAnalysisData();
            $efficiency_data = $dashboardRepo->getOperationalEfficiencyMetrics();
            $forecast_data = $dashboardRepo->getSalesForecast(7);
            
            // Controle de Acesso: Funcionários Ativos
            $stmtEmployees = $dashboardRepo->getConnection()->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id = ? AND user_type = 'employee'");
            $stmtEmployees->execute([$empresa_id]);
            $active_employees_count = $stmtEmployees->fetchColumn();

            // Dados Gerenciais Adicionais
            $top_produtos = $dashboardRepo->getTopSellingProducts(5);

            // Validação de fallback para evitar Warnings PHP quebrando a sintaxe JS
            if (!is_array($chart_data)) $chart_data = [];
            if (!is_array($forecast_data) || !isset($forecast_data['forecast'])) $forecast_data = ['forecast' => []];

            // Prepare View Data
            $chart_labels = json_encode(array_column($chart_data, 'label')) ?: '[]';
            $chart_sales = json_encode(array_column($chart_data, 'sales')) ?: '[]';
            $chart_profit = json_encode(array_column($chart_data, 'profit')) ?: '[]';
            $chart_cost = json_encode(array_column($chart_data, 'cost')) ?: '[]';
            $chart_forecast = json_encode($forecast_data['forecast']) ?: '[]';

            $total_sales_period = array_sum(array_column($chart_data, 'sales'));
            $total_profit_period = array_sum(array_column($chart_data, 'profit'));
            
            // Render View
            require __DIR__ . '/../../views/admin/dashboard.php';

        } catch (Throwable $e) {
            $this->renderError($e);
        }
    }

    /**
     * Define qual módulo o funcionário deve cair primeiro baseado em suas permissões
     */
    private function getRecommendedModule(): ?string
    {
        $permissions = $_SESSION['permissions'] ?? [];
        if (empty($permissions) || !is_array($permissions)) return null;

        // 1. Buscar módulos REALMENTE ativos na empresa (ignora cache de plano)
        $conn = Database::getInstance();
        $stmt = $conn->prepare("SELECT active_modules, ai_plan FROM empresas WHERE id = ?");
        $stmt->execute([$_SESSION['empresa_id']]);
        $emp = $stmt->fetch();
        
        $plan = $emp['ai_plan'] ?? 'foundation';
        $db_active_modules = json_decode($emp['active_modules'] ?? '[]', true);

        require_once __DIR__ . '/../../includes/planos_config.php';
        $allowed_by_plan = get_modules_by_plan($plan);
        
        // Módulos ativos são a interseção entre o que o plano permite e o que está selecionado
        $active_modules = !empty($db_active_modules) ? array_intersect($db_active_modules, $allowed_by_plan) : $allowed_by_plan;

        // Prioridade de redirecionamento (Regra de Negócio)
        $priority = [
            'pdv' => '../employee/pdv.php',
            'estoque' => 'produtos.php',
            'crm' => '../modules/crm/views/kanban.php',
            'financeiro' => '../modules/financeiro/views/index.php',
            'ai_hub' => 'agentes_ia.php',
            'relatorios' => 'relatorios.php'
        ];

        foreach ($priority as $mod => $url) {
            $p = $permissions[$mod] ?? 0;
            
            // Normalização de permissão
            $has_access = false;
            if (is_numeric($p)) {
                $has_access = ($p > 0);
            } else {
                $has_access = ($p === 'total' || $p === 'leitura' || $p === 'escrita');
            }

            if (in_array($mod, $active_modules) && $has_access) {
                return $url;
            }
        }

        return null;
    }

    private function renderError(Throwable $e)
    {
        echo "<div class='alert alert-danger m-4'>
                <h4>Erro no Dashboard</h4>
                <p>" . $e->getMessage() . "</p>
                <pre>" . $e->getTraceAsString() . "</pre>
              </div>";
        exit;
    }
}
