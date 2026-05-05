<?php

namespace App\Modules\Auth\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Modules\Auth\Services\AuthService;

/**
 * AuthController — trata requisições HTTP de autenticação.
 * Toda lógica de negócio está em AuthService.
 */
class AuthController
{
    public function __construct(private AuthService $authService) {}

    /** GET /auth/login — exibe a tela de login. */
    public function showLogin(Request $request, Response $response): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Se já logado, redireciona
        if (!empty($_SESSION['user_id'])) {
            $this->redirectLoggedUser($response);
        }

        $error = $_SESSION['auth_error'] ?? '';
        unset($_SESSION['auth_error']);

        $response->view('auth/login', compact('error'));
    }

    /** POST /auth/login — processa o formulário de login. */
    public function login(Request $request, Response $response): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $email    = $request->input('email', '');
        $password = $request->input('password', '');

        try {
            $user = $this->authService->attempt($email, $password);
            $this->authService->startSession($user);
            $redirect = $this->authService->getRedirectAfterLogin($user);
            $response->redirect($redirect);
        } catch (\RuntimeException $e) {
            $_SESSION['auth_error'] = $e->getMessage();
            $response->redirect('/auth/login');
        }
    }

    /** GET /auth/logout — encerra a sessão. */
    public function logout(Request $request, Response $response): void
    {
        $this->authService->logout();
        $response->redirect('/auth/login');
    }

    /** GET /auth/register — exibe o formulário de registro. */
    public function showRegister(Request $request, Response $response): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $validPlans = ['free', 'growth', 'enterprise'];
        $plan = $request->query('plan', 'free');
        if (!in_array($plan, $validPlans)) $plan = 'free';

        $error    = $_SESSION['auth_error'] ?? '';
        $formData = $_SESSION['form_data']  ?? [];
        unset($_SESSION['auth_error'], $_SESSION['form_data']);

        $response->view('auth/register', compact('plan', 'error', 'formData'));
    }

    /** POST /auth/register — processa o cadastro de nova empresa/usuário. */
    public function register(Request $request, Response $response): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = $request->all();

        try {
            $result   = $this->authService->register($data);
            $userId   = $result['user_id'];
            $empresaId = $result['empresa_id'];
            $plan     = $result['plan'];

            // Auto-login
            $user = [
                'id'         => $userId,
                'username'   => $data['username'],
                'user_type'  => 'admin',
                'empresa_id' => $empresaId,
                'plan'       => 'free',
                'password'   => '', // não usado no startSession
            ];
            $this->authService->startSession($user);

            if (in_array($plan, ['growth', 'enterprise'])) {
                $_SESSION['message']      = 'Conta criada! Complete sua assinatura.';
                $_SESSION['message_type'] = 'info';
                $response->redirect('/admin/checkout?plan=' . $plan);
            } else {
                $_SESSION['message']      = 'Sua conta foi criada com sucesso!';
                $_SESSION['message_type'] = 'success';
                $response->redirect('/admin/dashboard');
            }
        } catch (\RuntimeException $e) {
            $_SESSION['auth_error'] = $e->getMessage();
            $_SESSION['form_data']  = $data;
            $response->redirect('/auth/register?plan=' . ($data['plan'] ?? 'free'));
        }
    }

    /** GET /auth/forgot-password */
    public function showForgotPassword(Request $request, Response $response): void
    {
        $error   = '';
        $success = '';
        if (session_status() === PHP_SESSION_NONE) session_start();
        $error   = $_SESSION['auth_error']   ?? '';
        $success = $_SESSION['auth_success'] ?? '';
        unset($_SESSION['auth_error'], $_SESSION['auth_success']);
        $response->view('auth/forgot-password', compact('error', 'success'));
    }

    /** POST /auth/forgot-password */
    public function forgotPassword(Request $request, Response $response): void
    {
        // Mantém funcionamento existente via redirect para arquivo legado
        // Será migrado completamente na Fase 2.
        $response->redirect('/enviar_link_redefinicao.php');
    }

    /** GET /auth/reset-password */
    public function showResetPassword(Request $request, Response $response): void
    {
        $response->redirect('/redefinir_senha.php?token=' . $request->query('token', ''));
    }

    /** POST /auth/reset-password */
    public function resetPassword(Request $request, Response $response): void
    {
        $response->redirect('/redefinir_senha.php');
    }

    /** GET /auth/verify-code */
    public function showVerifyCode(Request $request, Response $response): void
    {
        $response->redirect('/verificar_codigo.php');
    }

    /** POST /auth/verify-code */
    public function verifyCode(Request $request, Response $response): void
    {
        $response->redirect('/verificar_codigo.php');
    }

    private function redirectLoggedUser(Response $response): void
    {
        $type = $_SESSION['user_type'] ?? 'funcionario';
        match ($type) {
            'super_admin' => $response->redirect('/superadmin'),
            'admin'       => $response->redirect('/admin/dashboard'),
            default       => $response->redirect('/admin/dashboard'),
        };
    }
}
