<?php
session_start();
require_once __DIR__ . '/includes/funcoes.php';
require_once __DIR__ . '/includes/planos_config.php';

header('Content-Type: application/json');

$response = [
    'session' => $_SESSION,
    'user_data' => null,
    'empresa_data' => null,
    'can_access_crm' => false
];

if (isset($_SESSION['user_id'])) {
    $conn = connect_db();
    
    $stmt = $conn->prepare("SELECT id, username, user_type, permissions FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $response['user_data'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmtE = $conn->prepare("SELECT id, name, ai_plan, active_modules FROM empresas WHERE id = ?");
    $stmtE->execute([$_SESSION['empresa_id']]);
    $response['empresa_data'] = $stmtE->fetch(PDO::FETCH_ASSOC);
    
    $response['can_access_crm'] = check_permission('crm', 1);
}

echo json_encode($response, JSON_PRETTY_PRINT);
