<?php

namespace App\Core\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Middleware para Administradores.
 * Bloqueia se o tipo de usuário não for 'admin'.
 */
class AdminMiddleware
{
    public function handle(Request $request, Response $response, Closure $next): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            $response->redirect('/auth/login');
            return;
        }

        if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'super_admin') {
            // Se for funcionário tentando entrar no admin, manda para o dashboard dele
            $response->redirect('/admin/dashboard_funcionario.php'); 
            return;
        }

        $next();
    }
}
