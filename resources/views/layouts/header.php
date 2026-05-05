<?php
/**
 * Layout: Header
 */
if (session_status() === PHP_SESSION_NONE) session_start();

$username = $_SESSION['username'] ?? 'Usuário';
$userInitials = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Brasallis ERP' ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="/assets/img/pureza.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --navy: #0A2647;
            --emerald: #2C7865;
            --emerald-light: #399D85;
            --surface: #F8FAFC;
            --sidebar-width: 260px;
            --topbar-height: 70px;
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--surface); 
            color: #1E293B;
            min-height: 100vh;
            display: flex;
        }
        
        h1, h2, h3, h4, h5, .brand-text { font-family: 'Outfit', sans-serif; }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: #fff;
            border-right: 1px solid #E2E8F0;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            border-bottom: 1px solid #F1F5F9;
        }
        
        .brand-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--navy);
            margin-left: 0.75rem;
        }

        .sidebar-content {
            flex: 1;
            padding: 1.5rem 0.75rem;
            overflow-y: auto;
        }

        .nav-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94A3B8;
            padding: 0 1rem;
            margin-bottom: 0.75rem;
            margin-top: 1.5rem;
        }
        
        .nav-link-custom {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            color: #64748B;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
        }
        
        .nav-link-custom i {
            width: 20px;
            margin-right: 0.75rem;
            font-size: 1.1rem;
            text-align: center;
        }
        
        .nav-link-custom:hover {
            background: #F1F5F9;
            color: var(--navy);
        }
        
        .nav-link-custom.active {
            background: rgba(10, 38, 71, 0.05);
            color: var(--navy);
            font-weight: 600;
        }
        
        .nav-link-custom.active i {
            color: var(--navy);
        }

        /* Main Content */
        .main-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .topbar {
            height: var(--topbar-height);
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .page-content {
            padding: 2rem;
            flex: 1;
        }

        /* Profile Dropdown */
        .user-avatar {
            width: 38px;
            height: 38px;
            background: var(--navy);
            color: #fff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            cursor: pointer;
        }

        /* Cards */
        .card-premium {
            background: #fff;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            overflow: hidden;
        }
        
        .card-header-premium {
            padding: 1.25rem 1.5rem;
            background: #fff;
            border-bottom: 1px solid #F1F5F9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .table > :not(caption) > * > * {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #F1F5F9;
        }
        
        .btn-premium {
            border-radius: 10px;
            padding: 0.6rem 1.25rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .main-wrapper { margin-left: 0; }
            body.sidebar-open .sidebar { transform: translateX(0); }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <!-- Sidebar Header removido para unificar com a Navbar -->
        
        
        <div class="sidebar-content">
            <ul class="list-unstyled mb-0">
                <li class="nav-item mb-2">
                    <a href="/admin/dashboard" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'], 'dashboard') ? 'active' : '' ?>">
                        <i class="fas fa-home"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/estoque/produtos" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'], 'estoque/produtos') ? 'active' : '' ?>">
                        <i class="fas fa-box"></i>Produtos
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/estoque/categorias" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'], 'estoque/categorias') ? 'active' : '' ?>">
                        <i class="fas fa-tags"></i>Categorias
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/estoque/fornecedores" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'], 'estoque/fornecedores') ? 'active' : '' ?>">
                        <i class="fas fa-truck"></i>Fornecedores
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/estoque/compras" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'], 'estoque/compras') ? 'active' : '' ?>">
                        <i class="fas fa-file-import"></i>Entradas / Compras
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/pdv" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'], '/pdv') ? 'active' : '' ?>">
                        <i class="fas fa-cash-register"></i>Frente de Caixa (PDV)
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/financeiro" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'], 'financeiro') ? 'active' : '' ?>">
                        <i class="fas fa-wallet"></i>Financeiro
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/rh/usuarios" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'], 'rh/usuarios') ? 'active' : '' ?>">
                        <i class="fas fa-users-cog"></i>Equipe e RH
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/admin/configuracoes" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'], 'configuracoes') ? 'active' : '' ?>">
                        <i class="fas fa-cog"></i>Configurações
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/auth/logout" class="nav-link-custom text-danger">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-wrapper">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-link text-dark p-2 border-0 shadow-none" onclick="document.body.classList.toggle('sidebar-open')">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
                
                <a class="navbar-brand d-flex align-items-center gap-2" href="/admin/dashboard">
                    <img src="/assets/img/pureza.png" alt="Brasallis" height="32" style="border-radius: 8px;">
                    <span class="brand-text d-none d-sm-block">Brasallis</span>
                </a>
            </div>
            
            
            <div class="d-none d-md-flex align-items-center">
                <h5 class="mb-0 fw-bold"><?= $title ?? 'Dashboard' ?></h5>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <div class="user-avatar" data-bs-toggle="dropdown">
                        <?= $userInitials ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-3 rounded-4">
                        <li class="px-3 py-2">
                            <div class="fw-bold"><?= htmlspecialchars($username) ?></div>
                            <div class="small text-muted">Administrador</div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2" href="#"><i class="fas fa-user-circle me-2"></i>Perfil</a></li>
                        <li><a class="dropdown-item py-2 text-danger" href="/auth/logout"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                    </ul>
                </div>
            </div>
        </header>
        
        <main class="page-content">
            <?php if (isset($message)): ?>
                <div class="alert alert-<?= $messageType ?? 'info' ?> alert-dismissible fade show rounded-4 shadow-sm border-0 px-4 py-3" role="alert">
                    <i class="fas fa-info-circle me-2"></i> <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
