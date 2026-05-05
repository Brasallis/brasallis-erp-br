<?php
// admin/setor_dashboard.php
ob_start(); // Buffer output to prevent header issues
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/funcoes.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    $redirectUrl = '../login.php'; // Define the redirect URL
    if ($redirectUrl !== '#') {
        // Tenta redirecionar via Header
        if (!headers_sent()) {
            header("Location: $redirectUrl");
            exit;
        } else {
            // Fallback via JS se headers já foram enviados
            echo "<script>window.location.href = '$redirectUrl';</script>";
            echo "<noscript><meta http-equiv='refresh' content='0;url=$redirectUrl'></noscript>";
            exit;
        }
    }
}
$conn = connect_db();
$setor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['user_type'] === 'admin';

// 1. Validar Acesso ao Setor
// Se for admin, acessa tudo. Se for funcionario, deve pertencer ao setor.
$user_setor_id = $_SESSION['setor_id'] ?? 0;

if (!$is_admin && $user_setor_id != $setor_id) {
    // Redireciona ou mostra erro
    die("Acesso negado a este setor."); // Melhorar visual depois
}

// 2. Buscar Detalhes do Setor
$stmt = $conn->prepare("SELECT * FROM setores WHERE id = ?");
$stmt->execute([$setor_id]);
$setor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$setor) {
    die("Setor não encontrado.");
}

// 3. Buscar Módulos Disponíveis e Aplicar Filtro RBAC
// Se for admin, mostra todos. Se não, filtra pela permissão 'leitura' do módulo.
$stmtMod = $conn->prepare("SELECT * FROM modulos ORDER BY nome ASC");
$stmtMod->execute();
$allModules = $stmtMod->fetchAll(PDO::FETCH_ASSOC);

$modulos = [];
foreach ($allModules as $mod) {
    if ($is_admin) {
        $mod['nivel_acesso'] = 'admin'; // Admin tem acesso total
        $modulos[] = $mod;
    } else {
        if (check_permission($mod['slug'], 'leitura')) {
            // Recupera o nível para exibir na UI
            $mod['nivel_acesso'] = $_SESSION['permissions'][$mod['slug']] ?? 'leitura';
            $modulos[] = $mod;
        }
    }
}

// 4. Lógica de AUTO-REDIRECT (Fixed)
if (count($modulos) === 1 && !$is_admin) {
    $mod = $modulos[0];
    $redirectUrl = '#'; 
    if ($mod['slug'] === 'rh') $redirectUrl = '../modules/rh/views/index.php';
    elseif ($mod['slug'] === 'estoque') $redirectUrl = '../modules/estoque/views/index.php';
    elseif ($mod['slug'] === 'financeiro') $redirectUrl = '../modules/financeiro/views/index.php';
    elseif ($mod['slug'] === 'pdv') $redirectUrl = '../employee/pdv.php';
    elseif ($mod['slug'] === 'fiscal') $redirectUrl = '../modules/fiscal/views/index.php';
    
    if ($redirectUrl !== '#') {
        if (!headers_sent()) {
            header("Location: $redirectUrl");
            exit;
        } else {
            echo "<script>window.location.href = '$redirectUrl';</script>";
            echo "<noscript><meta http-equiv='refresh' content='0;url=$redirectUrl'></noscript>";
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/cabecalho.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex align-items-center mb-4">
        <div class="icon-shape rounded-3 text-white me-3 shadow-sm" style="background-color: <?= $setor['cor_hex'] ?>; width: 64px; height: 64px; display:flex; align-items:center; justify-content:center; font-size: 1.5rem;">
            <i class="fas fa-layer-group"></i> <!-- Ícone genérico se não tiver salvo -->
        </div>
        <div>
            <h2 class="fw-bold text-navy mb-0"><?= htmlspecialchars($setor['nome']) ?></h2>
            <p class="text-secondary small mb-0">Painel de Controle do Departamento</p>
        </div>
        <?php if($is_admin): ?>
            <a href="setor_config.php?id=<?= $setor['id'] ?>" class="btn btn-outline-secondary ms-auto"><i class="fas fa-cog me-2"></i>Configurar</a>
        <?php endif; ?>
    </div>

    <!-- Modules Grid -->
    <div class="row g-4">
        <?php if (empty($modulos)): ?>
            <div class="col-12 text-center py-5 text-muted">
                <i class="fas fa-box-open fa-3x mb-3 opacity-25"></i>
                <p>Nenhum módulo habilitado para este setor.</p>
                <?php if($is_admin): ?>
                    <a href="setor_config.php?id=<?= $setor['id'] ?>" class="btn btn-sm btn-primary">Habilitar Módulos</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach($modulos as $mod): ?>
                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm card-hover-effect">
                        <div class="card-body text-center p-4">
                            <div class="text-primary mb-3 text-gradient" style="font-size: 2.5rem;">
                                <i class="<?= $mod['icone'] ?>"></i>
                            </div>
                            <h5 class="fw-bold mb-2"><?= htmlspecialchars($mod['nome']) ?></h5>
                            <p class="text-muted small mb-3"><?= htmlspecialchars($mod['descricao']) ?></p>
                            
                            <!-- Action Button -->
                            <?php 
                                // Simple routing logic
                                $url = '#'; 
                                if($mod['slug'] === 'rh') $url = '../modules/rh/views/index.php';
                                if($mod['slug'] === 'estoque') $url = '../modules/estoque/views/index.php';
                                if($mod['slug'] === 'financeiro') $url = '../modules/financeiro/views/index.php';
                                if($mod['slug'] === 'crm') $url = '../modules/crm/views/index.php';
                                if($mod['slug'] === 'fiscal') $url = '../modules/fiscal/views/index.php';
                                if($mod['slug'] === 'pdv') $url = '../employee/pdv.php';
                            ?>
                            <a href="<?= $url ?>" class="btn btn-outline-primary w-100 rounded-pill">Acessar</a>
                        </div>
                        <div class="card-footer bg-light border-0 py-2">
                            <small class="text-muted"><i class="fas fa-lock me-1"></i> Permissão: <strong><?= ucfirst($mod['nivel_acesso']) ?></strong></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Future: KPIs or Activity Feed specific to Sector -->
    <div class="row mt-5">
        <div class="col-12">
            <h5 class="fw-bold text-navy mb-3">Atividades Recentes do Setor</h5>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <p>Funcionalidade em desenvolvimento...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .text-navy { color: #0A2647; }
    .card-hover-effect { transition: transform 0.2s, box-shadow 0.2s; }
    .card-hover-effect:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>
