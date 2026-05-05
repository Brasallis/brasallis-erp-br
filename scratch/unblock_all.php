<?php
require_once __DIR__ . '/src/Core/Database.php';
use App\Core\Database;

try {
    $db = Database::getInstance();
    
    // Resetar TODAS as empresas para 'active' e estender a data de faturamento para 2027
    // Isso garante que o USER consiga testar tudo sem interrupções de billing.
    $stmt = $db->prepare("UPDATE empresas SET subscription_status = 'active', next_billing_at = '2027-01-01 00:00:00'");
    $stmt->execute();
    
    $affected = $stmt->rowCount();
    echo "Sucesso: $affected empresas foram desbloqueadas e o faturamento foi estendido para 2027.\n";
    
} catch (Exception $e) {
    echo "Erro Crítico ao Desbloquear: " . $e->getMessage() . "\n";
}
