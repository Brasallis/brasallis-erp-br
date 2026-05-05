<?php
require_once '../includes/funcoes.php';

header('Content-Type: application/json');

if (!isset($_GET['product_id'])) {
    echo json_encode(['error' => 'ID do produto não fornecido']);
    exit;
}

$product_id = (int)$_GET['product_id'];
$conn = connect_db();

try {
    $stmt = $conn->prepare("SELECT * FROM lotes WHERE produto_id = ? ORDER BY data_entrada DESC");
    $stmt->execute([$product_id]);
    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($lots);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar lotes: ' . $e->getMessage()]);
}
?>
