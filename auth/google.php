<?php
/**
 * BRASALLIS HUB — Google OAuth Entry Point
 * 
 * Para ativar o login com Google:
 * 1. Crie um projeto em https://console.cloud.google.com
 * 2. Ative a API "Google Identity" e crie credenciais OAuth 2.0
 * 3. Adicione ao .env:
 *      GOOGLE_CLIENT_ID=seu_client_id.apps.googleusercontent.com
 *      GOOGLE_CLIENT_SECRET=seu_client_secret
 *      GOOGLE_REDIRECT_URI=https://seusite.com/auth/google-callback.php
 * 4. Instale: composer require league/oauth2-google
 */

require_once __DIR__ . '/../bootstrap.php';

$client_id     = $_ENV['GOOGLE_CLIENT_ID']     ?? getenv('GOOGLE_CLIENT_ID')     ?? '';
$client_secret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET') ?? '';
$redirect_uri  = $_ENV['GOOGLE_REDIRECT_URI']  ?? getenv('GOOGLE_REDIRECT_URI')  ?? '';

// ── Verificar se as credenciais estão configuradas ──────────────
if (empty($client_id) || empty($client_secret)) {
    // Em dev: redirecionar para login com aviso
    header('Location: /login.php?error=Login+com+Google+ainda+não+configurado.+Contate+o+administrador.');
    exit();
}

// ── Montar URL de autorização do Google ─────────────────────────
$state = bin2hex(random_bytes(16)); // CSRF protection
$_SESSION['oauth_state'] = $state;

$params = http_build_query([
    'client_id'     => $client_id,
    'redirect_uri'  => $redirect_uri,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'access_type'   => 'online',
    'state'         => $state,
    'prompt'        => 'select_account',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit();
