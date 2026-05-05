<?php
require_once __DIR__ . '/../includes/funcoes.php';

try {
    $conn = connect_db();
    
    $sql = "CREATE TABLE IF NOT EXISTS lotes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        produto_id INT(11) UNSIGNED NOT NULL,
        numero_lote VARCHAR(50) NOT NULL,
        data_validade DATE DEFAULT NULL,
        quantidade_inicial INT NOT NULL,
        quantidade_atual INT NOT NULL,
        fornecedor VARCHAR(100) DEFAULT NULL,
        data_entrada DATETIME DEFAULT CURRENT_TIMESTAMP,
        empresa_id INT(11) UNSIGNED NOT NULL,
        FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $conn->exec($sql);
    echo "Tabela 'lotes' criada com sucesso!";
    
} catch (PDOException $e) {
    echo "Erro ao criar tabela: " . $e->getMessage();
}
?>
