<?php

namespace App\Core\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Middleware de autenticação.
 * Redireciona para /auth/login se não houver sessão ativa.
 */
class AuthMiddleware
{
    public function handle(Request $request, Response $response, Closure $next): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            $response->redirect('/auth/login');
        }

        $next();
    }
}
