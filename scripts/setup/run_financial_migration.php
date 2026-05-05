<?php
// scripts/setup/run_financial_migration.php
require_once __DIR__ . '/../../includes/db_config.php';

echo "--- Iniciando Migração Financeira ---\n";

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Disable FK checks to allow creating tables in any order if needed (though order is correct here)
    $conn->exec("SET foreign_key_checks = 0");

    $sql = file_get_contents(__DIR__ . '/../migrations/003_create_financial_tables.sql');
    
    // Split purely for simple parsing if needed, but PDO can exec multiple queries often.
    // However, it's safer to exec the whole block or split by statement if strictly needed.
    // Here we assume the file content is safe to run.
    $conn->exec($sql);
    
    $conn->exec("SET foreign_key_checks = 1");
    
    echo "Sucesso! Tabelas financeiras criadas.\n";

} catch (PDOException $e) {
    echo "Erro na migração: " . $e->getMessage() . "\n";
}
?>
