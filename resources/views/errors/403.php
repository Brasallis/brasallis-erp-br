<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso proibido – Brasallis ERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #0f172a;
            font-family: 'Inter', system-ui, sans-serif;
            color: #e2e8f0;
        }
        .error-box { text-align: center; padding: 3rem 2rem; }
        .code {
            font-size: 6rem;
            font-weight: 800;
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }
        h1 { font-size: 1.5rem; margin: 1rem 0 0.5rem; }
        p  { color: #94a3b8; margin-bottom: 2rem; }
        a  {
            display: inline-block;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s;
        }
        a:hover { opacity: 0.85; }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="code">403</div>
        <h1>Acesso proibido</h1>
        <p>Você não tem permissão para acessar esta página.</p>
        <a href="/admin/dashboard">Voltar ao início</a>
    </div>
</body>
</html>
