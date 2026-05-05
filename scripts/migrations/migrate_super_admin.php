<?php
// migrate_super_admin.php
require_once __DIR__ . '/includes/db_config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents(__DIR__ . '/sql/add_super_admin_enum.sql');
    $conn->exec($sql);
    
    echo "Sucesso: Coluna user_type atualizada para suportar 'super_admin'.\n";
    
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
