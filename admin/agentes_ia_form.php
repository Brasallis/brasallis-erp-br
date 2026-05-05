<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';

// Verificar se é admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$agent = [
    'id' => '',
    'name' => '',
    'role' => '',
    'instruction' => '',
    'model' => 'gemini-pro',
    'parameters' => json_encode(['temperature' => 0.7]),
    'status' => 'active'
];
$is_edit = false;

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? '';
    $instruction = $_POST['instruction'] ?? '';
    $temperature = $_POST['temperature'] ?? 0.7;
    $status = $_POST['status'] ?? 'active';
    $id = $_POST['id'] ?? '';
    
    $parameters = json_encode(['temperature' => (float)$temperature]);

    if (!empty($id)) {
        // Update
        $stmt = $conn->prepare("UPDATE ai_agents SET name = ?, role = ?, instruction = ?, parameters = ?, status = ? WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$name, $role, $instruction, $parameters, $status, $id, $empresa_id]);
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO ai_agents (empresa_id, name, role, instruction, parameters, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$empresa_id, $name, $role, $instruction, $parameters, $status]);
    }

    header('Location: agentes_ia.php');
    exit;
}

// Carregar dados se for edição
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM ai_agents WHERE id = ? AND empresa_id = ?");
    $stmt->execute([$_GET['id'], $empresa_id]);
    $fetched_agent = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($fetched_agent) {
        $agent = $fetched_agent;
        $is_edit = true;
    }
}

$parameters = json_decode($agent['parameters'] ?? '{}', true);
$temperature = $parameters['temperature'] ?? 0.7;

include_once '../includes/cabecalho.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold text-dark mb-0"><?php echo $is_edit ? 'Editar Agente' : 'Novo Agente de IA'; ?></h2>
            <p class="text-muted small">Configure as instruções e comportamento do seu especialista</p>
        </div>
        <a href="agentes_ia.php" class="btn btn-outline-secondary rounded-pill px-4">
            Voltar
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($agent['id']); ?>">
                
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Informações Básicas</h5>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nome do Agente</label>
                            <input type="text" class="form-control form-control-lg" name="name" 
                                   placeholder="Ex: Consultor Financeiro" 
                                   value="<?php echo htmlspecialchars($agent['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Função / Papel</label>
                            <input type="text" class="form-control" name="role" 
                                   placeholder="Ex: Especialista em análise de custos e redução de despesas" 
                                   value="<?php echo htmlspecialchars($agent['role']); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold d-flex justify-content-between">
                                Instruções do Sistema (Prompt)
                                <button type="button" class="btn btn-sm btn-link text-decoration-none p-0" data-bs-toggle="modal" data-bs-target="#promptTemplatesModal">
                                    <i class="fas fa-magic me-1"></i>Usar Template
                                </button>
                            </label>
                            <textarea class="form-control" name="instruction" id="instructionArea" rows="10" 
                                      placeholder="Defina como o agente deve se comportar, o que ele sabe e como deve responder..."><?php echo htmlspecialchars($agent['instruction']); ?></textarea>
                            <div class="form-text">
                                Seja específico sobre o tom de voz, limitações e formato de resposta desejado.
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Temperatura (Criatividade)</label>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="range" class="form-range flex-grow-1" name="temperature" 
                                           min="0" max="1" step="0.1" value="<?php echo $temperature; ?>" 
                                           oninput="document.getElementById('tempVal').innerText = this.value">
                                    <span class="badge bg-light text-dark border" id="tempVal"><?php echo $temperature; ?></span>
                                </div>
                                <div class="form-text small">
                                    0.0 = Preciso e focado | 1.0 = Criativo e variado
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active" <?php echo $agent['status'] === 'active' ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="inactive" <?php echo $agent['status'] === 'inactive' ? 'selected' : ''; ?>>Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mb-5">
                    <a href="agentes_ia.php" class="btn btn-light border">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-5 fw-bold">Salvar Agente</button>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-primary-subtle text-primary mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-lightbulb me-2"></i>Dica Pro</h5>
                    <p class="mb-0">
                        Bons agentes precisam de instruções claras. Defina um **objetivo**, um **contexto** e um **formato de saída**.
                        <br><br>
                        <em>Exemplo:</em> "Você é um especialista em estoques. Sua meta é identificar produtos parados há mais de 30 dias e sugerir promoções."
                    </p>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Parâmetros Atuais</h6>
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Modelo</span>
                            <span class="fw-bold">gemini-pro</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Contexto</span>
                            <span class="fw-bold"><?php echo strlen($agent['instruction']); ?> chars</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Templates -->
<div class="modal fade" id="promptTemplatesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Templates de Agentes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <button type="button" class="list-group-item list-group-item-action p-3" onclick="applyTemplate('finance')">
                        <div class="d-flex w-100 justify-content-between mb-1">
                            <h6 class="mb-1 fw-bold text-primary">Gerente Financeiro</h6>
                        </div>
                        <p class="mb-1 small text-muted">Especialista em análise de fluxo de caixa, redução de custos e saúde financeira.</p>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action p-3" onclick="applyTemplate('marketing')">
                        <div class="d-flex w-100 justify-content-between mb-1">
                            <h6 class="mb-1 fw-bold text-primary">Estrategista de Marketing</h6>
                        </div>
                        <p class="mb-1 small text-muted">Focado em criar campanhas, textos persuasivos e ideias de promoção.</p>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action p-3" onclick="applyTemplate('stock')">
                        <div class="d-flex w-100 justify-content-between mb-1">
                            <h6 class="mb-1 fw-bold text-primary">Otimizador de Estoque</h6>
                        </div>
                        <p class="mb-1 small text-muted">Analisa níveis de estoque, validade e sugestão de reposição inteligente.</p>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function applyTemplate(type) {
        const instructionArea = document.getElementById('instructionArea');
        const templates = {
            'finance': "ATUE COMO um Gerente Financeiro Sênior para uma empresa de varejo.\n\nSEU OBJETIVO: Analisar dados financeiros, identificar gargalos de custo e sugerir estratégias para aumentar a lucratividade.\n\nDIRETRIZES:\n- Seja analítico e direto.\n- Sempre justifique suas sugestões com base em princípios econômicos sólidos.\n- Use formatação clara (bullet points) para listar recomendações.\n- Se faltarem dados, peça explicitamente o que precisa.",
            'marketing': "ATUE COMO um Especialista em Marketing Digital.\n\nSEU OBJETIVO: Criar copies persuasivas e estratégias de campanha para produtos do estoque.\n\nDIRETRIZES:\n- Use linguagem persuasiva e voltada para ação (Call to Action).\n- Foque nos benefícios do produto, não apenas nas características.\n- Considere o público-alvo da empresa ao sugerir o tom de voz.",
            'stock': "ATUE COMO um Especialista em Logística e Estoque.\n\nSEU OBJETIVO: Otimizar o giro de estoque e prevenir perdas por validade.\n\nDIRETRIZES:\n- Analise produtos com baixo giro e sugira ações (queima de estoque, bundles).\n- Monitore datas de validade.\n- Sugira quantidades ideais de reposição baseadas no histórico de saída."
        };

        if (templates[type]) {
            instructionArea.value = templates[type];
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('promptTemplatesModal'));
            modal.hide();
        }
    }
</script>

<?php include_once '../includes/rodape.php'; ?>
