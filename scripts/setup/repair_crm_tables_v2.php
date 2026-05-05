<?php
// scripts/setup/repair_crm_tables_v2.php
require_once __DIR__ . '/../../includes/db_config.php';

echo "--- Iniciando Reparo de Tabelas CRM (V2 - Sem FK) ---\n";

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão OK.\n";
    
    // Tenta desabilitar checks de FK temporariamente para permitir criação fora de ordem
    $conn->exec("SET foreign_key_checks = 0");

} catch (PDOException $e) {
    die("ERRO CONEXÃO: " . $e->getMessage() . "\n");
}

$sql = "
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    tipo ENUM('PF', 'PJ') DEFAULT 'PF',
    cpf_cnpj VARCHAR(20),
    email VARCHAR(100),
    telefone VARCHAR(20),
    endereco TEXT,
    cidade VARCHAR(100),
    estado VARCHAR(2),
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (empresa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crm_etapas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nome VARCHAR(50) NOT NULL,
    ordem INT NOT NULL DEFAULT 1,
    cor_hex VARCHAR(7) DEFAULT '#e9ecef',
    is_final BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (empresa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crm_oportunidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    cliente_id INT,
    titulo VARCHAR(100) NOT NULL,
    valor_estimado DECIMAL(10, 2) DEFAULT 0.00,
    etapa_id INT NOT NULL,
    responsavel_id INT,
    origem VARCHAR(50),
    status ENUM('aberto', 'ganho', 'perdido') DEFAULT 'aberto',
    data_fechamento DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (empresa_id),
    INDEX (cliente_id),
    INDEX (etapa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $conn->exec($sql);
    echo "Tabelas criadas (sem FK Constraints por enquanto).\n";
    
    // Re-enable checks
    $conn->exec("SET foreign_key_checks = 1");
    
    // Check
    $stmt = $conn->query("SHOW TABLES LIKE 'clientes'");
    if ($stmt->fetch()) echo "SUCESSO: Tabela 'clientes' existe.\n";
    
} catch (PDOException $e) {
    echo "ERRO SQL: " . $e->getMessage() . "\n";
}
?>
