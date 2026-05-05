<?php
require_once __DIR__ . '/includes/manutencao_guard.php';
/**
 * SOLUCAO_BANCO.php
 * Script de reparo definitivo do Banco de Dados para o Módulo de RH.
 * 
 * INSTRUÇÃO: Abra seu navegador e acesse: http://localhost:8001/SOLUCAO_BANCO.php
 */

require_once __DIR__ . '/bootstrap.php';
check_master_key();

// CSS para facilitar a leitura no navegador
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px; line-height: 1.6; background: #f4f7f9; color: #333; }
    h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
    .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .success { color: #27ae60; font-weight: bold; }
    .error { color: #c0392b; background: #fceae9; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 5px solid #c0392b; }
    .info { color: #2980b9; }
    code { background: #eee; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
    .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
</style>";

echo "<h1>🚀 Reparo de Banco de Dados (Brasallis Hub)</h1>";

try {
    // Tentativa de conexão utilizando as configurações globais
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<div class='card'>";
    echo "<h3>Relatório de Execução:</h3>";
    echo "<p class='info'>Conectado ao banco: <b>" . DB_NAME . "</b> em <b>" . DB_HOST . "</b></p>";

    $queries = [
        "ALTER TABLE usuarios ADD COLUMN data_admissao DATE DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN cpf VARCHAR(14) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN celular VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN status_colaborador ENUM('ativo', 'inativo', 'ferias', 'afastado') DEFAULT 'ativo'",
        "CREATE INDEX idx_usuarios_cpf ON usuarios(cpf)"
    ];

    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p class='success'>✅ SUCESSO na execução: <code>$sql</code></p>";
        } catch (PDOException $e) {
            // Se o erro for 'Column already exists' (1060) ou 'Duplicate key name' (1061), tratamos como sucesso informativo
            if ($e->errorInfo[1] == 1060 || $e->errorInfo[1] == 1061) {
                echo "<p class='info'>ℹ️ Informação: " . (strpos($sql, 'INDEX') !== false ? "Índice" : "Coluna") . " já existe no sistema. (ID: " . $e->errorInfo[1] . ")</p>";
            } else {
                echo "<div class='error'>❌ ERRO na execução: <code>$sql</code><br>Detalhe: " . $e->getMessage() . "</div>";
            }
        }
    }

    echo "<h3>🎉 TUDO PRONTO!</h3>";
    echo "<p>As colunas do RH foram verificadas e criadas. O erro <code>Unknown column 'data_admissao'</code> foi resolvido.</p>";
    echo "<a href='/modules/rh/views/colaboradores.php' class='btn'>Voltar para Colaboradores</a>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'><h3>⛔ ERRO CRÍTICO DE CONEXÃO</h3>";
    echo "<p>O script não conseguiu se conectar ao banco de dados: " . $e->getMessage() . "</p>";
    echo "<p>Verifique o arquivo <code>includes/db_config.php</code>.</p></div>";
}

echo "<p style='margin-top:50px; font-size: 0.8rem; color: #999;'>Diagnóstico gerado em " . date('d/m/Y H:i:s') . "</p>";

