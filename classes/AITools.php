<?php
namespace App;

use PDO;

class AITools {
    private $pdo;
    private $empresa_id;

    public function __construct(PDO $pdo, $empresa_id) {
        $this->pdo = $pdo;
        $this->empresa_id = $empresa_id;
    }

    /**
     * Lista as ferramentas disponíveis e seus schemas para o Gemini.
     */
    public function getToolsSchema() {
        return [
            [
                'name' => 'get_low_stock_items',
                'description' => 'Lista produtos que estão com estoque abaixo do mínimo definido.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'limit' => ['type' => 'INTEGER', 'description' => 'Número máximo de itens para retornar (padrão 5)']
                    ]
                ]
            ],
            [
                'name' => 'get_sales_report',
                'description' => 'Obtém um relatório resumido de vendas para um período específico.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'period' => ['type' => 'STRING', 'description' => 'Período desejado: "hoje", "ontem", "esta_semana", "este_mes"'],
                    ],
                    'required' => ['period']
                ]
            ],
            [
                'name' => 'search_product',
                'description' => 'Busca informações detalhadas de um produto pelo nome ou SKU.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'query' => ['type' => 'STRING', 'description' => 'Nome ou parte do nome do produto']
                    ],
                    'required' => ['query']
                ]
            ],
            [
                'name' => 'get_financial_summary',
                'description' => 'Retorna um resumo financeiro rápido (Total Vendas vs Total Compras) do mês atual.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => (object)[] 
                ]
            ],
            [
                'name' => 'get_top_products',
                'description' => 'Lista os produtos mais vendidos, incluindo quem foi o vendedor destaque de cada um.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'limit' => ['type' => 'INTEGER', 'description' => 'Quantidade de produtos para listar (Padrão 5)']
                    ]
                ]
            ],
            [
                'name' => 'get_product_sales_history',
                'description' => 'Obtém o histórico de vendas de um produto específico agrupado por mês.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'product_name' => ['type' => 'STRING', 'description' => 'Nome ou SKU do produto'],
                        'months' => ['type' => 'INTEGER', 'description' => 'Número de meses para análise (padrão 6)']
                    ],
                    'required' => ['product_name']
                ]
            ],
            [
                'name' => 'get_slow_moving_items',
                'description' => 'Lista produtos com estoque parado (sem vendas) há um determinado período.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'days' => ['type' => 'INTEGER', 'description' => 'Dias sem vendas para considerar parado (padrão 30)']
                    ]
                ]
            ],
            [
                'name' => 'get_profit_analysis',
                'description' => 'Analisa a lucratividade (Receita vs Custo Estimado) para um período.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'period' => ['type' => 'STRING', 'description' => 'Período: "este_mes", "ultimo_mes", "esta_semana", "ano_atual"']
                    ]
                ]
            ]
        ];
    }

    // --- Implementação das Funções ---

    public function get_top_products($args) {
        $limit = $args['limit'] ?? 5;
        
        // Complex Query:
        // 1. Join itens and products to get totals.
        // 2. Subquery to find the user_id with max sales for that product.
        // 3. Join with users to get username.
        
        $sql = "
            SELECT 
                p.name as product_name,
                SUM(vi.quantity) as total_sold,
                (
                    SELECT u.username 
                    FROM venda_itens vi2
                    JOIN vendas v2 ON vi2.venda_id = v2.id
                    JOIN usuarios u ON v2.user_id = u.id
                    WHERE vi2.product_id = p.id
                    GROUP BY u.id
                    ORDER BY SUM(vi2.quantity) DESC
                    LIMIT 1
                ) as top_seller
            FROM venda_itens vi
            JOIN produtos p ON vi.product_id = p.id
            JOIN vendas v ON vi.venda_id = v.id
            WHERE v.empresa_id = :empresa_id
            GROUP BY p.id
            ORDER BY total_sold DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':empresa_id', $this->empresa_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) return "Ainda não há dados de vendas suficientes.";

        return json_encode([
            'data' => $data,
            'type' => 'table',
            'title' => 'Top Produtos & Vendedores'
        ]);
    }

    public function get_low_stock_items($args) {
        $limit = $args['limit'] ?? 5;
        $sql = "SELECT name, quantity, minimum_stock, (minimum_stock - quantity) as deficit 
                FROM produtos 
                WHERE empresa_id = :empresa_id AND quantity <= minimum_stock 
                ORDER BY deficit DESC 
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':empresa_id', $this->empresa_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) return "Ótimo! Nenhum produto está abaixo do estoque mínimo.";
        
        return json_encode(['data' => $data, 'type' => 'table', 'title' => 'Produtos com Estoque Baixo']);
    }

    public function get_sales_report($args) {
        $period = $args['period'] ?? 'este_mes';
        $dateCondition = "";
        
        switch($period) {
            case 'hoje': $dateCondition = "DATE(created_at) = CURDATE()"; break;
            case 'ontem': $dateCondition = "DATE(created_at) = CURDATE() - INTERVAL 1 DAY"; break;
            case 'esta_semana': $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)"; break;
            case 'este_mes': 
            default: $dateCondition = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"; break;
        }

        $sql = "SELECT 
                    COUNT(*) as total_orders, 
                    COALESCE(SUM(total_amount), 0) as total_revenue
                FROM vendas 
                WHERE empresa_id = :empresa_id AND $dateCondition";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':empresa_id' => $this->empresa_id]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        return json_encode([
            'data' => $summary, 
            'type' => 'card', 
            'title' => "Relatório de Vendas ($period)",
            'text' => "Total de vendas: " . number_format($summary['total_revenue'], 2, ',', '.') . " (" . $summary['total_orders'] . " pedidos)"
        ]);
    }

    public function search_product($args) {
        $query = $args['query'] ?? '';
        $sql = "SELECT name, sku, quantity, price 
                FROM produtos 
                WHERE empresa_id = :empresa_id AND (name LIKE :q OR sku LIKE :q) 
                LIMIT 5";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':empresa_id' => $this->empresa_id, ':q' => "%$query%"]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) return "Nenhum produto encontrado para '$query'.";

        return json_encode(['data' => $data, 'type' => 'list', 'title' => 'Resultados da Busca']);
    }

    public function get_financial_summary($args) {
        // Vendas Mês
        $sqlVendas = "SELECT COALESCE(SUM(total_amount), 0) FROM vendas WHERE empresa_id = ? AND MONTH(created_at) = MONTH(CURDATE())";
        $stmt = $this->pdo->prepare($sqlVendas);
        $stmt->execute([$this->empresa_id]);
        $vendas = $stmt->fetchColumn();

        // Compras Mês
        $sqlCompras = "SELECT COALESCE(SUM(total_amount), 0) FROM compras WHERE empresa_id = ? AND MONTH(purchase_date) = MONTH(CURDATE())";
        $stmt = $this->pdo->prepare($sqlCompras);
        $stmt->execute([$this->empresa_id]);
        $compras = $stmt->fetchColumn();

        return json_encode([
            'data' => ['vendas' => $vendas, 'compras' => $compras, 'saldo' => $vendas - $compras],
            'type' => 'chart',
            'chartType' => 'bar',
            'title' => 'Resumo Financeiro (Mês Atual)'
        ]);
    }

    public function get_product_sales_history($args) {
        $productName = $args['product_name'] ?? '';
        $months = $args['months'] ?? 6;

        $sql = "
            SELECT 
                DATE_FORMAT(v.created_at, '%Y-%m') as mes,
                SUM(vi.quantity) as total_vendido,
                SUM(vi.quantity * vi.unit_price) as faturamento
            FROM venda_itens vi
            JOIN vendas v ON vi.venda_id = v.id
            JOIN produtos p ON vi.product_id = p.id
            WHERE v.empresa_id = :empresa_id
            AND (p.name LIKE :pname OR p.sku LIKE :psku)
            AND v.created_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY mes
            ORDER BY mes ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':empresa_id', $this->empresa_id, PDO::PARAM_INT);
        $stmt->bindValue(':pname', "%$productName%", PDO::PARAM_STR);
        $stmt->bindValue(':psku', "%$productName%", PDO::PARAM_STR);
        $stmt->bindValue(':months', (int)$months, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) return "Não encontrei vendas para '$productName' nos últimos $months meses.";

        return json_encode([
            'data' => $data,
            'type' => 'chart',
            'chartType' => 'line',
            'title' => "Histórico de Vendas: $productName",
            'xAxis' => 'mes',
            'series' => ['total_vendido']
        ]);
    }

    public function get_slow_moving_items($args) {
        $days = $args['days'] ?? 30;

        // Produtos com estoque positivo que NÃO estão em vendas recentes
        $sql = "
            SELECT 
                p.name, 
                p.quantity, 
                p.price, 
                (p.quantity * p.price) as capital_parado
            FROM produtos p
            WHERE p.empresa_id = :empresa_id
            AND p.quantity > 0
            AND p.id NOT IN (
                SELECT DISTINCT vi.product_id
                FROM venda_itens vi
                JOIN vendas v ON vi.venda_id = v.id
                WHERE v.empresa_id = :empresa_id_sub
                AND v.created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                AND v.created_at IS NOT NULL
            )
            ORDER BY capital_parado DESC
            LIMIT 10
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':empresa_id', $this->empresa_id, PDO::PARAM_INT);
        $stmt->bindValue(':empresa_id_sub', $this->empresa_id, PDO::PARAM_INT);
        $stmt->bindValue(':days', (int)$days, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) return "Ótimas notícias! Todos os produtos com estoque tiveram vendas nos últimos $days dias.";

        // Calcular total parado para resumo
        $totalParado = array_sum(array_column($data, 'capital_parado'));

        return json_encode([
            'data' => $data,
            'type' => 'table',
            'title' => "Produtos Sem Giro (> $days dias)",
            'summary' => "Total em capital parado: R$ " . number_format($totalParado, 2, ',', '.')
        ]);
    }

    public function get_profit_analysis($args) {
        $period = $args['period'] ?? 'este_mes';
        $dateCondition = "";

        switch($period) {
            case 'hoje': $dateCondition = "DATE(v.created_at) = CURDATE()"; break;
            case 'ontem': $dateCondition = "DATE(v.created_at) = CURDATE() - INTERVAL 1 DAY"; break;
            case 'esta_semana': $dateCondition = "YEARWEEK(v.created_at, 1) = YEARWEEK(CURDATE(), 1)"; break;
            case 'este_mes': $dateCondition = "MONTH(v.created_at) = MONTH(CURDATE()) AND YEAR(v.created_at) = YEAR(CURDATE())"; break;
            case 'ultimo_mes': $dateCondition = "MONTH(v.created_at) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(v.created_at) = YEAR(CURDATE() - INTERVAL 1 MONTH)"; break;
            case 'ano_atual': $dateCondition = "YEAR(v.created_at) = YEAR(CURDATE())"; break;
            default: $dateCondition = "MONTH(v.created_at) = MONTH(CURDATE()) AND YEAR(v.created_at) = YEAR(CURDATE())"; break;
        }

        $sql = "
            SELECT 
                COUNT(DISTINCT v.id) as total_pedidos,
                COALESCE(SUM(vi.quantity * vi.unit_price), 0) as receita_total,
                COALESCE(SUM(vi.quantity * p.cost_price), 0) as custo_estimado
            FROM venda_itens vi
            JOIN vendas v ON vi.venda_id = v.id
            JOIN produtos p ON vi.product_id = p.id
            WHERE v.empresa_id = :empresa_id
            AND $dateCondition
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':empresa_id' => $this->empresa_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $receita = $result['receita_total'] ?? 0;
        $custo = $result['custo_estimado'] ?? 0;
        $lucro = $receita - $custo;
        $margem = ($receita > 0) ? ($lucro / $receita) * 100 : 0;

        return json_encode([
            'data' => [
                'receita' => $receita,
                'custo' => $custo,
                'lucro_bruto' => $lucro,
                'margem' => round($margem, 2) . '%'
            ],
            'type' => 'card',
            'title' => "Análise de Lucratividade ($period)",
            'text' => "Receita: R$ " . number_format($receita, 2, ',', '.') . "\n" .
                      "Lucro Bruto: R$ " . number_format($lucro, 2, ',', '.') . "\n" .
                      "Margem: " . round($margem, 1) . "%"
        ]);
    }
}
