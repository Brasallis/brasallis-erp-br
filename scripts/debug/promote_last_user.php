<?php
// promote_last_user.php
require_once __DIR__ . '/includes/funcoes.php';

$conn = connect_db();

// Pega o último usuário criado (provavelmente quem está registrando e testando agora)
$stmt = $conn->query("SELECT id, username, email, user_type FROM usuarios ORDER BY id DESC LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "Usuário encontrado: " . $user['username'] . " (" . $user['email'] . ") - Tipo atual: " . $user['user_type'] . "\n";
    
    $update = $conn->prepare("UPDATE usuarios SET user_type = 'super_admin' WHERE id = ?");
    $update->execute([$user['id']]);
    
    echo "SUCESSO: Usuário promovido a SUPER ADMIN!\n";
    echo "Agora você pode acessar: /superadmin/index.php\n";
} else {
    echo "Nenhum usuário encontrado.\n";
}
?>
