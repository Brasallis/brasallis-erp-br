<?php
require_once 'includes/db_config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM empresas LIKE 'plan_expires_at'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE empresas ADD COLUMN plan_expires_at TIMESTAMP NULL DEFAULT NULL");
        echo "Coluna 'plan_expires_at' adicionada com sucesso.";
    } else {
        echo "Coluna 'plan_expires_at' já existe.";
    }
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
