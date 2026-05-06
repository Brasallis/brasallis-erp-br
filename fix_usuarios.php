<?php
require_once __DIR__ . '/includes/db_config.php';

try {
    $port = defined('DB_PORT') ? DB_PORT : 3306;
    $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $queries = [
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS data_admissao DATE DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS cpf VARCHAR(14) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS celular VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS status_colaborador ENUM('ativo', 'inativo', 'ferias', 'afastado') DEFAULT 'ativo'",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS permissions JSON DEFAULT NULL"
    ];

    foreach ($queries as $sql) {
        $pdo->exec($sql);
    }
    
    echo "<h1>Sucesso!</h1><p>As colunas da tabela usuarios foram corrigidas.</p>";
} catch (Exception $e) {
    echo "<h1>Erro</h1><p>" . $e->getMessage() . "</p>";
}
@unlink(__FILE__); // Autodestruição para segurança
?>
