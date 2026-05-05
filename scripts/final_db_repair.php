<?php
// scripts/final_db_repair.php
// Script de reparo definitivo do banco de dados para o módulo RH.

require_once __DIR__ . '/../includes/db_config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== REPARO ESTRUTURAL DE BANCO (RH) ===\n";
    echo "Lidando com o esquema: " . DB_NAME . "\n\n";

    $queries = [
        "ALTER TABLE usuarios ADD COLUMN data_admissao DATE DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN cpf VARCHAR(14) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN celular VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN status_colaborador ENUM('ativo', 'inativo', 'ferias', 'afastado') DEFAULT 'ativo'",
        "CREATE INDEX idx_usuarios_cpf ON usuarios(cpf)"
    ];

    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
            echo "[SUCESSO] " . substr($sql, 0, 50) . "...\n";
        } catch (PDOException $e) {
            // Se o erro for "Duplicate column name" (1060), ignoramos
            if ($e->errorInfo[1] == 1060 || $e->errorInfo[1] == 1061) {
                echo "[INFO] Já existe: " . substr($sql, 0, 50) . "...\n";
            } else {
                echo "[FALHA CRÍTICA] Erro ao executar: $sql\n";
                echo "Detalhe: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n--- VERIFICAÇÃO FINAL ---\n";
    $stmt = $pdo->query("DESCRIBE usuarios");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "- " . $c['Field'] . " (" . $c['Type'] . ")\n";
    }

} catch (Exception $e) {
    die("\n[ERRO DE CONEXÃO] " . $e->getMessage() . "\n");
}
