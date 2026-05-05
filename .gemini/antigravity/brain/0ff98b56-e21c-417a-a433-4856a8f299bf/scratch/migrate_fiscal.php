<?php
// scratch/migrate_fiscal.php
// Script para adicionar campos fiscais ao banco de dados

require_once __DIR__ . '/../../includes/funcoes.php';

$conn = connect_db();

if (!$conn) {
    die("Falha na conexão com o banco de dados.");
}

$queries = [
    // Campos para a Empresa
    "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS inscricao_estadual VARCHAR(20) AFTER cnpj",
    "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS csc_id VARCHAR(10)",
    "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS csc_token VARCHAR(100)",
    "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS ambiente_fiscal ENUM('homologacao', 'producao') DEFAULT 'homologacao'",
    "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS certificado_path VARCHAR(255)",
    "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS certificado_senha VARCHAR(255)",

    // Campos para Produtos
    "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS ncm VARCHAR(10) AFTER sku",
    "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS cfop VARCHAR(5) AFTER ncm",
    "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS origem TINYINT DEFAULT 0 AFTER cfop",

    // Campos para Vendas
    "ALTER TABLE vendas ADD COLUMN IF NOT EXISTS status_fiscal ENUM('pendente', 'processando', 'emitida', 'erro', 'cancelada') DEFAULT 'pendente'",
    "ALTER TABLE vendas ADD COLUMN IF NOT EXISTS nfe_chave VARCHAR(44)",
    "ALTER TABLE vendas ADD COLUMN IF NOT EXISTS nfe_numero INT",
    "ALTER TABLE vendas ADD COLUMN IF NOT EXISTS nfe_serie INT",
    "ALTER TABLE vendas ADD COLUMN IF NOT EXISTS xml_link VARCHAR(255)",
    "ALTER TABLE vendas ADD COLUMN IF NOT EXISTS cliente_id INT UNSIGNED AFTER user_id",
    "ALTER TABLE vendas ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0 AFTER total_amount"
];

echo "Iniciando migração fiscal...\n";

foreach ($queries as $sql) {
    try {
        $conn->exec($sql);
        echo "Sucesso: " . substr($sql, 0, 50) . "...\n";
    } catch (PDOException $e) {
        echo "Erro (pode ser que a coluna já exista): " . $e->getMessage() . "\n";
    }
}

echo "Migração concluída.\n";
