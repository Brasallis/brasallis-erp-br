<?php
require_once __DIR__ . '/includes/manutencao_guard.php';
require_once __DIR__ . '/bootstrap.php';
check_master_key();

require_once __DIR__ . '/includes/db_config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    
    $files = [
        'sql/add_subscription_fields.sql',
        'sql/add_log_status.sql'
    ];

    foreach ($files as $file) {
        if (file_exists($file)) {
            echo "Executando $file... ";
            $sql = file_get_contents($file);
            $pdo->exec($sql);
            echo "OK!\n";
        } else {
            echo "Arquivo $file não encontrado.\n";
        }
    }
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}

