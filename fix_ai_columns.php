<?php
require_once __DIR__ . '/includes/db_config.php';

try {
    $port = defined('DB_PORT') ? DB_PORT : 3306;
    $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Reparando Colunas de IA e SaaS...</h1>";

    $columns = [
        "ai_token_limit" => "INT DEFAULT 100000",
        "ai_tokens_used_month" => "INT DEFAULT 0",
        "iq_actions_used_month" => "INT DEFAULT 0",
        "last_payment_at" => "DATETIME DEFAULT NULL",
        "next_billing_at" => "DATETIME DEFAULT NULL",
        "blocked_at" => "DATETIME DEFAULT NULL"
    ];

    foreach ($columns as $col => $definition) {
        try {
            $check = $pdo->query("SHOW COLUMNS FROM empresas LIKE '$col'");
            if ($check->rowCount() == 0) {
                $pdo->exec("ALTER TABLE empresas ADD COLUMN $col $definition");
                echo "<p style='color:green;'>Coluna <b>$col</b> adicionada.</p>";
            } else {
                echo "<p style='color:blue;'>Coluna <b>$col</b> já existe.</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:orange;'>Aviso em $col: " . $e->getMessage() . "</p>";
        }
    }

    echo "<h2>Tudo pronto! Tente abrir o Super Admin agora. 🚀</h2>";

} catch (Exception $e) {
    echo "<h1>Erro Crítico</h1><p>" . $e->getMessage() . "</p>";
}

@unlink(__FILE__);
?>
