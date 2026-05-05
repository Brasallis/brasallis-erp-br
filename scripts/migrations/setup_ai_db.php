<?php
// setup_ai_db.php
// Script para criar as tabelas do módulo de Agentes IA

require_once __DIR__ . '/includes/db_config.php';

// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Configuração do Banco de Dados - Agentes IA</h1>";

try {
    // 1. Conexão
    echo "<p>Tentando conectar ao banco de dados...</p>";
    
    // Verificar se estamos em ambiente local ou Heroku (com base no db_config.php padrão)
    $hosts = [DB_HOST, 'localhost'];
    $pdo = null;
    $lastError = null;

    foreach ($hosts as $host) {
        try {
            $dsn = "mysql:host=" . $host . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            echo "\nConectado com sucesso em: $host\n";
            break;
        } catch (PDOException $e) {
            $lastError = $e;
            echo "\nFalha ao conectar em $host: " . $e->getMessage() . "\n";
            continue;
        }
    }

    if (!$pdo) {
        throw new Exception("Não foi possível conectar ao banco de dados em nenhum dos hosts. Erro final: " . $lastError->getMessage());
    }
    echo "<p style='color: green;'><strong>Conexão bem-sucedida!</strong></p>";

    // 2. Ler Arquivo SQL
    $sqlFile = __DIR__ . '/sql/create_ai_agents_table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo SQL não encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // 3. Executar SQL
    // Separar queries se necessário, mas PDO->exec pode rodar múltiplas se o driver permitir.
    // Para garantir, vamos tentar executar em bloco.
    
    echo "<p>Executando script SQL...</p>";
    $pdo->exec($sql);
    
    if (php_sapi_name() === 'cli') {
        echo "\nConclusão: Sucesso!\n";
        echo "Tabelas criadas/atualizadas.\n";
    } else {
        echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>
                <h3>Sucesso!</h3>
                <p>As tabelas <code>ai_agents</code> e <code>ai_agent_logs</code> foram criadas ou atualizadas.</p>
                <p>As colunas de API Key foram adicionadas à tabela <code>empresas</code>.</p>
              </div>";
        echo "<p><a href='admin/agentes_ia.php'>Ir para Agentes IA</a></p>";
    }

} catch (PDOException $e) {
    if (php_sapi_name() === 'cli') {
        echo "\nPDO ERROR: " . $e->getMessage() . "\n";
    } else {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>
                <h3>Erro de Banco de Dados</h3>
                <p>" . htmlspecialchars($e->getMessage()) . "</p>
              </div>";
    }
} catch (Exception $e) {
    if (php_sapi_name() === 'cli') {
        echo "\nGENERAL ERROR: " . $e->getMessage() . "\n";
    } else {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>
                <h3>Erro Geral</h3>
                <p>" . htmlspecialchars($e->getMessage()) . "</p>
              </div>";
    }
}
?>
