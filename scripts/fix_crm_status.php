<?php
// scripts/fix_crm_status.php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/funcoes.php';

$conn = connect_db();
if (!$conn) { die("Erro de conexão.\n"); }

echo "Normalizando status de CRM...\n";
try {
    $stmt = $conn->prepare("UPDATE crm_oportunidades SET status = 'lead' WHERE status = 'aberto'");
    $stmt->execute();
    echo "Sucesso: " . $stmt->rowCount() . " registros atualizados.\n";
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
