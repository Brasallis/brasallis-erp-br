<?php
// create_god_user.php
require_once __DIR__ . '/includes/funcoes.php';

$conn = connect_db();

$email = 'superadmin@gestor.com';
$password = 'Admin123!'; // Senha padrão forte
$username = 'Super Admin';

// Verificar se já existe
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    echo "Usuário já existe. Atualizando permissões...\n";
    $conn->prepare("UPDATE usuarios SET user_type = 'super_admin' WHERE email = ?")->execute([$email]);
} else {
    echo "Criando novo Super Admin...\n";
    // Criar uma empresa placeholder para ele (Super Admin não precisa de empresa real, mas o DB pode exigir FK)
    // Vamos usar a empresa ID 1 ou criar uma "Gestão Global"
    
    // Verificar empresa 1
    $emp = $conn->query("SELECT id FROM empresas LIMIT 1")->fetch();
    $empresa_id = $emp['id'] ?? 0;
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO usuarios (empresa_id, username, email, password, user_type, created_at) VALUES (?, ?, ?, ?, 'super_admin', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$empresa_id, $username, $email, $hash]);
}

echo "--- CREDENCIAIS SUPER ADMIN ---\n";
echo "Email: $email\n";
echo "Senha: $password\n";
echo "-------------------------------\n";
?>
