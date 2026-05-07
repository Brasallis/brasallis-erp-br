<?php
require_once __DIR__ . '/../includes/db_config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Adicionando colunas de metas financeiras... ";
    
    // monthly_revenue_goal
    $pdo->exec("ALTER TABLE empresas ADD COLUMN IF NOT EXISTS monthly_revenue_goal DECIMAL(15,2) DEFAULT 0.00");
    
    // fixed_costs
    $pdo->exec("ALTER TABLE empresas ADD COLUMN IF NOT EXISTS fixed_costs DECIMAL(15,2) DEFAULT 0.00");

    echo "OK!\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
