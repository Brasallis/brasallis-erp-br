<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['error' => 'Acesso não autorizado. Requer autenticação.']);
    exit();
}
header('Content-Type: application/json');
require_once '../includes/funcoes.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID da compra inválido.']);
    http_response_code(400);
    exit;
}

$purchase_id = (int)$_GET['id'];
$conn = connect_db();

if (!$conn) {
    echo json_encode(['error' => 'Erro ao conectar ao banco de dados.']);
    http_response_code(500);
    exit;
}

// Busca os dados principais da compra, garantindo que pertence à empresa do usuário
$stmt = $conn->prepare("SELECT id, supplier_id, purchase_date, fiscal_note_path FROM compras WHERE id = :id AND empresa_id = :empresa_id");
$stmt->bindParam(':id', $purchase_id, PDO::PARAM_INT);
$stmt->bindParam(':empresa_id', $_SESSION['empresa_id'], PDO::PARAM_INT);
$stmt->execute();
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase) {
    echo json_encode(['error' => 'Compra não encontrada.']);
    http_response_code(404);
    exit;
}

// Busca os itens da compra
$items_stmt = $conn->prepare("SELECT product_id, quantity, unit_price FROM itens_compra WHERE purchase_id = :purchase_id");
$items_stmt->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
$items_stmt->execute();
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

$purchase['items'] = $items;

echo json_encode($purchase);
?>
