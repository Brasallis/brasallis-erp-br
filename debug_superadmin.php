<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "<h1>Debug Super Admin</h1>";
    
    echo "<h3>1. Verificando Sessão...</h3>";
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    echo "User Type: " . ($_SESSION['user_type'] ?? 'NÃO DEFINIDO') . "<br>";
    echo "User Email: " . ($_SESSION['user_email'] ?? 'NÃO DEFINIDO') . "<br>";

    echo "<h3>2. Testando Conexão com Banco...</h3>";
    require_once 'includes/db_config.php';
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão OK!<br>";

    echo "<h3>3. Verificando Tabelas Críticas...</h3>";
    $tabelas = ['empresas', 'usuarios', 'vendas', 'system_logs', 'avisos_globais'];
    foreach ($tabelas as $t) {
        $check = $pdo->query("SHOW TABLES LIKE '$t'");
        if ($check->rowCount() > 0) {
            echo "<span style='color:green;'>[OK]</span> Tabela $t existe.<br>";
        } else {
            echo "<span style='color:red;'>[ERRO]</span> Tabela $t NÃO existe!<br>";
        }
    }

    echo "<h3>4. Testando Repositório...</h3>";
    require_once 'vendor/autoload.php';
    require_once 'src/Core/Database.php';
    require_once 'src/Repository/SuperDashboardRepository.php';
    
    $repo = new \App\Repository\SuperDashboardRepository(\App\Core\Database::getInstance());
    
    echo "Chamando getGlobalStats()...<br>";
    $stats = $repo->getGlobalStats();
    echo "Stats OK!<br>";

    echo "Chamando getSaaSMetrics()...<br>";
    $saas = $repo->getSaaSMetrics();
    echo "SaaS Metrics OK!<br>";

    echo "Chamando getInfrastructureHealth()...<br>";
    $health = $repo->getInfrastructureHealth();
    echo "Health OK!<br>";

    echo "Chamando getPlatformRevenueChart()...<br>";
    $rev = $repo->getPlatformRevenueChart();
    echo "Revenue Chart OK!<br>";

    echo "Chamando getEfficiencyMetrics()...<br>";
    $eff = $repo->getEfficiencyMetrics();
    echo "Efficiency OK!<br>";

    echo "<h3>🏁 Tudo parece correto no backend!</h3>";
    echo "<p>Se a tela continua branca, o erro pode estar no <b>includes/header.php</b> ou no <b>includes/footer.php</b> do SuperAdmin.</p>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ ERRO ENCONTRADO</h2>";
    echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
}
?>
