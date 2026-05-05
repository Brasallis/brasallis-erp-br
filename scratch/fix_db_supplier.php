<?php
require_once __DIR__ . '/src/Core/Database.php';
use App\Core\Database;

try {
    $db = Database::getInstance();
    $db->exec("ALTER TABLE compras MODIFY supplier_id INT(11) NULL;");
    echo "Sucesso: Coluna supplier_id agora permite NULL na tabela compras.\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
