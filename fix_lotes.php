<?php
require_once __DIR__ . '/includes/db_config.php';

try {
    $port = defined('DB_PORT') ? DB_PORT : 3306;
    $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    
    echo "<h1>Sucesso!</h1><p>A tabela de 'lotes' foi recriada e integrada ao sistema.</p>";
} catch (Exception $e) {
    echo "<h1>Erro</h1><p>" . $e->getMessage() . "</p>";
}
@unlink(__FILE__); // Autodestruição para segurança
?>
