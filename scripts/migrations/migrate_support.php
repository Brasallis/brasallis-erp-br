<?php
// migrate_support.php
require_once __DIR__ . '/includes/db_config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents(__DIR__ . '/sql/create_support_tables.sql');
    $conn->exec($sql);
    
    echo "Sucesso: Tabelas 'avisos_globais' e 'chamados_suporte' criadas.\n";
    
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
