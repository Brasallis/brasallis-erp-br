<?php
require_once '../vendor/autoload.php';

use App\Repository\ProdutoRepository;
use App\Core\Database;

if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

if (!isset($_SESSION['user_id'], $_SESSION['empresa_id'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['error' => 'Acesso não autorizado. Requer autenticação.']);
    exit();
}

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do produto inválido.']);
    exit;
}

try {
    $product_id = (int)$_GET['id'];
    $produtoRepository = new ProdutoRepository(Database::getInstance(), $_SESSION['empresa_id']);
    $product = $produtoRepository->findById($product_id);

    if ($product) {
        echo json_encode($product);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Produto não encontrado.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro no servidor: ' . $e->getMessage()]);
}
?>
