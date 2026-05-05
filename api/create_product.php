<?php
require_once '../includes/funcoes.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connect_db();
    $empresa_id = $_SESSION['empresa_id'];

    $name = $_POST['name'] ?? '';
    $sku = $_POST['sku'] ?? null;
    $categoria_id = !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null;
    $unidade_medida = $_POST['unidade_medida'] ?? 'un';
    $cost_price = $_POST['cost_price'] ?? 0;
    $price = $_POST['price'] ?? 0;
    $minimum_stock = $_POST['minimum_stock'] ?? 5;

    if (empty($name) || empty($cost_price) || empty($price)) {
        echo json_encode(['success' => false, 'message' => 'Preencha os campos obrigatórios.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO produtos (empresa_id, name, sku, categoria_id, unidade_medida, cost_price, price, minimum_stock, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$empresa_id, $name, $sku, $categoria_id, $unidade_medida, $cost_price, $price, $minimum_stock]);
        $id = $conn->lastInsertId();

        echo json_encode([
            'success' => true,
            'product' => [
                'id' => $id,
                'name' => $name,
                'sku' => $sku,
                'cost_price' => (float)$cost_price
            ]
        ]);
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            echo json_encode(['success' => false, 'message' => 'SKU já existe.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro no banco: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}
