<?php
/**
 * Shim: admin/configuracoes.php
 * Serve como ponte para o ConfiguracaoController moderno dentro da estrutura legada.
 */

// Carrega o container da aplicação
$container = require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Request;
use App\Core\Response;
use App\Modules\Admin\Controllers\ConfiguracaoController;

if (session_status() === PHP_SESSION_NONE) session_start();

// --- AUDITORIA DE SEGURANÇA 360 (v2.17) ---
$user_role = $_SESSION['user_type'] ?? '';
if ($user_role !== 'admin' && $user_role !== 'super_admin') {
    header('Location: /admin/dashboard_funcionario.php');
    exit();
}

try {
    // Resolve as dependências da requisição e do controller através do Container
    $request  = $container->make(Request::class);
    $response = $container->make(Response::class);
    $controller = $container->make(ConfiguracaoController::class);

    // Roteamento simples
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->update($request, $response);
    } else {
        $controller->index($request, $response);
    }
} catch (Throwable $e) {
    // Caso ocorra erro na resolução, exibe de forma amigável (ou log)
    error_log("Erro no Shim Configuracoes: " . $e->getMessage());
    http_response_code(500);
    echo "<h1>Erro Interno</h1><p>Não foi possível carregar o módulo de Configurações.</p>";
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<pre>{$e}</pre>";
    }
}
