<?php
require_once __DIR__ . '/includes/manutencao_guard.php';
/**
 * fix_db_from_browser.php
 * Executa as migrações críticas de RH diretamente do servidor Web.
 * Resolvido: Erro Unknown column 'data_admissao'.
 */

require_once __DIR__ . '/bootstrap.php';
check_master_key();

require_once __DIR__ . '/includes/db_config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<style>body{font-family:sans-serif;padding:30px;line-height:1.6;} .ok{color:green;} .error{color:red;}</style>";

echo "<h1>🚀 Executando Reparo Estrutural (Modo Web)</h1>";

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<p>Conectado ao banco: <b>" . DB_NAME . "</b></p>";

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
            echo "<p class='ok'>[✓] Sucesso: <code>$sql</code></p>";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1060 || $e->errorInfo[1] == 1061) {
                echo "<p style='color:#666;'>[i] Já existe/Pulando: <code>$sql</code></p>";
            } else {
                echo "<p class='error'>[X] Falha: <code>$sql</code><br>Erro: " . $e->getMessage() . "</p>";
            }
        }
    }

    echo "<h3>✅ Saneamento concluído!</h3>";
    echo "<p><a href='diagnose_db.php'>Clique aqui para validar os resultados</a></p>";

} catch (Exception $e) {
    echo "<p class='error'><b>ERRO FATAL:</b> " . $e->getMessage() . "</p>";
}

