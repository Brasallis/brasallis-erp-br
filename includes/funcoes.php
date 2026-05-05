<?php

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/planos_config.php';

/**
 * Conecta-se ao banco de dados.
 */
function connect_db()
{
    $db_url = getenv('JAWSDB_URL');

    if ($db_url) {
        $db_parts = parse_url($db_url);
        $host = $db_parts['host'];
        $user = $db_parts['user'];
        $pass = $db_parts['pass'];
        $dbname = ltrim($db_parts['path'], '/');

        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            error_log("Erro de conexão com o banco de dados Heroku: " . $e->getMessage());
            return null;
        }
    } else {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            $conn = new PDO($dsn, DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            error_log("Erro de conexão com o banco de dados local: " . $e->getMessage());
            return null;
        }
    }
}

/**
 * Limpa os dados de entrada do usuário para evitar ataques XSS.
 */
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// --- Funções de Gerenciamento de Planos ---

function get_user_plan() {
    return $_SESSION['user_plan'] ?? 'foundation';
}

function podeAcessar($funcionalidade) {
    $config = get_planos_config();
    $plano_atual = get_user_plan();

    if (!isset($config['planos'][$plano_atual])) {
        return false;
    }

    $modulos = $config['planos'][$plano_atual]['modulos'] ?? [];
    return in_array($funcionalidade, $modulos);
}

// --- Super Admin Helpers ---

function isSuperAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'super_admin';
}

function checkSuperAdmin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isSuperAdmin()) {
        header("Location: /login.php");
        exit;
    }
}

/**
 * Rastreia a atividade do usuário em tempo real.
 */
function registrar_atividade_global() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['user_id'])) {
        try {
            global $conn;
            if (!$conn) $conn = connect_db();
            if ($conn) {
                $current_page = basename($_SERVER['PHP_SELF']);
                $stmt = $conn->prepare("UPDATE usuarios SET last_active_at = NOW(), last_module = ? WHERE id = ?");
                $stmt->execute([$current_page, $_SESSION['user_id']]);
            }
        } catch (Exception $e) {
            error_log("Erro no rastreador global: " . $e->getMessage());
        }
    }
}

/**
 * Registra um erro no banco de dados para o Super Admin.
 */
function registrar_erro_sistema($message, $severity = 'error', $source = 'PHP', $stack = '') {
    try {
        $conn_log = connect_db();
        if (!$conn_log) return;

        $stmt = $conn_log->prepare("INSERT INTO system_logs (empresa_id, user_id, severity, source, message, stack_trace, url, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['empresa_id'] ?? null,
            $_SESSION['user_id'] ?? null,
            $severity,
            $source,
            mb_substr($message, 0, 1000),
            $stack,
            $_SERVER['REQUEST_URI'] ?? 'Unknown',
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
    } catch (Exception $e) {
        error_log("Falha Crítica ao Logar Erro no Banco: " . $e->getMessage());
    }
}

// Handlers Globais
function global_exception_handler($exception) {
    registrar_erro_sistema($exception->getMessage(), 'error', 'PHP Exception', $exception->getTraceAsString());
    if (ini_get('display_errors')) {
        echo "<h1>Erro Crítico</h1><p>" . $exception->getMessage() . "</p>";
    }
}

function global_error_handler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    $severity = ($errno == E_USER_ERROR || $errno == E_ERROR) ? 'error' : 'warning';
    $msg = "Error: [$errno] $errstr em $errfile na linha $errline";
    registrar_erro_sistema($msg, $severity, 'PHP Error');
    return false;
}

set_exception_handler('global_exception_handler');
set_error_handler('global_error_handler');

function checkAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    checkSubscription();
}

/**
 * --- Subscription & Billing Logic ---
 */
function checkSubscription() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isSuperAdmin()) return;
    
    // Auto-Unlock para ambiente de Desenvolvimento (Local)
    if (getenv('APP_ENV') === 'local') return;

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) return;

    $allowed_pages = [
        'subscription_expired.php', 'blocked.php', 'checkout.php', 
        'processa_pix.php', 'processa_preference.php', 'check_status.php', 
        'sucesso.php', 'suporte.php', 'sair.php', 'meu-perfil.php'
    ];

    if (in_array(basename($_SERVER['PHP_SELF']), $allowed_pages)) return;

    global $conn;
    if (!$conn) $conn = connect_db();

    $stmt = $conn->prepare("SELECT ai_plan, subscription_status, next_billing_at FROM empresas WHERE id = ?");
    $stmt->execute([$_SESSION['empresa_id']]);
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) return;

    $now = new DateTime();
    $next_billing = new DateTime($empresa['next_billing_at']);
    
    // 1. Alerta de Vencimento Próximo (7 dias antes)
    $warning_date = clone $next_billing;
    $warning_date->modify('-7 days');

    if ($now >= $warning_date && $now <= $next_billing && $empresa['subscription_status'] !== 'warning') {
        $stmtWarn = $conn->prepare("UPDATE empresas SET subscription_status = 'warning' WHERE id = ?");
        $stmtWarn->execute([$_SESSION['empresa_id']]);
        registrar_erro_sistema("Sua fatura Brasallis Hub vence em " . $next_billing->format('d/m') . ". Regularize para evitar interrupções.", 'info', 'Billing');
    }

    // 2. Lógica de Bloqueio (Grace Period de 14 dias / 2 semanas)
    $grace_period_end = clone $next_billing;
    $grace_period_end->modify('+14 days');

    if ($now > $grace_period_end || $empresa['subscription_status'] === 'blocked') {
        if ($empresa['subscription_status'] !== 'blocked') {
            $stmtBlock = $conn->prepare("UPDATE empresas SET subscription_status = 'blocked', blocked_at = NOW() WHERE id = ?");
            $stmtBlock->execute([$_SESSION['empresa_id']]);
        }

        if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
            if (basename($_SERVER['PHP_SELF']) !== 'blocked.php' && basename($_SERVER['PHP_SELF']) !== 'checkout.php') {
                header("Location: ../admin/blocked.php");
                exit;
            }
        } else {
            die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Sistema Temporariamente Indisponível</h2><p>O acesso ao Brasallis Hub está suspenso para manutenção financeira. Por favor, contate o administrador da sua empresa.</p><a href='../sair.php'>Sair do Sistema</a></div>");
        }
    }

    // 3. Status Overdue (Atrasado mas ainda no Grace Period)
    if ($now > $next_billing && $now <= $grace_period_end && $empresa['subscription_status'] !== 'overdue') {
        $stmtOverdue = $conn->prepare("UPDATE empresas SET subscription_status = 'overdue' WHERE id = ?");
        $stmtOverdue->execute([$_SESSION['empresa_id']]);
    }
}

function verificarLimiteIQ() {
    $config = get_planos_config();
    $plano = get_user_plan();
    $limite = $config['planos'][$plano]['ai_token_limit'] ?? 100000;

    global $conn;
    if (!$conn) $conn = connect_db();
    
    $stmt = $conn->prepare("SELECT iq_actions_used_month FROM empresas WHERE id = ?");
    $stmt->execute([$_SESSION['empresa_id']]);
    $uso = $stmt->fetchColumn() ?: 0;

    return $uso < $limite;
}

/**
 * --- SEGURANÇA: CSRF PROTECTION ---
 * Token unificado: usa '_csrf_token' (mesmo padrao do bootstrap.php)
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $stored = $_SESSION['_csrf_token'] ?? '';
    if (empty($stored) || !hash_equals($stored, $token)) {
        registrar_erro_sistema("Tentativa de ataque CSRF bloqueada.", 'warning', 'Security');
        http_response_code(419);
        die("Erro de segurança: Token inválido. Recarregue a página e tente novamente.");
    }
    return true;
}

/**
 * --- RBAC: Verificação de Permissões ---
 */
function check_permission($slug, $nivel_minimo = 1) {
    if (isSuperAdmin()) return true;
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') return true;

    $perms = $_SESSION['permissions'] ?? [];
    if ($perms === 'all') return true;
    if (!is_array($perms)) return false; 
    if (!isset($perms[$slug])) return false; 

    $userPerm = $perms[$slug];
    $map = ['nenhuma' => 0, 'leitura' => 1, 'escrita' => 2, 'total' => 3, 'admin' => 3];

    $userLevel = is_numeric($userPerm) ? (int)$userPerm : ($map[$userPerm] ?? 0);
    $requiredLevel = is_numeric($nivel_minimo) ? (int)$nivel_minimo : ($map[$nivel_minimo] ?? 1);

    return $userLevel >= $requiredLevel;
}

function has_permission($slug, $nivel = 1) {
    return check_permission($slug, $nivel);
}

// Configurações de Produção Automáticas
if (getenv('APP_ENV') === 'production' || getenv('APP_DEBUG') === 'false') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

registrar_atividade_global();
?>
