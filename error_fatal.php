<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ops! Algo deu errado - Brasallis Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0A2647;
            --accent: #2C7865;
        }
        body {
            background: #f8fafc;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            color: #334155;
        }
        .error-card {
            background: white;
            padding: 3rem;
            border-radius: 2rem;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
            text-align: center;
            max-width: 500px;
            width: 90%;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .icon-box {
            width: 100px;
            height: 100px;
            background: #fff1f2;
            color: #e11d48;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 3rem;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(225, 29, 72, 0.4); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(225, 29, 72, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(225, 29, 72, 0); }
        }
        h1 { font-weight: 800; color: var(--primary); margin-bottom: 1rem; }
        p { color: #64748b; line-height: 1.6; margin-bottom: 2rem; }
        .btn-home {
            background: var(--primary);
            color: white;
            border-radius: 1rem;
            padding: 0.8rem 2rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }
        .btn-home:hover {
            background: #144272;
            transform: translateY(-2px);
            color: white;
        }
        .support-info {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #f1f5f9;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

    <div class="error-card">
        <div class="icon-box">
            <i class="fas fa-shield-virus"></i>
        </div>
        <h1>Ajustando os Motores</h1>
        <p>Ocorreu um imprevisto técnico no servidor. Fique tranquilo! Nossa equipe de engenharia já recebeu o log e está trabalhando na resolução agora mesmo.</p>
        
        <a href="index.php" class="btn-home">
            <i class="fas fa-arrow-left me-2"></i>Voltar para o Início
        </a>

        <div class="support-info">
            <span class="text-muted">Protocolo de Incidente registrado no Super Admin.</span>
        </div>
    </div>

</body>
</html>
