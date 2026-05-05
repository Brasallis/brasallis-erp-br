<?php
/**
 * admin/perfil.php - CENTRO DE CONTROLE DO USUÁRIO
 * Modernizado para o padrão Brasallis Hub (Glassmorphism & Material 3)
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';
checkAuth();

$conn = connect_db();
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// --- LÓGICA DE ATUALIZAÇÃO (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_token($_POST['csrf_token'] ?? '');
        
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validação de e-mail duplicado
        $stmtCheck = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmtCheck->execute([$email, $user_id]);
        if ($stmtCheck->fetch()) {
            throw new Exception("Este e-mail já está em uso por outro usuário.");
        }

        if (!empty($password)) {
            if (strlen($password) < 6) {
                throw new Exception("A senha deve ter pelo menos 6 caracteres.");
            }
            if ($password !== $confirm_password) {
                throw new Exception("As senhas digitadas não coincidem.");
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET username = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$username, $email, $hashed_password, $user_id]);
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([$username, $email, $user_id]);
        }
        
        $_SESSION['username'] = $username;
        $message = "Perfil atualizado com sucesso!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Buscar dados atuais
$stmt = $conn->prepare("SELECT username, email, user_type FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

include_once '../includes/cabecalho.php';
?>

<div class="container py-4" style="max-width: 800px;">
    <!-- HEADER DA PÁGINA -->
    <div class="d-flex align-items-center gap-3 mb-5">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-lg" style="width: 80px; height: 80px; font-size: 2rem; font-weight: 800;">
            <?= strtoupper(substr($user['username'], 0, 1)) ?>
        </div>
        <div>
            <h1 class="fw-bold mb-1" style="letter-spacing: -1.5px;">Meu Perfil</h1>
            <p class="text-muted mb-0">Gerencie suas informações de acesso e segurança.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3">
            <i class="fas fa-check-circle fs-4"></i>
            <div><?= $message ?></div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3">
            <i class="fas fa-exclamation-triangle fs-4"></i>
            <div><?= $error ?></div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- FORMULÁRIO PRINCIPAL -->
        <div class="col-md-12">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <form action="meu-perfil.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    
                    <div class="section-title mb-4 pb-2 border-bottom fw-bold text-uppercase small text-muted" style="letter-spacing: 1px;">
                        Dados Pessoais
                    </div>
                    
                    <div class="row g-3 mb-5">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">NOME COMPLETO</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 rounded-start-3"><i class="fas fa-user"></i></span>
                                <input type="text" name="username" class="form-control bg-light border-0 rounded-end-3" value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">E-MAIL</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 rounded-start-3"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control bg-light border-0 rounded-end-3" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                             <label class="form-label small fw-bold text-muted">TIPO DE CONTA</label>
                             <input type="text" class="form-control bg-light border-0 rounded-3 text-muted" value="<?= ucfirst($user['user_type']) ?>" disabled>
                             <small class="text-muted d-block mt-1">Nível de acesso gerenciado pelo Administrador.</small>
                        </div>
                    </div>

                    <div class="section-title mb-4 pb-2 border-bottom fw-bold text-uppercase small text-muted" style="letter-spacing: 1px;">
                        Alterar Senha <span class="fw-normal">(Deixe em branco para manter a atual)</span>
                    </div>

                    <div class="row g-3 mb-5">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">NOVA SENHA</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 rounded-start-3"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" class="form-control bg-light border-0 rounded-end-3" placeholder="••••••••">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">CONFIRMAR NOVA SENHA</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 rounded-start-3"><i class="fas fa-shield-alt"></i></span>
                                <input type="password" name="confirm_password" class="form-control bg-light border-0 rounded-end-3" placeholder="••••••••">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 pt-3">
                        <button type="reset" class="btn btn-link text-muted text-decoration-none fw-bold">Descartar</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow">
                            <i class="fas fa-save me-2"></i>Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- INFO ADICIONAL -->
        <div class="col-md-12">
            <div class="bg-white rounded-4 p-4 d-flex align-items-center gap-4 border border-dashed shadow-sm">
                <div class="icon-box bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="fas fa-shield-virus text-primary fs-4"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Segurança em Primeiro Lugar</h6>
                    <p class="text-muted small mb-0">Sua conta está protegida com criptografia de ponta a ponta. Nunca compartilhe sua senha com terceiros.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .rounded-4 { border-radius: 20px !important; }
    .border-dashed { border-style: dashed !important; border-width: 2px !important; border-color: #e2e8f0 !important; }
    .btn-primary { background: #000; border: none; }
    .btn-primary:hover { background: #222; transform: translateY(-1px); }
</style>

<?php include_once '../includes/rodape.php'; ?>
