<?php
define('BASE_PATH', __DIR__);

require_once __DIR__ . '/vendor/autoload.php';

// Carrega variáveis de ambiente do arquivo .env
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// --- CONTROLE DE EXIBIÇÃO DE ERROS (PRODUÇÃO VS LOCAL) ---
$app_debug = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
if ($app_debug === 'false' || $app_debug === false) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    // Oculta erros da tela em produção para segurança, registrando apenas em log do servidor
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

require_once __DIR__ . '/includes/db_config.php';

// --- CONFIGURAÇÕES DE SEGURANÇA DE SESSÃO ---
if (session_status() === PHP_SESSION_NONE) {
    // Configura cookies de sessão para serem mais seguros
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);

    session_start();
}

// --- HELPER DE PREVENÇÃO DE XSS ---
/**
 * Atalho para htmlspecialchars para proteção contra XSS.
 */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Gera um campo hidden com o token CSRF.
 */
function csrf_field() {
    // Session is now started at the top of bootstrap.php
    
    // Gera se não existir (redundância com o middleware)
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return '<input type="hidden" name="_csrf_token" value="' . $_SESSION['_csrf_token'] . '">';
}

/**
 * Verifica se a MASTER_KEY foi fornecida corretamente via Authorization header.
 * Uso: Authorization: Bearer SUA_MASTER_KEY
 *
 * SEGURANÇA: Não aceitamos mais chave via $_GET para evitar que ela
 * apareça em logs de servidor, histórico do browser e headers Referer.
 */
function check_master_key() {
    $master_key = $_ENV['MASTER_KEY'] ?? getenv('MASTER_KEY');

    // Extrair token do header Authorization: Bearer <token>
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $provided_key = '';
    if (preg_match('/^Bearer\s+(\S+)$/i', $auth_header, $matches)) {
        $provided_key = $matches[1];
    }

    if (empty($master_key) || !hash_equals($master_key, $provided_key)) {
        http_response_code(403);
        die("<h1>❌ Acesso Negado</h1><p>Esta operação exige a <strong>MASTER_KEY</strong> via header HTTP:<br><code>Authorization: Bearer SUA_CHAVE</code></p>");
    }
}


use App\Repository\DashboardRepository;

// Simple Dependency Injection Container (Service Locator)
$container = [];

// Database Connection Service
$container['db'] = function() {
    try {
        $port = defined('DB_PORT') ? DB_PORT : 3306;
        $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Database Connection Error: " . $e->getMessage());
    }
};

// Repository Services
$container[DashboardRepository::class] = function($c) {
    // Session is now started at the top of bootstrap.php
    
    $empresa_id = $_SESSION['empresa_id'] ?? null;
    
    if (!$empresa_id) {
        // Handle case where there is no logged in user/company
        // For now, we might return null or throw an exception depending on usage
        return null; 
    }

    return new DashboardRepository($c['db'](), $empresa_id);
};

// Helper function to get services
function resolve($key) {
    global $container;
    if (isset($container[$key])) {
        return $container[$key]($container);
    }
    throw new Exception("Service not found: " . $key);
}

return $container;
