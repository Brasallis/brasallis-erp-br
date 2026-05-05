<?php
require_once __DIR__ . '/includes/manutencao_guard.php';
require_once __DIR__ . '/bootstrap.php';
check_master_key();

require_once __DIR__ . '/includes/db_config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    
    echo "VERIFICANDO TABELAS...\n";
    
    // Verificar se system_logs existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'system_logs'");
    if ($stmt->rowCount() == 0) {
        echo "Tabela system_logs NÃO existe. Criando...\n";
        $pdo->exec("CREATE TABLE system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empresa_id INT,
            user_id INT,
            severity ENUM('info', 'warning', 'error') DEFAULT 'info',
            source VARCHAR(100),
            message TEXT,
            stack_trace TEXT,
            url VARCHAR(255),
            ip_address VARCHAR(45),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
    } else {
        echo "Tabela system_logs já existe.\n";
    }

    echo "EXECUTANDO MUDANÇAS DE STATUS...\n";
    // Tentar adicionar as colunas sem a foreign key primeiro para ver se o erro é nela
    $pdo->exec("ALTER TABLE system_logs ADD COLUMN IF NOT EXISTS status ENUM('new', 'resolved') DEFAULT 'new'");
    $pdo->exec("ALTER TABLE system_logs ADD COLUMN IF NOT EXISTS resolved_at DATETIME DEFAULT NULL");
    $pdo->exec("ALTER TABLE system_logs ADD COLUMN IF NOT EXISTS resolved_by INT DEFAULT NULL");
    
    echo "TENTANDO ADICIONAR CHAVE ESTRANGEIRA...\n";
    try {
        $pdo->exec("ALTER TABLE system_logs ADD CONSTRAINT fk_logs_resolved_by FOREIGN KEY (resolved_by) REFERENCES usuarios(id) ON DELETE SET NULL");
        echo "Chave estrangeira adicionada com sucesso!\n";
    } catch (Exception $e) {
        echo "Erro na FK: " . $e->getMessage() . "\n";
        echo "Prosseguindo sem a FK física por enquanto.\n";
    }

} catch (Exception $e) {
    echo "ERRO GERAL: " . $e->getMessage();
}

