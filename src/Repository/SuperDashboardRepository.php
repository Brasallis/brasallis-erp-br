<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

class SuperDashboardRepository
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getGlobalStats()
    {
        $stats = [];

        // Total de Empresas
        $stats['total_empresas'] = $this->conn->query("SELECT COUNT(*) FROM empresas")->fetchColumn();

        // Total de Usuários
        $stats['total_usuarios'] = $this->conn->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

        // Faturamento Total (Plataforma)
        $stats['faturamento_total'] = $this->conn->query("SELECT SUM(total_amount) FROM vendas")->fetchColumn() ?: 0;

        // Distribuição de Planos
        $stmt_plans = $this->conn->query("SELECT ai_plan, COUNT(*) as qtd FROM empresas GROUP BY ai_plan");
        $stats['planos'] = $stmt_plans->fetchAll(PDO::FETCH_ASSOC);

        // Empresas Ativas (Vendas nos últimos 7 dias)
        $stats['empresas_ativas'] = $this->conn->query("SELECT COUNT(DISTINCT empresa_id) FROM vendas WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

        return $stats;
    }

    public function getRecentCompanies($limit = 5)
    {
        $stmt = $this->conn->prepare("SELECT id, name, ai_plan, created_at FROM empresas ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPlatformRevenueChart()
    {
        $stmt = $this->conn->query("
            SELECT DATE_FORMAT(created_at, '%d/%m') as label, SUM(total_amount) as value 
            FROM vendas 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY) 
            GROUP BY label, DATE(created_at)
            ORDER BY DATE(created_at) ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllCompanies()
    {
        $stmt = $this->conn->query("
            SELECT e.*, 
                   (SELECT COUNT(*) FROM usuarios WHERE empresa_id = e.id) as total_users,
                   (SELECT SUM(total_amount) FROM vendas WHERE empresa_id = e.id) as gmv
            FROM empresas e 
            ORDER BY e.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSystemLogs($limit = 50)
    {
        // Tenta buscar da tabela de logs se existir, caso contrário retorna vazio
        try {
            $stmt = $this->conn->prepare("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT ?");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getBillingOverview()
    {
        $billing = [];
        // Empresas com mensalidade próxima ou atrasada
        $billing['atrasadas'] = $this->conn->query("SELECT COUNT(*) FROM empresas WHERE subscription_status = 'atrasado'")->fetchColumn();
        $billing['trial'] = $this->conn->query("SELECT COUNT(*) FROM empresas WHERE subscription_status = 'trial'")->fetchColumn();
        $billing['ativas'] = $this->conn->query("SELECT COUNT(*) FROM empresas WHERE subscription_status = 'active'")->fetchColumn();
        
        return $billing;
    }

    public function getRevenueByPlan()
    {
        $stmt = $this->conn->query("
            SELECT e.ai_plan as label, SUM(v.total_amount) as value 
            FROM vendas v
            JOIN empresas e ON v.empresa_id = e.id
            GROUP BY e.ai_plan
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEfficiencyMetrics()
    {
        // Cruzamento: Tokens consumidos vs Faturamento gerado por empresa
        // Pegamos as 10 maiores para um gráfico mais rico
        $stmt = $this->conn->query("
            SELECT e.name, e.ai_tokens_used_month as tokens, 
                   (SELECT SUM(total_amount) FROM vendas WHERE empresa_id = e.id) as revenue
            FROM empresas e
            WHERE (SELECT SUM(total_amount) FROM vendas WHERE empresa_id = e.id) > 0
            ORDER BY revenue DESC
            LIMIT 10
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSaaSMetrics()
    {
        $metrics = [];
        
        // 1. ARPU (Average Revenue Per User)
        $total_rev = $this->conn->query("SELECT SUM(total_amount) FROM vendas")->fetchColumn() ?: 0;
        $total_users = $this->conn->query("SELECT COUNT(*) FROM usuarios")->fetchColumn() ?: 1;
        $metrics['arpu'] = $total_rev / $total_users;

        // 2. Churn Risk (Empresas sem vendas nos últimos 15 dias)
        $metrics['churn_risk'] = $this->conn->query("
            SELECT COUNT(*) FROM empresas 
            WHERE id NOT IN (SELECT DISTINCT empresa_id FROM vendas WHERE created_at >= DATE_SUB(NOW(), INTERVAL 15 DAY))
            AND created_at <= DATE_SUB(NOW(), INTERVAL 15 DAY)
        ")->fetchColumn();

        // 3. Taxa de Crescimento Mensal (MoM)
        $this_month = $this->conn->query("SELECT SUM(total_amount) FROM vendas WHERE MONTH(created_at) = MONTH(NOW())")->fetchColumn() ?: 0;
        $last_month = $this->conn->query("SELECT SUM(total_amount) FROM vendas WHERE MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))")->fetchColumn() ?: 1;
        $metrics['growth_rate'] = (($this_month - $last_month) / $last_month) * 100;

        return $metrics;
    }

    public function getInfrastructureHealth()
    {
        $health = [];
        // Taxa de Erros pendentes (não resolvidos)
        $unresolved = $this->conn->query("SELECT COUNT(*) FROM system_logs WHERE status = 'new' AND severity IN ('error', 'critical')")->fetchColumn();
        $health['status'] = $unresolved > 10 ? 'Unstable' : ($unresolved > 2 ? 'Warning' : 'Healthy');
        $health['error_count'] = $unresolved;
        
        return $health;
    }
}
