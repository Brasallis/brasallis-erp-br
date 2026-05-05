<?php

/**
 * Bootstrap da aplicação.
 *
 * Responsável por:
 * 1. Carregar variáveis de ambiente (.env)
 * 2. Configurar constantes globais
 * 3. Registrar bindings no DI Container
 * 4. Retornar o Container para uso pelo Front Controller
 */

use App\Core\Container;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Estoque\Repositories\ProdutoRepository;
use App\Modules\Estoque\Repositories\CategoriaRepository;
use App\Modules\Estoque\Services\EstoqueService;
use App\Modules\Admin\Repositories\DashboardRepository;
use App\Modules\Admin\Repositories\OrganizacaoRepository;
use App\Modules\Admin\Controllers\DashboardController;
use App\Modules\Admin\Controllers\ConfiguracaoController;

// --- Módulos da Fase 3 (Estoque Avançado, PDV, Financeiro) ---
use App\Modules\Estoque\Repositories\FornecedorRepository;
use App\Modules\Estoque\Controllers\FornecedorController;
use App\Modules\Estoque\Repositories\CompraRepository;
use App\Modules\Estoque\Services\CompraService;
use App\Modules\Estoque\Controllers\CompraController;
use App\Modules\PDV\Repositories\VendaRepository;
use App\Modules\PDV\Services\PdvService;
use App\Modules\PDV\Controllers\PdvController;
use App\Modules\Financeiro\Repositories\FinanceiroRepository;
use App\Modules\Financeiro\Controllers\FinanceiroController;

use App\Modules\RH\Controllers\UserController;
use App\Modules\RH\Repositories\SetorRepository;
use App\Modules\RH\Repositories\CargoRepository;
use App\Modules\RH\Services\RhService;
use App\Modules\Auth\Repositories\UserRepository;

// ----------------------------------------------------------------
// Constantes globais
// ----------------------------------------------------------------
define('BASE_PATH', dirname(__DIR__));
define('APP_START', microtime(true));

// ----------------------------------------------------------------
// Autoloader do Composer
// ----------------------------------------------------------------
require_once BASE_PATH . '/vendor/autoload.php';

// ----------------------------------------------------------------
// Carregar variáveis de ambiente (.env)
// phpdotenv carrega o .env somente se existir (não quebra em produção
// onde as variáveis já estão no ambiente do servidor/container).
// ----------------------------------------------------------------
if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->safeLoad();
}

// ----------------------------------------------------------------
// Configurações da aplicação
// ----------------------------------------------------------------
$appConfig = require BASE_PATH . '/config/app.php';

// Timezone
date_default_timezone_set($appConfig['timezone'] ?? 'America/Sao_Paulo');

// Error reporting
if ($appConfig['debug']) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}



// ----------------------------------------------------------------
// DI Container
// ----------------------------------------------------------------
$container = new Container();
$container->singleton(Container::class, fn() => $container);
$container->singleton(get_class($container), fn() => $container);

// Banco de dados — singleton PDO
$container->singleton('PDO', function () {
    return \App\Core\Database::getInstance();
});


// Request / Response — nova instância por requisição
$container->bind(Request::class, fn() => new Request());
$container->bind(Response::class, fn() => new Response());

// Router
$container->singleton(Router::class, function ($c) {
    return new Router($c);
});

// --- Módulo Estoque ---

// Repositories (precisam do empresa_id da sessão)
$container->bind(\App\Modules\Estoque\Repositories\ProdutoRepository::class, function ($c) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $empresaId = $_SESSION['empresa_id'] ?? 0;
    return new \App\Modules\Estoque\Repositories\ProdutoRepository($c->make('PDO'), (int)$empresaId);
});

$container->bind(\App\Modules\Estoque\Repositories\CategoriaRepository::class, function ($c) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $empresaId = $_SESSION['empresa_id'] ?? 0;
    return new \App\Modules\Estoque\Repositories\CategoriaRepository($c->make('PDO'), (int)$empresaId);
});

// Service (auto-wiring resolverá as dependências dos repositórios)
$container->singleton(\App\Modules\Estoque\Services\EstoqueService::class, function ($c) {
    return new \App\Modules\Estoque\Services\EstoqueService(
        $c->make(\App\Modules\Estoque\Repositories\ProdutoRepository::class),
        $c->make(\App\Modules\Estoque\Repositories\CategoriaRepository::class)
    );
});

// --- Módulo Admin / Dashboard ---
$container->bind(\App\Modules\Admin\Repositories\DashboardRepository::class, function ($c) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $empresaId = $_SESSION['empresa_id'] ?? 1;
    return new \App\Modules\Admin\Repositories\DashboardRepository($c->make('PDO'), (int)$empresaId);
});

$container->bind(\App\Modules\Admin\Repositories\OrganizacaoRepository::class, function ($c) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $empId = $_SESSION['empresa_id'] ?? 1;
    return new \App\Modules\Admin\Repositories\OrganizacaoRepository($c->make('PDO'), (int)$empId);
});

// --- Módulo Estoque Avançado (Fornecedores e Compras) ---
$container->bind(\App\Modules\Estoque\Repositories\FornecedorRepository::class, function ($c) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $empId = $_SESSION['empresa_id'] ?? 1;
    return new \App\Modules\Estoque\Repositories\FornecedorRepository($c->make('PDO'), (int)$empId);
});

$container->bind(\App\Modules\Estoque\Repositories\CompraRepository::class, function ($c) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $empId = $_SESSION['empresa_id'] ?? 1;
    return new \App\Modules\Estoque\Repositories\CompraRepository($c->make('PDO'), (int)$empId);
});

$container->singleton(\App\Modules\Estoque\Services\CompraService::class, function ($c) {
    return new \App\Modules\Estoque\Services\CompraService(
        $c->make('PDO'),
        $c->make(\App\Modules\Estoque\Repositories\CompraRepository::class),
        $c->make(\App\Modules\Estoque\Repositories\ProdutoRepository::class)
    );
});

// --- Módulo PDV ---
$container->bind(\App\Modules\PDV\Repositories\VendaRepository::class, function ($c) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $empId = $_SESSION['empresa_id'] ?? 1;
    return new \App\Modules\PDV\Repositories\VendaRepository($c->make('PDO'), (int)$empId);
});

$container->singleton(\App\Modules\PDV\Services\PdvService::class, function ($c) {
    return new \App\Modules\PDV\Services\PdvService(
        $c->make('PDO'),
        $c->make(\App\Modules\PDV\Repositories\VendaRepository::class),
        $c->make(\App\Modules\Estoque\Repositories\ProdutoRepository::class)
    );
});

// --- Módulo Financeiro ---
$container->bind(\App\Modules\Financeiro\Repositories\FinanceiroRepository::class, function ($c) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $empId = $_SESSION['empresa_id'] ?? 1;
    return new \App\Modules\Financeiro\Repositories\FinanceiroRepository($c->make('PDO'), (int)$empId);
});

// --- Módulo RH ---
$container->bind(\App\Modules\RH\Repositories\SetorRepository::class, function ($c) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $empId = $_SESSION['empresa_id'] ?? 1;
    return new \App\Modules\RH\Repositories\SetorRepository($c->make('PDO'), (int)$empId);
});

$container->bind(\App\Modules\RH\Repositories\CargoRepository::class, function ($c) {
    return new \App\Modules\RH\Repositories\CargoRepository($c->make('PDO'));
});

$container->bind(\App\Modules\Auth\Repositories\UserRepository::class, function ($c) {
    return new \App\Modules\Auth\Repositories\UserRepository($c->make('PDO'));
});

$container->singleton(\App\Modules\RH\Services\RhService::class, function ($c) {

    return new \App\Modules\RH\Services\RhService(
        $c->make('PDO'),
        $c->make(\App\Modules\Auth\Repositories\UserRepository::class),
        $c->make(\App\Modules\RH\Repositories\SetorRepository::class),
        $c->make(\App\Modules\RH\Repositories\CargoRepository::class)
    );
});

// ----------------------------------------------------------------
// Compatibilidade retroativa: disponibiliza constantes DB_*
// para os arquivos legados em admin/, modules/, etc.
// Será removido gradualmente conforme a migração avançar.
// ----------------------------------------------------------------
$dbConfig = require BASE_PATH . '/config/database.php';
if (!defined('DB_HOST'))     define('DB_HOST',     $dbConfig['host']);
if (!defined('DB_NAME'))     define('DB_NAME',     $dbConfig['database']);
if (!defined('DB_USER'))     define('DB_USER',     $dbConfig['username']);
if (!defined('DB_PASS'))     define('DB_PASS',     $dbConfig['password']);

return $container;
