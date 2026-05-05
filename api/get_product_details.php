<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

// 1. Verificação de Autenticação
if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Acesso não autorizado.']);
    exit;
}

require_once '../includes/funcoes.php';

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$product_id = $_GET['id'] ?? 0;

if (empty($product_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do produto não fornecido.']);
    exit;
}

try {
    // Buscar detalhes do produto
    $stmt_produto = $conn->prepare(
        "SELECT p.id, p.name, p.description, p.sku, p.quantity, f.name as fornecedor_nome 
         FROM produtos p
         LEFT JOIN fornecedores f ON p.fornecedor_id = f.id
         WHERE p.id = ? AND p.empresa_id = ?"
    );
    $stmt_produto->execute([$product_id, $empresa_id]);
    $produto = $stmt_produto->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        http_response_code(404);
        echo json_encode(['error' => 'Produto não encontrado.']);
        exit;
    }

    // Buscar histórico de movimentações
    $stmt_historico = $conn->prepare(
        "SELECT h.action, h.quantity, h.created_at, u.username 
         FROM historico_estoque h
         JOIN usuarios u ON h.user_id = u.id
         WHERE h.product_id = ? AND h.empresa_id = ?
         ORDER BY h.created_at DESC
         LIMIT 10"
    );
    $stmt_historico->execute([$product_id, $empresa_id]);
    $historico = $stmt_historico->fetchAll(PDO::FETCH_ASSOC);

    // Montar a resposta
    $response = [
        'details' => $produto,
        'history' => $historico
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Erro ao buscar detalhes do produto: " . $e->getMessage());
    echo json_encode(['error' => 'Erro no servidor ao buscar detalhes do produto.']);
}
?>
