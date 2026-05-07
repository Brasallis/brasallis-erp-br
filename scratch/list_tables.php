<?php
require_once __DIR__ . '/../includes/db_config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tabelas encontradas:\n";
    foreach ($tables as $t) echo "- $t\n";
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
