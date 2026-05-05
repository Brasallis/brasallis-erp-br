<?php
// admin/editar_agente.php
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

// Get Agent Data
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: agentes_ia.php");
    exit;
}

$agent = $aiAgent->getById($id, $empresa_id);
if (!$agent) {
    echo "Agente não encontrado ou acesso negado.";
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitize_input($_POST['name']),
        'role' => sanitize_input($_POST['role']),
        'model' => sanitize_input($_POST['model']),
        'instruction' => sanitize_input($_POST['instruction']),
        'temperature' => (float)$_POST['temperature'],
        'status' => $_POST['status']
    ];
    
    // Simple validation
    if (empty($data['name']) || empty($data['role'])) {
        $error = "Nome e Função são obrigatórios.";
    } else {
        try {
            if ($aiAgent->update($id, $empresa_id, $data)) {
                $success = "Agente atualizado com sucesso!";
                $agent = array_merge($agent, $data); // Refresh view
            } else {
                $error = "Erro ao atualizar agente.";
            }
        } catch (Exception $e) {
            $error = "Erro: " . $e->getMessage();
        }
    }
}

// Handle Delete (Simple Logic via GET for now, ideal would be POST)
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    if ($aiAgent->delete($id, $empresa_id)) {
        header("Location: agentes_ia.php?msg=deleted");
        exit;
    }
}

include_once '../includes/cabecalho.php';
?>

<div class="container my-5" style="max-width: 800px;">
    <div class="header-section mb-4 d-flex justify-content-between align-items-center">
        <div>
            <a href="agentes_ia.php" class="text-decoration-none text-muted mb-2 d-inline-block"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
            <h1 class="fw-bold">Editar Agente</h1>
        </div>
        <button onclick="if(confirm('Tem certeza que deseja excluir este agente?')) window.location.href='editar_agente.php?id=<?php echo $id; ?>&action=delete';" class="btn btn-outline-danger btn-sm rounded-pill px-3">
            <i class="fas fa-trash me-2"></i> Excluir
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger rounded-3 shadow-sm border-0"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success rounded-3 shadow-sm border-0"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="card-body p-5">
            <form method="POST">
                <!-- Status Switch -->
                <div class="d-flex justify-content-end mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="statusSwitch" name="status" value="active" <?php echo $agent['status'] === 'active' ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold small text-uppercase" for="statusSwitch">Agente Ativo</label>
                    </div>
                    <!-- Hidden input for unchecked state handling is cleaner via ternary but simple way: -->
                    <input type="hidden" name="status_default" value="inactive"> 
                    <!-- (PHP logic needs to handle if checkbox not sent - actually my update logic checks isset, so checkbox needs care. 
                         Fix: JS or explicit value handling below. Better: Select/Radio for clarity or ensure POST always sends status)
                    -->
                </div>

                <!-- Basic Info -->
                <h5 class="fw-bold mb-4 text-primary">Identidade do Agente</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted text-uppercase">Nome do Agente</label>
                        <input type="text" name="name" class="form-control form-control-lg bg-light border-0" value="<?php echo htmlspecialchars($agent['name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted text-uppercase">Função / Papel</label>
                        <input type="text" name="role" class="form-control form-control-lg bg-light border-0" value="<?php echo htmlspecialchars($agent['role']); ?>" required>
                    </div>
                </div>

                <!-- Model Config -->
                <h5 class="fw-bold mb-4 text-primary mt-5">Inteligência</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted text-uppercase">Modelo AI</label>
                        <select name="model" class="form-select form-select-lg bg-light border-0">
                            <option value="gemini-2.5-flash" <?php echo ($agent['model'] === 'gemini-2.5-flash') ? 'selected' : ''; ?>>Gemini 2.5 Flash (Padrão e Rápido)</option>
                            <option value="gemini-2.5-pro" <?php echo ($agent['model'] === 'gemini-2.5-pro') ? 'selected' : ''; ?>>Gemini 2.5 Pro (Mais Inteligente)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted text-uppercase">Criatividade (Temperatura)</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="range" class="form-range" name="temperature" min="0" max="1" step="0.1" value="<?php echo htmlspecialchars($agent['temperature']); ?>" id="tempRange" oninput="document.getElementById('tempVal').innerText = this.value">
                            <span class="badge bg-primary rounded-pill py-2 px-3" id="tempVal"><?php echo htmlspecialchars($agent['temperature']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <h5 class="fw-bold mb-4 text-primary mt-5">Instruções do Sistema</h5>
                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted text-uppercase">Como o agente deve se comportar?</label>
                    <textarea name="instruction" class="form-control bg-light border-0" rows="6" style="resize: none;"><?php echo htmlspecialchars($agent['system_instruction']); ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-3 pt-4 border-top">
                    <a href="agentes_ia.php" class="btn btn-light btn-lg px-4 rounded-pill">Cancelar</a>
                    <button type="submit" class="btn btn-primary btn-lg px-5 rounded-pill shadow fw-bold">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Simple fix for checkbox value if not submitted
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!document.getElementById('statusSwitch').checked) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'status';
            input.value = 'inactive';
            this.appendChild(input);
        }
    });
</script>

<?php include_once '../includes/rodape.php'; ?>
