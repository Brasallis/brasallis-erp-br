<?php
// superadmin/arquiteto.php - GESTÃO DE MÓDULOS POR TENANT (Arquiteto de Tenants)
declare(strict_types=1);

require_once '../vendor/autoload.php';
require_once '../includes/funcoes.php';

use App\Core\Database;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
checkSuperAdmin();

$conn = Database::getInstance();
$empresa_id = $_GET['id'] ?? null;

if (!$empresa_id) {
    header("Location: empresas.php");
    exit;
}

// Buscar dados da empresa
$stmt = $conn->prepare("SELECT * FROM empresas WHERE id = ?");
$stmt->execute([$empresa_id]);
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    die("Empresa não encontrada.");
}

$active_modules = json_decode($empresa['active_modules'] ?? '[]', true);

// Processar atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_modules = $_POST['modules'] ?? [];
    $modules_json = json_encode($selected_modules);
    
    $update = $conn->prepare("UPDATE empresas SET active_modules = ? WHERE id = ?");
    if ($update->execute([$modules_json, $empresa_id])) {
        $_SESSION['msg_success'] = "Módulos da empresa '{$empresa['name']}' atualizados com sucesso!";
        header("Location: empresas.php");
        exit;
    }
}

// Lista de módulos disponíveis no ecossistema Brasallis
$available_modules = [
    'estoque'    => ['label' => 'Estoque & Catálogo', 'icon' => 'fa-box-open', 'desc' => 'Gestão de produtos, SKUs e movimentações.'],
    'financeiro' => ['label' => 'Financeiro Hub', 'icon' => 'fa-money-bill-transfer', 'desc' => 'Contas a pagar/receber e fluxo de caixa.'],
    'crm'        => ['label' => 'CRM & Vendas', 'icon' => 'fa-users-gear', 'desc' => 'Gestão de leads e funil de vendas.'],
    'rh'         => ['label' => 'Equipe & RH', 'icon' => 'fa-users', 'desc' => 'Gestão de colaboradores e permissões.'],
    'ai_hub'     => ['label' => 'Brasallis AI (IA)', 'icon' => 'fa-brain-circuit', 'desc' => 'Acesso a agentes inteligentes e automações.'],
    'fiscal'     => ['label' => 'Inteligência Tributária', 'icon' => 'fa-file-invoice-dollar', 'desc' => 'Cálculo de impostos e notas fiscais.'],
    'pdv'        => ['label' => 'Frente de Caixa (PDV)', 'icon' => 'fa-cash-register', 'desc' => 'Vendas rápidas e emissão de cupons.'],
    'relatorios' => ['label' => 'BI & Relatórios', 'icon' => 'fa-chart-bar', 'desc' => 'Análise de dados e inteligência de negócio.'],
];

require_once 'includes/header.php';
?>

    <div class="page-header mb-5">
        <div class="d-flex align-items-center gap-3">
            <a href="empresas.php" class="btn btn-light rounded-circle shadow-sm" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="page-title">Arquiteto de Tenants</h2>
                <p class="page-subtitle">Configurando ecossistema para: <strong><?php echo htmlspecialchars($empresa['name']); ?></strong></p>
            </div>
        </div>
    </div>

    <form method="POST">
        <div class="row g-4">
            <?php foreach ($available_modules as $key => $mod): ?>
            <div class="col-md-6 col-lg-4">
                <div class="google-card h-100 p-4 d-flex flex-column" style="cursor: pointer;" onclick="toggleModule('<?php echo $key; ?>')">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary fs-4 shadow-sm" style="width: 50px; height: 50px;">
                            <i class="fas <?php echo $mod['icon']; ?>"></i>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="modules[]" value="<?php echo $key; ?>" id="mod_<?php echo $key; ?>" <?php echo in_array($key, $active_modules) ? 'checked' : ''; ?> style="width: 40px; height: 20px;">
                        </div>
                    </div>
                    <h5 class="fw-bold text-dark mb-2"><?php echo $mod['label']; ?></h5>
                    <p class="text-muted small mb-0"><?php echo $mod['desc']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-5 p-4 bg-white border rounded-4 shadow-sm d-flex align-items-center justify-content-between">
            <div>
                <h6 class="fw-bold m-0 text-dark">Deseja aplicar as alterações?</h6>
                <p class="text-muted small m-0">As mudanças refletirão instantaneamente no painel do cliente.</p>
            </div>
            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm">
                <i class="fas fa-save me-2"></i> Salvar Configuração
            </button>
        </div>
    </form>

    <script>
    function toggleModule(key) {
        const checkbox = document.getElementById('mod_' + key);
        checkbox.checked = !checkbox.checked;
    }
    // Prevenir clique duplo no checkbox disparar o onclick do card
    document.querySelectorAll('.form-check-input').forEach(el => {
        el.addEventListener('click', (e) => e.stopPropagation());
    });
    </script>

<?php require_once 'includes/footer.php'; ?>
