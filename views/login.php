<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sessão Administrativa - Brasallis Hub</title>
    <link rel="icon" type="image/png" href="/assets/img/pureza.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --sys-navy: #001E3C;
            --sys-navy-light: #0A2647;
            --sys-blue-accent: #0070F2;
            --sys-surface: #FFFFFF;
            --sys-text-muted: #64748B;
        }

        html, body {
            margin: 0; padding: 0; height: 100%;
            overflow: hidden !important;
            font-family: 'Inter', sans-serif;
            background-color: var(--sys-navy);
        }
        h1, h2, h3, .brand-text { font-family: 'Outfit', sans-serif; }

        .split-screen { height: 100vh; display: flex; }

        /* ── LEFT PANE ── */
        .left-pane {
            background-color: #ffffff;
            color: var(--sys-navy);
            display: flex; flex-direction: column; justify-content: center;
            padding: 5rem 8%;
            border-right: 1px solid #E2E8F0;
            width: 42%; flex-shrink: 0;
        }
        .brand-header { margin-bottom: 3.5rem; }
        .big-logo { max-width: 260px; height: auto; display: block; }
        .comm-title { font-size: 2.25rem; font-weight: 700; line-height: 1.2; color: var(--sys-navy); margin-bottom: 1.5rem; }
        .feature-line { display: flex; align-items: center; gap: 12px; margin-bottom: 0.85rem; font-size: 0.95rem; font-weight: 500; color: var(--sys-text-muted); }
        .feature-line i { color: var(--sys-blue-accent); font-size: 0.8rem; }

        /* ── RIGHT PANE ── */
        .right-pane {
            background-color: var(--sys-navy);
            display: flex; align-items: center; justify-content: center;
            padding: 3rem; flex-grow: 1;
        }
        .auth-container { width: 100%; max-width: 400px; display: flex; flex-direction: column; }
        .mobile-brand-logo { margin-bottom: 2rem; text-align: center; }
        .auth-container h2 { color: #fff; font-size: 1.65rem; font-weight: 700; margin-bottom: 0.4rem; }
        .auth-container > p { color: rgba(255,255,255,0.4); font-size: 0.9rem; margin-bottom: 2rem; }

        /* ── INPUTS ── */
        .form-group-suite { margin-bottom: 1.5rem; }
        .form-label-suite {
            color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 0.6rem; display: block;
        }
        .input-wrapper { position: relative; }
        .input-suite {
            background: rgba(255,255,255,0.04) !important;
            border: 1.5px solid rgba(255,255,255,0.12) !important;
            border-radius: 12px; padding: 15px 48px 15px 18px;
            color: #fff !important; font-size: 1rem;
            transition: all 0.25s ease; width: 100%; outline: none;
        }
        .input-suite::placeholder { color: rgba(255,255,255,0.15); }
        .input-suite:focus {
            background: rgba(255,255,255,0.08) !important;
            border-color: var(--sys-blue-accent) !important;
            box-shadow: 0 0 0 1px var(--sys-blue-accent);
        }

        /* ── TOGGLE SENHA ── */
        .btn-eye {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: rgba(255,255,255,0.3);
            cursor: pointer; padding: 4px; transition: color 0.2s; font-size: 15px;
            line-height: 1;
        }
        .btn-eye:hover { color: rgba(255,255,255,0.75); }

        /* ── DIVISOR ── */
        .divider {
            display: flex; align-items: center; gap: 12px;
            margin: 1.5rem 0; color: rgba(255,255,255,0.2); font-size: 0.75rem;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px;
            background: rgba(255,255,255,0.1);
        }

        /* ── BOTÃO GOOGLE ── */
        .btn-google {
            width: 100%; padding: 13px 18px;
            background: rgba(255,255,255,0.06);
            border: 1.5px solid rgba(255,255,255,0.14);
            border-radius: 12px; color: #fff; font-size: 0.9rem; font-weight: 600;
            cursor: pointer; transition: all 0.25s ease;
            display: flex; align-items: center; justify-content: center; gap: 12px;
            text-decoration: none;
        }
        .btn-google:hover {
            background: rgba(255,255,255,0.12);
            border-color: rgba(255,255,255,0.3);
            color: #fff; transform: translateY(-1px);
        }
        .google-logo { width: 18px; height: 18px; }

        /* ── SUBMIT ── */
        .btn-suite {
            background: var(--sys-blue-accent); color: #fff;
            border: none; padding: 15px; border-radius: 12px;
            font-weight: 700; font-size: 0.9rem; text-transform: uppercase;
            letter-spacing: 1.5px; transition: all 0.3s ease;
            width: 100%; margin-top: 0.25rem;
            box-shadow: 0 4px 15px rgba(0,112,242,0.3);
        }
        .btn-suite:hover { background: #005DC9; transform: translateY(-2px); }
        .btn-suite:disabled { opacity: 0.6; transform: none; cursor: not-allowed; }

        /* ── LINK ESQUECI SENHA ── */
        .forgot-link {
            color: rgba(255,255,255,0.4); font-size: 0.72rem; text-decoration: none;
            font-weight: 600; transition: color 0.2s;
        }
        .forgot-link:hover { color: var(--sys-blue-accent); }

        /* ── FOOTER ── */
        .auth-footer { margin-top: 2rem; text-align: center; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 1.25rem; }
        .auth-footer a { color: rgba(255,255,255,0.3); text-decoration: none; font-size: 0.75rem; font-weight: 600; transition: color 0.2s; }
        .auth-footer a:hover { color: #fff; }

        /* ── MOBILE (TEMA CLARO E MINIMALISTA) ── */
        @media (max-width: 991px) {
            .left-pane { display: none !important; }
            .right-pane { 
                width: 100%; min-height: 100vh; height: auto; 
                padding: 2rem 1.5rem; overflow-y: auto !important; 
                background-color: #ffffff !important; /* Tela branca no mobile */
            }
            .auth-container { max-width: 100%; }
            html, body { overflow: auto !important; height: auto !important; background-color: #ffffff !important; }
            
            /* Ajuste de Textos para Modo Claro */
            .auth-container h2 { color: var(--sys-navy) !important; font-size: 1.5rem; }
            .auth-container > p { color: var(--sys-text-muted) !important; font-size: 0.85rem; }
            .form-label-suite { color: var(--sys-navy-light) !important; }
            .forgot-link { color: var(--sys-blue-accent) !important; }
            
            /* Ajuste de Inputs para Modo Claro */
            .input-suite { 
                background: #f8f9fa !important;
                border: 1px solid #e2e8f0 !important;
                color: var(--sys-navy) !important;
                border-radius: 8px !important;
            }
            .input-suite::placeholder { color: #adb5bd !important; }
            .input-suite:focus {
                background: #ffffff !important;
                border-color: var(--sys-blue-accent) !important;
                box-shadow: 0 0 0 3px rgba(0,112,242,0.1) !important;
            }
            .btn-eye { color: #6c757d !important; }
            
            /* Botão Principal Minimalista */
            .btn-suite { border-radius: 8px !important; padding: 12px !important; }

            /* Divisor Claro */
            .divider { color: var(--sys-text-muted) !important; }
            .divider::before, .divider::after { background: #e2e8f0 !important; }

            /* Botão Google Minimalista */
            .btn-google {
                background: #ffffff !important;
                border: 1px solid #e2e8f0 !important;
                color: var(--sys-navy) !important;
                border-radius: 8px !important;
                padding: 10px 15px !important;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
                font-weight: 500 !important;
            }
            .btn-google:hover {
                background: #f8f9fa !important;
                border-color: #cbd5e1 !important;
            }

            /* Footer Claro */
            .auth-footer { border-top: 1px solid #e2e8f0 !important; }
            .auth-footer a { color: var(--sys-text-muted) !important; }
            .auth-footer p { color: #cbd5e1 !important; }
        }
        @media (max-height: 700px) {
            .auth-container h2 { font-size: 1.4rem; }
            .auth-container > p { margin-bottom: 1.25rem; }
            .form-group-suite { margin-bottom: 1rem; }
            .divider { margin: 1rem 0; }
        }
    </style>
</head>
<body>

    <div class="split-screen g-0">
        <!-- ── LEFT PANE ── -->
        <div class="left-pane">
            <div class="brand-header">
                <img src="/assets/img/pureza.png" alt="Brasallis Hub" class="big-logo">
            </div>
            <h1 class="comm-title">Gestão Corporativa de<br>Alta Performance.</h1>
            <div class="comm-features">
                <div class="feature-line"><i class="fas fa-check-circle"></i> Governança de Dados Centralizada</div>
                <div class="feature-line"><i class="fas fa-check-circle"></i> Analytics em Tempo Real</div>
                <div class="feature-line"><i class="fas fa-check-circle"></i> Automação Fiscal Inteligente</div>
            </div>
            <div class="mt-auto pt-5 opacity-40">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-server small"></i>
                    <small class="fw-bold text-uppercase">System Status: Operational</small>
                </div>
            </div>
        </div>

        <!-- ── RIGHT PANE ── -->
        <div class="right-pane">
            <div class="auth-container">

                <!-- Mobile branding -->
                <div class="mobile-brand-logo d-lg-none">
                    <img src="/assets/img/pureza.png" alt="Brasallis" height="40">
                </div>

                <h2>Acesso ao Shell</h2>
                <p>Insira suas credenciais para autenticação.</p>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger border-0 bg-danger bg-opacity-20 text-white rounded-3 p-3 mb-4 d-flex align-items-center">
                        <i class="fas fa-shield-exclamation me-3 fs-5"></i>
                        <span class="small fw-bold"><?= htmlspecialchars($error_message) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_GET['success'])): ?>
                    <div class="alert alert-success border-0 bg-success bg-opacity-20 text-white rounded-3 p-3 mb-4 d-flex align-items-center">
                        <i class="fas fa-check-circle me-3 fs-5"></i>
                        <span class="small fw-bold"><?= htmlspecialchars($_GET['success']) ?></span>
                    </div>
                <?php endif; ?>

                <!-- ── FORMULÁRIO DE LOGIN ── -->
                <form action="/login.php" method="POST" id="loginForm">

                    <div class="form-group-suite">
                        <label for="email" class="form-label-suite">E-mail</label>
                        <div class="input-wrapper">
                            <input type="email" class="input-suite" id="email" name="email"
                                   placeholder="usuario@dominio.com" required autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group-suite">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label for="password" class="form-label-suite mb-0">Senha</label>
                            <a href="/esqueceu_senha.php" class="forgot-link">
                                <i class="fas fa-key me-1" style="font-size:0.65rem;"></i>Esqueci minha senha
                            </a>
                        </div>
                        <div class="input-wrapper">
                            <input type="password" class="input-suite" id="password" name="password"
                                   placeholder="••••••••" required autocomplete="current-password">
                            <button type="button" class="btn-eye" id="togglePassword" title="Mostrar/ocultar senha">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-suite" id="submitBtn">
                        <i class="fas fa-lock-open me-2"></i>Iniciar Sessão Segura
                    </button>
                </form>

                <!-- ── DIVISOR ── -->
                <div class="divider">ou continue com</div>

                <!-- ── GOOGLE OAUTH ── -->
                <a href="/auth/google.php" class="btn-google" id="btnGoogle">
                    <svg class="google-logo" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Entrar com Google
                </a>

                <!-- ── FOOTER ── -->
                <div class="auth-footer">
                    <div class="d-flex justify-content-center gap-4">
                        <a href="/register.php">Criar Conta</a>
                        <a href="/" class="opacity-50">Portal Público</a>
                    </div>
                    <p class="mt-2 small opacity-20" style="color:#fff; font-size:0.65rem;">
                        &copy; <?= date('Y') ?> Brasallis Enterprise Hub
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ── Toggle visualizar senha
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        toggleBtn.addEventListener('click', () => {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            eyeIcon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
            toggleBtn.title = isHidden ? 'Ocultar senha' : 'Mostrar senha';
        });

        // ── Loading state no submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Autenticando...';
        });
    </script>
</body>
</html>
