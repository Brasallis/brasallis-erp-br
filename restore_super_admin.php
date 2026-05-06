<?php
require_once __DIR__ . '/includes/db_config.php';

try {
    $port = defined('DB_PORT') ? DB_PORT : 3306;
    $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Atualizar o ENUM da tabela para suportar super_admin (caso ainda não suporte)
    $pdo->exec("ALTER TABLE usuarios MODIFY COLUMN user_type ENUM('admin', 'employee', 'super_admin') NOT NULL DEFAULT 'employee'");

    // 2. Criar Empresa Mestre se não existir
    $stmt = $pdo->prepare("SELECT id FROM empresas WHERE name = 'Brasallis Corporate' LIMIT 1");
    $stmt->execute();
    $empresa = $stmt->fetch();
    
    if (!$empresa) {
        $stmt_empresa = $pdo->prepare("INSERT INTO empresas (name, owner_user_id, ai_plan, max_users, support_level) VALUES ('Brasallis Corporate', 1, 'enterprise_elite', 999, 'dedicated')");
        $stmt_empresa->execute();
        $master_empresa_id = $pdo->lastInsertId();
    } else {
        $master_empresa_id = $empresa['id'];
    }

    // 3. Criar a conta Super Admin
    $pwd_hash = password_hash('brasallismaster', PASSWORD_DEFAULT);
    
    // Verifica se já existe para não duplicar
    $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE email = 'admin@brasallis.com.br'");
    $stmt_check->execute();
    if (!$stmt_check->fetch()) {
        $stmt_sa = $pdo->prepare("INSERT INTO usuarios (empresa_id, username, password, email, user_type, plan) VALUES (?, 'Super Admin (God Mode)', ?, 'admin@brasallis.com.br', 'super_admin', 'enterprise_elite')");
        $stmt_sa->execute([$master_empresa_id, $pwd_hash]);
        $sa_id = $pdo->lastInsertId();
        
        $pdo->exec("UPDATE empresas SET owner_user_id = {$sa_id} WHERE id = {$master_empresa_id}");
        
        echo "<h1>Tudo Pronto! 🌟</h1>";
        echo "<p>Sua conta de Super Admin foi forjada com sucesso e já está imortalizada nos scripts de reset.</p>";
        echo "<hr/>";
        echo "<p><b>E-mail:</b> admin@brasallis.com.br<br/><b>Senha:</b> brasallismaster</p>";
    } else {
        echo "<h1>Aviso!</h1><p>A conta Super Admin já existe neste banco.</p>";
    }

} catch (Exception $e) {
    echo "<h1>Erro Crítico</h1><p>" . $e->getMessage() . "</p>";
}

// Autodestruição silenciosa
@unlink(__FILE__);
?>
