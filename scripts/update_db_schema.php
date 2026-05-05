<?php
require_once __DIR__ . '/../includes/db_config.php';

echo "<pre>";
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conectado ao banco de dados.\n";

    // Adicionar coluna stock_at_purchase na tabela itens_compra
    echo "Verificando tabela 'itens_compra'...\n";
    
    // Verificar se a coluna já existe
    $stmt = $conn->prepare("SHOW COLUMNS FROM itens_compra LIKE 'stock_at_purchase'");
    $stmt->execute();
    $exists = $stmt->fetch();

    if (!$exists) {
        $conn->exec("ALTER TABLE itens_compra ADD COLUMN stock_at_purchase INT(11) NULL AFTER unit_price");
        echo "Coluna 'stock_at_purchase' adicionada com sucesso.\n";
    } else {
        echo "Coluna 'stock_at_purchase' já existe.\n";
    }

    echo "\nAtualização de esquema concluída!";

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
echo "</pre>";
