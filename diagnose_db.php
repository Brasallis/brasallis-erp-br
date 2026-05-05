<?php
require_once __DIR__ . '/includes/manutencao_guard.php';
/**
 * diagnose_db.php
 * Script de diagnóstico para auditoria do banco de dados Brasallis Hub.
 * Use para identificar discrepâncias entre ambientes CLI e Web.
 */

require_once __DIR__ . '/includes/db_config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<style>body{font-family:sans-serif;padding:30px;line-height:1.6;color:#333;} h1{color:#1e293b;} .panel{background:#f8fafc;border:1px solid #e2e8f0;padding:20px;border-radius:12px;margin-bottom:20px;} b{color: #000;}</style>";

echo "<h1>🔍 Diagnóstico de Banco de Dados</h1>";

echo "<div class='panel'>";
echo "<h3>Configuração (Ambiente):</h3>";
echo "<b>DB_HOST:</b> " . DB_HOST . "<br>";
echo "<b>DB_NAME:</b> " . DB_NAME . "<br>";
echo "<b>JAWSDB_URL Set:</b> " . (getenv('JAWSDB_URL') ? 'SIM (Remoto)' : 'NÃO (Local)') . "<br>";
echo "</div>";

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='panel' style='border-left: 5px solid #22c55e;'>";
    echo "<h3 style='color:#16a34a;'>✅ Conexão PDO: SUCESSO</h3>";
    
    $db_real = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $host_real = $pdo->query("SELECT @@hostname")->fetchColumn();
    $version = $pdo->query("SELECT VERSION()")->fetchColumn();
    
    echo "<b>Database em uso:</b> $db_real<br>";
    echo "<b>Hostname Real:</b> $host_real<br>";
    echo "<b>Versão MySQL/MariaDB:</b> $version<br>";
    echo "</div>";
    
    echo "<div class='panel'>";
    echo "<h3>📊 Estrutura da Tabela 'usuarios':</h3>";
    $stmt = $pdo->query("DESCRIBE usuarios");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='0' width='100%' style='border-collapse: collapse; margin-top:10px;'>";
    echo "<tr style='background:#e2e8f0;'><th align='left' style='padding:10px;'>Campo</th><th align='left' style='padding:10px;'>Tipo</th><th align='left' style='padding:10px;'>Null</th></tr>";
    foreach ($cols as $c) {
        $color = in_array($c['Field'], ['data_admissao', 'cpf', 'celular', 'status_colaborador']) ? 'background:#dcfce7;font-weight:bold;' : '';
        echo "<tr style='border-bottom:1px solid #e2e8f0;$color'><td style='padding:10px;'>{$c['Field']}</td><td style='padding:10px;'>{$c['Type']}</td><td style='padding:10px;'>{$c['Null']}</td></tr>";
    }
    echo "</table>";
    echo "</div>";

    echo "<div class='panel'>";
    echo "<h3>📁 Arquivo Final de Customização (Preview):</h3>";
    $sqlFile = __DIR__ . '/database/setup_customization.sql';
    if(file_exists($sqlFile)) {
        echo "<pre style='background:#1e293b;color:#f8f8f2;padding:15px;border-radius:8px;overflow:auto;max-height:200px;'>" . htmlspecialchars(file_get_contents($sqlFile)) . "</pre>";
    } else {
        echo "<p style='color:red;'>Arquivo setup_customization.sql não encontrado.</p>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='panel' style='border-left: 5px solid #ef4444;'>";
    echo "<h3 style='color:#dc2626;'>❌ Erro de Conexão:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<footer>Gerado em: " . date('Y-m-d H:i:s') . "</footer>";

