<?php

namespace App\Core\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Middleware de proteção CSRF (Cross-Site Request Forgery).
 * Verifica o token CSRF em requisições que alteram estado (POST, PUT, DELETE, PATCH).
 */
class CsrfMiddleware
{
    public function handle(Request $request, Response $response, Closure $next): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Métodos que requerem check de CSRF
        $unsafeMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];

        if (in_array($request->method(), $unsafeMethods)) {
            $token = $request->input('_csrf_token');
            $sessionToken = $_SESSION['_csrf_token'] ?? null;

            if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
                // Se for AJAX, retorna JSON
                if ($request->isAjax()) {
                    $response->json(['error' => 'Token CSRF inválido ou expirado.'], 403);
                }

                $response->abort(403, 'Ação não permitida (CSRF Token inválido).');
            }
        }

        // Gera um novo token se não houver um na sessão
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        $next();
    }
}
