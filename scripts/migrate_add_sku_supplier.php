<?php
require_once __DIR__ . '/../includes/funcoes.php';

try {
    $conn = connect_db();
    
    // Add sku column
    try {
        $conn->exec("ALTER TABLE produtos ADD COLUMN sku VARCHAR(50) NULL AFTER name");
        echo "Coluna 'sku' adicionada com sucesso.\n";
    } catch (PDOException $e) {
        // Ignore if exists (error code 42S21 or similar, but generic catch is safer for simple scripts)
        echo "Nota: Coluna 'sku' pode já existir ou erro: " . $e->getMessage() . "\n";
    }

    // Add fornecedor_id column
    try {
        $conn->exec("ALTER TABLE produtos ADD COLUMN fornecedor_id INT(11) UNSIGNED NULL AFTER categoria_id");
        $conn->exec("ALTER TABLE produtos ADD CONSTRAINT fk_produtos_fornecedor FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL");
        echo "Coluna 'fornecedor_id' adicionada com sucesso.\n";
    } catch (PDOException $e) {
        echo "Nota: Coluna 'fornecedor_id' pode já existir ou erro: " . $e->getMessage() . "\n";
    }
    
    echo "Migração concluída.";
    
} catch (PDOException $e) {
    echo "Erro fatal: " . $e->getMessage();
}
?>
