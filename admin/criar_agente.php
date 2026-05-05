<?php
// admin/criar_agente.php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}
require_once '../includes/funcoes.php';
require_once '../classes/AIAgent.php';

// Check auth
if (!isset($_SESSION['empresa_id'])) {
    header('Location: ../login.php');
    exit;
}

$conn = connect_db();
$aiAgent = new App\AIAgent($conn);
$empresa_id = $_SESSION['empresa_id'];

$error = '';
$success = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $role = sanitize_input($_POST['role']);
    $model = sanitize_input($_POST['model']);
    $instruction = sanitize_input($_POST['instruction']);
    $temperature = (float)$_POST['temperature'];
    
    // Simple validation
    if (empty($name) || empty($role)) {
        $error = "Nome e Função são obrigatórios.";
    } else {
        try {
            if ($aiAgent->create($empresa_id, $name, $role, $model, $instruction, $temperature)) {
                $success = "Agente criado com sucesso!";
                // Redirect after functionality
                header("Location: agentes_ia.php");
                exit;
            } else {
                $error = "Erro ao criar agente.";
            }
        } catch (Exception $e) {
            $error = "Erro: " . $e->getMessage();
        }
    }
}

include_once '../includes/cabecalho.php';

// Plan Check
require_once '../classes/AIPlanManager.php';
$planManager = new App\AIPlanManager($conn, $empresa_id);
if (!$planManager->canCreateCustomAgent()) {
    ?>
    <div class="container my-5 text-center" style="max-width: 600px;">
        <div class="card border-0 shadow-lg rounded-4 p-5">
            <div class="mb-4 text-warning">
                <i class="fas fa-lock fa-4x"></i>
            </div>
            <h2 class="fw-bold text-dark mb-3">Recurso Premium</h2>
            <p class="text-muted lead mb-4">A criação de Agentes Personalizados está disponível apenas nos planos <strong>Growth</strong> e <strong>Enterprise</strong>.</p>
            
            <div class="d-grid gap-3 col-md-8 mx-auto">
                <a href="agentes_ia.php#upgrade" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm pulse-btn">Fazer Upgrade Agora</a>
                <a href="agentes_ia.php" class="btn btn-link text-muted text-decoration-none">Voltar</a>
            </div>
            
            <div class="mt-4 pt-4 border-top">
                <small class="text-muted d-block mb-2">No plano Free, você tem acesso aos 4 agentes especialistas padrão.</small>
                <div class="d-flex justify-content-center gap-2">
                    <span class="badge bg-light text-dark border">Growth Manager</span>
                    <span class="badge bg-light text-dark border">SEO Expert</span>
                    <span class="badge bg-light text-dark border">Trend Hunter</span>
                    <span class="badge bg-light text-dark border">Sarah</span>
                </div>
            </div>
        </div>
    </div>
    <?php
    include_once '../includes/rodape.php';
    exit;
}
?>

<div class="container my-5" style="max-width: 800px;">
    <div class="header-section mb-4">
        <a href="agentes_ia.php" class="text-decoration-none text-muted mb-2 d-inline-block"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
        <h1 class="fw-bold">Criar Novo Agente</h1>
        <p class="text-muted">Configure a personalidade e capacidades do seu assistente de IA.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger rounded-3 shadow-sm border-0"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success rounded-3 shadow-sm border-0"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="card-body p-5">
            <form method="POST" action="criar_agente.php">
                <!-- Basic Info -->
                <h5 class="fw-bold mb-4 text-primary">Identidade do Agente</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted text-uppercase">Nome do Agente</label>
                        <input type="text" name="name" class="form-control form-control-lg bg-light border-0" placeholder="Ex: Ana Financeira" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted text-uppercase">Função / Papel</label>
                        <input type="text" name="role" class="form-control form-control-lg bg-light border-0" placeholder="Ex: Analista de Custos" required>
                    </div>
                </div>

                <!-- Model Config -->
                <h5 class="fw-bold mb-4 text-primary mt-5">Inteligência</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted text-uppercase">Modelo AI</label>
                        <select name="model" class="form-select form-select-lg bg-light border-0">
                            <option value="gemini-2.5-flash">Gemini 2.5 Flash (Padrão e Rápido)</option>
                            <option value="gemini-2.5-pro">Gemini 2.5 Pro (Mais Inteligente)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted text-uppercase">Criatividade (Temperatura)</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="range" class="form-range" name="temperature" min="0" max="1" step="0.1" value="0.7" id="tempRange" oninput="document.getElementById('tempVal').innerText = this.value">
                            <span class="badge bg-primary rounded-pill py-2 px-3" id="tempVal">0.7</span>
                        </div>
                        <small class="text-muted d-block mt-1">Valores altos (0.9) tornam o agente mais criativo. Valores baixos (0.2) tornam-no mais preciso.</small>
                    </div>
                </div>

                <!-- Instructions -->
                <h5 class="fw-bold mb-4 text-primary mt-5">Instruções do Sistema</h5>
                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted text-uppercase">Como o agente deve se comportar?</label>
                    <textarea name="instruction" class="form-control bg-light border-0" rows="6" placeholder="Você é um especialista em finanças experiente. Analise os dados fornecidos e ofereça insights estratégicos de redução de custos..." style="resize: none;"></textarea>
                    <div class="form-text mt-2"><i class="fas fa-magic me-1"></i> Dica: Seja específico sobre o tom de voz e o formato das respostas desejadas.</div>
                </div>

                <div class="d-flex justify-content-end gap-3 pt-4 border-top">
                    <a href="agentes_ia.php" class="btn btn-light btn-lg px-4 rounded-pill">Cancelar</a>
                    <button type="submit" class="btn btn-primary btn-lg px-5 rounded-pill shadow fw-bold">Criar Agente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/rodape.php'; ?>
