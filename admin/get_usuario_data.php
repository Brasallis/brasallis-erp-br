<?php
// admin/get_usuario_data.php
// Endpoint para recuperar dados de usuário via AJAX (Suporte a Edição no RH)

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/funcoes.php';

// Segurança: Apenas logados e com permissão de RH (ou Admin)
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$empresa_id = $_SESSION['empresa_id'];

if ($id <= 0) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

try {
    $conn = connect_db();
    
    // Busca os dados básicos + campos de RH
    $stmt = $conn->prepare("
        SELECT id, username, email, user_type, plan,
               data_admissao, cpf, celular, status_colaborador 
        FROM usuarios 
        WHERE id = ? AND empresa_id = ?
    ");
    $stmt->execute([$id, $empresa_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['error' => 'Usuário não encontrado ou fora do escopo da empresa']);
        exit;
    }

    // Busca setor e cargo
    $stmtS = $conn->prepare("SELECT setor_id, cargo_id FROM usuario_setor WHERE user_id = ?");
    $stmtS->execute([$id]);
    $vinc = $stmtS->fetch(PDO::FETCH_ASSOC);
    
    if ($vinc) {
        $user['setor_id'] = $vinc['setor_id'];
        $user['cargo_id'] = $vinc['cargo_id'];
    } else {
        $user['setor_id'] = null;
        $user['cargo_id'] = null;
    }

    header('Content-Type: application/json');
    echo json_encode($user);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
