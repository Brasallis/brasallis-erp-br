<?php

include_once 'includes/cabecalho.php';
require_once 'includes/funcoes.php';

// Verifica se o token foi fornecido via GET
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    header('Location: esqueceu_senha.php?error=Link+invalido+ou+expirado.');
    exit();
}

// Valida o token no banco de dados (token deve ser único e ter expiração)
$conn = connect_db();
$token_valido = false;
$email_do_token = '';

if ($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT email 
            FROM redefinicoes_senha 
            WHERE token = :token 
              AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($registro) {
            $token_valido = true;
            $email_do_token = $registro['email'];
        }
    } catch (PDOException $e) {
        error_log("Erro na validação do token de redefinição: " . $e->getMessage());
    }
}

// Token inválido ou expirado
if (!$token_valido) {
    header('Location: esqueceu_senha.php?error=Token+invalido+ou+expirado.+Solicite+um+novo+link.');
    exit();
}

// Processa o formulário de nova senha
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validações
    if (empty($password) || strlen($password) < 8) {
        $error_message = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'As senhas não coincidem.';
    } else {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Atualiza a senha SOMENTE do usuário vinculado ao token válido
            $stmt = $conn->prepare("UPDATE usuarios SET password = :password WHERE email = :email");
            $stmt->execute([
                ':password' => $hashed_password,
                ':email'    => $email_do_token,
            ]);

            // Invalida o token imediatamente após o uso
            $stmt = $conn->prepare("DELETE FROM redefinicoes_senha WHERE token = :token");
            $stmt->bindParam(':token', $token);
            $stmt->execute();

            header('Location: index.php?success=Senha+redefinida+com+sucesso!+Faca+login.');
            exit();

        } catch (PDOException $e) {
            $error_message = 'Erro no servidor. Tente novamente mais tarde.';
            error_log("Erro de redefinição de senha: " . $e->getMessage());
        }
    }
}

?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mt-5">
                <div class="card-header">
                    <h5 class="card-title">Redefinir Senha</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error_message ?? '')): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="password" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                            <div class="form-text">Mínimo 8 caracteres.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Redefinir Senha</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/rodape.php'; ?>
