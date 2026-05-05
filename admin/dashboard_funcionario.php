<?php
// admin/dashboard_funcionario.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Layout minimalista para página de bloqueio
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Pendente - Brasallis</title>
    <link rel="icon" type="image/png" href="/assets/img/pureza.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --navy: #0A2647;
            --surface: #F8FAFC;
        }
        body { 
            background-color: var(--surface); 
            font-family: 'Outfit', sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0;
            color: #1E293B;
        }
        .pending-card {
            background: #fff;
            border-radius: 28px;
            padding: 3rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: rgba(255, 193, 7, 0.1);
            color: #FFC107;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }
        .btn-logout {
            background: var(--navy);
            color: #fff;
            border-radius: 100px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            border: none;
        }
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(10, 38, 71, 0.2);
            color: #fff;
        }
    </style>
</head>
<body>

<div class="pending-card">
    <div class="icon-circle">
        <i class="fas fa-user-clock"></i>
    </div>
    
    <h3 class="fw-bold text-dark mb-2">Acesso em Análise</h3>
    <p class="text-muted mb-4 px-3">
        Bem-vindo, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Colaborador') ?></strong>! 
        Sua conta ainda não foi vinculada a um departamento ou cargo específico.
    </p>

    <div class="bg-light p-3 rounded-4 mb-4 small text-muted border-0">
        <i class="fas fa-shield-halved me-2"></i>
        Por favor, solicite ao <strong>Administrador</strong> da empresa para configurar suas permissões de acesso.
    </div>

    <a href="../sair.php" class="btn-logout">
        <i class="fas fa-sign-out-alt"></i> Sair do Sistema
    </a>
</div>

</body>
</html>

