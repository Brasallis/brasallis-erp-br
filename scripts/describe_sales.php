<?php
require_once __DIR__ . '/../includes/db_config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $output = "";
    foreach (['vendas', 'venda_itens'] as $table) {
        $stmt = $conn->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $output .= "\nStructure of table '$table':\n";
        foreach ($columns as $col) {
            $output .= "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    }
    file_put_contents(__DIR__ . '/../schema_sales_utf8.txt', $output);
    echo "Written to schema_sales_utf8.txt";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
