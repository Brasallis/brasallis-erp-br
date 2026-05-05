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

// Apenas administradores podem acessar
if ($_SESSION['user_type'] !== 'admin') {
    echo json_encode(['error' => 'Acesso não autorizado.']);
    http_response_code(403);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID do usuário inválido.']);
    http_response_code(400);
    exit;
}

$user_id = (int)$_GET['id'];
$conn = connect_db();

if (!$conn) {
    echo json_encode(['error' => 'Erro ao conectar ao banco de dados.']);
    http_response_code(500);
    exit;
}

// Busca os dados do usuário, excluindo a senha por segurança
$stmt = $conn->prepare("SELECT id, username, email, user_type FROM usuarios WHERE id = :id AND empresa_id = :empresa_id");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':empresa_id', $_SESSION['empresa_id'], PDO::PARAM_INT);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo json_encode($user);
} else {
    echo json_encode(['error' => 'Usuário não encontrado.']);
    http_response_code(404);
}
?>
