<?php
require __DIR__ . '/../includes/db_config.php';
require __DIR__ . '/../includes/funcoes.php';

try {
    $conn = connect_db();
    $sql = "
    CREATE TABLE IF NOT EXISTS venda_pagamentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        venda_id INT NOT NULL,
        metodo_pagamento VARCHAR(50) NOT NULL,
        valor DECIMAL(10,2) NOT NULL,
        data_pagamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (venda_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $conn->exec($sql);
    echo "Tabela 'venda_pagamentos' criada com sucesso!\n";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
