<?php
require_once 'vendor/autoload.php';
use App\Core\Database;

$conn = Database::getInstance()->getConnection();

$stmt = $conn->prepare("SELECT id, name FROM empresas WHERE name LIKE ?");
$stmt->execute(['%Pão de Açúcar%']);
$empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($empresas)) {
    echo "Empresa não encontrada. Criando...\n";
    
    // Create the company
    $conn->beginTransaction();
    try {
        // Insert Company
        $stmt = $conn->prepare("INSERT INTO empresas (name, owner_user_id) VALUES (?, ?)");
        $stmt->execute(['Pão de Açúcar', 0]);
        $empresa_id = $conn->lastInsertId();
        
        // Insert Admin User
        $stmt = $conn->prepare("INSERT INTO usuarios (empresa_id, username, email, password, user_type, plan, subscription_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $password = password_hash('123456', PASSWORD_DEFAULT);
        $stmt->execute([$empresa_id, 'Admin Pão de Açúcar', 'admin@paodeacucar.com', $password, 'admin', 'enterprise', 'active']);
        $user_id = $conn->lastInsertId();
        
        // Update Owner
        $stmt = $conn->prepare("UPDATE empresas SET owner_user_id = ? WHERE id = ?");
        $stmt->execute([$user_id, $empresa_id]);
        
        $conn->commit();
        echo "Empresa criada com ID: " . $empresa_id . "\n";
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo "Erro ao criar empresa: " . $e->getMessage() . "\n";
    }
} else {
    print_r($empresas);
}
?>
