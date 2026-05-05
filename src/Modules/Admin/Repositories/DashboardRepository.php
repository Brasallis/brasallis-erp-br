<?php

namespace App\Modules\Admin\Repositories;

use PDO;

/**
 * DashboardRepository — busca métricas e dados estratégicos para gestores.
 */
class DashboardRepository
{
    public function __construct(
        private PDO $pdo,
        private int $empresaId
    ) {}

    /**
     * Retorna o resumo executivo de KPIs.
     */
    public function getExecutiveKPIs(): array
    {
        return [
            'revenue' => $this->getRevenueMetrics(),
            'profit' => $this->getProfitMetrics(),
            'operational' => [
                'low_stock' => $this->getLowStockCount(),
                'avg_ticket' => $this->getAverageTicket(),
            ],
            'financial' => $this->getFinancialSummary(),
        ];
    }

    /**
     * Métricas de faturamento (mês atual vs anterior).
     */
    private function getRevenueMetrics(): array
    {
        $current = $this->pdo->prepare(
            "SELECT SUM(total_amount) FROM vendas 
             WHERE empresa_id = ? AND MONTH(data_venda) = MONTH(CURDATE()) AND YEAR(data_venda) = YEAR(CURDATE())"
        );
        $current->execute([$this->empresaId]);
        $currentVal = (float)$current->fetchColumn();

        $previous = $this->pdo->prepare(
            "SELECT SUM(total_amount) FROM vendas 
             WHERE empresa_id = ? AND MONTH(data_venda) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
             AND YEAR(data_venda) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))"
        );
        $previous->execute([$this->empresaId]);
        $previousVal = (float)$previous->fetchColumn();

        return [
            'current' => $currentVal,
            'previous' => $previousVal,
            'growth' => $this->calculateGrowth($currentVal, $previousVal)
        ];
    }

    /**
     * Métricas de lucratividade e ROI.
     */
    private function getProfitMetrics(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT 
                SUM(iv.quantity * (iv.unit_price - COALESCE(p.cost_price, 0))) as profit,
                SUM(iv.quantity * iv.unit_price) as revenue
             FROM itens_venda iv
             JOIN vendas v ON iv.venda_id = v.id
             LEFT JOIN produtos p ON iv.product_id = p.id
             WHERE v.empresa_id = ? AND MONTH(v.data_venda) = MONTH(CURDATE()) AND YEAR(v.data_venda) = YEAR(CURDATE())"
        );
        $stmt->execute([$this->empresaId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        $profit = (float)($data['profit'] ?? 0);
        $revenue = (float)($data['revenue'] ?? 1); // Avoid division by zero

        return [
            'value' => $profit,
            'margin' => ($profit / $revenue) * 100
        ];
    }

    /**
     * Ticket médio dos últimos 30 dias.
     */
    private function getAverageTicket(): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT AVG(total_amount) FROM vendas 
             WHERE empresa_id = ? AND data_venda >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        );
        $stmt->execute([$this->empresaId]);
        return (float)$stmt->fetchColumn();
    }

    /**
     * Resumo financeiro (Contas a receber/pagar).
     */
    private function getFinancialSummary(): array
    {
        // Contas a Receber Vencidas
        $receivable = $this->pdo->prepare(
            "SELECT SUM(valor) FROM contas_receber 
             WHERE empresa_id = ? AND status != 'pago' AND data_vencimento < CURDATE()"
        );
        $receivable->execute([$this->empresaId]);
        
        // Contas a Pagar próximas 7 dias
        $payable = $this->pdo->prepare(
            "SELECT SUM(valor) FROM contas_pagar 
             WHERE empresa_id = ? AND status != 'pago' AND data_vencimento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
        );
        $payable->execute([$this->empresaId]);

        return [
            'overdue_receivables' => (float)$receivable->fetchColumn(),
            'upcoming_payables' => (float)$payable->fetchColumn()
        ];
    }

    public function getLowStockCount(): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM produtos 
             WHERE empresa_id = ? AND quantity <= minimum_stock"
        );
        $stmt->execute([$this->empresaId]);
        return (int)$stmt->fetchColumn();
    }

    public function getUltimasCompras(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, f.nome as fornecedor_nome 
             FROM compras c 
             LEFT JOIN fornecedores f ON c.fornecedor_id = f.id 
             WHERE c.empresa_id = ? 
             ORDER BY c.data_compra DESC, c.id DESC LIMIT ?"
        );
        $stmt->execute([$this->empresaId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRevenueToday(): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT SUM(total_amount) FROM vendas 
             WHERE empresa_id = ? AND DATE(data_venda) = CURDATE()"
        );
        $stmt->execute([$this->empresaId]);
        return (float)$stmt->fetchColumn();
    }

    private function calculateGrowth($current, $previous): float
    {
        if ($previous <= 0) return $current > 0 ? 100 : 0;
        return (($current - $previous) / $previous) * 100;
    }

    public function getLayout(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT layout_json FROM dashboard_layouts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        
        if ($row) {
            return json_decode($row['layout_json'], true);
        }

        return [
            'row1' => ['financeiro_revenue', 'financeiro_profit'],
            'row2' => ['sales_chart', 'estoque_saude']
        ];
    }
}
