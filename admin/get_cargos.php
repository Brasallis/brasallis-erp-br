<?php
// admin/get_cargos.php
require_once __DIR__ . '/../includes/funcoes.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$setor_id = isset($_GET['setor_id']) ? (int)$_GET['setor_id'] : 0;

if ($setor_id <= 0) {
    echo json_encode([]);
    exit;
}

$conn = connect_db();
try {
    $stmt = $conn->prepare("SELECT id, nome FROM cargos WHERE setor_id = ? ORDER BY nome ASC");
    $stmt->execute([$setor_id]);
    $cargos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cargos);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
