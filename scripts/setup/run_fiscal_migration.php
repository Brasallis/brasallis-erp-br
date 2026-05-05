<?php
// scripts/setup/run_fiscal_migration.php
require_once __DIR__ . '/../../includes/db_config.php';

echo "--- Iniciando Migração Fiscal ---\n";

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $conn->exec("SET foreign_key_checks = 0");

    $sql = file_get_contents(__DIR__ . '/../migrations/004_create_fiscal_tables.sql');
    
    $conn->exec($sql);
    
    $conn->exec("SET foreign_key_checks = 1");
    
    echo "Sucesso! Tabelas fiscais criadas.\n";

} catch (PDOException $e) {
    echo "Erro na migração: " . $e->getMessage() . "\n";
}
?>
