<?php
// includes/navigation-brasallis.php v7.1 - REPAIRED & ORGANIZED
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/funcoes.php';

$user_type = $_SESSION['user_type'] ?? 'employee';
$empresa_id = $_SESSION['empresa_id'] ?? 1;
$is_admin = in_array($user_type, ['admin', 'super_admin']);
$page_active = basename($_SERVER['PHP_SELF']);

// Detectar Base URL dinamicamente respeitando proxies (Railway/Cloudflare)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') 
            ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

// Remove as pastas conhecidas do caminho para encontrar a raiz real do projeto
// O regex agora garante que estamos pegando a pasta exata (fim de string ou seguida de /)
$project_root = preg_replace('/(\/(admin|employee|modules|includes|api|assets|uploads))($|\/.*)/', '', $script_path);

$base_url = $protocol . "://" . $host . rtrim($project_root, '/') . '/';

require_once __DIR__ . '/planos_config.php';

// 1. CARREGAR DADOS EM TEMPO REAL (Garante sincronia com SuperAdmin)
$conn_nav = connect_db();
if ($conn_nav) {
    $stmt_nav = $conn_nav->prepare("SELECT active_modules, ai_plan FROM empresas WHERE id = ?");
    $stmt_nav->execute([$empresa_id]);
    $emp_data = $stmt_nav->fetch();
    $plan = $emp_data['ai_plan'] ?? 'foundation';
    $selected_modules = json_decode($emp_data['active_modules'] ?? '[]', true);
    
    // Sincronizar com a sessão para outros controladores
    $_SESSION['ai_plan'] = $plan;
} else { 
    $plan = $_SESSION['ai_plan'] ?? 'foundation'; 
    $selected_modules = []; 
}

// Obter módulos permitidos pelo plano centralizado
$allowed_by_plan = get_modules_by_plan($plan);
$company_active_modules = !empty($selected_modules) ? array_intersect($selected_modules, $allowed_by_plan) : $allowed_by_plan;
if (!in_array('relatorios', $company_active_modules)) { $company_active_modules[] = 'relatorios'; }

// 2. FILTRAR POR PERMISSÕES DO USUÁRIO
$user_permissions = $_SESSION['permissions'] ?? [];
$user_type = $_SESSION['user_type'] ?? 'employee';

global $active_modules;
$active_modules = [];

$is_admin = ($user_type === 'admin' || $user_type === 'super_admin' || $user_permissions === 'all');

foreach ($company_active_modules as $mod) {
    if ($is_admin) {
        $active_modules[] = $mod;
    } else {
        $perm = $user_permissions[$mod] ?? 0;
        if ($perm > 0 || $perm === 'total' || $perm === 'leitura') {
            $active_modules[] = $mod;
        }
    }
}

// Helper para checar permissão específica
function can_access($module) {
    global $active_modules;
    return is_array($active_modules) && in_array($module, $active_modules);
}
?>

<!-- GOOGLE-STYLE EXPANDABLE SIDEBAR -->
<aside class="brasallis-sidebar" id="mainSidebar">
    <div class="sidebar-top-branding" onclick="toggleBrasallisHub()">
        <div class="branding-logo bg-transparent text-muted"><i class="fas fa-bars"></i></div>
        <span class="branding-text">Menu</span>
    </div>

    <nav class="brasallis-nav">
        <!-- DASHBOARD (Apenas se tiver acesso ou for admin) -->
        <?php if ($is_admin): ?>
        <a href="<?php echo $base_url; ?>admin/painel_admin.php" class="brasallis-item <?= ($page_active == 'dashboard.php' || $page_active == 'painel_admin.php') ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i>
            <span class="nav-label">Dashboard</span>
        </a>
        <?php endif; ?>

        <!-- GRUPO OPERACIONAL -->
        <?php if (can_access('pdv') || can_access('estoque')): ?>
        <div class="nav-group">
            <div class="brasallis-item has-submenu" onclick="toggleSubmenu('sub-operacional')">
                <i class="fas fa-rocket"></i>
                <span class="nav-label">Operacional</span>
                <i class="fas fa-chevron-right submenu-arrow"></i>
            </div>
            <div class="brasallis-submenu" id="sub-operacional">
                <?php if (can_access('pdv')): ?>
                    <a href="<?php echo $base_url; ?>employee/pdv.php" class="submenu-item">PDV (Frente de Caixa)</a>
                    <a href="<?php echo $base_url; ?>admin/vendas.php" class="submenu-item">Relatório de Vendas</a>
                    <a href="<?php echo $base_url; ?>modules/financeiro/views/fluxo_caixa.php" class="submenu-item">Fluxo de Caixa de Vendas</a>
                <?php endif; ?>
                <?php if (can_access('estoque')): ?>
                    <a href="<?php echo $base_url; ?>admin/produtos.php" class="submenu-item">Produtos</a>
                    <a href="<?php echo $base_url; ?>admin/categorias.php" class="submenu-item">Categorias</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- GRUPO GESTÃO -->
        <?php if (can_access('financeiro') || can_access('crm') || can_access('fiscal')): ?>
        <div class="nav-group">
            <div class="brasallis-item has-submenu" onclick="toggleSubmenu('sub-gestao')">
                <i class="fas fa-briefcase"></i>
                <span class="nav-label">Gestão</span>
                <i class="fas fa-chevron-right submenu-arrow"></i>
            </div>
            <div class="brasallis-submenu" id="sub-gestao">
                <?php if (can_access('financeiro')): ?>
                    <a href="<?php echo $base_url; ?>modules/financeiro/views/index.php" class="submenu-item">Financeiro Hub</a>
                <?php endif; ?>
                <?php if (can_access('crm')): ?>
                    <a href="<?php echo $base_url; ?>modules/crm/views/kanban.php" class="submenu-item">CRM Kanban</a>
                    <a href="<?php echo $base_url; ?>modules/crm/views/clientes.php" class="submenu-item">Clientes</a>
                <?php endif; ?>
                <?php if (can_access('fiscal')): ?>
                    <a href="<?php echo $base_url; ?>admin/fiscal.php" class="submenu-item">Fiscal NF-e</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- GRUPO INTELIGÊNCIA -->
        <?php if (can_access('ai_hub') || can_access('relatorios')): ?>
        <div class="nav-group">
            <div class="brasallis-item has-submenu" onclick="toggleSubmenu('sub-ai')">
                <i class="fas fa-brain"></i>
                <span class="nav-label">Inteligência</span>
                <i class="fas fa-chevron-right submenu-arrow"></i>
            </div>
            <div class="brasallis-submenu" id="sub-ai">
                <?php if (can_access('ai_hub')): ?>
                    <a href="<?php echo $base_url; ?>admin/agentes_ia.php" class="submenu-item">Agentes IA</a>
                <?php endif; ?>
                <?php if (can_access('relatorios')): ?>
                    <a href="<?php echo $base_url; ?>admin/relatorios.php" class="submenu-item">BI & Dashboards</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- CONFIGURAÇÕES -->
        <div class="nav-group">
            <div class="brasallis-item has-submenu" onclick="toggleSubmenu('sub-config')">
                <i class="fas fa-cog"></i>
                <span class="nav-label">Configurações</span>
                <i class="fas fa-chevron-right submenu-arrow"></i>
            </div>
            <div class="brasallis-submenu" id="sub-config">
                <a href="<?php echo $base_url; ?>admin/meu-perfil.php" class="submenu-item">Meu Perfil / Senha</a>
                <a href="<?php echo $base_url; ?>admin/suporte.php" class="submenu-item">Suporte Técnico</a>
                <?php if ($is_admin): ?>
                    <a href="<?php echo $base_url; ?>admin/usuarios.php" class="submenu-item">Gestão de Equipe</a>
                    <a href="<?php echo $base_url; ?>admin/configuracoes.php" class="submenu-item">Minha Empresa</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</aside>

<script>
function toggleSubmenu(id) {
    const submenus = document.querySelectorAll('.brasallis-submenu');
    const items = document.querySelectorAll('.has-submenu');
    const target = document.getElementById(id);
    const targetItem = target.previousElementSibling;

    // Comportamento Acordeão: Fecha outros submenus antes de abrir o novo
    if (!target.classList.contains('active')) {
        submenus.forEach(s => s.classList.remove('active'));
        items.forEach(i => i.classList.remove('submenu-open'));
    }
    
    // Toggle o atual
    target.classList.toggle('active');
    targetItem.classList.toggle('submenu-open');
}

// Auto-recolhimento ao sair da sidebar
document.getElementById('mainSidebar').addEventListener('mouseleave', function() {
    const submenus = document.querySelectorAll('.brasallis-submenu');
    const items = document.querySelectorAll('.has-submenu');
    
    submenus.forEach(s => s.classList.remove('active'));
    items.forEach(i => i.classList.remove('submenu-open'));
});
</script>

<!-- ELITE TOPBAR -->
<header class="brasallis-topbar">
    <a href="<?php echo $base_url; ?>admin/painel_admin.php" class="d-flex align-items-center gap-3 text-decoration-none">
        <img src="<?php echo $base_url; ?>assets/img/pureza.png" alt="Logo" style="height: 32px; width: auto;">
        <h5 class="mb-0 fw-bold d-none d-lg-block" style="color: #0A2647; font-size: 0.95rem;">Brasallis Hub</h5>
    </a>

    <div class="brasallis-search-container d-none d-md-flex mx-auto" id="omniSearchContainer">
        <i class="fas fa-search text-muted opacity-50"></i>
        <input type="text" class="brasallis-search-input" id="omniSearchInput" placeholder="Pesquisar em toda a organização..." onfocus="document.getElementById('omniSearchContainer').classList.add('focused')" onblur="document.getElementById('omniSearchContainer').classList.remove('focused')">
    </div>

    <div class="d-flex align-items-center gap-3">
        <!-- NOTIFICATION CENTER (BELL) -->
        <div class="notification-wrapper">
            <button class="notification-bell" id="bellBtn" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notifBadge" style="display: none;">0</span>
            </button>
            
            <div class="notification-dropdown" id="notifDropdown">
                <div class="notif-header">
                    <h6 class="mb-0 fw-bold">Notificações</h6>
                    <button class="btn-mark-all" onclick="markAllAsRead()">Limpar tudo</button>
                </div>
                <div class="notif-body" id="notifList">
                    <div class="p-4 text-center text-muted small">Carregando alertas...</div>
                </div>
                <div class="notif-footer">
                    <a href="<?php echo $base_url; ?>admin/notificacoes.php">Ver todos os registros</a>
                </div>
            </div>
        </div>

        <div class="profile-wrapper">
            <div class="profile-pill" onclick="toggleProfileMenu()">
                <?= strtoupper(substr($_SESSION['user_nome'] ?? $_SESSION['username'] ?? 'A', 0, 1)) ?>
                <div class="status-indicator"></div>
            </div>
            
            <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-dropdown-header">
                    <h6 class="mb-0 fw-bold"><?= $_SESSION['user_nome'] ?? $_SESSION['username'] ?? 'Usuário' ?></h6>
                    <small class="text-muted"><?= ucfirst($_SESSION['user_type'] ?? 'membro') ?></small>
                </div>
                <div class="profile-dropdown-body">
                    <a href="<?php echo $base_url; ?>admin/meu-perfil.php"><i class="fas fa-user-circle"></i> Meu Perfil / Senha</a>
                    <a href="<?php echo $base_url; ?>admin/suporte.php"><i class="fas fa-headset"></i> Suporte Técnico</a>
                    <?php if ($is_admin): ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo $base_url; ?>admin/configuracoes.php"><i class="fas fa-cog"></i> Configurações Empresa</a>
                        <a href="<?php echo $base_url; ?>admin/usuarios.php"><i class="fas fa-users-gear"></i> Gestão de Equipe</a>
                    <?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo $base_url; ?>sair.php" class="text-danger"><i class="fas fa-power-off"></i> Sair do Sistema</a>
                </div>
            </div>
        </div>
    </div>
</header>

<?php
// Lógica para os 4 botões essenciais dinâmicos
$essential_links = [];
// Sempre prioriza Início/Dashboard para Administradores
$essential_links[] = ['url' => $base_url . 'admin/painel_admin.php', 'icon' => 'fa-house', 'label' => 'Início', 'active' => ($page_active == 'painel_admin.php' || $page_active == 'dashboard.php')];

if (can_access('pdv')) { $essential_links[] = ['url' => $base_url . 'admin/vendas.php', 'icon' => 'fa-cash-register', 'label' => 'Vendas', 'active' => ($page_active == 'vendas.php')]; }
if (can_access('estoque')) { $essential_links[] = ['url' => $base_url . 'admin/produtos.php', 'icon' => 'fa-boxes-stacked', 'label' => 'Estoque', 'active' => ($page_active == 'produtos.php')]; }
if (can_access('financeiro')) { $essential_links[] = ['url' => $base_url . 'modules/financeiro/views/index.php', 'icon' => 'fa-wallet', 'label' => 'Caixa', 'active' => (strpos($_SERVER['PHP_SELF'], 'financeiro') !== false)]; }
if (can_access('crm')) { $essential_links[] = ['url' => $base_url . 'modules/crm/views/clientes.php', 'icon' => 'fa-user-group', 'label' => 'CRM', 'active' => (strpos($_SERVER['PHP_SELF'], 'crm') !== false)]; }

$display_links = array_slice($essential_links, 0, 4);
$left_links = array_slice($display_links, 0, 2);
$right_links = array_slice($display_links, 2, 2);
?>

<!-- BOTTOM NAV (Mobile) -->
<nav class="brasallis-bottom-nav">
    <?php foreach($left_links as $link): ?>
    <a href="<?= $link['url'] ?>" class="bottom-nav-item <?= $link['active'] ? 'active' : '' ?>">
        <i class="fas <?= $link['icon'] ?>"></i><span><?= $link['label'] ?></span>
    </a>
    <?php endforeach; ?>

    <!-- MENU CENTRAL (GOOGLE STYLE) -->
    <a href="javascript:void(0)" class="bottom-nav-menu-btn" onclick="toggleBrasallisHub()">
        <div class="menu-grid-icon">
            <span></span><span></span><span></span><span></span>
        </div>
        <span class="mt-1">Menu</span>
    </a>

    <?php foreach($right_links as $link): ?>
    <a href="<?= $link['url'] ?>" class="bottom-nav-item <?= $link['active'] ? 'active' : '' ?>">
        <i class="fas <?= $link['icon'] ?>"></i><span><?= $link['label'] ?></span>
    </a>
    <?php endforeach; ?>
</nav>

<!-- APP HUB OVERLAY (GOOGLE APP DRAWER STYLE) -->
<div id="brasallisAppHub">
    <div class="hub-header">
        <div class="hub-handle"></div>
        <div class="d-flex justify-content-between align-items-center w-100 px-4 mt-3">
            <h5 class="fw-bold m-0 text-navy">Explorar Módulos</h5>
            <button class="btn-close-hub" onclick="toggleBrasallisHub()"><i class="fas fa-times"></i></button>
        </div>
    </div>
    
    <div class="hub-content">
        <div class="hub-search-bar mb-4">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Buscar funcionalidade..." onkeyup="filterHub(this.value)">
        </div>

        <div class="hub-section">
            <label>OPERACIONAL</label>
            <div class="app-drawer-grid">
                <?php if (can_access('pdv')): ?>
                <a href="<?= $base_url; ?>employee/pdv.php" class="app-item">
                    <div class="app-icon bg-success-soft text-success"><i class="fas fa-cash-register"></i></div>
                    <span>PDV</span>
                </a>
                <a href="<?= $base_url; ?>admin/vendas.php" class="app-item">
                    <div class="app-icon bg-info-soft text-info"><i class="fas fa-receipt"></i></div>
                    <span>Vendas</span>
                </a>
                <?php endif; ?>
                <?php if (can_access('estoque')): ?>
                <a href="<?= $base_url; ?>admin/produtos.php" class="app-item">
                    <div class="app-icon bg-primary-soft text-primary"><i class="fas fa-boxes-stacked"></i></div>
                    <span>Estoque</span>
                </a>
                <a href="<?= $base_url; ?>admin/categorias.php" class="app-item">
                    <div class="app-icon bg-secondary-soft text-muted"><i class="fas fa-tags"></i></div>
                    <span>Categorias</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="hub-section mt-4">
            <label>GESTÃO & FINANCEIRO</label>
            <div class="app-drawer-grid">
                <?php if (can_access('financeiro')): ?>
                <a href="<?= $base_url; ?>modules/financeiro/views/index.php" class="app-item">
                    <div class="app-icon bg-warning-soft text-warning"><i class="fas fa-wallet"></i></div>
                    <span>Financeiro</span>
                </a>
                <?php endif; ?>
                <?php if (can_access('crm')): ?>
                <a href="<?= $base_url; ?>modules/crm/views/clientes.php" class="app-item">
                    <div class="app-icon bg-purple-soft text-purple"><i class="fas fa-user-group"></i></div>
                    <span>Clientes</span>
                </a>
                <?php endif; ?>
                <?php if (can_access('fiscal')): ?>
                <a href="<?= $base_url; ?>admin/fiscal.php" class="app-item">
                    <div class="app-icon bg-danger-soft text-danger"><i class="fas fa-file-invoice"></i></div>
                    <span>Fiscal</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="hub-section mt-4">
            <label>INTELIGÊNCIA</label>
            <div class="app-drawer-grid">
                <?php if (can_access('ai_hub')): ?>
                <a href="<?= $base_url; ?>admin/agentes_ia.php" class="app-item">
                    <div class="app-icon bg-indigo-soft text-indigo shadow-sm"><i class="fas fa-brain"></i></div>
                    <span>Agentes IA</span>
                </a>
                <?php endif; ?>
                <?php if (can_access('relatorios')): ?>
                <a href="<?= $base_url; ?>admin/relatorios.php" class="app-item">
                    <div class="app-icon bg-dark-soft text-dark"><i class="fas fa-chart-simple"></i></div>
                    <span>BI</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const omniInput = document.getElementById('omniSearchInput');
    const omniContainer = document.getElementById('omniSearchContainer');
    
    // Create Results Dropdown
    const resultsDiv = document.createElement('div');
    resultsDiv.id = 'omniSearchResults';
    resultsDiv.className = 'omni-results-dropdown';
    omniContainer.appendChild(resultsDiv);

    let debounceTimer;

    if(omniInput) {
        omniInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const term = this.value.trim();
            
            if (term.length < 2) {
                resultsDiv.classList.remove('active');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`<?php echo $base_url; ?>api/omni_search.php?term=${encodeURIComponent(term)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.results && data.results.length > 0) {
                        renderResults(data.results);
                        resultsDiv.classList.add('active');
                    } else {
                        resultsDiv.innerHTML = '<div class="p-3 text-center text-muted small">Nenhum resultado encontrado.</div>';
                        resultsDiv.classList.add('active');
                    }
                });
            }, 300);
        });

        omniInput.addEventListener('keydown', function(e) {
            if(e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                const term = this.value.trim();
                if(term.length > 0) { 
                    window.location.href = '<?php echo $base_url; ?>admin/produtos.php?search=' + encodeURIComponent(term); 
                }
            }
        });

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!omniContainer.contains(e.target)) {
                resultsDiv.classList.remove('active');
            }
        });
    }

    function renderResults(results) {
        const icons = {
            'produto': 'fa-box text-primary',
            'cliente': 'fa-user text-success',
            'venda': 'fa-receipt text-warning',
            'nota': 'fa-file-invoice text-danger',
            'funcionario': 'fa-user-tie text-info',
            'modulo': 'fa-rocket text-purple',
            'config': 'fa-cog text-secondary'
        };

        resultsDiv.innerHTML = results.map(res => `
            <a href="${res.url.includes('=') ? res.url + encodeURIComponent(res.exact_term || res.title) : '<?php echo $base_url; ?>' + res.url.replace(/^\//, '')}" class="omni-result-item">
                <div class="result-icon">
                    <i class="fas ${icons[res.type] || 'fa-search'}"></i>
                </div>
                <div class="result-info">
                    <div class="result-title">${res.title}</div>
                    <div class="result-subtitle">${res.subtitle || res.type}</div>
                </div>
            </a>
        `).join('');
    }
});

function toggleBrasallisHub() {
    const hub = document.getElementById('brasallisAppHub');
    hub.classList.toggle('active');
    document.body.style.overflow = hub.classList.contains('active') ? 'hidden' : '';
}

function filterHub(term) {
    term = term.toLowerCase();
    const items = document.querySelectorAll('.app-item');
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(term) ? 'flex' : 'none';
    });
}

// ... (Rest of notifications logic) ...

// NOTIFICATION LOGIC
function toggleNotifications() {
    const dropdown = document.getElementById('notifDropdown');
    dropdown.classList.toggle('active');
    if (dropdown.classList.contains('active')) {
        loadNotifications();
    }
}

async function loadNotifications() {
    const list = document.getElementById('notifList');
    try {
        const response = await fetch('<?php echo $base_url; ?>api/get_notifications.php');
        const data = await response.json();
        
        if (data.success) {
            const badge = document.getElementById('notifBadge');
            if (data.unread_count > 0) {
                badge.innerText = data.unread_count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }

            if (data.notifications.length === 0) {
                list.innerHTML = '<div class="p-4 text-center text-muted small">Nenhuma notificação no momento.</div>';
                return;
            }

            list.innerHTML = data.notifications.map(n => `
                <div class="notif-item ${n.is_read ? 'read' : 'unread'}" onclick="markAsRead(${n.id})">
                    <div class="notif-icon ${n.type}">
                        <i class="fas ${getNotifIcon(n.type)}"></i>
                    </div>
                    <div class="notif-content">
                        <p class="mb-0">${n.message}</p>
                        <span class="notif-time">${n.time_ago}</span>
                    </div>
                </div>
            `).join('');
        } else {
            list.innerHTML = `<div class="p-4 text-center text-danger small">Erro: ${data.error}</div>`;
        }
    } catch (e) {
        list.innerHTML = '<div class="p-4 text-center text-danger small">Erro de conexão com o servidor.</div>';
    }
}

function getNotifIcon(type) {
    switch(type) {
        case 'low_stock': return 'fa-boxes-stacked';
        case 'nearing_expiration': return 'fa-hourglass-half';
        default: return 'fa-info-circle';
    }
}

async function markAsRead(id) {
    const formData = new FormData();
    formData.append('id', id);
    await fetch('<?php echo $base_url; ?>api/mark_notification_read.php', { method: 'POST', body: formData });
    loadNotifications();
}

async function markAllAsRead() {
    const formData = new FormData();
    formData.append('all', 'true');
    await fetch('<?php echo $base_url; ?>api/mark_notification_read.php', { method: 'POST', body: formData });
    loadNotifications();
}

// Auto-check notifications every 5 minutes
setInterval(loadNotifications, 300000);
loadNotifications();

function toggleProfileMenu() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('active');
    // Fechar notificação se abrir perfil
    document.getElementById('notifDropdown').classList.remove('active');
}

// Fechar dropdowns ao clicar fora
document.addEventListener('click', function(e) {
    const pWrapper = document.querySelector('.profile-wrapper');
    const nWrapper = document.querySelector('.notification-wrapper');
    const pDropdown = document.getElementById('profileDropdown');
    const nDropdown = document.getElementById('notifDropdown');

    if (pWrapper && !pWrapper.contains(e.target)) {
        pDropdown.classList.remove('active');
    }
    if (nWrapper && !nWrapper.contains(e.target)) {
        nDropdown.classList.remove('active');
    }
});
</script>

<style>
.profile-wrapper { position: relative; }
.profile-dropdown {
    position: absolute; top: 100%; right: 0; width: 220px; background: #fff;
    border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;
    margin-top: 10px; z-index: 1200; display: none; overflow: hidden;
}
.profile-dropdown.active { display: block; animation: slideDown 0.3s ease; }

.profile-dropdown-header { padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.profile-dropdown-body { padding: 8px; }
.profile-dropdown-body a {
    display: flex; align-items: center; gap: 12px; padding: 10px 15px;
    color: #475569; text-decoration: none; font-size: 0.85rem; font-weight: 500;
    border-radius: 10px; transition: all 0.2s;
}
.profile-dropdown-body a:hover { background: #f1f5f9; color: #0A2647; }
.profile-dropdown-body i { width: 16px; text-align: center; color: #94a3b8; }
.dropdown-divider { height: 1px; background: #e2e8f0; margin: 8px 0; }

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.notification-wrapper { position: relative; }
.notification-bell {
    background: none; border: none; color: #64748b; font-size: 1.25rem;
    padding: 8px; border-radius: 12px; transition: all 0.2s; position: relative;
}
.notification-bell:hover { background: #f1f5f9; color: #0A2647; }
.notification-badge {
    position: absolute; top: 4px; right: 4px; background: #ef4444; color: white;
    font-size: 0.65rem; font-weight: bold; width: 18px; height: 18px;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    border: 2px solid #fff;
}

.notification-dropdown {
    position: absolute; top: 100%; right: 0; width: 320px; background: #fff;
    border-radius: 20px; shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;
    margin-top: 10px; z-index: 1100; display: none; overflow: hidden;
}
.notification-dropdown.active { display: block; animation: slideDown 0.3s ease; }

.notif-header { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.btn-mark-all { background: none; border: none; color: #3b82f6; font-size: 0.75rem; font-weight: 600; }
.notif-body { max-height: 400px; overflow-y: auto; }
.notif-footer { padding: 12px; text-align: center; border-top: 1px solid #f1f5f9; }
.notif-footer a { font-size: 0.75rem; font-weight: 600; color: #64748b; text-decoration: none; }

.notif-item { padding: 15px 20px; display: flex; gap: 15px; cursor: pointer; transition: background 0.2s; border-bottom: 1px solid #f8fafc; }
.notif-item:hover { background: #f8fafc; }
.notif-item.unread { background: #f0f7ff; }
.notif-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.notif-icon.low_stock { background: #fff7ed; color: #f97316; }
.notif-icon.nearing_expiration { background: #fef2f2; color: #ef4444; }
.notif-content p { font-size: 0.85rem; color: #334155; line-height: 1.4; }
.notif-time { font-size: 0.7rem; color: #94a3b8; }

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.brasallis-search-container { position: relative; }
.omni-results-dropdown {
    position: absolute; top: calc(100% + 12px); left: 0; right: 0;
    background: #fff; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.15);
    border: 1px solid rgba(0,0,0,0.08); z-index: 5000; display: none;
    max-height: 400px; overflow-y: auto; padding: 10px;
}
.omni-results-dropdown.active { display: block; animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1); }

.omni-result-item {
    display: flex; align-items: center; gap: 15px; padding: 12px 15px;
    border-radius: 12px; text-decoration: none !important; color: inherit;
    transition: background 0.2s;
}
.omni-result-item:hover { background: #f8fafc; }

.result-icon {
    width: 36px; height: 36px; border-radius: 10px; background: #f1f5f9;
    display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0;
}
.result-info { flex: 1; min-width: 0; }
.result-title { font-size: 0.9rem; font-weight: 700; color: var(--navy); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.result-subtitle { font-size: 0.75rem; color: #94a3b8; text-transform: capitalize; }

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
