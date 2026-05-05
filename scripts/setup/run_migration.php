<?php
// Run this via CLI: php scripts/setup/run_migration.php

echo "--- Iniciando Migracao: Arquitetura Piramide ---\n";

require_once __DIR__ . '/../../includes/db_config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexao com banco de dados estabelecida.\n";
} catch (PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage() . "\n");
}

$sqlFile = __DIR__ . '/../migrations/001_create_pyramid_architecture.sql';

if (!file_exists($sqlFile)) {
    die("Arquivo SQL nao encontrado: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

try {
    // Executa o SQL (pode conter multiplos comandos, mas PDO->exec pode falhar com delimitadores se nao for inteligente. 
    // Vamos tentar execucao direta assumindo comandos padrao. Se falhar, talvez precise dividir por ;)
    // Para migrações simples como CREATE TABLE IF NOT EXISTS funciona bem em bloco.
    $conn->exec($sql);
    echo "Sucesso! Tabelas 'setores', 'modulos', 'permissoes' criadas/verificadas.\n";
} catch (PDOException $e) {
    echo "Erro ao executar SQL:\n" . $e->getMessage() . "\n";
}

echo "--- Migracao Concluida ---\n";
?>
