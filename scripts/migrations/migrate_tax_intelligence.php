<?php
// migrate_tax_intelligence.php
require_once __DIR__ . '/includes/db_config.php';

echo "<pre>";

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão bem-sucedida.\n";

    // 1. Tabela de Regras Fiscais (Tax Rules)
    // Armazena regras para validação cruzada (ex: NCM x Tipo de Tributação)
    $sql_tax_rules = "CREATE TABLE IF NOT EXISTS tax_rules (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ncm VARCHAR(20) NOT NULL,
        cest VARCHAR(20) NULL,
        type ENUM('monofasico', 'substituicao_tributaria', 'isento', 'tributado') NOT NULL DEFAULT 'tributado',
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ncm (ncm)
    ) ENGINE=InnoDB;";
    $conn->exec($sql_tax_rules);
    echo "Tabela 'tax_rules' verificada/criada com sucesso.\n";

    // 2. Tabela de Análise Tributária
    // Armazena os insights gerados pela IA ou Motor de Regras para cada item de compra
    $sql_analise = "CREATE TABLE IF NOT EXISTS analise_tributaria (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        compra_id INT(11) UNSIGNED NOT NULL,
        product_id INT(11) UNSIGNED NULL, -- Pode ser nulo se o produto ainda não foi cadastrado no sistema
        item_name_xml VARCHAR(255) NULL, -- Nome como veio na nota
        ncm_detectado VARCHAR(20) NULL,
        cfop_entrada VARCHAR(10) NULL,
        cst_csosn_entrada VARCHAR(10) NULL,
        alert_level ENUM('info', 'warning', 'critical', 'ok') NOT NULL DEFAULT 'info',
        ai_suggestion TEXT NULL, -- Texto explicativo da IA ou Regra
        savings_potential DECIMAL(10, 2) DEFAULT 0.00, -- Estimativa de economia (ex: crédito de PIS/COFINS)
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES produtos(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;";
    $conn->exec($sql_analise);
    echo "Tabela 'analise_tributaria' verificada/criada com sucesso.\n";

    // 3. Inserir algumas regras fiscais de exemplo (Seed)
    // Exemplo: Bebidas Frias (Geralmente Monofásicas)
    // NCM 2202 (Águas, incluindo águas minerais e águas gaseificadas, adicionadas de açúcar ou de outros edulcorantes ou aromatizadas)
    $conn->prepare("INSERT IGNORE INTO tax_rules (ncm, type, description) VALUES 
        ('22021000', 'monofasico', 'Águas, incluídas as águas minerais, adicionadas de açúcar - Monofásico PIS/COFINS'),
        ('22030000', 'substituicao_tributaria', 'Cervejas de malte - Sujeito a ICMS ST'),
        ('40111000', 'monofasico', 'Pneus novos de borracha - Monofásico PIS/COFINS')
    ")->execute();
    echo "Regras fiscais de exemplo inseridas.\n";

} catch (PDOException $e) {
    echo "ERRO: " . $e->getMessage();
}

echo "</pre>";
?>
