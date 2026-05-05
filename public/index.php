<?php

/**
 * Front Controller
 *
 * Único ponto de entrada da aplicação.
 * Todo tráfego HTTP é redirecionado pelo servidor para este arquivo.
 *
 * Apache (.htaccess na pasta public/) ou Nginx (config de location)
 * devem apontar o document root para esta pasta.
 */

// ----------------------------------------------------------------
// Bootstrap: carrega container, autoload, .env e configurações
// ----------------------------------------------------------------
$container = require dirname(__DIR__) . '/bootstrap/app.php';

// ----------------------------------------------------------------
// Registra módulos/serviços adicionais aqui se necessário
// ----------------------------------------------------------------

// ----------------------------------------------------------------
// Carrega e registra as rotas
// ----------------------------------------------------------------
use App\Core\Router;
use App\Core\Request;
use App\Core\Response;

/** @var Router $router */
$router = $container->make(Router::class);

$routeDefinitions = require dirname(__DIR__) . '/config/routes.php';
$routeDefinitions($router);

// ----------------------------------------------------------------
// Despacha a requisição
// ----------------------------------------------------------------
$request  = $container->make(Request::class);
$response = $container->make(Response::class);

try {
    $router->dispatch($request, $response);
} catch (Throwable $e) {
    $appConfig = require dirname(__DIR__) . '/config/app.php';

    if ($appConfig['debug']) {
        // Modo debug: mostra detalhes do erro
        http_response_code(500);
        echo '<pre style="background:#1e1e1e;color:#f44;padding:1rem">';
        echo '<strong>Erro: ' . htmlspecialchars($e->getMessage()) . '</strong>' . PHP_EOL;
        echo 'Arquivo: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL . PHP_EOL;
        echo htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
    } else {
        // Modo produção: erro genérico
        http_response_code(500);
        $errorView = dirname(__DIR__) . '/resources/views/errors/500.php';
        file_exists($errorView) ? require $errorView : print '<h1>Erro interno. Tente novamente.</h1>';
    }
}
