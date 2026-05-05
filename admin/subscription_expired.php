<?php
// admin/subscription_expired.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';

// Se o usuário não estiver logado, manda para o login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$empresa_id = $_SESSION['empresa_id'];

// Buscar info da empresa
$conn = connect_db();
$stmt = $conn->prepare("SELECT * FROM empresas WHERE id = ?");
$stmt->execute([$empresa_id]);
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

// Se por acaso já tiver feito upgrade, redireciona de volta
if ($empresa['ai_plan'] !== 'free') {
    header("Location: painel_admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sua Sessão Expirou | Gestor Inteligente</title>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0071e3 0%, #42a5f5 100%);
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: 1px solid rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f7;
            color: #1d1d1f;
            overflow-x: hidden;
            min-height: 100vh;
        }

        .hero-section {
            padding: 80px 20px;
            text-align: center;
            background: radial-gradient(circle at top, #eef2f5 0%, #f5f5f7 100%);
        }

        .logo-area {
            margin-bottom: 2rem;
            animation: fadeInDown 0.8s ease;
        }

        .lock-icon {
            font-size: 3rem;
            background: linear-gradient(45deg, #ff6b6b, #ff8e53);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 1rem;
            background: linear-gradient(90deg, #1d1d1f, #434344);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: fadeInUp 0.8s ease 0.2s backwards;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: #86868b;
            max-width: 600px;
            margin: 0 auto 3rem;
            font-weight: 400;
            animation: fadeInUp 0.8s ease 0.3s backwards;
        }

        /* Plan Cards */
        .plans-container {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
            padding: 0 20px 80px;
            max-width: 1200px;
            margin: 0 auto;
            animation: fadeInUp 0.8s ease 0.4s backwards;
        }

        .plan-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 24px;
            padding: 40px;
            width: 350px;
            box-shadow: var(--glass-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .plan-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        .plan-card.highlight {
            border: 2px solid #0071e3;
            background: white;
        }

        .badge-popular {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #0071e3;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            text-transform: uppercase;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .plan-price {
            font-size: 3rem;
            font-weight: 800;
            color: #1d1d1f;
        }

        .plan-period {
            color: #86868b;
            font-size: 1rem;
        }

        .features-list {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
            text-align: left;
        }

        .features-list li {
            margin-bottom: 1rem;
            color: #424245;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .check-icon {
            color: #0071e3;
            font-size: 1.1rem;
        }

        .btn-cta {
            display: block;
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary-cta {
            background: #0071e3;
            color: white;
        }
        .btn-primary-cta:hover {
            background: #0077ed;
            transform: scale(1.02);
        }

        .btn-secondary-cta {
            background: rgba(0,0,0,0.05);
            color: #1d1d1f;
        }
        .btn-secondary-cta:hover {
            background: rgba(0,0,0,0.1);
        }

        .logout-link {
            position: fixed;
            top: 20px;
            right: 20px;
            color: #86868b;
            text-decoration: none;
            font-size: 0.9rem;
            z-index: 10;
        }
        .logout-link:hover { color: #1d1d1f; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Mobile */
        @media (max-width: 768px) {
            .hero-title { font-size: 2.5rem; }
            .plan-card { width: 100%; }
        }
    </style>
</head>
<body>

    <a href="../sair.php" class="logout-link"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>

    <div class="hero-section">
        <div class="logo-area">
            <i class="fas fa-cube fa-2x" style="color: #1d1d1f;"></i>
        </div>
        <div class="lock-icon">
            <i class="fas fa-lock"></i>
        </div>
        <h1 class="hero-title">Seu teste terminou.</h1>
        <p class="hero-subtitle">Mas sua jornada com a IA está apenas começando. Escolha um plano para destravar todo o potencial da sua empresa.</p>
    </div>

    <div class="plans-container">
        <!-- Growth Plan -->
        <div class="plan-card highlight">
            <span class="badge-popular">Mais Escolhido</span>
            <h3 class="plan-name">Growth</h3>
            <div class="price-box">
                <span class="plan-price">R$ 99</span>
                <span class="plan-period">/mês</span>
            </div>
            <p style="color: #86868b; margin-top: 10px; font-size: 0.9rem;">Tudo para escalar suas vendas.</p>
            
            <ul class="features-list">
                <li><i class="fas fa-check-circle check-icon"></i> 5 Usuários</li>
                <li><i class="fas fa-check-circle check-icon"></i> 2 Milhões de Tokens IA</li>
                <li><i class="fas fa-check-circle check-icon"></i> Leitura de Notas Fiscais Automática</li>
                <li><i class="fas fa-check-circle check-icon"></i> Suporte Prioritário</li>
            </ul>

            <a href="checkout.php?plan=growth" class="btn-cta btn-primary-cta">
                Assinar Growth
            </a>
            <p style="text-align: center; margin-top: 15px; font-size: 0.8rem; color: #86868b;">
                <i class="fas fa-bolt" style="color: #ffb300;"></i> Ativação imediata via Pix ou Cartão
            </p>
        </div>

        <!-- Enterprise Plan -->
        <div class="plan-card">
            <h3 class="plan-name">Enterprise</h3>
            <div class="price-box">
                <span class="plan-price">R$ 299</span>
                <span class="plan-period">/mês</span>
            </div>
            <p style="color: #86868b; margin-top: 10px; font-size: 0.9rem;">Para operações complexas.</p>
            
            <ul class="features-list">
                <li><i class="fas fa-check-circle check-icon"></i> Usuários Ilimitados</li>
                <li><i class="fas fa-check-circle check-icon"></i> Tokens Ilimitados (Fair Use)</li>
                <li><i class="fas fa-check-circle check-icon"></i> API para Agentes Autônomos</li>
                <li><i class="fas fa-check-circle check-icon"></i> Gerente de Conta Dedicado</li>
            </ul>

            <a href="checkout.php?plan=enterprise" class="btn-cta btn-secondary-cta">
                Assinar Enterprise
            </a>
        </div>
    </div>

</body>
</html>
