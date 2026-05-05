<?php

namespace App\Core\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Middleware de tenant/empresa.
 * Garante que o empresa_id da sessão está setado, necessário para
 * consultas multi-tenant.
 */
class TenantMiddleware
{
    public function handle(Request $request, Response $response, Closure $next): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['empresa_id'])) {
            // Empresa não identificada: faz logout e redireciona
            session_destroy();
            $response->redirect('/auth/login');
        }

        // Disponibiliza o empresa_id globalmente via constante para acesso fácil
        if (!defined('CURRENT_EMPRESA_ID')) {
            define('CURRENT_EMPRESA_ID', $_SESSION['empresa_id']);
        }

        $next();
    }
}
