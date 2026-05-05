<?php
/**
 * error404.php
 * Interceptador Onisciente de Rota (Apache level).
 * Captura qualquer tentativa de acesso a arquivos inexistentes e loga no painel Super Admin.
 */

// Define o status HTTP corretamente
http_response_code(404);

// Carrega o motor do sistema
require_once __DIR__ . '/includes/funcoes.php';

$url_tentada = $_SERVER['REQUEST_URI'] ?? 'Unknown URL';
$referer = $_SERVER['HTTP_REFERER'] ?? 'Direto / Link Externo';

// Loga o erro com severidade moderada (Warning) para não poluir como erro crítico de código,
// mas garantir que o Super Admin veja.
$mensagem = "Página Não Encontrada (404): " . $url_tentada . " | Referer: " . $referer;
registrar_erro_sistema($mensagem, 'warning', 'Apache Route');

// Renderiza uma página amigável
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página Não Encontrada</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; height: 100vh; display: flex; align-items: center; justify-content: center; background-color: #f4f7fa; color: #1e293b; margin: 0; }
        .container { text-align: center; padding: 40px; background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); max-width: 500px; }
        h1 { font-size: 80px; margin: 0; color: #0a2647; letter-spacing: -2px; }
        p { color: #64748b; margin-top: 10px; line-height: 1.6; }
        .btn { display: inline-block; margin-top: 30px; padding: 12px 30px; background: #0a2647; color: white; text-decoration: none; border-radius: 50px; font-weight: bold; transition: 0.3s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(10,38,71,0.2); }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <h2>Oops! Link quebrado.</h2>
        <p>A página que você está procurando (<code><?php echo htmlspecialchars($url_tentada); ?></code>) não foi encontrada ou foi movida.</p>
        <p><strong>Fique tranquilo:</strong> Nosso suporte já foi notificado automaticamente via SaaS God Mode.</p>
        <a href="/index.php" class="btn">Voltar ao Início</a>
    </div>
</body>
</html>
