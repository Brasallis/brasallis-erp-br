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
    echo json_encode(['error' => 'ID do fornecedor inválido.']);
    http_response_code(400);
    exit;
}

$supplier_id = (int)$_GET['id'];
$conn = connect_db();

if (!$conn) {
    echo json_encode(['error' => 'Erro ao conectar ao banco de dados.']);
    http_response_code(500);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM fornecedores WHERE id = :id AND empresa_id = :empresa_id");
$stmt->bindParam(':id', $supplier_id, PDO::PARAM_INT);
$stmt->bindParam(':empresa_id', $_SESSION['empresa_id'], PDO::PARAM_INT);
$stmt->execute();

$supplier = $stmt->fetch(PDO::FETCH_ASSOC);

if ($supplier) {
    echo json_encode($supplier);
} else {
    echo json_encode(['error' => 'Fornecedor não encontrado.']);
    http_response_code(404);
}
?>
