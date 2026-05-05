<?php
require_once __DIR__ . '/../includes/db_config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in " . DB_NAME . ":\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
