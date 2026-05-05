<?php
require_once 'includes/db_config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Tabela Avisos Globais
    $sqlAvisos = "CREATE TABLE IF NOT EXISTS avisos_globais (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        mensagem TEXT NOT NULL,
        tipo ENUM('info', 'warning', 'danger', 'success') DEFAULT 'info',
        ativo BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlAvisos);
    echo "Tabela 'avisos_globais': OK<br>";

    // 2. Tabela Chamados Suporte (Sem FK inicialmente)
    $sqlChamados = "CREATE TABLE IF NOT EXISTS chamados_suporte (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT(11) NOT NULL,
        assunto VARCHAR(255) NOT NULL,
        mensagem TEXT NOT NULL,
        resposta TEXT,
        status ENUM('aberto', 'respondido', 'fechado') DEFAULT 'aberto',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlChamados);
    echo "Tabela 'chamados_suporte': OK<br>";

    // 3. Tentar adicionar FK (em bloco try/catch separado)
    try {
        // Verificar se FK já existe antes de adicionar
        // (Simplificação: apenas rodar o ALTER IGNORE ou similar não funciona bem no MySQL, então vamos tentar direto e pegar erro se já existir)
        
        // Primeiro verificar se empresas.id é compatível. Assumindo INT.
        $pdo->exec("ALTER TABLE chamados_suporte ADD CONSTRAINT fk_chamados_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE");
        echo "Foreign Key 'fk_chamados_empresa': OK<br>";
    } catch (PDOException $e) {
        echo "Aviso (FK não criada, mas tabela existe): " . $e->getMessage() . "<br>";
    }

} catch (PDOException $e) {
    echo "ERRO CRÍTICO: " . $e->getMessage();
}
