<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../includes/funcoes.php';

header('Content-Type: application/json');

if (!isset($_SESSION['empresa_id']) || !isset($_GET['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

$db = connect_db();
$stmt = $db->prepare("SELECT id, username, email, user_type, cpf, celular, status_colaborador, permissions FROM usuarios WHERE id = ? AND empresa_id = ?");
$stmt->execute([$_GET['id'], $_SESSION['empresa_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuário não encontrado']);
    exit;
}

echo json_encode($user);
