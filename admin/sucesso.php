<?php
// admin/sucesso.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';
include_once '../includes/cabecalho.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 text-center">
        <div class="card border-0 shadow-lg rounded-4 p-5">
            <div class="mb-4 text-success">
                <i class="fas fa-check-circle fa-5x"></i>
            </div>
            <h2 class="fw-bold mb-3">Pagamento Aprovado!</h2>
            <p class="text-muted mb-4">Sua assinatura foi ativada com sucesso. Aproveite todos os recursos premium.</p>
            <a href="painel_admin.php" class="btn btn-primary btn-lg rounded-pill px-5">Ir para o Dashboard</a>
        </div>
    </div>
</div>

<?php include_once '../includes/rodape.php'; ?>
