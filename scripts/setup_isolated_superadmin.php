<?php
// scripts/setup_isolated_superadmin.php
require_once __DIR__ . '/../includes/funcoes.php';

$db = connect_db();

try {
    // 1. Limpa rastros antigos
    $db->exec("DELETE FROM usuarios WHERE email IN ('master@brasallis.com.br', 'admin@brasallis.pro')");

    // 2. Cria o SuperAdmin Global (empresa_id = 0)
    $username = 'SuperAdmin Global';
    $email = 'admin@brasallis.pro';
    $password = 'Brasallis@2026!Master';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (empresa_id, username, email, password, user_type, status_colaborador, created_at) 
            VALUES (0, ?, ?, ?, 'super_admin', 'ativo', NOW())";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$username, $email, $hashedPassword]);

    echo "SUCESSO: SuperAdmin Global criado!\n";
    echo "Login: $email\n";
    echo "Senha: $password\n";
    echo "Isolamento: empresa_id = 0 (Global)\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
