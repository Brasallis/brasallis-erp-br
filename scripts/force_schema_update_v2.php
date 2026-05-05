<?php
// scripts/force_schema_update_v2.php
require_once __DIR__ . '/../includes/db_config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- INICIANDO MIGRAÇÃO DE RH V2 (MODO COMPATÍVEL) ---\n";
    echo "DB: " . DB_NAME . " @ " . DB_HOST . "\n\n";

    $columns_to_add = [
        'data_admissao' => "DATE DEFAULT NULL",
        'cpf' => "VARCHAR(14) DEFAULT NULL",
        'celular' => "VARCHAR(20) DEFAULT NULL",
        'status_colaborador' => "ENUM('ativo', 'inativo', 'ferias', 'afastado') DEFAULT 'ativo'"
    ];

    foreach ($columns_to_add as $col => $definition) {
        // Verificar se a coluna existe
        $check = $pdo->prepare("
            SELECT COLUMN_NAME 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = 'usuarios' 
            AND COLUMN_NAME = ?
        ");
        $check->execute([DB_NAME, $col]);
        
        if ($check->fetch()) {
            echo "[INFO] Coluna '$col' já existe. Pulando...\n";
        } else {
            echo "[!] Coluna '$col' AUSENTE. Adicionando...\n";
            $pdo->exec("ALTER TABLE usuarios ADD $col $definition");
            echo "[OK] Coluna '$col' adicionada com sucesso.\n";
        }
    }

    // Adicionar índice no CPF se não existir
    $idxCheck = $pdo->prepare("
        SELECT INDEX_NAME 
        FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = ? 
        AND TABLE_NAME = 'usuarios' 
        AND INDEX_NAME = 'idx_usuarios_cpf'
    ");
    $idxCheck->execute([DB_NAME]);
    if (!$idxCheck->fetch()) {
        $pdo->exec("CREATE INDEX idx_usuarios_cpf ON usuarios(cpf)");
        echo "[OK] Índice idx_usuarios_cpf criado.\n";
    }

    echo "\n--- SUCESSO COLETIVO! O esquema está pronto. ---\n";

} catch (Exception $e) {
    die("\n[ERRO CRÍTICO] " . $e->getMessage() . "\n");
}
