<?php
// scripts/migrate_rh_v2.php
// Script de Migração Preciso para o Módulo de RH

require_once __DIR__ . '/../includes/db_config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conectado ao banco de dados: " . DB_NAME . "\n";
    echo "Iniciando migração da tabela 'usuarios'...\n";

    // 1. Verificar colunas existentes para evitar duplicidade
    $stmt = $conn->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $fieldsToAdd = [
        'data_admissao' => "ADD COLUMN data_admissao DATE DEFAULT NULL",
        'cpf'           => "ADD COLUMN cpf VARCHAR(14) DEFAULT NULL",
        'celular'       => "ADD COLUMN celular VARCHAR(20) DEFAULT NULL",
        'status_colaborador' => "ADD COLUMN status_colaborador ENUM('ativo', 'inativo', 'ferias', 'afastado') DEFAULT 'ativo'"
    ];

    foreach ($fieldsToAdd as $field => $sql) {
        if (!in_array($field, $columns)) {
            $conn->exec("ALTER TABLE usuarios $sql");
            echo "[SUCESSO] Coluna '$field' adicionada.\n";
        } else {
            echo "[AVISO] Coluna '$field' já existe. Pulando...\n";
        }
    }

    // 2. Criar índice para o CPF se não existir
    try {
        $conn->exec("CREATE INDEX idx_usuarios_cpf ON usuarios(cpf)");
        echo "[SUCESSO] Índice 'idx_usuarios_cpf' criado.\n";
    } catch (Exception $e) {
        echo "[AVISO] Índice 'idx_usuarios_cpf' pode já existir. Detalhe: " . $e->getMessage() . "\n";
    }

    echo "Migração concluída com sucesso!\n";

} catch (PDOException $e) {
    die("[ERRO CRÍTICO] Falha na migração: " . $e->getMessage() . "\n");
}
