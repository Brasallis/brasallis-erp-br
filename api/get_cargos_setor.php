<?php
// api/get_cargos_setor.php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

header('Content-Type: application/json');
require_once '../includes/funcoes.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

if (!isset($_GET['setor_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetro setor_id obrigatório.']);
    exit;
}

$setorId = (int)$_GET['setor_id'];
$empresaId = $_SESSION['empresa_id'];
$conn = connect_db();

try {
    // Verifica se setor pertence a empresa
    $check = $conn->prepare("SELECT id FROM setores WHERE id = ? AND empresa_id = ?");
    $check->execute([$setorId, $empresaId]);
    if ($check->rowCount() == 0) {
        throw new Exception("Setor inválido.");
    }

    $stmt = $conn->prepare("SELECT id, nome, nivel_hierarquia FROM cargos WHERE setor_id = ? ORDER BY nome ASC");
    $stmt->execute([$setorId]);
    $cargos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($cargos);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
