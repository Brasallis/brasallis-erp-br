<?php
require_once __DIR__ . '/includes/db_config.php';

try {
    $port = defined('DB_PORT') ? DB_PORT : 3306;
    $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Reparando Tabela de Logs (Cloud Logging)...</h1>";

    $sql = "CREATE TABLE IF NOT EXISTS system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT(11) UNSIGNED NULL,
        user_id INT(11) UNSIGNED NULL,
        severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
        source VARCHAR(100) DEFAULT 'System',
        message TEXT NOT NULL,
        stack_trace TEXT NULL,
        url VARCHAR(255) NULL,
        ip_address VARCHAR(45) NULL,
        status ENUM('new', 'resolved', 'ignored') DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;";

    $pdo->exec($sql);
    echo "<p style='color:green;'>Sucesso! Tabela <b>system_logs</b> criada.</p>";

    echo "<h2>Reparo concluído! O Painel Super Admin deve abrir agora. ✨</h2>";

} catch (Exception $e) {
    echo "<h1>Erro Crítico</h1><p>" . $e->getMessage() . "</p>";
}

// Autodestruição para segurança
@unlink(__FILE__);
?>
