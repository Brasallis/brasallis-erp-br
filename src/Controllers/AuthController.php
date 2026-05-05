<?php

namespace App\Controllers;

use PDO;
use PDOException;

// Funções auxiliares (registrar_erro_sistema, etc.)
if (!function_exists('registrar_erro_sistema')) {
    require_once __DIR__ . '/../../includes/funcoes.php';
}

class AuthController {
    
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function login() {
        $error_message = '';
        require __DIR__ . '/../../views/login.php';
    }

    public function authenticate($data) {
        // --- RATE LIMITING: Máx. 5 tentativas por IP em 15 minutos ---
        $ip_key   = 'login_attempts_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $lock_key = 'login_locked_'   . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        if (!empty($_SESSION[$lock_key]) && time() < $_SESSION[$lock_key]) {
            $wait = ceil(($_SESSION[$lock_key] - time()) / 60);
            // Registra tentativa de acesso durante bloqueio nos logs do SuperAdmin
            registrar_erro_sistema(
                "Tentativa de login bloqueada por rate limit. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
                'warning',
                'Security'
            );
            $error_message = "Muitas tentativas. Aguarde {$wait} minuto(s) antes de tentar novamente.";
            require __DIR__ . '/../../views/login.php';
            return;
        }

        $email    = $this->sanitize_input($data['email']);
        $password = $this->sanitize_input($data['password']);
        $error_message = '';

        try {
            $stmt = $this->pdo->prepare("SELECT id, username, password, user_type, empresa_id, plan, permissions FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (password_verify($password, $user['password'])) {
                    // Start Session if not started
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['empresa_id'] = $user['empresa_id'];
                    $_SESSION['user_plan'] = $user['plan'];

                    // Regenerate session ID for security
                    session_regenerate_id(true);

                    // --- CARREGAR DADOS ESPECÍFICOS DE EMPRESA (Pular para SuperAdmin Global) ---
                    if ($user['empresa_id'] > 0) {
                        // Branding
                        $stmtBranding = $this->pdo->prepare("SELECT branding_primary_color, branding_secondary_color, branding_bg_style FROM empresas WHERE id = ?");
                        $stmtBranding->execute([$user['empresa_id']]);
                        if ($branding = $stmtBranding->fetch(PDO::FETCH_ASSOC)) {
                            $_SESSION['branding'] = $branding;
                        }

                        // Dados Organizacionais
                        $stmtOrg = $this->pdo->prepare("SELECT setor_id, cargo_id FROM usuario_setor WHERE user_id = ?");
                        $stmtOrg->execute([$user['id']]);
                        $orgData = $stmtOrg->fetch(PDO::FETCH_ASSOC);

                        $_SESSION['setor_id'] = $orgData['setor_id'] ?? null;
                        $_SESSION['cargo_id'] = $orgData['cargo_id'] ?? null;
                    } else {
                        $_SESSION['branding'] = null;
                        $_SESSION['setor_id'] = null;
                        $_SESSION['cargo_id'] = null;
                    }

                    // Carregar dados da empresa na sessão
                    $_SESSION['empresa_id'] = $user['empresa_id'];
                    $_SESSION['ai_plan'] = $user['ai_plan'] ?? 'foundation';

                    // Carregar Permissões
                    if ($user['user_type'] === 'super_admin') {
                        $_SESSION['permissions'] = 'all';
                    } elseif ($user['user_type'] === 'admin') {
                        $_SESSION['permissions'] = 'all'; 
                    } else {
                        // Novo sistema de permissões via JSON
                        $permissions = json_decode($user['permissions'] ?? '{}', true);
                        
                        // Fallback para sistema legado de cargos se o JSON estiver vazio e houver cargo
                        if (empty($permissions) && !empty($_SESSION['cargo_id'])) {
                            $stmtPerms = $this->pdo->prepare("
                                SELECT m.slug, pc.nivel_acesso
                                FROM permissoes_cargo pc
                                JOIN modulos m ON pc.modulo_id = m.id
                                WHERE pc.cargo_id = ?
                            ");
                            $stmtPerms->execute([$_SESSION['cargo_id']]);
                            while ($row = $stmtPerms->fetch(PDO::FETCH_ASSOC)) {
                                $permissions[$row['slug']] = $row['nivel_acesso'];
                            }
                        }
                        $_SESSION['permissions'] = $permissions;
                    }

                    // Redirect logic
                    if ($user['user_type'] === 'super_admin') {
                        header('Location: superadmin/index.php');
                    } elseif ($user['user_type'] === 'admin') {
                        // Verificar se já completou o Onboarding
                        $stmtOnb = $this->pdo->prepare("SELECT onboarding_completed FROM empresas WHERE id = ?");
                        $stmtOnb->execute([$user['empresa_id']]);
                        $onboarding = $stmtOnb->fetchColumn();

                        if ($onboarding == 0) {
                            header('Location: admin/onboarding.php');
                        } else {
                            header('Location: admin/painel_admin.php');
                        }
                    } else {
                        // Todos os funcionários agora passam pelo painel_admin.php para roteamento inteligente
                        header('Location: admin/painel_admin.php');
                    }
                    exit();
                } else {
                    // Incrementar contador de tentativas falhas
                    $_SESSION[$ip_key] = ($_SESSION[$ip_key] ?? 0) + 1;
                    if ($_SESSION[$ip_key] >= 5) {
                        $_SESSION[$lock_key] = time() + 900; // Bloqueio de 15 minutos
                        unset($_SESSION[$ip_key]);
                        // Registra bloqueio nos logs de segurança do SuperAdmin
                        registrar_erro_sistema(
                            "IP bloqueado por brute force no login. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " | Email tentado: {$email}",
                            'warning',
                            'Security'
                        );
                        $error_message = "Conta temporariamente bloqueada por excesso de tentativas. Tente novamente em 15 minutos.";
                    } else {
                        $remaining = 5 - $_SESSION[$ip_key];
                        $error_message = "E-mail ou senha incorretos. ({$remaining} tentativa(s) restante(s))";
                    }
                }
            } else {
                $_SESSION[$ip_key] = ($_SESSION[$ip_key] ?? 0) + 1;
                $error_message = "E-mail ou senha incorretos.";
            }

        } catch (PDOException $e) {
            $error_message = "Erro no servidor. Tente novamente mais tarde.";
            error_log("Erro de login: " . $e->getMessage());
        }

        require __DIR__ . '/../../views/login.php';
    }

    private function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    public function register() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $planos_validos = ['foundation', 'vision', 'enterprise_elite', 'enterprise'];
        $plano_selecionado = (isset($_GET['plan']) && in_array($_GET['plan'], $planos_validos)) ? $_GET['plan'] : 'foundation';
        
        $billing_validos = ['mensal', 'semestral', 'anual', 'bienal'];
        $billing_selecionado = (isset($_GET['billing']) && in_array($_GET['billing'], $billing_validos)) ? $_GET['billing'] : 'mensal';

        $error_message = $_SESSION['error_message'] ?? '';
        $form_data = $_SESSION['form_data'] ?? [];
        unset($_SESSION['error_message'], $_SESSION['form_data']);
        require __DIR__ . '/../../views/auth/register.php';
    }

    public function store($data) {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $company_name = $this->sanitize_input($data['company_name'] ?? '');
        $username = $this->sanitize_input($data['username'] ?? '');
        $email = $this->sanitize_input($data['email'] ?? '');
        $password = $this->sanitize_input($data['password'] ?? '');
        $confirm_password = $this->sanitize_input($data['confirm_password'] ?? '');
        $plano_selecionado = $data['plan'] ?? 'foundation';
        $billing_selecionado = $data['billing'] ?? 'mensal';


        $_SESSION['form_data'] = $data;

        if (empty($company_name) || empty($username) || empty($email) || empty($password)) {
            $_SESSION['error_message'] = 'Todos os campos são obrigatórios.';
            header('Location: register.php?plan=' . $plano_selecionado);
            exit();
        }

        if ($password !== $confirm_password) {
            $_SESSION['error_message'] = 'As senhas não coincidem.';
            header('Location: register.php?plan=' . $plano_selecionado);
            exit();
        }

        // Configurações iniciais com base no plano
        require_once __DIR__ . '/../../includes/planos_config.php';
        $central_config = \get_planos_config();
        
        // Normalizar slug do plano
        $plano_key = (strpos($plano_selecionado, 'enterprise') !== false) ? 'enterprise' : $plano_selecionado;
        $plan_info = $central_config['planos'][$plano_key] ?? $central_config['planos']['foundation'];

        $ai_plan_db = $plano_key; 
        $ai_token_limit = $plan_info['ai_token_limit'];
        $max_users = $plan_info['users_limit'];
        $support_level = ($plano_key === 'enterprise') ? 'dedicated' : (($plano_key === 'vision') ? 'priority' : 'community');


        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new PDOException("O e-mail informado já está em uso.", 23000);
            }

            // Insert Empresa com 1 mês de trial
            $stmt = $this->pdo->prepare("INSERT INTO empresas (name, owner_user_id, ai_plan, ai_token_limit, max_users, support_level, ai_tokens_used_month, subscription_status, next_billing_at) VALUES (?, 0, ?, ?, ?, ?, 0, 'trial', DATE_ADD(NOW(), INTERVAL 1 MONTH))");
            $stmt->execute([$company_name, $ai_plan_db, $ai_token_limit, $max_users, $support_level]);
            $empresa_id = $this->pdo->lastInsertId();


            // Insert User
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (empresa_id, username, email, password, user_type, plan) VALUES (?, ?, ?, ?, 'admin', ?)");
            $stmt->execute([$empresa_id, $username, $email, $hashed_password, $ai_plan_db]);
            $user_id = $this->pdo->lastInsertId();

            // Update Empresa owner
            $stmt = $this->pdo->prepare("UPDATE empresas SET owner_user_id = ? WHERE id = ?");
            $stmt->execute([$user_id, $empresa_id]);

            $this->pdo->commit();
            unset($_SESSION['form_data']);

            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = 'admin';
            $_SESSION['empresa_id'] = $empresa_id;
            $_SESSION['user_plan'] = $ai_plan_db;
            $_SESSION['user_billing'] = $billing_selecionado;

            if ($plano_selecionado === 'vision' || $plano_selecionado === 'enterprise_elite') {
                $_SESSION['message'] = "Conta criada! Aproveite seu 1 mês de teste grátis no plano {$plano_selecionado}.";
                $_SESSION['message_type'] = 'info';
            } else {
                $_SESSION['message'] = 'Sua conta foi criada! Bem-vindo ao Foundation Hub (1 mês grátis).';
                $_SESSION['message_type'] = 'success';
            }
            header('Location: admin/onboarding.php');
            exit();

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            
            // Logar o erro detalhado
            require_once __DIR__ . '/../../includes/funcoes.php';
            registrar_erro_sistema("Erro no registro: " . $e->getMessage(), 'error', 'AuthController', $e->getTraceAsString());
            
            $_SESSION['error_message'] = ($e->getCode() == 23000) ? 'O e-mail informado já está em uso.' : 'Erro ao criar conta: ' . $e->getMessage();
            header('Location: register.php?plan=' . $plano_selecionado);
            exit();
        }

    }
}
