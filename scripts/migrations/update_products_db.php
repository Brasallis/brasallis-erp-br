<?php

require_once __DIR__ . '/includes/db_config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Adiciona a coluna 'lote' se não existir
    $sql_lote = "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS lote VARCHAR(255) NULL";
    $conn->exec($sql_lote);

    // Adiciona a coluna 'validade' se não existir
    $sql_validade = "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS validade DATE NULL";
    $conn->exec($sql_validade);

    // Adiciona a coluna 'observacoes' se não existir
    $sql_observacoes = "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS observacoes TEXT NULL";
    $conn->exec($sql_observacoes);

    echo "Colunas 'lote', 'validade' e 'observacoes' adicionadas à tabela 'products' com sucesso!";

} catch (PDOException $e) {
    echo "Erro ao adicionar colunas: " . $e->getMessage();
}

?>
