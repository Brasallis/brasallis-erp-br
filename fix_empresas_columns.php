<?php
require_once __DIR__ . '/includes/db_config.php';

try {
    $port = defined('DB_PORT') ? DB_PORT : 3306;
    $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Reparando Tabela Empresas...</h1>";

    $queries = [
        "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS ai_plan VARCHAR(50) DEFAULT 'foundation'",
        "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS max_users INT DEFAULT 5",
        "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS support_level VARCHAR(50) DEFAULT 'community'",
        "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS subscription_status VARCHAR(50) DEFAULT 'trial'",
        "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS onboarding_completed TINYINT(1) DEFAULT 0",
        "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS branding_primary_color VARCHAR(20) DEFAULT '#1e3a8a'",
        "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS branding_secondary_color VARCHAR(20) DEFAULT '#3b82f6'",
        "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS branding_bg_style VARCHAR(50) DEFAULT 'modern_light'",
        "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS segmento VARCHAR(100) NULL",
        "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS active_modules JSON DEFAULT NULL"
    ];

    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p style='color:green;'>Sucesso: $sql</p>";
        } catch (Exception $e) {
            echo "<p style='color:orange;'>Aviso: " . $e->getMessage() . "</p>";
        }
    }

    echo "<h2>Reparo concluído!</h2>";
    echo "<p>Agora tente fazer o login com admin@brasallis.com.br novamente.</p>";

} catch (Exception $e) {
    echo "<h1>Erro Crítico</h1><p>" . $e->getMessage() . "</p>";
}

// Autodestruição para segurança
@unlink(__FILE__);
?>
