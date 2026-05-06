<?php
require_once __DIR__ . '/includes/db_config.php';

try {
    $port = defined('DB_PORT') ? DB_PORT : 3306;
    $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Reparando Resolução de Logs...</h1>";

    $queries = [
        "ALTER TABLE system_logs ADD COLUMN IF NOT EXISTS resolved_at TIMESTAMP NULL",
        "ALTER TABLE system_logs ADD COLUMN IF NOT EXISTS resolved_by INT(11) UNSIGNED NULL"
    ];

    // Usando método compatível
    $columns = [
        "resolved_at" => "TIMESTAMP NULL",
        "resolved_by" => "INT(11) UNSIGNED NULL"
    ];

    foreach ($columns as $col => $def) {
        $check = $pdo->query("SHOW COLUMNS FROM system_logs LIKE '$col'");
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE system_logs ADD COLUMN $col $def");
            echo "<p style='color:green;'>Coluna $col adicionada.</p>";
        } else {
            echo "<p style='color:blue;'>Coluna $col já existe.</p>";
        }
    }

    echo "<h2>Reparo concluído! Agora os botões de resolver devem funcionar. ✨</h2>";

} catch (Exception $e) {
    echo "<h1>Erro Crítico</h1><p>" . $e->getMessage() . "</p>";
}

@unlink(__FILE__);
?>
