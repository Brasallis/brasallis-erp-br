<?php

namespace App\Core;

use Closure;
use Exception;

/**
 * Roteador front-controller. Mapeia URI + Método HTTP para Controller::action.
 * Suporta grupos de rota com prefixo e middlewares.
 */
class Router
{
    /** @var array Rotas registradas: [method, pattern, handler, middlewares] */
    private array $routes = [];

    /** Prefixo atual (quando dentro de um grupo) */
    private string $prefix = '';

    /** Middlewares do grupo atual */
    private array $groupMiddlewares = [];

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /** Registra rota GET. */
    public function get(string $uri, array|Closure $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $uri, $handler, $middlewares);
    }

    /** Registra rota POST. */
    public function post(string $uri, array|Closure $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $uri, $handler, $middlewares);
    }

    /** Registra rota para qualquer método. */
    public function any(string $uri, array|Closure $handler, array $middlewares = []): void
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->addRoute($method, $uri, $handler, $middlewares);
        }
    }

    /**
     * Agrupa rotas sob um prefixo de URI comum e middlewares compartilhados.
     */
    public function group(array $attributes, Closure $callback): void
    {
        $prevPrefix      = $this->prefix;
        $prevMiddlewares = $this->groupMiddlewares;

        $this->prefix           = $prevPrefix . ($attributes['prefix'] ?? '');
        $this->groupMiddlewares = array_merge($prevMiddlewares, $attributes['middleware'] ?? []);

        $callback($this);

        $this->prefix           = $prevPrefix;
        $this->groupMiddlewares = $prevMiddlewares;
    }

    private function addRoute(string $method, string $uri, array|Closure $handler, array $middlewares): void
    {
        $fullUri    = $this->prefix . '/' . ltrim($uri, '/');
        $fullUri    = '/' . ltrim($fullUri, '/');
        $pattern    = $this->uriToPattern($fullUri);
        $allMiddles = array_merge($this->groupMiddlewares, $middlewares);

        $this->routes[] = compact('method', 'pattern', 'handler', 'allMiddles', 'fullUri');
    }

    /**
     * Converte placeholders {id} em grupos de captura regex.
     */
    private function uriToPattern(string $uri): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $uri);
        return '#^' . rtrim($pattern, '/') . '/?$#';
    }

    /**
     * Despacha a requisição atual para o handler correto.
     *
     * @throws Exception se a rota não for encontrada
     */
    public function dispatch(Request $request, Response $response): void
    {
        $uri    = $request->uri();
        $method = $request->method();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['pattern'], $uri, $matches)) {
                continue;
            }

            // Extrai apenas os named captures como parâmetros
            $params = array_filter($matches, fn($k) => !is_numeric($k), ARRAY_FILTER_USE_KEY);

            // Executa middlewares em cadeia
            $this->runMiddlewares($route['allMiddles'], $request, $response, function () use ($route, $params, $request, $response) {
                $this->callHandler($route['handler'], $params, $request, $response);
            });

            return;
        }

        // Rota não encontrada
        $response->abort(404, "Rota não encontrada: {$method} {$uri}");
    }

    private function runMiddlewares(array $middlewares, Request $request, Response $response, Closure $next): void
    {
        if (empty($middlewares)) {
            $next();
            return;
        }

        $middleware = $this->container->make(array_shift($middlewares));

        $middleware->handle($request, $response, function () use ($middlewares, $request, $response, $next) {
            $this->runMiddlewares($middlewares, $request, $response, $next);
        });
    }

    private function callHandler(array|Closure $handler, array $params, Request $request, Response $response): void
    {
        if ($handler instanceof Closure) {
            $handler($request, $response, $params);
            return;
        }

        [$controllerClass, $method] = $handler;
        $controller = $this->container->make($controllerClass);
        $controller->$method($request, $response, $params);
    }
}
