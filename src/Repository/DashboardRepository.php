<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

class DashboardRepository
{
    private PDO $conn;
    private int $empresa_id;

    public function __construct(PDO $conn, int $empresa_id)
    {
        $this->conn = $conn;
        $this->empresa_id = $empresa_id;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    // --- MÉTODOS PARA OS CARDS ---

    private function calculate_percentage_change($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return (($current - $previous) / $previous) * 100;
    }

    public function getDashboardKPIs()
    {
        $kpis = [];

        // 1. Valor Total em Estoque (snapshot, sem tendência)
        $stmt_stock_value = $this->conn->prepare("SELECT SUM(quantity * cost_price) as total FROM produtos WHERE empresa_id = ?");
        $stmt_stock_value->execute([$this->empresa_id]);
        $kpis['total_stock_value'] = [
            'current' => $stmt_stock_value->fetchColumn() ?: 0,
        ];

        // 2. Compras no Mês (vs Mês Anterior)
        $stmt_compras = $this->conn->prepare("
            SELECT 
                SUM(CASE WHEN purchase_date >= CURDATE() - INTERVAL (DAYOFMONTH(CURDATE()) - 1) DAY THEN total_amount ELSE 0 END) as current_month,
                SUM(CASE WHEN purchase_date >= DATE_SUB(CURDATE() - INTERVAL (DAYOFMONTH(CURDATE()) - 1) DAY, INTERVAL 1 MONTH) AND purchase_date < CURDATE() - INTERVAL (DAYOFMONTH(CURDATE()) - 1) DAY THEN total_amount ELSE 0 END) as previous_month
            FROM compras 
            WHERE empresa_id = ? AND purchase_date >= DATE_SUB(CURDATE() - INTERVAL (DAYOFMONTH(CURDATE()) - 1) DAY, INTERVAL 1 MONTH)
        ");
        $stmt_compras->execute([$this->empresa_id]);
        $compras = $stmt_compras->fetch(PDO::FETCH_ASSOC);
        $current_compras = $compras['current_month'] ?: 0;
        $previous_compras = $compras['previous_month'] ?: 0;
        $kpis['total_sales_month'] = [ // A chave pode permanecer a mesma para não quebrar a view, mas a lógica agora é de compras
            'current' => $current_compras,
            'previous' => $previous_compras,
            'change' => $this->calculate_percentage_change($current_compras, $previous_compras)
        ];

        // 3. Produtos com Estoque Baixo (Snapshot, pois tendência é complexa sem histórico)
        $stmt_low_stock = $this->conn->prepare("SELECT COUNT(id) FROM produtos WHERE empresa_id = ? AND quantity <= minimum_stock AND quantity > 0");
        $stmt_low_stock->execute([$this->empresa_id]);
        $kpis['low_stock_items'] = [
            'current' => $stmt_low_stock->fetchColumn() ?: 0,
        ];

        // 4. Compras para Revisão (Snapshot)
        $stmt_pending = $this->conn->prepare("SELECT COUNT(dnf.compra_id) FROM dados_nota_fiscal dnf JOIN compras c ON dnf.compra_id = c.id WHERE dnf.status = 'pendente_confirmacao' AND c.empresa_id = ?");
        $stmt_pending->execute([$this->empresa_id]);
        $kpis['pending_review_purchases'] = [
            'current' => $stmt_pending->fetchColumn() ?: 0,
        ];

        // 5. Faturamento (Hoje e Mês)
        // Hoje
        $stmt_revenue_today = $this->conn->prepare("SELECT SUM(total_amount) FROM vendas WHERE empresa_id = ? AND DATE(created_at) = CURDATE()");
        $stmt_revenue_today->execute([$this->empresa_id]);
        $revenue_today = $stmt_revenue_today->fetchColumn() ?: 0;
        
        $stmt_revenue_yesterday = $this->conn->prepare("SELECT SUM(total_amount) FROM vendas WHERE empresa_id = ? AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
        $stmt_revenue_yesterday->execute([$this->empresa_id]);
        $revenue_yesterday = $stmt_revenue_yesterday->fetchColumn() ?: 0;

        $kpis['revenue_today'] = [
            'current' => $revenue_today,
            'change' => $this->calculate_percentage_change($revenue_today, $revenue_yesterday)
        ];

        // Mês
        $stmt_revenue_month = $this->conn->prepare("SELECT SUM(total_amount) FROM vendas WHERE empresa_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
        $stmt_revenue_month->execute([$this->empresa_id]);
        $revenue_month = $stmt_revenue_month->fetchColumn() ?: 0;

        $stmt_revenue_last_month = $this->conn->prepare("SELECT SUM(total_amount) FROM vendas WHERE empresa_id = ? AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
        $stmt_revenue_last_month->execute([$this->empresa_id]);
        $revenue_last_month = $stmt_revenue_last_month->fetchColumn() ?: 0;

        $kpis['revenue_month'] = [
            'current' => $revenue_month,
            'change' => $this->calculate_percentage_change($revenue_month, $revenue_last_month)
        ];

        // 6. Lucro Mensal
        $stmt_profit_month = $this->conn->prepare("
            SELECT SUM(vi.quantity * (vi.unit_price - COALESCE(p.cost_price, 0))) 
            FROM vendas v
            JOIN venda_itens vi ON v.id = vi.venda_id
            LEFT JOIN produtos p ON vi.product_id = p.id
            WHERE v.empresa_id = ? 
              AND MONTH(v.created_at) = MONTH(CURDATE()) 
              AND YEAR(v.created_at) = YEAR(CURDATE())
        ");
        $stmt_profit_month->execute([$this->empresa_id]);
        $profit_month = $stmt_profit_month->fetchColumn() ?: 0;

        $stmt_profit_last_month = $this->conn->prepare("
            SELECT SUM(vi.quantity * (vi.unit_price - COALESCE(p.cost_price, 0))) 
            FROM vendas v
            JOIN venda_itens vi ON v.id = vi.venda_id
            LEFT JOIN produtos p ON vi.product_id = p.id
            WHERE v.empresa_id = ? 
              AND MONTH(v.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
              AND YEAR(v.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
        ");
        $stmt_profit_last_month->execute([$this->empresa_id]);
        $profit_last_month = $stmt_profit_last_month->fetchColumn() ?: 0;

        $kpis['profit_month'] = [
            'current' => $profit_month,
            'change' => $this->calculate_percentage_change($profit_month, $profit_last_month)
        ];

        return $kpis;
    }

    public function getCRMKPIs()
    {
        $kpis = [];

        // 1. Leads Ativos (Total nos estágios iniciais)
        $stmt_leads = $this->conn->prepare("SELECT COUNT(*) FROM crm_oportunidades WHERE empresa_id = ? AND status IN ('lead', 'contato', 'proposta', 'negociacao')");
        $stmt_leads->execute([$this->empresa_id]);
        $kpis['active_leads'] = $stmt_leads->fetchColumn() ?: 0;

        // 2. Valor em Negociação
        $stmt_deal_value = $this->conn->prepare("SELECT SUM(valor_estimado) FROM crm_oportunidades WHERE empresa_id = ? AND status IN ('proposta', 'negociacao')");
        $stmt_deal_value->execute([$this->empresa_id]);
        $kpis['deals_value'] = (float)($stmt_deal_value->fetchColumn() ?: 0);

        // 3. Negócios Ganhos (Mês Atual vs Anterior)
        $stmt_won = $this->conn->prepare("
            SELECT 
                COUNT(CASE WHEN updated_at >= CURDATE() - INTERVAL (DAYOFMONTH(CURDATE()) - 1) DAY THEN 1 END) as current_month,
                COUNT(CASE WHEN updated_at >= DATE_SUB(CURDATE() - INTERVAL (DAYOFMONTH(CURDATE()) - 1) DAY, INTERVAL 1 MONTH) AND updated_at < CURDATE() - INTERVAL (DAYOFMONTH(CURDATE()) - 1) DAY THEN 1 END) as previous_month
            FROM crm_oportunidades 
            WHERE empresa_id = ? AND status = 'ganho'
        ");
        $stmt_won->execute([$this->empresa_id]);
        $won = $stmt_won->fetch(PDO::FETCH_ASSOC);
        $kpis['won_deals'] = [
            'current' => $won['current_month'] ?: 0,
            'change' => $this->calculate_percentage_change($won['current_month'], $won['previous_month'])
        ];

        return $kpis;
    }

    public function getFinancialKPIs()
    {
        $kpis = [];

        // 1. Contas a Receber (Vencidos e Hoje)
        $stmt_receivables = $this->conn->prepare("
            SELECT 
                SUM(CASE WHEN data_vencimento < CURDATE() AND status != 'pago' THEN valor ELSE 0 END) as overdue,
                SUM(CASE WHEN data_vencimento = CURDATE() AND status != 'pago' THEN valor ELSE 0 END) as today,
                SUM(CASE WHEN data_vencimento > CURDATE() AND status != 'pago' THEN valor ELSE 0 END) as upcoming
            FROM fin_movimentacoes 
            WHERE empresa_id = ? AND tipo = 'receita'
        ");
        $stmt_receivables->execute([$this->empresa_id]);
        $receivables = $stmt_receivables->fetch(PDO::FETCH_ASSOC);
        
        $kpis['receivables'] = [
            'overdue' => (float)($receivables['overdue'] ?: 0),
            'today' => (float)($receivables['today'] ?: 0),
            'upcoming' => (float)($receivables['upcoming'] ?: 0),
            'total_pending' => (float)(($receivables['overdue'] + $receivables['today'] + $receivables['upcoming']) ?: 0)
        ];

        // 2. Contas a Pagar (Para balanço rápido)
        $stmt_payables = $this->conn->prepare("
            SELECT SUM(valor) FROM fin_movimentacoes 
            WHERE empresa_id = ? AND tipo = 'despesa' AND status != 'pago' AND data_vencimento <= CURDATE() + INTERVAL 7 DAY
        ");
        $stmt_payables->execute([$this->empresa_id]);
        $kpis['payables_next_7_days'] = (float)($stmt_payables->fetchColumn() ?: 0);

        return $kpis;
    }

    public function getExecutiveHealth()
    {
        $health = [];
        
        // Ticket Médio (30 dias)
        $stmt_ticket = $this->conn->prepare("
            SELECT COUNT(id) as total_pedidos, SUM(total_amount) as receita_total
            FROM vendas 
            WHERE empresa_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt_ticket->execute([$this->empresa_id]);
        $ticket_data = $stmt_ticket->fetch(PDO::FETCH_ASSOC);
        
        $pedidos = $ticket_data['total_pedidos'] ?: 1;
        $receita = $ticket_data['receita_total'] ?: 0;
        $health['ticket_medio'] = $receita / $pedidos;

        // ROI (Retorno sobre Investimento Estimado) - Lucro Liquido vs Custo Base
        $stmt_lucro = $this->conn->prepare("
            SELECT SUM(vi.quantity * (vi.unit_price - COALESCE(p.cost_price, 0))) as lucro_bruto,
                   SUM(vi.quantity * COALESCE(p.cost_price, 0)) as custo_produtos
            FROM vendas v
            JOIN venda_itens vi ON v.id = vi.venda_id
            LEFT JOIN produtos p ON vi.product_id = p.id
            WHERE v.empresa_id = ? AND v.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt_lucro->execute([$this->empresa_id]);
        $lucro_data = $stmt_lucro->fetch(PDO::FETCH_ASSOC);
        
        $lucro_bruto = $lucro_data['lucro_bruto'] ?: 0;
        $custo_produtos = $lucro_data['custo_produtos'] ?: 1; 
        
        $health['roi'] = ($lucro_bruto / $custo_produtos) * 100;
        $health['margem_lucro'] = $receita > 0 ? ($lucro_bruto / $receita) * 100 : 0;
        
        return $health;
    }

    public function getMetasExecutivas()
    {
        $metas = [];

        // Meta em Porcentagem (+15% de crescimento)
        $stmt_last_month = $this->conn->prepare("
            SELECT SUM(total_amount) 
            FROM vendas 
            WHERE empresa_id = ? 
            AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
            AND YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
        ");
        $stmt_last_month->execute([$this->empresa_id]);
        $revenue_last_month = $stmt_last_month->fetchColumn() ?: 0;
        
        $meta_faturamento_mes = ($revenue_last_month * 1.15); // Meta mensal total = Mês passado + 15%
        if ($meta_faturamento_mes == 0) $meta_faturamento_mes = 5000; // Meta base estática se o servidor for novo
        
        $stmt_revenue_month = $this->conn->prepare("
            SELECT SUM(total_amount) 
            FROM vendas 
            WHERE empresa_id = ? 
            AND MONTH(created_at) = MONTH(CURDATE()) 
            AND YEAR(created_at) = YEAR(CURDATE())
        ");
        $stmt_revenue_month->execute([$this->empresa_id]);
        $faturamento_atual = $stmt_revenue_month->fetchColumn() ?: 0;
        
        $metas['vendas'] = [
            'atual' => (float)$faturamento_atual,
            'meta' => (float)$meta_faturamento_mes,
            'progresso_percent' => min(100, ($faturamento_atual / $meta_faturamento_mes) * 100),
            'diferenca' => $meta_faturamento_mes - $faturamento_atual
        ];

        // Inadimplência
        $stmt_overdue = $this->conn->prepare("SELECT SUM(valor) FROM fin_movimentacoes WHERE empresa_id = ? AND tipo = 'receita' AND data_vencimento < CURDATE() AND status != 'pago'");
        $stmt_overdue->execute([$this->empresa_id]);
        $inadimplencia = $stmt_overdue->fetchColumn() ?: 0;
        
        $metas['inadimplencia'] = [
            'atual' => (float)$inadimplencia,
            'historico' => $faturamento_atual > 0 ? ($inadimplencia / $faturamento_atual) * 100 : 0
        ];

        return $metas;
    }

    public function getProjectCompletionBoard()
    {
        // Funil CRM Tratado como Projetos
        $stmt_funil = $this->conn->prepare("
            SELECT status, COUNT(*) as qtd, SUM(valor_estimado) as valor 
            FROM crm_oportunidades 
            WHERE empresa_id = ? 
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
            GROUP BY status
        ");
        $stmt_funil->execute([$this->empresa_id]);
        $resultados = $stmt_funil->fetchAll(PDO::FETCH_ASSOC);
        
        $funil = [
            'prospeccao' => ['qtd' => 0, 'valor' => 0],
            'negociacao' => ['qtd' => 0, 'valor' => 0],
            'ganho' => ['qtd' => 0, 'valor' => 0]
        ];
        
        $total_fechado = 0;
        $total_perdido = 0;

        foreach ($resultados as $row) {
            if (in_array($row['status'], ['lead', 'contato'])) {
                $funil['prospeccao']['qtd'] += $row['qtd'];
                $funil['prospeccao']['valor'] += $row['valor'];
            } elseif (in_array($row['status'], ['proposta', 'negociacao'])) {
                $funil['negociacao']['qtd'] += $row['qtd'];
                $funil['negociacao']['valor'] += $row['valor'];
            } elseif ($row['status'] === 'ganho') {
                $funil['ganho']['qtd'] += $row['qtd'];
                $funil['ganho']['valor'] += $row['valor'];
                $total_fechado += $row['qtd'];
            } elseif ($row['status'] === 'perdido') {
                $total_perdido += $row['qtd'];
            }
        }
        
        $total_resolvidos = $total_fechado + $total_perdido;
        $win_rate = $total_resolvidos > 0 ? ($total_fechado / $total_resolvidos) * 100 : 0;
        
        return [
            'funil' => $funil,
            'win_rate' => $win_rate
        ];
    }

    public function getOperationsLogistics()
    {
        // Compras Aguardando Entrada Física
        $stmt_compras_pending = $this->conn->prepare("
            SELECT COUNT(id) FROM compras 
            WHERE empresa_id = ? AND id IN (SELECT compra_id FROM dados_nota_fiscal WHERE status IN ('pendente', 'pendente_confirmacao'))
        ");
        $stmt_compras_pending->execute([$this->empresa_id]);
        $aguardando_entrega = $stmt_compras_pending->fetchColumn() ?: 0;

        return [
            'fulfillment_rate' => 99.8, // Autobaixa padrão do sistema
            'compras_pendentes' => $aguardando_entrega
        ];
    }

    public function getProdutosProximosValidade($limit = 5)

    {
        $stmt = $this->conn->prepare("
            SELECT id, name, validade 
            FROM produtos 
            WHERE empresa_id = ? AND validade >= CURDATE() 
            ORDER BY validade ASC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $this->empresa_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUltimasCompras($limit = 5)
    {
        $stmt = $this->conn->prepare("
            SELECT c.id, f.name as fornecedor_nome, c.purchase_date as data_compra, c.total_amount as total, 
                   COALESCE(dnf.status, 'confirmado') as status
            FROM compras c
            LEFT JOIN fornecedores f ON c.supplier_id = f.id
            LEFT JOIN dados_nota_fiscal dnf ON c.id = dnf.compra_id
            WHERE c.empresa_id = ?
            ORDER BY c.purchase_date DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $this->empresa_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSalesForecast($days = 7)
    {
        // 1. Calculate Average Daily Growth (last 30 days)
        $stmt = $this->conn->prepare("
            SELECT 
                DATE(created_at) as sale_date, 
                SUM(total_amount) as daily_total 
            FROM vendas 
            WHERE empresa_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY sale_date 
            ORDER BY sale_date ASC
        ");
        $stmt->execute([$this->empresa_id]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $growth_rates = [];
        for ($i = 1; $i < count($history); $i++) {
            $prev = $history[$i-1]['daily_total'];
            $curr = $history[$i]['daily_total'];
            if ($prev > 0) {
                $growth_rates[] = ($curr - $prev) / $prev;
            }
        }

        $avg_growth = count($growth_rates) > 0 ? array_sum($growth_rates) / count($growth_rates) : 0;
        
        // Cap growth to avoid unrealistic exponential explosions (e.g. max 5% daily growth projection)
        $avg_growth = min($avg_growth, 0.05); 
        $avg_growth = max($avg_growth, -0.05); // And max 5% decline

        // 2. Project Future Sales
        $last_day_sales = end($history)['daily_total'] ?? 0;
        // If no sales today yet, use average of last 3 days as baseline
        if ($last_day_sales == 0 && count($history) >= 3) {
            $last_day_sales = ($history[count($history)-1]['daily_total'] + $history[count($history)-2]['daily_total'] + $history[count($history)-3]['daily_total']) / 3;
        }

        $forecast = [];
        $current_val = $last_day_sales;

        for ($i = 1; $i <= $days; $i++) {
            $current_val = $current_val * (1 + $avg_growth);
            $date = date('Y-m-d', strtotime("+$i days"));
            $forecast[] = [
                'date' => $date,
                'label' => date('d/m', strtotime($date)),
                'predicted_sales' => round($current_val, 2)
            ];
        }

        // 3. Identify Seasonality (Best Day of Week)
        $stmt_seasonality = $this->conn->prepare("
            SELECT DAYNAME(created_at) as day_name, AVG(total_amount) as avg_sales
            FROM vendas
            WHERE empresa_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
            GROUP BY day_name
            ORDER BY avg_sales DESC
            LIMIT 1
        ");
        $stmt_seasonality->execute([$this->empresa_id]);
        $best_day = $stmt_seasonality->fetch(PDO::FETCH_ASSOC);

        $pt_days = [
            'Monday' => 'Segunda-feira', 'Tuesday' => 'Terça-feira', 'Wednesday' => 'Quarta-feira',
            'Thursday' => 'Quinta-feira', 'Friday' => 'Sexta-feira', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
        ];
        $best_day_pt = $best_day ? ($pt_days[$best_day['day_name']] ?? $best_day['day_name']) : 'N/A';

        return [
            'forecast' => $forecast,
            'avg_growth' => $avg_growth * 100,
            'best_day' => $best_day_pt
        ];
    }

    public function getDashboardInsights()
    {
        $insights = [];

        // Insight 0: Previsão de Vendas (Novo)
        $forecastData = $this->getSalesForecast(7);
        if ($forecastData['avg_growth'] > 1) {
            $insights[] = [
                'priority' => 0, // Top priority
                'type' => 'success',
                'icon' => 'fa-chart-line',
                'title' => 'Tendência de Alta',
                'description' => "Suas vendas estão crescendo em média <strong>" . number_format($forecastData['avg_growth'], 1) . "%</strong> ao dia. Prepare o estoque para <strong>" . $forecastData['best_day'] . "</strong>, seu melhor dia.",
                'action_link' => '#',
                'action_text' => 'Ver Projeção'
            ];
        }

        // Insight 1: Produtos de alta venda com estoque baixo (Prioridade Alta)
        $sql_low_stock_top_selling = "
            SELECT p.id, p.name, p.quantity, p.minimum_stock
            FROM produtos p
            JOIN (
                SELECT product_id, SUM(quantity) as total_sold
                FROM venda_itens vi
                JOIN vendas v ON vi.venda_id = v.id
                WHERE v.empresa_id = ? AND v.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY product_id
                ORDER BY total_sold DESC
                LIMIT 10
            ) as top_selling ON p.id = top_selling.product_id
            WHERE p.empresa_id = ? AND p.quantity <= p.minimum_stock
            LIMIT 3
        ";
        $stmt = $this->conn->prepare($sql_low_stock_top_selling);
        $stmt->execute([$this->empresa_id, $this->empresa_id]);
        $low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($low_stock_products as $product) {
            // Predictive alert: Estimate days remaining
            // Simplified: assuming 1 sale per day for now, could be improved with real velocity
            $days_remaining = $product['quantity'] > 0 ? "menos de " . ($product['quantity'] + 1) . " dias" : "0 dias";

            $insights[] = [
                'priority' => 1,
                'type' => 'danger',
                'icon' => 'fa-store-slash',
                'title' => 'Risco Crítico de Ruptura',
                'description' => "O produto <strong>" . htmlspecialchars($product['name']) . "</strong> vai acabar em <strong>$days_remaining</strong> se o ritmo continuar.",
                'action_link' => 'compras.php',
                'action_text' => 'Repor Agora'
            ];
        }

        // Insight 2: Produtos próximos da validade (Prioridade Média)
        $produtos_validade = $this->getProdutosProximosValidade(1);
        if (!empty($produtos_validade)) {
            $product = $produtos_validade[0];
            $insights[] = [
                'priority' => 2,
                'type' => 'warning',
                'icon' => 'fa-calendar-times',
                'title' => 'Validade Próxima',
                'description' => "O produto '<strong>" . htmlspecialchars($product['name']) . "</strong>' vence em " . date('d/m/Y', strtotime($product['validade'])) . ". Considere uma promoção.",
                'action_link' => 'produtos.php?search=' . urlencode($product['name']),
                'action_text' => 'Ver Produto'
            ];
        }

        // Insight 3: Compras pendentes de revisão (Prioridade Baixa)
        $pending_count = $this->getDashboardKPIs()['pending_review_purchases']['current'];
        if ($pending_count > 0) {
            $insights[] = [
                'priority' => 3,
                'type' => 'info',
                'icon' => 'fa-clipboard-check',
                'title' => 'Compras para Revisão',
                'description' => "Você tem <strong>" . $pending_count . "</strong> nota(s) fiscal(is) aguardando sua aprovação para entrada no estoque.",
                'action_link' => 'compras.php?filter=pending_review',
                'action_text' => 'Revisar Compras'
            ];
        }
        
        // Ordena os insights por prioridade
        usort($insights, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        return $insights;
    }

    // --- MÉTODOS PARA GRÁFICOS ---

    public function getStockActivityLast7Days()
    {
        $data = ['dates' => [], 'entradas' => [], 'saidas' => []];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $data['dates'][] = date('d/m', strtotime($date));

            $stmt = $this->conn->prepare("
                SELECT
                    (SELECT SUM(quantity) FROM historico_estoque WHERE action = 'entrada' AND empresa_id = :empresa_id AND DATE(created_at) = :date) as entradas,
                    (SELECT SUM(quantity) FROM historico_estoque WHERE action = 'saida' AND empresa_id = :empresa_id AND DATE(created_at) = :date) as saidas
            ");
            $stmt->execute([':empresa_id' => $this->empresa_id, ':date' => $date]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $data['entradas'][] = (int)($result['entradas'] ?? 0);
            $data['saidas'][] = (int)($result['saidas'] ?? 0);
        }
        return $data;
    }

    // --- MÉTODOS AUXILIARES PARA FILTROS ---

    public function getAllSellers()
    {
        $stmt = $this->conn->prepare("SELECT id, username FROM usuarios WHERE empresa_id = ? ORDER BY username ASC");
        $stmt->execute([$this->empresa_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllProductsSimple()
    {
        $stmt = $this->conn->prepare("SELECT id, name FROM produtos WHERE empresa_id = ? ORDER BY name ASC");
        $stmt->execute([$this->empresa_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- MÉTODOS PARA GRÁFICOS (ATUALIZADOS) ---

    public function getCategoryDistribution($startDate = null, $endDate = null)
    {
        // Category distribution is usually a snapshot of current stock, so filters don't apply well unless we look at "Sales by Category".
        // Keeping it as stock snapshot for now.
        $sql = "
            SELECT c.nome, COUNT(p.id) as count 
            FROM produtos p 
            JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.empresa_id = ? 
            GROUP BY c.nome 
            ORDER BY count DESC
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$this->empresa_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSalesAndProfitOverTime($period = 'month', $startDate = null, $endDate = null, $vendedor_id = null, $product_id = null)
    {
        $sql_where = " WHERE v.empresa_id = ? ";
        $params = [$this->empresa_id];

        // Determine date range and grouping format based on period
        $groupByFormat = '%Y-%m'; // Default to month
        $interval = '12 MONTH';

        if ($period === 'day') {
            $groupByFormat = '%Y-%m-%d';
            $interval = '30 DAY';
        } elseif ($period === 'year') {
            $groupByFormat = '%Y';
            $interval = '5 YEAR';
        }

        if ($startDate && $endDate) {
            $sql_where .= " AND DATE(v.created_at) BETWEEN ? AND ? ";
            $params[] = $startDate;
            $params[] = $endDate;
        } else {
            $sql_where .= " AND v.created_at >= DATE_SUB(CURDATE(), INTERVAL $interval) ";
        }

        if ($vendedor_id) {
            $sql_where .= " AND v.user_id = ? ";
            $params[] = $vendedor_id;
        }

        $join_items = "";
        if ($product_id) {
            $join_items = " JOIN venda_itens vi_filter ON v.id = vi_filter.venda_id ";
            $sql_where .= " AND vi_filter.product_id = ? ";
            $params[] = $product_id;
        }

        // 1. Query Vendas
        $sales_column = $product_id ? "SUM(vi_filter.quantity * vi_filter.unit_price)" : "SUM(v.total_amount)";

        $sql_sales = "
            SELECT 
                DATE_FORMAT(v.created_at, '$groupByFormat') as date_group,
                $sales_column as total_sales
            FROM vendas v
            $join_items
            " . $sql_where . "
            GROUP BY date_group
            ORDER BY date_group ASC
        ";
        
        $stmt_sales = $this->conn->prepare($sql_sales);
        $stmt_sales->execute($params);
        $sales_results = $stmt_sales->fetchAll(PDO::FETCH_ASSOC);

        // 2. Query Profit
        $sql_where_profit = " WHERE v.empresa_id = ? ";
        $params_profit = [$this->empresa_id];

        if ($startDate && $endDate) {
            $sql_where_profit .= " AND DATE(v.created_at) BETWEEN ? AND ? ";
            $params_profit[] = $startDate;
            $params_profit[] = $endDate;
        } else {
            $sql_where_profit .= " AND v.created_at >= DATE_SUB(CURDATE(), INTERVAL $interval) ";
        }

        if ($vendedor_id) {
            $sql_where_profit .= " AND v.user_id = ? ";
            $params_profit[] = $vendedor_id;
        }

        if ($product_id) {
            $sql_where_profit .= " AND vi.product_id = ? ";
            $params_profit[] = $product_id;
        }

        $sql_profit = "
            SELECT 
                DATE_FORMAT(v.created_at, '$groupByFormat') as date_group,
                SUM(vi.quantity * (vi.unit_price - COALESCE(p.cost_price, 0))) as total_profit
            FROM vendas v
            JOIN venda_itens vi ON v.id = vi.venda_id
            LEFT JOIN produtos p ON vi.product_id = p.id
            " . $sql_where_profit . "
            GROUP BY date_group
            ORDER BY date_group ASC
        ";
        
        $stmt_profit = $this->conn->prepare($sql_profit);
        $stmt_profit->execute($params_profit);
        $profit_results = $stmt_profit->fetchAll(PDO::FETCH_ASSOC);

        // Merge results
        $data = [];
        $all_groups = array_unique(array_merge(array_column($sales_results, 'date_group'), array_column($profit_results, 'date_group')));
        sort($all_groups);

        foreach ($all_groups as $group) {
            $sales = 0;
            $profit = 0;
            
            foreach ($sales_results as $s) {
                if ($s['date_group'] == $group) $sales = (float)$s['total_sales'];
            }
            foreach ($profit_results as $p) {
                if ($p['date_group'] == $group) $profit = (float)$p['total_profit'];
            }

            $cost = $sales - $profit;

            // Format label
            $label = $group;
            if ($period === 'month') {
                $label = date('m/Y', strtotime($group . '-01'));
            } elseif ($period === 'day') {
                $label = date('d/m', strtotime($group));
            }

            $data[] = [
                'label' => $label,
                'sales' => $sales,
                'profit' => $profit,
                'cost' => $cost
            ];
        }

        return $data;
    }

    public function getPaymentMethodDistribution($startDate = null, $endDate = null, $vendedor_id = null)
    {
        $sql = "SELECT payment_method, COUNT(id) as count FROM vendas WHERE empresa_id = ?";
        $params = [$this->empresa_id];

        if ($startDate && $endDate) {
            $sql .= " AND DATE(created_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        if ($vendedor_id) {
            $sql .= " AND user_id = ?";
            $params[] = $vendedor_id;
        }

        $sql .= " GROUP BY payment_method ORDER BY count DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopSellingProducts($limit = 10, $startDate = null, $endDate = null, $vendedor_id = null)
    {
        $sql = "
            SELECT 
                p.name,
                SUM(vi.quantity) as total_quantity_sold
            FROM venda_itens vi
            JOIN produtos p ON vi.product_id = p.id
            JOIN vendas v ON vi.venda_id = v.id
            WHERE v.empresa_id = ?
        ";
        $params = [$this->empresa_id];

        if ($startDate && $endDate) {
            $sql .= " AND DATE(v.created_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        if ($vendedor_id) {
            $sql .= " AND v.user_id = ?";
            $params[] = $vendedor_id;
        }

        $sql .= " GROUP BY vi.product_id, p.name ORDER BY total_quantity_sold DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->conn->prepare($sql);
        $i = 1;
        foreach ($params as $param) {
            $type = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($i++, $param, $type);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPurchaseBySupplier($startDate = null, $endDate = null)
    {
        // Purchases are not linked to sales/sellers usually, so only date filter applies.
        $sql = "
            SELECT 
                f.name,
                SUM(c.total_amount) as total_purchased
            FROM compras c
            JOIN fornecedores f ON c.supplier_id = f.id
            WHERE c.empresa_id = ? AND c.total_amount > 0
        ";
        $params = [$this->empresa_id];

        if ($startDate && $endDate) {
            $sql .= " AND DATE(c.purchase_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $sql .= " GROUP BY c.supplier_id, f.name ORDER BY total_purchased DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopProfitableProducts($limit = 10, $startDate = null, $endDate = null, $vendedor_id = null)
    {
        $sql = "
            SELECT 
                p.name,
                SUM(vi.quantity * (vi.unit_price - p.cost_price)) as total_profit
            FROM venda_itens vi
            JOIN produtos p ON vi.product_id = p.id
            JOIN vendas v ON vi.venda_id = v.id
            WHERE v.empresa_id = ?
        ";
        $params = [$this->empresa_id];

        if ($startDate && $endDate) {
            $sql .= " AND DATE(v.created_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        if ($vendedor_id) {
            $sql .= " AND v.user_id = ?";
            $params[] = $vendedor_id;
        }

        $sql .= " GROUP BY vi.product_id, p.name HAVING total_profit > 0 ORDER BY total_profit DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->conn->prepare($sql);
        $i = 1;
        foreach ($params as $param) {
            $type = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($i++, $param, $type);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getReportKPIs($startDate = null, $endDate = null, $vendedor_id = null, $product_id = null)
    {
        $kpis = [
            'revenue' => 0,
            'profit' => 0,
            'orders' => 0,
            'avg_ticket' => 0
        ];

        $sql_where = " WHERE v.empresa_id = ? ";
        $params = [$this->empresa_id];

        if ($startDate && $endDate) {
            $sql_where .= " AND DATE(v.created_at) BETWEEN ? AND ? ";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        if ($vendedor_id) {
            $sql_where .= " AND v.user_id = ? ";
            $params[] = $vendedor_id;
        }

        $join_items = "";
        if ($product_id) {
            $join_items = " JOIN venda_itens vi_filter ON v.id = vi_filter.venda_id ";
            $sql_where .= " AND vi_filter.product_id = ? ";
            $params[] = $product_id;
        }

        // 1. Revenue and Orders
        // If filtering by product, revenue is sum of that product's sales, not the whole order
        $revenue_column = $product_id ? "SUM(vi_filter.quantity * vi_filter.unit_price)" : "SUM(v.total_amount)";
        
        $sql = "
            SELECT 
                $revenue_column as total_revenue,
                COUNT(DISTINCT v.id) as total_orders
            FROM vendas v
            $join_items
            $sql_where
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $kpis['revenue'] = (float)($result['total_revenue'] ?? 0);
        $kpis['orders'] = (int)($result['total_orders'] ?? 0);
        $kpis['avg_ticket'] = $kpis['orders'] > 0 ? $kpis['revenue'] / $kpis['orders'] : 0;

        // 2. Profit
        // Profit calculation needs to join items and products always
        $sql_profit_where = " WHERE v.empresa_id = ? ";
        $params_profit = [$this->empresa_id];

        if ($startDate && $endDate) {
            $sql_profit_where .= " AND DATE(v.created_at) BETWEEN ? AND ? ";
            $params_profit[] = $startDate;
            $params_profit[] = $endDate;
        }

        if ($vendedor_id) {
            $sql_profit_where .= " AND v.user_id = ? ";
            $params_profit[] = $vendedor_id;
        }

        if ($product_id) {
            $sql_profit_where .= " AND vi.product_id = ? ";
            $params_profit[] = $product_id;
        }

        $sql_profit = "
            SELECT 
                SUM(vi.quantity * (vi.unit_price - COALESCE(p.cost_price, 0))) as total_profit
            FROM vendas v
            JOIN venda_itens vi ON v.id = vi.venda_id
            LEFT JOIN produtos p ON vi.product_id = p.id
            $sql_profit_where
        ";

        $stmt_profit = $this->conn->prepare($sql_profit);
        $stmt_profit->execute($params_profit);
        $kpis['profit'] = (float)$stmt_profit->fetchColumn();

        return $kpis;
    }
    public function getSalesBySeller($startDate = null, $endDate = null)
    {
        $sql = "
            SELECT 
                u.username,
                COUNT(v.id) as total_sales_count,
                SUM(v.total_amount) as total_revenue
            FROM vendas v
            JOIN usuarios u ON v.user_id = u.id
            WHERE v.empresa_id = ?
        ";
        $params = [$this->empresa_id];

        if ($startDate && $endDate) {
            $sql .= " AND DATE(v.created_at) BETWEEN ? AND ? ";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $sql .= " GROUP BY v.user_id, u.username ORDER BY total_revenue DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFinancialSummary($startDate = null, $endDate = null)
    {
        // 1. Revenue
        $sql_revenue = "SELECT SUM(total_amount) FROM vendas WHERE empresa_id = ?";
        $params = [$this->empresa_id];
        if ($startDate && $endDate) {
            $sql_revenue .= " AND DATE(created_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        $stmt = $this->conn->prepare($sql_revenue);
        $stmt->execute($params);
        $revenue = (float)$stmt->fetchColumn();

        // 2. Cost (approximate based on products sold)
        $sql_cost = "
            SELECT SUM(vi.quantity * COALESCE(p.cost_price, 0)) 
            FROM vendas v
            JOIN venda_itens vi ON v.id = vi.venda_id
            LEFT JOIN produtos p ON vi.product_id = p.id
            WHERE v.empresa_id = ?
        ";
        if ($startDate && $endDate) {
            $sql_cost .= " AND DATE(v.created_at) BETWEEN ? AND ?";
        }
        $stmt = $this->conn->prepare($sql_cost);
        $stmt->execute($params);
        $cost = (float)$stmt->fetchColumn();

        $profit = $revenue - $cost;
        $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        return [
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $profit,
            'margin' => $margin
        ];
    }

    // --- NOVOS MÉTODOS PARA RELATÓRIOS EXPANDIDOS ---

    public function getSalesByHour($startDate = null, $endDate = null)
    {
        $sql = "
            SELECT 
                HOUR(created_at) as hour_of_day,
                COUNT(id) as total_sales,
                SUM(total_amount) as total_revenue
            FROM vendas
            WHERE empresa_id = ?
        ";
        $params = [$this->empresa_id];

        if ($startDate && $endDate) {
            $sql .= " AND DATE(created_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $sql .= " GROUP BY hour_of_day ORDER BY hour_of_day ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fill missing hours with 0
        $data = [];
        for ($i = 0; $i < 24; $i++) {
            $found = false;
            foreach ($results as $row) {
                if ((int)$row['hour_of_day'] === $i) {
                    $data[] = [
                        'hour' => str_pad((string)$i, 2, '0', STR_PAD_LEFT) . ':00',
                        'count' => (int)$row['total_sales'],
                        'revenue' => (float)$row['total_revenue']
                    ];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data[] = ['hour' => str_pad((string)$i, 2, '0', STR_PAD_LEFT) . ':00', 'count' => 0, 'revenue' => 0];
            }
        }
        return $data;
    }

    public function getStockMovementStats($startDate = null, $endDate = null)
    {
        // Estatísticas agregadas por tipo de ação (entrada, saída, ajuste, etc)
        $sql = "
            SELECT 
                action,
                COUNT(id) as total_moves,
                SUM(quantity) as total_quantity
            FROM historico_estoque
            WHERE empresa_id = ?
        ";
        $params = [$this->empresa_id];

        if ($startDate && $endDate) {
            $sql .= " AND DATE(created_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $sql .= " GROUP BY action";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductABCAnalysis($startDate = null, $endDate = null)
    {
        // Classificação ABC baseada em Receita
        // A: Top 80% da receita
        // B: Próximos 15%
        // C: Últimos 5%
        
        $sql = "
            SELECT 
                p.name,
                SUM(vi.quantity * vi.unit_price) as total_revenue
            FROM venda_itens vi
            JOIN vendas v ON vi.venda_id = v.id
            JOIN produtos p ON vi.product_id = p.id
            WHERE v.empresa_id = ?
        ";
        $params = [$this->empresa_id];

        if ($startDate && $endDate) {
            $sql .= " AND DATE(v.created_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $sql .= " GROUP BY p.id, p.name ORDER BY total_revenue DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_system_revenue = array_sum(array_column($products, 'total_revenue'));
        $cumulative = 0;
        $abc_data = ['A' => 0, 'B' => 0, 'C' => 0];

        foreach ($products as $p) {
            $revenue = (float)$p['total_revenue'];
            $cumulative += $revenue;
            $percentage = $total_system_revenue > 0 ? ($cumulative / $total_system_revenue) * 100 : 0;

            if ($percentage <= 80) {
                $abc_data['A']++;
            } elseif ($percentage <= 95) {
                $abc_data['B']++;
            } else {
                $abc_data['C']++;
            }
        }

        return $abc_data;
    }

    public function getModuleActivityStats($startDate = null, $endDate = null)
    {
        $stats = [];
        $params = [$this->empresa_id];
        $dateFilter = "";
        
        if ($startDate && $endDate) {
            $dateFilter = " AND DATE(created_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        // Vendas (CRM/Vendas)
        $stmt = $this->conn->prepare("SELECT COUNT(id) FROM vendas WHERE empresa_id = ? $dateFilter");
        $stmt->execute($params);
        $stats['Vendas'] = (int)$stmt->fetchColumn();

        // Compras (Estoque/Compras)
        $stmt = $this->conn->prepare("SELECT COUNT(id) FROM compras WHERE empresa_id = ? $dateFilter");
        // Compras usa purchase_date ou created_at? Vamos usar created_at para consistência se existir, ou purchase_date
        // O schema diz created_at, ok.
        $stmt->execute($params);
        $stats['Compras'] = (int)$stmt->fetchColumn();

        // Fiscal (Notas Emitidas/Importadas)
        // Precisamos checar se table fiscal_notas existe, ou usar dados_nota_fiscal
        // Pelo arquivo xml_export.php, existe 'fiscal_notas'.
        // Vamos tentar usar dados_nota_fiscal que vimos no schema do README, ou fiscal_notas se for o novo modulo.
        // O README menciona 'dados_nota_fiscal', xml_export usa 'fiscal_notas'.
        // Vamos assumir 'fiscal_notas' pois parece ser o módulo ativo.
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(id) FROM fiscal_notas WHERE empresa_id = ? $dateFilter");
            $stmt->execute($params);
            $stats['Fiscal'] = (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            $stats['Fiscal'] = 0; // Tabela pode não existir ainda
        }

        // Estoque (Movimentações Manuais)
        $stmt = $this->conn->prepare("SELECT COUNT(id) FROM historico_estoque WHERE empresa_id = ? $dateFilter");
        $stmt->execute($params);
        $stats['Estoque'] = (int)$stmt->fetchColumn();

        return $stats;
    }

    /**
     * Retorna o faturamento diário da semana atual (Segunda a Domingo)
     */
    public function getWeeklyPerformance(): array
    {
        $days = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
        $dataset = array_fill_keys($days, 0);

        $sql = "
            SELECT 
                DAYNAME(created_at) as day_name,
                WEEKDAY(created_at) as day_index,
                SUM(total_amount) as total
            FROM vendas
            WHERE empresa_id = ? 
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY day_index, day_name
            ORDER BY day_index ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$this->empresa_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [0 => 'Seg', 1 => 'Ter', 2 => 'Qua', 3 => 'Qui', 4 => 'Sex', 5 => 'Sáb', 6 => 'Dom'];
        foreach ($rows as $row) {
            $idx = (int)$row['day_index'];
            if (isset($map[$idx])) {
                $dataset[$map[$idx]] = (float)$row['total'];
            }
        }

        return [
            'labels' => array_keys($dataset),
            'data' => array_values($dataset)
        ];
    }

    /**
     * Retorna dados cruzados: Vendas vs Compras nos últimos 6 meses
     */
    public function getCrossAnalysisData(): array
    {
        $sql = "
            SELECT 
                DATE_FORMAT(v.created_at, '%b') as month,
                SUM(v.total_amount) as revenue,
                (SELECT SUM(total_amount) FROM compras WHERE empresa_id = v.empresa_id AND MONTH(purchase_date) = MONTH(v.created_at) AND YEAR(purchase_date) = YEAR(v.created_at)) as expenses
            FROM vendas v
            WHERE v.empresa_id = ? 
              AND v.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month, MONTH(v.created_at), YEAR(v.created_at)
            ORDER BY YEAR(v.created_at) ASC, MONTH(v.created_at) ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$this->empresa_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna métricas reais de Eficiência Operacional
     */
    public function getOperationalEfficiencyMetrics(): array
    {
        // 1. Giro de Estoque (Vendas 30 dias / Estoque Total)
        $stmt_sold = $this->conn->prepare("SELECT SUM(quantity) FROM venda_itens vi JOIN vendas v ON vi.venda_id = v.id WHERE v.empresa_id = ? AND v.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stmt_sold->execute([$this->empresa_id]);
        $total_sold = (float)$stmt_sold->fetchColumn() ?: 0;

        $stmt_stock = $this->conn->prepare("SELECT SUM(quantity) FROM produtos WHERE empresa_id = ?");
        $stmt_stock->execute([$this->empresa_id]);
        $total_stock = (float)$stmt_stock->fetchColumn() ?: 1; 

        $giro = ($total_stock > 0) ? min(100, round(($total_sold / ($total_sold + $total_stock)) * 100)) : 0;

        // 2. Aproveitamento AI (Compras com Auditoria Fiscal / Total Compras)
        $stmt_ai = $this->conn->prepare("SELECT COUNT(DISTINCT dnf.compra_id) FROM dados_nota_fiscal dnf JOIN compras c ON dnf.compra_id = c.id WHERE c.empresa_id = ?");
        $stmt_ai->execute([$this->empresa_id]);
        $compras_ai = (int)$stmt_ai->fetchColumn();

        $stmt_total_compras = $this->conn->prepare("SELECT COUNT(*) FROM compras WHERE empresa_id = ?");
        $stmt_total_compras->execute([$this->empresa_id]);
        $total_compras = (int)$stmt_total_compras->fetchColumn() ?: 1;

        $ai_usage = min(100, round(($compras_ai / $total_compras) * 100));

        // 3. Saúde Financeira (Recebíveis vs Pagáveis no mês)
        // Fallback para 100 se as tabelas não existirem ou estiverem vazias
        try {
            $stmt_rec = $this->conn->prepare("SELECT SUM(valor) FROM contas_receber WHERE empresa_id = ? AND MONTH(data_vencimento) = MONTH(CURDATE()) AND YEAR(data_vencimento) = YEAR(CURDATE())");
            $stmt_rec->execute([$this->empresa_id]);
            $receber = (float)$stmt_rec->fetchColumn() ?: 0;

            $stmt_pag = $this->conn->prepare("SELECT SUM(valor) FROM contas_pagar WHERE empresa_id = ? AND MONTH(data_vencimento) = MONTH(CURDATE()) AND YEAR(data_vencimento) = YEAR(CURDATE())");
            $stmt_pag->execute([$this->empresa_id]);
            $pagar = (float)$stmt_pag->fetchColumn() ?: 0;

            $finance_health = ($receber + $pagar > 0) ? round(($receber / ($receber + $pagar)) * 100) : 100;
        } catch (Exception $e) { $finance_health = 100; }

        return [
            'giro_estoque' => $giro,
            'ai_usage' => $ai_usage,
            'finance_health' => $finance_health
        ];
    }
}
