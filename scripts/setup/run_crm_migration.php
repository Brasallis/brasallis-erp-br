<?php
// scripts/setup/run_crm_migration.php
echo "--- Iniciando Migracao: CRM & Vendas (002) ---\n";

require_once __DIR__ . '/../../includes/db_config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexao estabelecida.\n";
} catch (PDOException $e) {
    die("Erro conexao: " . $e->getMessage());
}

$sqlFile = __DIR__ . '/../migrations/002_create_crm_tables.sql';
if (!file_exists($sqlFile)) die("Arquivo SQL nao encontrado.\n");

$sql = file_get_contents($sqlFile);

try {
    $conn->exec($sql);
    echo "Sucesso! Tabelas 'clientes', 'crm_etapas', 'crm_oportunidades' criadas.\n";
    
    // Opcional: Popular etapas padrão para a empresa atual no loop (assumindo ID 1 ou pega todas)
    // Para simplificar, vamos inserir apenas se nao existir para a empresa 1 (Demo)
    // Em produção seria melhor fazer via aplicação quando cria empresa.
    
} catch (PDOException $e) {
    echo "Erro SQL: " . $e->getMessage() . "\n";
}
echo "--- Migracao Finalizada ---\n";
?>
