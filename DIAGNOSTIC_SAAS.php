<?php
require_once __DIR__ . '/includes/manutencao_guard.php';
require_once __DIR__ . '/includes/db_config.php';
header('Content-Type: application/json');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    
    $results = [];

    // 1. List Tables
    $stmt = $pdo->query("SHOW TABLES");
    $results['tables'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Database Size
    $stmt = $pdo->query("
        SELECT 
            table_schema AS 'db_name', 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb' 
        FROM information_schema.TABLES 
        WHERE table_schema = '" . DB_NAME . "'
        GROUP BY table_schema
    ");
    $results['db_size'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. User activity check (count sessions in last 15 mins if column exists)
    // We'll check 'usuarios' table for 'last_activity' or similar
    $stmt = $pdo->query("DESCRIBE usuarios");
    $results['usuarios_columns'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($results, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

