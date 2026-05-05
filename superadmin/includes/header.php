<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>God Mode | Gestor Inteligente</title>
    <link rel="icon" type="image/png" href="/assets/img/pureza.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --sidebar-width: 260px;
            --google-blue: #1a73e8;
            --google-grey: #5f6368;
            --google-bg: #f8f9fa;
            --surface: #ffffff;
            --border-color: #e0e0e0;
        }
        
        body { 
            background-color: var(--google-bg); 
            font-family: 'Outfit', sans-serif; 
            color: #202124;
            letter-spacing: -0.2px;
        }

        .mobile-top-bar { display: none; }
        .overlay { 
            display: none; 
            position: fixed; 
            top: 0; left: 0; right: 0; bottom: 0; 
            background: rgba(0,0,0,0.5); 
            z-index: 1040; 
        }
        .overlay.active { display: block; }

        /* Sidebar - Google Style */
        .sidebar { 
            width: var(--sidebar-width); 
            height: 100vh; 
            position: fixed; 
            background: #ffffff; 
            padding: 20px; 
            border-right: 1px solid var(--border-color);
            z-index: 1000;
        }

        .brand-box {
            padding: 10px 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-logo {
            width: 32px; height: 32px;
            background: var(--google-blue);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.2rem;
        }
        .brand-text { font-weight: 700; font-size: 1.1rem; color: #3c4043; }

        .nav-link { 
            color: var(--google-grey); 
            padding: 12px 18px; 
            border-radius: 25px; 
            margin-bottom: 4px; 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        
        .nav-link:hover { background: #f8f9fa; color: var(--google-blue); transform: translateX(5px); }
        .nav-link.active { 
            background: #e8f0fe; 
            color: var(--google-blue); 
            font-weight: 600;
            box-shadow: inset 4px 0 0 var(--google-blue);
            border-radius: 0 25px 25px 0;
            margin-left: -20px;
            padding-left: 38px;
        }

        /* Content Area */
        .main-content { 
            margin-left: var(--sidebar-width); 
            padding: 40px 60px; 
        }

        /* Premium Google Cards */
        .google-card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .google-card:hover { 
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-color: #d0d0d0;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #3c4043;
            margin-bottom: 25px;
        }

        .status-pill {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #aaa; }

        /* Mobile Responsive */
        @media (max-width: 991px) {
            .sidebar { 
                transform: translateX(-100%); 
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: none;
                z-index: 1050; /* Highest z-index */
            }
            .sidebar.active { 
                transform: translateX(0); 
                box-shadow: 10px 0 30px rgba(0,0,0,0.5);
            }
            
            .main-content { 
                margin-left: 0; 
                padding: 20px; 
                padding-top: 90px; /* Space for mobile header */
            }

            /* Premium Mobile Top Bar */
            .mobile-top-bar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 70px;
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(10px);
                border-bottom: 1px solid rgba(0,0,0,0.05);
                padding: 0 24px;
                z-index: 1030; /* Below overlay */
                box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05);
            }

            .mobile-logo {
                font-weight: 800;
                font-size: 1.2rem;
                color: #1e293b;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .sidebar-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
                border: none;
                background: transparent;
                color: #1e293b;
                font-size: 1.5rem;
                padding: 5px;
                cursor: pointer;
            }

            /* Adjust Cards for Mobile */
            .stat-card { padding: 20px; }
            .stat-card .d-flex { flex-direction: column; }
            .stat-card .stat-icon-box { margin-bottom: 15px; margin-top: 5px; width: 40px; height: 40px; font-size: 1rem; }
            .big-number { font-size: 1.75rem; }
            
            .d-flex.justify-content-between.align-items-center.mb-5 {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 15px;
                margin-bottom: 30px !important;
            }
            .d-flex.justify-content-between.align-items-center.mb-5 button {
                width: 100%;
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>

</head>
<body>

    <!-- Overlay for Mobile -->
    <div class="overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Mobile Top Bar -->
    <?php 
        $active_page = basename($_SERVER['PHP_SELF']); 
        if ($active_page != 'login.php'): 
    ?>
    <div class="mobile-top-bar">
        <div class="brand-box mb-0 py-0" style="margin-bottom:0 !important">
            <div class="brand-logo" style="width:24px; height:24px; font-size: 0.8rem;"><i class="fas fa-terminal"></i></div>
            <div class="brand-text" style="font-size: 0.9rem;">God Mode</div>
        </div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- Sidebar -->
    <div class="sidebar" id="mainSidebar">
        <!-- Brand Box -->
        <div class="brand-box">
            <div class="brand-logo"><i class="fas fa-terminal"></i></div>
            <div class="brand-text">Brasallis <span style="color:var(--google-blue)">Mestre</span></div>
        </div>

        <nav class="flex-grow-1">
            <?php 
                $conn_notif = \connect_db();
                $stmt_notif = $conn_notif->query("SELECT COUNT(*) FROM system_logs WHERE status = 'new'");
                $new_logs_count = $stmt_notif->fetchColumn();
            ?>
            <a href="index.php" class="nav-link <?php echo $active_page == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-grid-2"></i> Dashboard</a>
            <a href="empresas.php" class="nav-link <?php echo $active_page == 'empresas.php' ? 'active' : ''; ?>"><i class="fas fa-building"></i> Empresas</a>
            <a href="insights.php" class="nav-link <?php echo $active_page == 'insights.php' ? 'active' : ''; ?>"><i class="fas fa-analytics"></i> Insights</a>
            <a href="logs.php" class="nav-link <?php echo $active_page == 'logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-code"></i> Logs
                <?php if ($new_logs_count > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto" style="font-size: 0.65rem; padding: 0.4em 0.6em; animation: pulse 2s infinite;">
                        <?php echo $new_logs_count; ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="avisos.php" class="nav-link <?php echo $active_page == 'avisos.php' ? 'active' : ''; ?>"><i class="fas fa-bullhorn"></i> Avisos</a>
            <a href="suporte.php" class="nav-link <?php echo $active_page == 'suporte.php' ? 'active' : ''; ?>"><i class="fas fa-headset"></i> Suporte</a>
        </nav>

        <div class="mt-auto border-top pt-3">
            <a href="../sair.php" class="nav-link text-danger"><i class="fas fa-power-off"></i> Sair da Central</a>
        </div>
    </div>

    <!-- Script Inline para garantir funcionamento -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
    </script>

    <!-- Main Content Wrapper -->
    <div class="main-content">
