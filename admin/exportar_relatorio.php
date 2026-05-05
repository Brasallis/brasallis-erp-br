<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

// Validações de segurança
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    die('Acesso negado.');
}
if (!podeAcessar('relatorios_avancados')) {
    http_response_code(403);
    die('Funcionalidade não permitida pelo seu plano.');
}

require_once '../includes/funcoes.php';
$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// --- LER PARÂMETROS DO FILTRO ---
$report_type = $_GET['report'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$product_id_filter = $_GET['product_id'] ?? 'all';
$category_id_filter = $_GET['categoria_id'] ?? 'all';
$end_date_for_query = date('Y-m-d 23:59:59', strtotime($end_date));

// --- LÓGICA PARA GERAR O CSV ---

// Define o nome do arquivo e os cabeçalhos HTTP
$filename = "relatorio_" . $report_type . "_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Abre o output stream do PHP para escrita
$output = fopen('php://output', 'w');

// Adiciona o BOM para garantir a codificação UTF-8 correta no Excel
fputs($output, "\xEF\xBB\xBF");

// --- CONSTRUIR E EXECUTAR A QUERY ---

$data_sql = '';
$headers = [];

// Base da query de vendas
$sales_where_clauses = ["h.empresa_id = ?", "TRIM(LOWER(h.action)) = 'saida'", "h.created_at BETWEEN ? AND ?"];
$sales_params = [$empresa_id, $start_date, $end_date_for_query];
if ($product_id_filter !== 'all') {
    $sales_where_clauses[] = "h.product_id = ?";
    $sales_params[] = $product_id_filter;
}
if ($category_id_filter !== 'all') {
    $sales_where_clauses[] = "p.categoria_id = ?";
    $sales_params[] = $category_id_filter;
}
$sales_where_sql = " WHERE " . implode(" AND ", $sales_where_clauses);


// Define a query e os cabeçalhos com base no tipo de relatório
switch ($report_type) {
    case 'vendas_por_periodo':
        $headers = ['Data', 'Valor Total Vendido (R$)'];
        fputcsv($output, $headers);
        $sql = "SELECT DATE(h.created_at) as data, SUM(h.quantity * p.price) as total FROM historico_estoque h JOIN produtos p ON h.product_id = p.id" . $sales_where_sql . " GROUP BY data ORDER BY data ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute($sales_params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [date('d/m/Y', strtotime($row['data'])), number_format($row['total'], 2, ',', '.')]);
        }
        break;

    case 'top_produtos':
        $headers = ['Produto', 'Quantidade Vendida'];
        fputcsv($output, $headers);
        $sql = "SELECT p.name, SUM(h.quantity) as total FROM historico_estoque h JOIN produtos p ON h.product_id = p.id" . $sales_where_sql . " GROUP BY p.name ORDER BY total DESC LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->execute($sales_params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$row['name'], $row['total']]);
        }
        break;

    case 'top_produtos_lucrativos':
        $headers = ['Produto', 'Lucro Total (R$)'];
        fputcsv($output, $headers);
        $sql = "SELECT p.name, SUM(h.quantity * (p.price - p.cost_price)) as total FROM historico_estoque h JOIN produtos p ON h.product_id = p.id" . $sales_where_sql . " GROUP BY p.id, p.name HAVING total > 0 ORDER BY total DESC LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->execute($sales_params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$row['name'], number_format($row['total'], 2, ',', '.')]);
        }
        break;

    case 'top_vendedores':
        $headers = ['Vendedor', 'Valor Total Vendido (R$)'];
        fputcsv($output, $headers);
        $sql = "SELECT u.username, SUM(h.quantity * p.price) as total FROM historico_estoque h JOIN produtos p ON h.product_id = p.id JOIN usuarios u ON h.user_id = u.id" . $sales_where_sql . " GROUP BY u.username ORDER BY total DESC LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->execute($sales_params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$row['username'], number_format($row['total'], 2, ',', '.')]);
        }
        break;

    case 'estoque_por_categoria':
        $headers = ['Categoria', 'Quantidade Total em Estoque'];
        fputcsv($output, $headers);
        $sql = "SELECT c.nome, SUM(p.quantity) as total FROM produtos p JOIN categorias c ON p.categoria_id = c.id WHERE p.empresa_id = ? GROUP BY c.nome ORDER BY total DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$empresa_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$row['nome'], $row['total']]);
        }
        break;

    default:
        fputcsv($output, ['Erro', 'Tipo de relatorio invalido.']);
        break;
}

fclose($output);
exit();
