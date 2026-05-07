<?php
require_once __DIR__ . '/../includes/db_config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Adicionando colunas de metas financeiras... ";
    
    // Helper para adicionar coluna se não existir (MySQL compat)
    function addColumnIfMissing($pdo, $table, $column, $definition) {
        $check = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            return true;
        }
        return false;
    }

    addColumnIfMissing($pdo, 'empresas', 'monthly_revenue_goal', "DECIMAL(15,2) DEFAULT 0.00");
    addColumnIfMissing($pdo, 'empresas', 'fixed_costs', "DECIMAL(15,2) DEFAULT 0.00");

    echo "OK!\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
