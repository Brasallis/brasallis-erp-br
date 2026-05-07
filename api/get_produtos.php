<?php
// api/get_produtos.php - Retorna todos os produtos ativos para o PDV
header('Content-Type: application/json');
require_once '../includes/funcoes.php';

$conn = connect_db();

if (!isset($_SESSION['empresa_id'])) {
    echo json_encode([]);
    exit;
}

$empresa_id = $_SESSION['empresa_id'];

try {
    // Busca produtos com saldo em estoque e join com categorias para os chips
    $sql = "SELECT p.id, p.name, p.price, p.quantity as stock, c.nome as category 
            FROM produtos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.empresa_id = ? AND p.quantity > 0 
            ORDER BY p.name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$empresa_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
