<?php

require_once __DIR__ . '/includes/db_config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Adiciona a coluna 'category' à tabela 'products' se não existir
    $sql_category = "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS category VARCHAR(255) NULL";
    $conn->exec($sql_category);

    echo "Coluna 'category' adicionada à tabela 'products' com sucesso!";

} catch (PDOException $e) {
    echo "Erro ao adicionar coluna 'category': " . $e->getMessage();
}

?>
