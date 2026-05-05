<?php
// superadmin/nova_empresa.php v3.0 - O ARQUITETO DE TENANTS
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';
checkSuperAdmin();

$conn = connect_db();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['name'];
    $cnpj = $_POST['cnpj'] ?? '';
    $plano = $_POST['ai_plan'];
    $email_admin = $_POST['email_admin'];
    $senha_admin = password_hash($_POST['senha_admin'], PASSWORD_DEFAULT);
    
    // Módulos e Telas Selecionadas
    $modulos_selecionados = $_POST['modules'] ?? [];
    $telas_selecionadas = $_POST['screens'] ?? [];

    try {
        $conn->beginTransaction();

        // 1. Cria a empresa
        $modules_json = json_encode($modulos_selecionados);
        $stmt = $conn->prepare("INSERT INTO empresas (name, cnpj, ai_plan, subscription_status, onboarding_completed, active_modules) VALUES (?, ?, ?, 'active', 0, ?)");
        $stmt->execute([$nome, $cnpj, $plano, $modules_json]);
        $empresa_id = $conn->lastInsertId();

        // 2. Cria o usuário administrador com os campos corretos (username, password, user_type)
        $username = explode('@', $email_admin)[0]; // Fallback para username
        $stmt_user = $conn->prepare("INSERT INTO usuarios (username, email, password, user_type, empresa_id, subscription_status) VALUES (?, ?, ?, 'admin', ?, 'active')");
        $stmt_user->execute([$username, $email_admin, $senha_admin, $empresa_id]);

        $conn->commit();
        
        // Mensagem de sucesso
        $_SESSION['msg_success'] = "Empresa '{$nome}' e Administrador criados com sucesso! Você pode prosseguir com o Onboarding.";
        
        // Redireciona para a lista de empresas
        header("Location: empresas.php");
        exit;
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $message = "Erro no Arquiteto: " . $e->getMessage();
    }
}

// Definição de Estrutura de Módulos e Telas
$modulos_config = [
    'vendas' => [
        'nome' => 'Vendas & PDV',
        'icon' => 'fa-cash-register',
        'telas' => ['PDV (Ponto de Venda)', 'Frente de Caixa', 'Orçamentos', 'Pedidos de Venda', 'Gestão de Comissões']
    ],
    'estoque' => [
        'nome' => 'Estoque & Logística',
        'icon' => 'fa-boxes-stacked',
        'telas' => ['Cadastro de Produtos', 'Categorias', 'Movimentações (Kardex)', 'Inventário', 'Fornecedores']
    ],
    'financeiro' => [
        'nome' => 'Financeiro Hub',
        'icon' => 'fa-wallet',
        'telas' => ['Fluxo de Caixa', 'Contas a Pagar', 'Contas a Receber', 'Bancos & Conciliação', 'DRE Operacional']
    ],
    'rh' => [
        'nome' => 'Capital Humano (RH)',
        'icon' => 'fa-user-astronaut',
        'telas' => ['Gestão de Equipe', 'Cargos & Salários', 'Controle de Ponto', 'Arquivos de Funcionários']
    ],
    'crm' => [
        'nome' => 'CRM & Growth',
        'icon' => 'fa-handshake',
        'telas' => ['Funil de Vendas', 'Gestão de Leads', 'Base de Clientes', 'Histórico de Interações']
    ],
    'ai' => [
        'nome' => 'Brasallis AI Hub',
        'icon' => 'fa-wand-magic-sparkles',
        'telas' => ['Agentes de Atendimento', 'Treinamento de IA', 'Automações Inteligentes']
    ]
];

require_once 'includes/header.php';
?>

<style>
    .module-card { 
        border: 2px solid #f1f3f4; border-radius: 20px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        background: #fff; cursor: pointer; height: 100%;
    }
    .module-card:hover { border-color: var(--google-blue); transform: translateY(-5px); }
    .module-card.active { border-color: var(--google-blue); background: #e8f0fe; }
    
    .screen-item { 
        padding: 8px 12px; border-radius: 10px; font-size: 0.85rem; margin-bottom: 5px; 
        display: flex; align-items: center; gap: 10px; transition: background 0.2s;
    }
    .screen-item:hover { background: rgba(0,0,0,0.03); }
    
    .form-section { margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid #f1f3f4; }
    .sticky-setup { position: sticky; top: 100px; }
</style>

<form action="" method="POST" id="creationForm">
    <div class="row g-5">
        <div class="col-lg-8">
            <div class="page-header mb-5">
                <h2 class="fw-bold"><i class="fas fa-magic text-primary me-3"></i>Arquiteto de Tenants</h2>
                <p class="text-muted">Configure a infraestrutura e os módulos para o novo parceiro.</p>
            </div>

            <!-- Seção 1: Dados Básicos -->
            <div class="form-section">
                <h5 class="fw-bold mb-4">1. Identidade da Organização</h5>
                <div class="row g-4">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Nome da Empresa</label>
                        <input type="text" name="name" class="form-control form-control-lg border-2" required placeholder="Nome Comercial">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Plano Operacional</label>
                        <select name="ai_plan" class="form-select form-select-lg border-2" required id="planSelector">
                            <option value="foundation">Foundation Hub</option>
                            <option value="vision">Vision AI Hub</option>
                            <option value="enterprise">Enterprise Elite</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Seção 2: Módulos e Telas -->
            <div class="form-section border-0">
                <h5 class="fw-bold mb-4">2. Seleção de Módulos & Telas <span class="badge bg-primary ms-2" style="font-size: 0.7rem;">GOD MODE</span></h5>
                <div class="row g-4">
                    <?php foreach ($modulos_config as $key => $m): ?>
                    <div class="col-md-6">
                        <div class="module-card p-4" onclick="toggleModule('<?= $key ?>')">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="stat-icon-box bg-light text-primary m-0"><i class="fas <?= $m['icon'] ?>"></i></div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="modules[]" value="<?= $key ?>" id="check_<?= $key ?>" checked>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-3"><?= $m['nome'] ?></h6>
                            <div class="screens-list">
                                <?php foreach ($m['telas'] as $tela): ?>
                                <div class="screen-item">
                                    <input type="checkbox" name="screens[<?= $key ?>][]" value="<?= $tela ?>" checked class="form-check-input">
                                    <span><?= $tela ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Coluna Lateral de Confirmação -->
        <div class="col-lg-4">
            <div class="sticky-setup">
                <div class="google-card shadow-lg border-0" style="background: #202124; color: #fff;">
                    <h5 class="fw-bold mb-4">Configuração do Admin</h5>
                    
                    <div class="mb-4">
                        <label class="small opacity-75 mb-2">E-mail do Proprietário</label>
                        <input type="email" name="email_admin" class="form-control bg-dark border-0 text-white" required placeholder="admin@exemplo.com">
                    </div>
                    
                    <div class="mb-5">
                        <label class="small opacity-75 mb-2">Senha de Primeiro Acesso</label>
                        <input type="password" name="senha_admin" class="form-control bg-dark border-0 text-white" required placeholder="••••••••">
                    </div>

                    <div class="alert bg-white bg-opacity-10 border-0 rounded-4 mb-4">
                        <div class="small fw-bold mb-1"><i class="fas fa-info-circle me-2"></i> Próximo Passo:</div>
                        <div class="small opacity-75">O sistema redirecionará para o Quiz de Onboarding para finalizar a calibração da IA.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow">
                        Criar e Iniciar Onboarding <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function toggleModule(key) {
    // Apenas visual, o checkbox real trata a lógica
    const card = document.querySelector(`.module-card[onclick="toggleModule('${key}')"]`);
    const check = document.getElementById('check_' + key);
    // check.checked = !check.checked; // Comentado para não conflitar com clique direto no checkbox
}

// Inteligência de Planos
document.getElementById('planSelector').addEventListener('change', function() {
    const plano = this.value;
    // Lógica para pré-marcar ou desmarcar conforme o plano
    if (plano === 'foundation') {
        ['financeiro', 'crm', 'ai'].forEach(k => document.getElementById('check_'+k).checked = false);
    } else {
        ['financeiro', 'crm', 'ai'].forEach(k => document.getElementById('check_'+k).checked = true);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
