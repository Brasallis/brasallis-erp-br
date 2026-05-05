<?php
/**
 * api/heartbeat.php
 * Endpoint de 'Batimento Cardíaco' do sistema.
 * Mantém o status 'Online Agora' ativo enquanto o usuário estiver com a aba aberta.
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

// Só processa se o usuário estiver logado
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../includes/funcoes.php';
    
    try {
        $conn = connect_db();
        if ($conn) {
            $current_page = $_GET['module'] ?? 'Unknown';
            $stmt = $conn->prepare("UPDATE usuarios SET last_active_at = NOW(), last_module = ? WHERE id = ?");
            $stmt->execute([$current_page, $_SESSION['user_id']]);
            echo json_encode(['status' => 'alive', 'timestamp' => date('Y-m-d H:i:s')]);
            exit;
        }
    } catch (Exception $e) {
        // Silêncio
    }
}

echo json_encode(['status' => 'idle']);
