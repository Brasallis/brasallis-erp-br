<?php
require_once __DIR__ . '/../includes/funcoes.php';

$conn = connect_db();

header('Content-Type: application/json');

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT id, name, description, price, quantity, minimum_stock, lote, validade, observacoes, category FROM produtos WHERE id = :id");
        $stmt->bindParam(':id', $product_id);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            echo json_encode($product);
        } else {
            echo json_encode(['error' => 'Produto não encontrado.']);
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar dados do produto: " . $e->getMessage());
        echo json_encode(['error' => 'Erro de banco de dados ao buscar produto.']);
    }
} else {
    echo json_encode(['error' => 'ID do produto inválido.']);
}
?>
