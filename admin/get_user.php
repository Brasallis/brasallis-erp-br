<?php
require_once __DIR__ . '/../includes/funcoes.php';

$conn = connect_db();

header('Content-Type: application/json');

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT id, username, email, user_type FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode($user);
        } else {
            echo json_encode(['error' => 'Usuário não encontrado.']);
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar dados do usuário: " . $e->getMessage());
        echo json_encode(['error' => 'Erro de banco de dados ao buscar usuário.']);
    }
} else {
    echo json_encode(['error' => 'ID do usuário inválido.']);
}
?>
