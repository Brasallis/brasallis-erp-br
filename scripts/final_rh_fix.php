<?php
// scripts/final_rh_fix.php
require_once __DIR__ . '/../includes/db_config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando reparo estrutural da tabela 'usuarios'...\n";

    $queries = [
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS data_admissao DATE DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS cpf VARCHAR(14) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS celular VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS status_colaborador ENUM('ativo', 'inativo', 'ferias', 'afastado') DEFAULT 'ativo'"
    ];

    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
            echo "[OK] Executado: " . substr($sql, 0, 40) . "...\n";
        } catch (Exception $e) {
            // Se IF NOT EXISTS falhar (versões antigas do MariaDB/MySQL), tentamos tratar
            echo "[INFO] Notificando: " . $e->getMessage() . "\n";
        }
    }

    echo "Saneamento concluído!\n";

} catch (Exception $e) {
    die("[ERRO] " . $e->getMessage());
}
