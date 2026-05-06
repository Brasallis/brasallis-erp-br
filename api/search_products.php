<?php
header('Content-Type: application/json');
require_once '../includes/funcoes.php';

// A conexão com o banco já inicia a sessão via funcoes.php
$conn = connect_db();

// 1. Verificação de Autenticação
if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Acesso não autorizado.',
        'debug' => [
            'has_session' => session_status() === PHP_SESSION_ACTIVE,
            'user_id_set' => isset($_SESSION['user_id']),
            'empresa_id_set' => isset($_SESSION['empresa_id'])
        ]
    ]);
    exit;
}
$empresa_id = $_SESSION['empresa_id'];
$query = $_GET['term'] ?? '';

if (strlen($query) < 1) { // Permite busca por 1 caractere, útil para SKUs curtos
    echo json_encode([]);
    exit;
}

$trimmed_query = trim($query);

if (strlen($trimmed_query) < 1) {
    echo json_encode([]);
    exit;
}

try {
    // A consulta busca por correspondência exata no SKU ou parcial no nome
    $sql = "SELECT id, name, sku, price, quantity FROM produtos WHERE empresa_id = ? AND (sku = ? OR name LIKE ?)";
    
    // Filtra por estoque apenas se solicitado (ex: PDV)
    if (isset($_GET['in_stock']) && $_GET['in_stock'] == '1') {
        $sql .= " AND quantity > 0";
    }
    
    $sql .= " LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$empresa_id, $trimmed_query, '%' . $trimmed_query . '%']);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro no servidor ao buscar produtos.']);
}

?>
