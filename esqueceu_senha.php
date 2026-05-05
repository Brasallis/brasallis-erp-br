<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Acesso — Brasallis Hub</title>
    <link rel="icon" type="image/png" href="/assets/img/pureza.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --sys-navy: #001E3C;
            --sys-navy-light: #0A2647;
            --sys-blue-accent: #0070F2;
        }
        html, body {
            margin: 0; padding: 0;
            min-height: 100vh;
            background: var(--sys-navy);
            font-family: 'Inter', sans-serif;
            display: flex; align-items: center; justify-content: center;
        }
        .recovery-card {
            width: 100%; max-width: 420px;
            padding: 2.5rem;
            margin: 2rem auto;
        }
        .recovery-card img { height: 38px; margin-bottom: 2rem; }
        .recovery-card h2 { color: #fff; font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 700; margin-bottom: 0.4rem; }
        .recovery-card > p { color: rgba(255,255,255,0.4); font-size: 0.9rem; margin-bottom: 2rem; line-height: 1.5; }

        .form-label-suite {
            color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 0.6rem; display: block;
        }
        .input-suite {
            background: rgba(255,255,255,0.04) !important;
            border: 1.5px solid rgba(255,255,255,0.12) !important;
            border-radius: 12px; padding: 15px 18px;
            color: #fff !important; font-size: 1rem; width: 100%; outline: none;
            transition: all 0.25s ease;
        }
        .input-suite::placeholder { color: rgba(255,255,255,0.15); }
        .input-suite:focus {
            background: rgba(255,255,255,0.08) !important;
            border-color: var(--sys-blue-accent) !important;
            box-shadow: 0 0 0 1px var(--sys-blue-accent);
        }
        .btn-suite {
            background: var(--sys-blue-accent); color: #fff; border: none;
            padding: 15px; border-radius: 12px; font-weight: 700;
            font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1.5px;
            width: 100%; margin-top: 1rem; transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,112,242,0.3);
        }
        .btn-suite:hover { background: #005DC9; transform: translateY(-2px); }
        .btn-suite:disabled { opacity: 0.6; transform: none; }

        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: rgba(255,255,255,0.35); font-size: 0.8rem; text-decoration: none;
            margin-top: 1.5rem; transition: color 0.2s;
        }
        .back-link:hover { color: #fff; }

        .alert-glass {
            border-radius: 12px; border: none; font-size: 0.85rem; font-weight: 500;
            display: flex; align-items: flex-start; gap: 10px; padding: 14px 16px;
            margin-bottom: 1.5rem;
        }
        .alert-glass.success { background: rgba(30,142,62,0.2); color: #86efac; }
        .alert-glass.error   { background: rgba(217,48,37,0.2); color: #fca5a5; }
    </style>
</head>
<body>
    <div class="recovery-card">
        <img src="/assets/img/pureza.png" alt="Brasallis Hub">

        <h2>Recuperar Acesso</h2>
        <p>Informe o e-mail da sua conta. Enviaremos um link seguro para criar uma nova senha.</p>

        <?php if (!empty($_GET['success'])): ?>
            <div class="alert-glass success">
                <i class="fas fa-check-circle mt-1"></i>
                <span><?= htmlspecialchars($_GET['success']) ?></span>
            </div>
        <?php elseif (!empty($_GET['error'])): ?>
            <div class="alert-glass error">
                <i class="fas fa-exclamation-circle mt-1"></i>
                <span><?= htmlspecialchars($_GET['error']) ?></span>
            </div>
        <?php endif; ?>

        <?php if (empty($_GET['success'])): ?>
        <form action="/enviar_link_redefinicao.php" method="POST" id="resetForm">
            <div class="mb-4">
                <label for="email" class="form-label-suite">E-mail cadastrado</label>
                <input type="email" class="input-suite" id="email" name="email"
                       placeholder="seu@email.com" required autocomplete="email"
                       value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
            </div>
            <button type="submit" class="btn-suite" id="submitBtn">
                <i class="fas fa-paper-plane me-2"></i>Enviar Link de Redefinição
            </button>
        </form>
        <?php endif; ?>

        <a href="/login.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Voltar ao login
        </a>
    </div>

    <script>
        const form = document.getElementById('resetForm');
        if (form) {
            form.addEventListener('submit', () => {
                const btn = document.getElementById('submitBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
            });
        }
    </script>
</body>
</html>
