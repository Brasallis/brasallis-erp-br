<?php
// scripts/decisive_crm_fix.php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/funcoes.php';

$conn = connect_db();
if (!$conn) { die("Erro de conexão.\n"); }

echo "Executando correção decisiva de CRM...\n";

try {
    // 1. Alterar o ENUM para garantir que 'lead' e 'negociacao' existam
    $conn->exec("ALTER TABLE crm_oportunidades MODIFY COLUMN status ENUM('lead','negociacao','ganho','perdido','aberto') DEFAULT 'lead'");
    echo "ENUM atualizado (adicionado suporte temporário a 'aberto').\n";

    // 2. Normalizar 'aberto' para 'lead'
    $stmt = $conn->prepare("UPDATE crm_oportunidades SET status = 'lead' WHERE status = 'aberto'");
    $stmt->execute();
    echo "Registros 'aberto' convertidos para 'lead': " . $stmt->rowCount() . "\n";

    // 3. Remover 'aberto' do ENUM final
    $conn->exec("ALTER TABLE crm_oportunidades MODIFY COLUMN status ENUM('lead','negociacao','ganho','perdido') DEFAULT 'lead'");
    echo "ENUM finalizado (removido status legado 'aberto').\n";

    echo "Correção concluída com sucesso!\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
