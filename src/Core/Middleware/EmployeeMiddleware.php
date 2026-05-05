<?php

namespace App\Core\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Middleware para Funcionários.
 * Bloqueia se o tipo de usuário for 'admin' ou 'super_admin' tentando acesso indevido se houver silos.
 */
class EmployeeMiddleware
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

        // Se for admin em rota de funcionário, permitimos para suporte, 
        // mas o foco aqui é capturar o funcionário logado.
        if ($_SESSION['user_type'] !== 'employee' && $_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'super_admin') {
            $response->redirect('/auth/login');
            return;
        }

        $next();
    }
}
