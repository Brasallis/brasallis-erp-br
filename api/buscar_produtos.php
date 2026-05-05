<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['error' => 'Acesso não autorizado.']);
    exit();
}

// Produção: erros ficam no log, nunca expostos ao cliente
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/funcoes.php';

$conn = connect_db();

header('Content-Type: application/json');

$search_query = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';

$products = [];
if (!empty($search_query)) {
    $stmt = $conn->prepare("SELECT id, name, quantity FROM produtos WHERE name LIKE :search_query AND empresa_id = :empresa_id ORDER BY name ASC LIMIT 10");
    $search_param = '%' . $search_query . '%';
    $stmt->bindParam(':search_query', $search_param);
    $stmt->bindParam(':empresa_id', $_SESSION['empresa_id'], PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($products);
?>
