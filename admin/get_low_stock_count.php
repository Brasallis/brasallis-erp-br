<?php
require_once __DIR__ . '/../includes/funcoes.php';

$conn = connect_db();

header('Content-Type: application/json');

$low_stock_count = 0;
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM produtos WHERE quantity <= minimum_stock");
    $low_stock_count = $stmt->fetchColumn();
    echo json_encode(['count' => $low_stock_count]);
} catch (PDOException $e) {
    error_log("Erro ao buscar contagem de estoque baixo: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao buscar contagem de estoque baixo.']);
}
?>
