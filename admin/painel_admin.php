<?php

// Bootstrap da aplicação
$container = require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\AdminController;
use App\Repository\DashboardRepository;

// Verifica se o usuário está logado (redundância, mas útil no legacy)
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

if (!isset($_SESSION['empresa_id']) && !isset($_SESSION['user_type'])) {
    header('Location: ../login.php');
    exit;
}

// Injeção de dependência simples
try {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'super_admin') {
        $controller = new \App\Controllers\SuperAdminController();
        $controller->index();
        exit;
    }
    $dashboardRepo = resolve(DashboardRepository::class);
} catch (Exception $e) {
    $dashboardRepo = null; // Controller tentará o fallback
}

// Instancia e executa o Controller comum para Admins de Empresa
$adminController = new AdminController($dashboardRepo);
$adminController->index();
