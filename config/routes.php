<?php

use App\Core\Router;
use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\TenantMiddleware;
use App\Core\Middleware\CsrfMiddleware;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Admin\Controllers\DashboardController;
use App\Modules\Estoque\Controllers\ProdutoController;
use App\Modules\Estoque\Controllers\CategoriaController;
use App\Modules\Estoque\Controllers\FornecedorController;
use App\Modules\Estoque\Controllers\CompraController;
use App\Modules\PDV\Controllers\PdvController;
use App\Modules\Financeiro\Controllers\FinanceiroController;
use App\Modules\RH\Controllers\UserController;

/**
 * Definição centralizada de todas as rotas da aplicação.
 *
 * @param Router $router
 */
return function (Router $router): void {

    // ----------------------------------------------------------------
    // Rotas públicas (sem autenticação)
    // ----------------------------------------------------------------
    $router->get('/', [AuthController::class, 'showLogin']);

    $router->group(['prefix' => '/auth'], function (Router $r) {
        $r->get('/login',    [AuthController::class, 'showLogin']);
        $r->post('/login',   [AuthController::class, 'login']);
        $r->get('/logout',   [AuthController::class, 'logout']);
        $r->get('/register', [AuthController::class, 'showRegister']);
        $r->post('/register',[AuthController::class, 'register']);

        $r->get('/forgot-password',  [AuthController::class, 'showForgotPassword']);
        $r->post('/forgot-password', [AuthController::class, 'forgotPassword']);
        $r->get('/reset-password',   [AuthController::class, 'showResetPassword']);
        $r->post('/reset-password',  [AuthController::class, 'resetPassword']);
        $r->get('/verify-code',      [AuthController::class, 'showVerifyCode']);
        $r->post('/verify-code',     [AuthController::class, 'verifyCode']);
    });

    // ----------------------------------------------------------------
    // Middlewares padrão para rotas protegidas
    // ----------------------------------------------------------------
    $protegido = [AuthMiddleware::class, TenantMiddleware::class, CsrfMiddleware::class];

    // ----------------------------------------------------------------
    // Admin / Dashboard
    // ----------------------------------------------------------------    // Admin / Dashboard / Configurações
    $router->group(['prefix' => '/admin', 'middleware' => $protegido], function (Router $r) {
        $r->get('/dashboard',      [DashboardController::class, 'index']);
        $r->get('/configuracoes',  [ConfiguracaoController::class, 'index']);
        $r->post('/configuracoes', [ConfiguracaoController::class, 'update']);
    });

    // ----------------------------------------------------------------
    // Módulo Estoque / Compras / Fornecedores
    // ----------------------------------------------------------------
    $router->group(['prefix' => '/estoque', 'middleware' => $protegido], function (Router $r) {
        // Produtos
        $r->get('/produtos',              [ProdutoController::class, 'index']);
        $r->post('/produtos',             [ProdutoController::class, 'store']);
        $r->post('/produtos/{id}/update', [ProdutoController::class, 'update']);
        $r->post('/produtos/{id}/delete', [ProdutoController::class, 'destroy']);

        // Categorias
        $r->get('/categorias',              [CategoriaController::class, 'index']);
        $r->post('/categorias',             [CategoriaController::class, 'store']);
        $r->post('/categorias/{id}/update', [CategoriaController::class, 'update']);
        $r->post('/categorias/{id}/delete', [CategoriaController::class, 'destroy']);

        // Fornecedores
        $r->get('/fornecedores',              [FornecedorController::class, 'index']);
        $r->post('/fornecedores',             [FornecedorController::class, 'store']);
        $r->post('/fornecedores/{id}/update', [FornecedorController::class, 'update']);
        $r->post('/fornecedores/{id}/delete', [FornecedorController::class, 'destroy']);

        // Compras (Entradas)
        $r->get('/compras',        [CompraController::class, 'index']);
        $r->get('/compras/create', [CompraController::class, 'create']);
        $r->post('/compras',       [CompraController::class, 'store']);
        $r->get('/compras/{id}',   [CompraController::class, 'show']);
    });

    // ----------------------------------------------------------------
    // Módulo PDV (Frente de Caixa)
    // ----------------------------------------------------------------
    $router->group(['prefix' => '/pdv', 'middleware' => $protegido], function (Router $r) {
        $r->get('/', [PdvController::class, 'index']);
        // API PDV integrada ao controller
        $r->get('/search', [PdvController::class, 'searchProducts']);
        $r->post('/sale',  [PdvController::class, 'processSale']);
    });

    // ----------------------------------------------------------------
    // Módulo Financeiro
    // ----------------------------------------------------------------
    $router->group(['prefix' => '/financeiro', 'middleware' => $protegido], function (Router $r) {
        $r->get('/', [FinanceiroController::class, 'index']);
    });

    // ----------------------------------------------------------------
    // Módulo RH (Equipe e Permissões)
    // ----------------------------------------------------------------
    $router->group(['prefix' => '/rh', 'middleware' => $protegido], function (Router $r) {
        $r->get('/usuarios',              [UserController::class, 'index']);
        $r->post('/usuarios',             [UserController::class, 'store']);
        $r->post('/usuarios/{id}/update', [UserController::class, 'update']);
        $r->post('/usuarios/{id}/delete', [UserController::class, 'destroy']);
        
        // API auxiliar para cargos
        $r->get('/usuarios/cargos', [UserController::class, 'getCargos']);
    });

    // ----------------------------------------------------------------
    // API v1 — Endpoints JSON (migração progressiva)
    // ----------------------------------------------------------------
    $router->group(['prefix' => '/api/v1', 'middleware' => $protegido], function (Router $r) {
        // Estoque
        $r->get('/estoque/produtos/{id}',    [ProdutoController::class,  'show']);
        $r->get('/estoque/categorias/{id}',  [CategoriaController::class, 'show']);
        $r->get('/estoque/fornecedores/{id}', [FornecedorController::class, 'show']);
    });

    // ----------------------------------------------------------------
    // Módulos restantes — serão migrados na Fase 3
    // ----------------------------------------------------------------
};
