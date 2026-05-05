<?php
/**
 * scripts/create_superadmin.php
 * Cria um novo usuário com privilégios de SuperAdmin.
 */
require_once __DIR__ . '/../bootstrap.php';
use App\Core\Database;

if (php_sapi_name() !== 'cli' && !isset($_GET['key'])) {
    die("Acesso negado. Use via CLI ou forneça a MASTER_KEY.");
}

if (php_sapi_name() !== 'cli') {
    check_master_key();
}

$username = $argv[1] ?? 'Novo SuperAdmin';
$email = $argv[2] ?? 'superadmin' . rand(10, 99) . '@brasallis.pro';
$password = $argv[3] ?? bin2hex(random_bytes(4)); // Senha curta aleatória se não fornecida

try {
    $conn = Database::getInstance();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO usuarios (empresa_id, username, email, password, user_type, subscription_status) VALUES (0, ?, ?, ?, 'super_admin', 'active')");
    $stmt->execute([$username, $email, $hashedPassword]);

    echo "\n🚀 SuperAdmin criado com sucesso!\n";
    echo "-----------------------------------\n";
    echo "Username: $username\n";
    echo "Email:    $email\n";
    echo "Senha:    $password\n";
    echo "-----------------------------------\n";
    echo "Guarde estas credenciais em local seguro.\n\n";

} catch (Exception $e) {
    echo "\n❌ Erro ao criar SuperAdmin: " . $e->getMessage() . "\n\n";
}
