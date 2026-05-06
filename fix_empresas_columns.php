<?php
require_once __DIR__ . '/includes/db_config.php';

try {
    $port = defined('DB_PORT') ? DB_PORT : 3306;
    $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Reparando Tabela Empresas (Modo Compatível)...</h1>";

    $columns = [
        "ai_plan" => "VARCHAR(50) DEFAULT 'foundation'",
        "max_users" => "INT DEFAULT 5",
        "support_level" => "VARCHAR(50) DEFAULT 'community'",
        "subscription_status" => "VARCHAR(50) DEFAULT 'trial'",
        "onboarding_completed" => "TINYINT(1) DEFAULT 0",
        "branding_primary_color" => "VARCHAR(20) DEFAULT '#1e3a8a'",
        "branding_secondary_color" => "VARCHAR(20) DEFAULT '#3b82f6'",
        "branding_bg_style" => "VARCHAR(50) DEFAULT 'modern_light'",
        "segmento" => "VARCHAR(100) NULL",
        "active_modules" => "JSON DEFAULT NULL"
    ];

    foreach ($columns as $col => $definition) {
        try {
            // Verifica se a coluna existe
            $check = $pdo->query("SHOW COLUMNS FROM empresas LIKE '$col'");
            if ($check->rowCount() == 0) {
                $pdo->exec("ALTER TABLE empresas ADD COLUMN $col $definition");
                echo "<p style='color:green;'>Coluna <b>$col</b> adicionada com sucesso.</p>";
            } else {
                echo "<p style='color:blue;'>Coluna <b>$col</b> já existe.</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:orange;'>Aviso em $col: " . $e->getMessage() . "</p>";
        }
    }

    echo "<h2>Reparo concluído! ✨</h2>";
    echo "<p>Agora tente fazer o login com <b>admin@brasallis.com.br</b> novamente.</p>";

} catch (Exception $e) {
    echo "<h1>Erro Crítico</h1><p>" . $e->getMessage() . "</p>";
}

// Autodestruição para segurança
@unlink(__FILE__);
?>
