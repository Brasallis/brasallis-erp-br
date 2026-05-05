<?php
// api/get_sale_details.php - DETALHES DOS ITENS
header('Content-Type: application/json');
require_once '../vendor/autoload.php';
require_once '../includes/funcoes.php';

use App\Core\Database;
use App\Repository\VendaRepository;

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    http_response_code(401); exit;
}

$empresa_id = $_SESSION['empresa_id'];
$venda_id = $_GET['id'] ?? null;

if (!$venda_id) { echo json_encode([]); exit; }

try {
    $vendaRepo = new VendaRepository(Database::getInstance(), $empresa_id);
    $items = $vendaRepo->getVendaDetails((int)$venda_id);

    echo json_encode($items);
} catch (Exception $e) {
    http_response_code(500);
}
