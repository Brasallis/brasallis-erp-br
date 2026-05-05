<?php
// scripts/setup/run_api_migration.php
require_once __DIR__ . '/../../includes/db_config.php';

echo "--- Iniciando Migração API & Performance ---\n";

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Allow index creation explicitly if needed, usually fine.
    
    $sql = file_get_contents(__DIR__ . '/../migrations/005_create_api_structure.sql');
    
    $conn->exec($sql);
    
    echo "Sucesso! Estrutura de API e Índices criados.\n";

} catch (PDOException $e) {
    echo "Erro na migração: " . $e->getMessage() . "\n";
}
?>
