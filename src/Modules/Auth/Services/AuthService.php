<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Repositories\UserRepository;
use PDO;

/**
 * AuthService — toda lógica de autenticação centralizada aqui.
 * Controllers apenas chamam métodos deste service.
 */
class AuthService
{
    public function __construct(private UserRepository $users) {}

    /**
     * Tenta autenticar o usuário.
     * Retorna array com os dados do usuário ou lança \RuntimeException.
     *
     * @throws \RuntimeException em caso de credenciais inválidas
     */
    public function attempt(string $email, string $password): array
    {
        $user = $this->users->findByEmail($this->sanitize($email));

        if (!$user || !password_verify($password, $user['password'])) {
            throw new \RuntimeException('E-mail ou senha incorretos.');
        }

        return $user;
    }

    /**
     * Inicia a sessão do usuário após login bem-sucedido.
     */
    public function startSession(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['user_type']  = $user['user_type'];
        $_SESSION['empresa_id'] = $user['empresa_id'];
        $_SESSION['user_plan']  = $user['plan'];

        // Branding da empresa
        $branding = $this->users->getBranding($user['empresa_id']);
        if ($branding) {
            $_SESSION['branding'] = $branding;
        }

        // Dados organizacionais
        $orgData = $this->users->getOrgData($user['id']);
        $_SESSION['setor_id'] = $orgData['setor_id'] ?? null;
        $_SESSION['cargo_id'] = $orgData['cargo_id'] ?? null;

        // Permissões
        if ($user['user_type'] === 'admin' || $user['user_type'] === 'super_admin') {
            $_SESSION['permissions'] = 'all';
        } else {
            $perms = [];
            if (!empty($_SESSION['cargo_id'])) {
                $perms = $this->users->getPermissionsByCargo($_SESSION['cargo_id']);
            }
            $_SESSION['permissions'] = $perms;
        }
    }

    /**
     * Encerra a sessão do usuário.
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        session_destroy();
    }

    /**
     * Determina para onde redirecionar após login.
     */
    public function getRedirectAfterLogin(array $user): string
    {
        if ($user['user_type'] === 'super_admin') {
            return '/superadmin';
        }

        if ($user['user_type'] === 'admin') {
            return '/admin/dashboard';
        }

        // Funcionário: smart redirect por módulo único
        $perms = $_SESSION['permissions'] ?? [];
        if (is_array($perms) && count($perms) === 1) {
            $slug = array_key_first($perms);
            $map  = [
                'pdv'        => '/pdv',
                'rh'         => '/rh',
                'fiscal'     => '/fiscal',
                'financeiro' => '/financeiro',
                'crm'        => '/crm',
            ];
            if (isset($map[$slug])) {
                return $map[$slug];
            }
        }

        // Fallback por setor
        if (!empty($_SESSION['setor_id'])) {
            return '/admin/setor/' . $_SESSION['setor_id'];
        }

        return '/admin/dashboard';
    }

    /**
     * Registra uma nova empresa + usuário admin.
     * Retorna array com user_id e empresa_id.
     *
     * @throws \RuntimeException
     */
    public function register(array $data): array
    {
        $companyName = $this->sanitize($data['company_name'] ?? '');
        $username    = $this->sanitize($data['username']     ?? '');
        $email       = $this->sanitize($data['email']        ?? '');
        $password    = $data['password']         ?? '';
        $confirm     = $data['confirm_password'] ?? '';
        $plan        = $data['plan']             ?? 'free';

        if (empty($companyName) || empty($username) || empty($email) || empty($password)) {
            throw new \RuntimeException('Todos os campos são obrigatórios.');
        }

        if ($password !== $confirm) {
            throw new \RuntimeException('As senhas não coincidem.');
        }

        if ($this->users->existsByEmail($email)) {
            throw new \RuntimeException('O e-mail informado já está em uso.');
        }

        // Cria empresa e usuário em transação gerenciada pelo repositório
        $empresaId = $this->users->createEmpresa($companyName);
        $hashed    = password_hash($password, PASSWORD_DEFAULT);
        $userId    = $this->users->createAdminUser($empresaId, $username, $email, $hashed);
        $this->users->updateEmpresaOwner($empresaId, $userId);

        return ['user_id' => $userId, 'empresa_id' => $empresaId, 'plan' => $plan];
    }

    private function sanitize(string $value): string
    {
        return htmlspecialchars(stripslashes(trim($value)));
    }
}
