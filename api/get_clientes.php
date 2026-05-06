<?php
// api/get_clientes.php - Simple customer lookup for PDV
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    echo json_encode([]);
    exit;
}

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$term = $_GET['term'] ?? '';

if (empty($term)) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, nome, cpf_cnpj, email FROM clientes WHERE empresa_id = ? AND (nome LIKE ? OR cpf_cnpj LIKE ?) LIMIT 10");
    $stmt->execute([$empresa_id, "%$term%", "%$term%"]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($clientes);
} catch (Exception $e) {
    echo json_encode([]);
}
