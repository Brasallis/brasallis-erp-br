<?php
require_once __DIR__ . '/includes/db_config.php';

try {
    $port = defined('DB_PORT') ? DB_PORT : 3306;
    $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Reparando Helpdesk (Chamados)...</h1>";

    $sql = "CREATE TABLE IF NOT EXISTS chamados_suporte (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT(11) UNSIGNED NOT NULL,
        assunto VARCHAR(255) NOT NULL,
        mensagem TEXT NOT NULL,
        resposta TEXT NULL,
        status ENUM('aberto', 'respondido', 'fechado') DEFAULT 'aberto',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;";

    $pdo->exec($sql);
    echo "<p style='color:green;'>Sucesso! Tabela <b>chamados_suporte</b> criada.</p>";

    echo "<h2>Reparo concluído! O módulo de Suporte deve funcionar agora. ✨</h2>";

} catch (Exception $e) {
    echo "<h1>Erro Crítico</h1><p>" . $e->getMessage() . "</p>";
}

@unlink(__FILE__);
?>
