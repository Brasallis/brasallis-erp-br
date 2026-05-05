<?php
/**
 * sair.php
 * Finaliza a sessão do usuário e limpa o status 'Online' no Super Admin instantaneamente.
 */
session_start();

if (isset($_SESSION['user_id'])) {
    require_once 'includes/funcoes.php';
    try {
        $conn = connect_db();
        if ($conn) {
            // Define a última atividade para 1 hora atrás (expira imediatamente do painel)
            $stmt = $conn->prepare("UPDATE usuarios SET last_active_at = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        }
    } catch (Exception $e) {
        // Silêncio
    }
}

session_destroy();
header('Location: index.php');
exit();
