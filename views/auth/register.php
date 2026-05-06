<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Novo Registro - Brasallis Hub</title>
    <link rel="icon" type="image/png" href="/assets/img/pureza.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <link href="../../assets/css/material_system.css" rel="stylesheet">
    
    <style>
        :root {
            /* The Corporate Brasallis Hub Blue Palette */
            --sys-navy: #001E3C;
            --sys-navy-light: #0A2647;
            --sys-blue-accent: var(--m3-primary);
            --sys-surface: #FFFFFF;
            --sys-text-muted: #64748B;
        }

        /* Zero Scroll Architecture */
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Inter', sans-serif;
            background-color: var(--sys-navy);
        }

        @media (min-width: 992px) {
            html, body { overflow: hidden !important; }
        }

        h1, h2, h3, .brand-text {
            font-family: 'Outfit', sans-serif;
        }

        .split-screen {
            height: 100vh;
            display: flex;
        }

        /* --- THE ENTERPRISE RAIL (LEFT/WHITE) --- Hidden on Mobile to match Login v5.1 */
        .left-pane {
            background-color: #ffffff;
            color: var(--sys-navy);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 5rem 8%;
            border-right: 1px solid #E2E8F0;
            width: 42%;
            flex-shrink: 0;
        }

        .brand-header {
            margin-bottom: 3rem;
        }

        .big-logo {
            max-width: 240px;
            height: auto;
            display: block;
        }

        .comm-title {
            font-size: 2.25rem;
            font-weight: 700;
            line-height: 1.2;
            color: var(--sys-navy);
            margin-bottom: 1.5rem;
        }

        .comm-features {
            margin-bottom: 3rem;
        }

        .feature-line {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 0.85rem;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--sys-text-muted);
        }

        .feature-line i {
            color: var(--sys-blue-accent);
            font-size: 0.8rem;
        }

        /* --- THE REGISTRATION TERMINAL (RIGHT/BLUE) --- */
        .right-pane {
            background-color: var(--sys-navy);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            flex-grow: 1;
            box-shadow: inset 20px 0 30px rgba(0,0,0,0.05);
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
            display: flex;
            flex-direction: column;
        }

        /* Mobile Logo */
        .mobile-brand-logo {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .auth-container h2 {
            color: #ffffff;
            font-size: 1.65rem;
            font-weight: 700;
            margin-bottom: 0.4rem;
        }

        .auth-container p {
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }

        /* --- BRASALLIS ENTERPRISE INPUTS (HIGH-DENSITY) --- */
        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-group-suite {
            margin-bottom: 1rem;
            flex: 1;
        }

        .form-label-suite {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 0.5rem;
            display: block;
        }

        .input-suite {
            background: rgba(255, 255, 255, 0.04) !important;
            border: 1.5px solid rgba(255, 255, 255, 0.12) !important;
            border-radius: 10px;
            padding: 12px 16px;
            color: #ffffff !important;
            font-size: 0.95rem;
            transition: all 0.25s ease;
            width: 100%;
            outline: none;
        }

        .input-suite::placeholder {
            color: rgba(255, 255, 255, 0.15);
        }

        .input-suite:focus {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: var(--sys-blue-accent) !important;
            box-shadow: 0 0 0 1px var(--sys-blue-accent);
        }

        .btn-suite {
            background: var(--sys-blue-accent);
            color: #ffffff;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 0.5rem;
            box-shadow: 0 4px 15px rgba(0, 112, 242, 0.3);
        }

        .btn-suite:hover {
            background: #005DC9;
            transform: translateY(-2px);
        }

        .auth-footer {
            margin-top: 1.25rem;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 1rem;
        }

        .auth-footer a {
            color: rgba(255, 255, 255, 0.3);
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 600;
            transition: color 0.2s;
        }

        .auth-footer a:hover {
            color: #ffffff;
        }

        /* Plan Badge */
        .plan-badge {
            background: rgba(0, 112, 242, 0.2);
            color: var(--sys-blue-accent);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 1px solid rgba(0, 112, 242, 0.3);
            display: inline-block;
            margin-bottom: 1rem;
        }

        /* --- MOBILE LEAN (THE ZERO-SCROLL TEMA CLARO) --- */
        @media (max-width: 991px) {
            .left-pane { display: none !important; }
            .right-pane { 
                width: 100%; min-height: 100vh; height: auto; 
                padding: 2rem 1.5rem; overflow-y: auto !important; 
                background-color: #ffffff !important; /* Tela branca no mobile */
                box-shadow: none !important;
            }
            .auth-container { max-width: 100%; }
            html, body { overflow: auto !important; height: auto !important; background-color: #ffffff !important; }
            
            .form-row { flex-direction: column; gap: 0; }
            
            /* Ajuste de Textos para Modo Claro */
            .auth-container h2 { color: var(--sys-navy) !important; font-size: 1.5rem; }
            .auth-container > p { color: var(--sys-text-muted) !important; font-size: 0.85rem; }
            .form-label-suite { color: var(--sys-navy-light) !important; }
            
            /* Ajuste de Inputs para Modo Claro */
            .input-suite { 
                background: #f8f9fa !important;
                border: 1px solid #e2e8f0 !important;
                color: var(--sys-navy) !important;
                border-radius: 8px !important;
                padding: 12px 14px !important;
            }
            .input-suite::placeholder { color: #adb5bd !important; }
            .input-suite:focus {
                background: #ffffff !important;
                border-color: var(--sys-blue-accent) !important;
                box-shadow: 0 0 0 3px rgba(0,112,242,0.1) !important;
            }
            
            /* Botão Principal Minimalista */
            .btn-suite { border-radius: 8px !important; padding: 12px !important; margin-top: 1rem; }

            /* Footer Claro */
            .auth-footer { border-top: 1px solid #e2e8f0 !important; }
            .auth-footer a { color: var(--sys-text-muted) !important; }
            .auth-footer p { color: #cbd5e1 !important; }
        }

        @media (max-height: 850px) {
            .comm-title { font-size: 1.8rem; }
            .brand-header { margin-bottom: 2rem; }
            .comm-features { margin-bottom: 2rem; }
            .auth-container h2 { font-size: 1.4rem; }
            .btn-suite { padding: 12px; }
        }
    </style>
</head>
<body>

    <div class="split-screen g-0">
        <!-- THE ENTERPRISE RAIL (LEFT/WHITE) -->
        <div class="left-pane">
            <div class="brand-header">
                <img src="/assets/img/pureza.png" alt="Brasallis Hub" class="big-logo">
            </div>
            
            <h1 class="comm-title">A Central de Comando<br>do seu Negócio.</h1>
            
            <div class="comm-features">
                <div class="feature-line"><i class="fas fa-check-circle"></i> Implementação Ultra-Rápida</div>
                <div class="feature-line"><i class="fas fa-check-circle"></i> Gestão Fiscal de Alta Precisão</div>
                <div class="feature-line"><i class="fas fa-check-circle"></i> Suporte Especializado 24/7</div>
            </div>

            <div class="mt-auto pt-4 opacity-40">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-shield-alt small"></i>
                    <small class="fw-bold text-uppercase">Enterprise-Grade Infrastructure</small>
                </div>
            </div>
        </div>

        <!-- THE REGISTRATION TERMINAL (RIGHT/BLUE) -->
        <div class="right-pane">
            <div class="auth-container">
                <!-- Mobile Logo -->
                <div class="mobile-brand-logo d-lg-none">
                    <img src="/assets/img/pureza.png" alt="Brasallis" height="30">
                </div>

                <div class="plan-badge">
                    <?php echo htmlspecialchars($plano_selecionado); ?> | 
                    <?php echo ucfirst(htmlspecialchars($billing_selecionado)); ?>
                </div>

                <h2>Nova Empresa</h2>
                <p>Configure sua instância corporativa em segundos.</p>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger border-0 bg-danger bg-opacity-20 text-white rounded-3 p-2 mb-3 d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-2 fs-6"></i>
                        <span class="small fw-bold"><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                <?php endif; ?>

                <form action="/registrar_action.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="plan" value="<?php echo htmlspecialchars($plano_selecionado); ?>">
                    <input type="hidden" name="billing" value="<?php echo htmlspecialchars($billing_selecionado); ?>">

                    <div class="form-group-suite">
                        <label for="company_name" class="form-label-suite">Instituição / Empresa</label>
                        <input type="text" class="input-suite" id="company_name" name="company_name" value="<?php echo htmlspecialchars($form_data['company_name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group-suite">
                            <label for="username" class="form-label-suite">Gestor Responsável</label>
                            <input type="text" class="input-suite" id="username" name="username" value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group-suite">
                            <label for="email" class="form-label-suite">E-mail Corporativo</label>
                            <input type="email" class="input-suite" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group-suite">
                            <label for="password" class="form-label-suite">Senha de Acesso</label>
                            <input type="password" class="input-suite" id="password" name="password" required>
                        </div>
                        <div class="form-group-suite">
                            <label for="confirm_password" class="form-label-suite">Validar Senha</label>
                            <input type="password" class="input-suite" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-suite">
                        Confirmar e Ativar 15 Dias Grátis
                    </button>
                </form>

                <div class="auth-footer">
                    <div class="d-flex justify-content-center gap-4">
                        <a href="login.php">Já sou Cliente</a>
                        <a href="/" class="opacity-50">Saber Mais</a>
                    </div>
                    <p class="mt-2 small opacity-10" style="color: #ffffff; font-size: 0.6rem;">&copy; <?php echo date('Y'); ?> Enterprise Deployment Suite</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
