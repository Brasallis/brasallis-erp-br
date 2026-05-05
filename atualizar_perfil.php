<?php

require_once __DIR__ . '/includes/funcoes.php';
session_start();

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitiza os dados de entrada
    $id = sanitize_input($_POST['id']);
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);

    // Conecta-se ao banco de dados
    $conn = connect_db();

    if ($conn) {
        try {
            // Verifica se a senha foi alterada
            if (!empty($password)) {
                // Gera o hash da nova senha
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // Prepara a consulta para atualizar o usuário com a nova senha
                $stmt = $conn->prepare("UPDATE usuarios SET username = :username, email = :email, password = :password WHERE id = :id");
                $stmt->execute([
                    ':id' => $id,
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $hashed_password
                ]);
            } else {
                // Prepara a consulta para atualizar o usuário sem alterar a senha
                $stmt = $conn->prepare("UPDATE usuarios SET username = :username, email = :email WHERE id = :id");
                $stmt->execute([
                    ':id' => $id,
                    ':username' => $username,
                    ':email' => $email
                ]);
            }

            // Atualiza o nome de usuário na sessão
            $_SESSION['username'] = $username;

            // Redireciona para a página de edição de perfil com uma mensagem de sucesso
            header('Location: editar_perfil.php?success=Perfil atualizado com sucesso!');
            exit();

        } catch (PDOException $e) {
            // Redireciona para a página de edição de perfil com uma mensagem de erro
            header('Location: editar_perfil.php?error=Erro ao atualizar perfil: ' . $e->getMessage());
            exit();
        }
    }
}

?>
