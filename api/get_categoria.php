<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

// 1. Verificação de Autenticação e Empresa
if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Acesso não autorizado.']);
    exit;
}

require_once '../includes/funcoes.php';

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$categoria_id = $_GET['id'] ?? null;

if (!$categoria_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID da categoria não fornecido.']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, nome FROM categorias WHERE id = ? AND empresa_id = ?");
    $stmt->execute([$categoria_id, $empresa_id]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($categoria) {
        echo json_encode($categoria);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Categoria não encontrada ou não pertence à sua empresa.']);
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Erro no servidor ao buscar categoria.']);
}

?>
