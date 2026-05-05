<?php
/**
 * admin/blocked.php - AVISO DE CONTA BLOQUEADA
 * Interface amigável para usuários inadimplentes ou suspensos.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';

// Se por algum motivo o status não for mais 'blocked', redireciona para a home
// (Evita que o usuário fique preso aqui se já pagou)
$conn = connect_db();
$stmt = $conn->prepare("SELECT subscription_status FROM empresas WHERE id = ?");
$stmt->execute([$_SESSION['empresa_id'] ?? 0]);
$status = $stmt->fetchColumn();

if ($status !== 'blocked' && $status !== 'overdue') {
    header("Location: painel_admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Suspenso - Brasallis Hub</title>
    <link rel="icon" type="image/png" href="/assets/img/pureza.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .blocked-card { max-width: 500px; width: 90%; background: white; border-radius: 30px; padding: 40px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.05); }
        .icon-warning { width: 80px; height: 80px; background: #fff1f2; color: #e11d48; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 2.5rem; }
        h2 { font-weight: 800; color: #0f172a; letter-spacing: -1px; }
        p { color: #64748b; line-height: 1.6; }
        .btn-pay { background: #0f172a; color: white; border-radius: 50px; padding: 12px 30px; font-weight: 700; text-decoration: none; display: inline-block; transition: 0.3s; margin-top: 20px; }
        .btn-pay:hover { background: #334155; transform: translateY(-2px); color: white; }
        .btn-support { color: #64748b; font-weight: 600; text-decoration: none; font-size: 0.9rem; display: block; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="blocked-card">
        <div class="icon-warning">
            <i class="fas fa-lock"></i>
        </div>
        <h2>Acesso Suspenso</h2>
        <p>Identificamos uma pendência em sua assinatura ou sua conta foi suspensa temporariamente por um administrador.</p>
        
        <div class="alert alert-warning border-0 rounded-4 small py-2 mt-3">
            <i class="fas fa-info-circle me-2"></i> Para regularizar, realize o pagamento da fatura em aberto.
        </div>

        <a href="checkout.php" class="btn-pay shadow">Regularizar Assinatura</a>
        
        <a href="suporte.php" class="btn-support"><i class="fas fa-headset me-1"></i> Falar com Suporte Técnico</a>
        
        <div class="mt-4 pt-4 border-top">
            <a href="../sair.php" class="text-muted small text-decoration-none">Sair do Sistema</a>
        </div>
    </div>
</body>
</html>
