<?php
/**
 * BRASALLIS HUB — Google OAuth Callback
 * Recebe o código de autorização do Google e autentica o usuário.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/funcoes.php';

// ── 1. Validar state (proteção CSRF) ──────────────────────────
$state_session = $_SESSION['oauth_state'] ?? '';
$state_get     = $_GET['state'] ?? '';

if (empty($state_session) || !hash_equals($state_session, $state_get)) {
    registrar_erro_sistema('Tentativa de OAuth com state CSRF inválido.', 'warning', 'Security');
    header('Location: /login.php?error=Sessão+inválida.+Tente+novamente.');
    exit();
}
unset($_SESSION['oauth_state']); // Invalida state após uso

// ── 2. Verificar se o Google retornou código ───────────────────
$code = $_GET['code'] ?? '';
if (empty($code)) {
    $error = $_GET['error'] ?? 'acesso_negado';
    header('Location: /login.php?error=Login+com+Google+cancelado:+' . urlencode($error));
    exit();
}

// ── 3. Trocar código por access_token ─────────────────────────
$client_id     = $_ENV['GOOGLE_CLIENT_ID']     ?? getenv('GOOGLE_CLIENT_ID');
$client_secret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET');
$redirect_uri  = $_ENV['GOOGLE_REDIRECT_URI']  ?? getenv('GOOGLE_REDIRECT_URI');

$token_response = http_post_json('https://oauth2.googleapis.com/token', [
    'code'          => $code,
    'client_id'     => $client_id,
    'client_secret' => $client_secret,
    'redirect_uri'  => $redirect_uri,
    'grant_type'    => 'authorization_code',
]);

if (!isset($token_response['access_token'])) {
    error_log('Google OAuth token error: ' . json_encode($token_response));
    header('Location: /login.php?error=Falha+na+autenticação+com+Google.+Tente+novamente.');
    exit();
}

// ── 4. Buscar dados do usuário Google ─────────────────────────
$userinfo = http_get_json(
    'https://www.googleapis.com/oauth2/v3/userinfo',
    $token_response['access_token']
);

if (empty($userinfo['email'])) {
    header('Location: /login.php?error=Não+foi+possível+obter+seu+e-mail+Google.');
    exit();
}

$google_email  = $userinfo['email'];
$google_name   = $userinfo['name'] ?? $userinfo['given_name'] ?? 'Usuário Google';
$google_sub    = $userinfo['sub']  ?? ''; // ID único do Google
$email_verified = $userinfo['email_verified'] ?? false;

if (!$email_verified) {
    header('Location: /login.php?error=Seu+e-mail+Google+não+está+verificado.');
    exit();
}

// ── 5. Localizar ou criar usuário no banco ────────────────────
$conn = connect_db();

try {
    // Busca por google_sub OU por email
    $stmt = $conn->prepare("
        SELECT id, username, user_type, empresa_id, plan, permissions
        FROM usuarios
        WHERE google_sub = :sub OR email = :email
        LIMIT 1
    ");
    $stmt->execute([':sub' => $google_sub, ':email' => $google_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // ── Usuário existente: atualizar google_sub se ainda não vinculado
        if (empty($user['google_sub'])) {
            $conn->prepare("UPDATE usuarios SET google_sub = ? WHERE id = ?")
                 ->execute([$google_sub, $user['id']]);
        }
    } else {
        // ── Novo usuário: auto-registro via Google (plano Foundation trial)
        // Cria empresa temporária e usuário
        $conn->beginTransaction();

        $stmt = $conn->prepare("
            INSERT INTO empresas (name, owner_user_id, ai_plan, ai_token_limit, max_users,
                                  support_level, subscription_status, next_billing_at)
            VALUES (:name, 0, 'foundation', 100000, 5, 'community', 'trial', DATE_ADD(NOW(), INTERVAL 1 MONTH))
        ");
        $stmt->execute([':name' => $google_name . ' (Google)']);
        $empresa_id = $conn->lastInsertId();

        $stmt = $conn->prepare("
            INSERT INTO usuarios (empresa_id, username, email, password, user_type, plan, google_sub)
            VALUES (:empresa_id, :username, :email, :password, 'admin', 'foundation', :google_sub)
        ");
        $stmt->execute([
            ':empresa_id' => $empresa_id,
            ':username'   => $google_name,
            ':email'      => $google_email,
            ':password'   => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), // Senha aleatória (não usada)
            ':google_sub' => $google_sub,
        ]);
        $user_id = $conn->lastInsertId();

        $conn->prepare("UPDATE empresas SET owner_user_id = ? WHERE id = ?")
             ->execute([$user_id, $empresa_id]);

        $conn->commit();

        // Recarregar user para a sessão
        $stmt = $conn->prepare("SELECT id, username, user_type, empresa_id, plan, permissions FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    registrar_erro_sistema('Erro no OAuth Google callback: ' . $e->getMessage(), 'error', 'AuthController', $e->getTraceAsString());
    error_log('Google OAuth DB error: ' . $e->getMessage());
    header('Location: /login.php?error=Erro+interno.+Tente+novamente.');
    exit();
}

// ── 6. Iniciar sessão autenticada ──────────────────────────────
session_regenerate_id(true);

$_SESSION['user_id']    = $user['id'];
$_SESSION['username']   = $user['username'];
$_SESSION['user_type']  = $user['user_type'];
$_SESSION['empresa_id'] = $user['empresa_id'];
$_SESSION['user_plan']  = $user['plan'];
$_SESSION['auth_via']   = 'google'; // Identifica método de login

// Permissões
if (in_array($user['user_type'], ['super_admin', 'admin'])) {
    $_SESSION['permissions'] = 'all';
} else {
    $_SESSION['permissions'] = json_decode($user['permissions'] ?? '{}', true);
}

// ── 7. Redirecionar conforme tipo de usuário ───────────────────
match ($user['user_type']) {
    'super_admin' => header('Location: /superadmin/index.php'),
    'admin'       => header('Location: /admin/painel_admin.php'),
    default       => header('Location: /admin/painel_admin.php'),
};
exit();


// ── Helpers HTTP ───────────────────────────────────────────────

function http_post_json(string $url, array $data): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response ?: '{}', true) ?? [];
}

function http_get_json(string $url, string $access_token): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $access_token"],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response ?: '{}', true) ?? [];
}
