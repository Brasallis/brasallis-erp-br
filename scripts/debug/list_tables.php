<?php
require_once 'includes/db_config.php';
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $stmt = $conn->query("SHOW TABLES");
    echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
} catch(Exception $e) {
    echo $e->getMessage();
}
