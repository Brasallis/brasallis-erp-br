<?php

require_once __DIR__ . '/includes/functions.php';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitiza o código de entrada
    $code = sanitize_input($_POST['code']);

    // Conecta-se ao banco de dados
    $conn = connect_db();

    if ($conn) {
        try {
            // Verifica se o código é válido e não expirou (expiração de 10 minutos)
            $stmt = $conn->prepare("SELECT email FROM redefinicoes_senha WHERE code = :code AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
            $stmt->bindParam(':code', $code);
            $stmt->execute();

            if ($stmt->rowCount() === 1) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $email = $row['email'];

                // Redireciona para a página de redefinição de senha com o e-mail
                header('Location: redefinir_senha.php?email=' . urlencode($email));
                exit();

            } else {
                header('Location: forgot_password.php?error=Código inválido ou expirado.');
                exit();
            }
        } catch (PDOException $e) {
            header('Location: esqueceu_senha.php?error=Erro no servidor. Tente novamente mais tarde.');
            error_log("Erro de verificação de código: " . $e->getMessage());
            exit();
        }
    }
}

?>
