<?php
require_once __DIR__ . '/../includes/funcoes.php';

try {
    $conn = connect_db();
    
    // Add venda_id column
    try {
        $conn->exec("ALTER TABLE historico_estoque ADD COLUMN venda_id INT(11) UNSIGNED NULL AFTER new_quantity");
        $conn->exec("ALTER TABLE historico_estoque ADD CONSTRAINT fk_historico_venda FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE");
        echo "Coluna 'venda_id' adicionada com sucesso.\n";
    } catch (PDOException $e) {
        echo "Nota: Coluna 'venda_id' pode já existir ou erro: " . $e->getMessage() . "\n";
    }
    
    // Also, we need to make new_quantity nullable or handle it in PDV, 
    // because PDV insert might not calculate new_quantity explicitly in the same way?
    // Checking pdv.php: 
    // $history_stmt = $conn->prepare("INSERT INTO historico_estoque (empresa_id, product_id, user_id, action, quantity, venda_id) VALUES (?, ?, ?, 'saida', ?, ?)");
    // It does NOT insert new_quantity! 
    // So we must make new_quantity NULLABLE or default 0, or update PDV to calculate it.
    // Let's make it nullable for flexibility.
    
    try {
        $conn->exec("ALTER TABLE historico_estoque MODIFY COLUMN new_quantity INT(11) NULL");
        echo "Coluna 'new_quantity' alterada para permitir NULL.\n";
    } catch (PDOException $e) {
        echo "Erro ao alterar 'new_quantity': " . $e->getMessage() . "\n";
    }

    echo "Migração concluída.";
    
} catch (PDOException $e) {
    echo "Erro fatal: " . $e->getMessage();
}
?>
