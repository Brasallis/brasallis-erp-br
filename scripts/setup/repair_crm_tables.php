<?php
// scripts/setup/repair_crm_tables.php
require_once __DIR__ . '/../../includes/db_config.php';

echo "--- Iniciando Reparo de Tabelas CRM ---\n";
echo "Host: " . DB_HOST . "\n";
echo "Banco: " . DB_NAME . "\n";

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão OK.\n";
} catch (PDOException $e) {
    die("ERRO FATAL DE CONEXÃO: " . $e->getMessage() . "\n");
}

// SQL Direto para garantir (sem depender de arquivo externo)
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crm_etapas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nome VARCHAR(50) NOT NULL,
    ordem INT NOT NULL DEFAULT 1,
    cor_hex VARCHAR(7) DEFAULT '#e9ecef',
    is_final BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (etapa_id) REFERENCES crm_etapas(id),
    FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $conn->exec($sql);
    echo "SQL executado com sucesso.\n";
    
    // Verificação
    $stmt = $conn->query("SHOW TABLES LIKE 'clientes'");
    if ($stmt->fetch()) {
        echo "CONFIRMADO: Tabela 'clientes' existe.\n";
    } else {
        echo "ERRO: Tabela 'clientes' NÃO foi encontrada após execução.\n";
    }

} catch (PDOException $e) {
    echo "ERRO SQL: " . $e->getMessage() . "\n";
}
echo "--- Fim do Reparo ---\n";
?>
