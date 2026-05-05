<?php
// modules/rh/views/colaboradores.php - APPLE PURE INTERACTIVE EDITION
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

// Check Auth & Permission
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('rh', 'leitura')) { header('Location: ../../../admin/painel_admin.php?error=acesso_negado'); exit; }

$params = check_permission('rh', 'escrita'); 
$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// Fetch Initial Data
try {
    $stmt = $conn->prepare("
        SELECT u.*, u.username as nome, s.nome as setor_nome, s.id as setor_id
        FROM usuarios u 
        LEFT JOIN usuario_setor us ON u.id = us.user_id
        LEFT JOIN setores s ON us.setor_id = s.id 
        WHERE u.empresa_id = ? 
        ORDER BY u.username ASC
    ");
    $stmt->execute([$empresa_id]);
    $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Sectors for Modal
    $stmtSet = $conn->prepare("SELECT id, nome FROM setores WHERE empresa_id = ? ORDER BY nome ASC");
    $stmtSet->execute([$empresa_id]);
    $setores = $stmtSet->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $colaboradores = [];
    $setores = [];
}

require_once __DIR__ . '/../../../includes/cabecalho.php';
?>

<div class="container-fluid py-4 min-vh-100 bg-white">
    <!-- Header: Apple Style -->
    <div class="d-flex justify-content-between align-items-end mb-5 px-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2" style="letter-spacing: 0.5px; opacity: 0.6; font-size: 0.7rem;">
                    <li class="breadcrumb-item"><a href="../../../admin/painel_admin.php" class="text-decoration-none text-dark uppercase">CORE</a></li>
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-dark uppercase">RH HUB</a></li>
                    <li class="breadcrumb-item active fw-bold uppercase" aria-current="page">EQUIPE</li>
                </ol>
            </nav>
            <h1 class="display-5 fw-bold text-dark mb-0" style="letter-spacing: -1.5px;">Gestão de <span class="text-primary">Talentos</span></h1>
            <p class="text-secondary mt-2">Visão consolidada da ficha funcional e administrativa.</p>
        </div>
        <div class="d-flex gap-2">
            <div class="input-group shadow-sm rounded-pill overflow-hidden bg-light border-0" style="width: 250px;">
                <span class="input-group-text bg-transparent border-0 ps-3"><i class="fas fa-search text-muted small"></i></span>
                <input type="text" id="searchTerm" class="form-control border-0 bg-transparent py-2 ps-1 small" placeholder="Buscar colaborador..." onkeyup="filterTable()">
            </div>
            <?php if ($params): ?>
                <button class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm" onclick="openModalCreate()">
                    <i class="fas fa-user-plus me-2"></i>Novo Contrato
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info border-0 shadow-sm mb-4 mx-3 rounded-4 animate__animated animate__fadeInDown">
            <i class="fas fa-info-circle me-2"></i> <?= $_SESSION['message'] ?>
            <?php unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <!-- Table: Glassmorphism List -->
    <div class="card border-0 shadow-sm mx-3" style="border-radius: 28px; overflow: hidden; border: 1px solid #f1f5f9 !important;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="employeesTable">
                <thead class="bg-light text-secondary small text-uppercase fw-bold">
                    <tr>
                        <th class="ps-5 py-4 border-0">Profissional</th>
                        <th class="py-4 border-0">Função / Unidade</th>
                        <th class="py-4 border-0 text-center">Data Admissão</th>
                        <th class="py-4 border-0 text-center">Status</th>
                        <th class="text-end pe-5 py-4 border-0">Ações</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if (empty($colaboradores)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Aguardando seu primeiro registro...</td></tr>
                    <?php else: ?>
                        <?php foreach($colaboradores as $c): ?>
                        <tr class="transition-all employee-row">
                            <td class="ps-5 py-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle bg-dark text-white shadow-sm fw-bold" style="width: 48px; height: 48px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                        <?= strtoupper(substr($c['nome'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <div class="ms-3">
                                        <div class="fw-bold text-dark fs-6 searchable-name"><?= htmlspecialchars($c['nome'] ?: ($c['username'] ?? 'Usuário')) ?></div>
                                        <div class="text-muted small" style="font-size: 0.7rem;">CPF: <?= ($c['cpf'] ?? '---') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4">
                                <div class="fw-bold text-dark small mb-1"><?= ucfirst($c['user_type']) ?></div>
                                <span class="badge rounded-pill bg-light text-secondary fw-normal border" style="font-size: 0.65rem;"><?= htmlspecialchars($c['setor_nome'] ?? 'SETOR GERAL') ?></span>
                            </td>
                            <td class="py-4 text-center">
                                <div class="text-dark small fw-bold">
                                    <?= (!empty($c['data_admissao'])) ? date('d/m/Y', strtotime($c['data_admissao'])) : '-' ?>
                                </div>
                            </td>
                            <td class="py-4 text-center">
                                <?php 
                                    $st = $c['status_colaborador'] ?? 'ativo';
                                    $stClass = [
                                        'ativo' => 'bg-success text-success',
                                        'inativo' => 'bg-danger text-danger',
                                        'ferias' => 'bg-info text-info',
                                        'afastado' => 'bg-warning text-warning'
                                    ];
                                ?>
                                <span class="badge rounded-pill bg-opacity-10 <?= $stClass[$st] ?? 'bg-secondary text-secondary' ?> px-3 py-2 fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                    <?= strtoupper($st) ?>
                                </span>
                            </td>
                            <td class="text-end pe-5">
                                <div class="btn-group gap-2">
                                    <button class="btn btn-light btn-sm rounded-circle shadow-none action-btn" onclick="editColaborador(<?= $c['id'] ?>)" title="Editar">
                                        <i class="fas fa-user-edit text-primary small"></i>
                                    </button>
                                    <?php if ($c['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-light btn-sm rounded-circle shadow-none action-btn" onclick="deleteColaborador(<?= $c['id'] ?>)" title="Remover">
                                            <i class="fas fa-trash-alt text-danger small"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: iOS Sheet CRUD -->
<div class="modal fade" id="modalColaborador" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg p-3" style="border-radius: 32px;">
            <form id="formColaborador" action="/admin/usuarios.php/store" method="POST">
                <!-- ID para Edição (Oculto) -->
                <input type="hidden" name="id" id="colab_id">
                
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h3 class="fw-bold text-dark mb-1" id="modalTitle">Novo Contrato</h3>
                        <p class="text-muted small mb-0">Preencha os dados institucionais do colaborador.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body py-4">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">Nome Completo</label>
                            <input type="text" name="username" id="colab_username" class="form-control apple-input" placeholder="Ex: Lucas Ferreira de Lima" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">CPF</label>
                            <input type="text" name="cpf" id="colab_cpf" class="form-control apple-input mask-cpf" placeholder="000.000.000-00">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">E-mail Corporativo</label>
                            <input type="email" name="email" id="colab_email" class="form-control apple-input" placeholder="lucas@empresa.com" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">Celular / WhatsApp</label>
                            <input type="text" name="celular" id="colab_celular" class="form-control apple-input mask-tel" placeholder="(00) 0 0000-0000">
                        </div>
                        
                        <div class="col-12 py-2"><hr class="my-0 opacity-10"></div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">Unidade / Setor</label>
                            <select name="setor_id" id="colab_setor" class="form-select apple-input" onchange="loadCargos(this.value)" required>
                                <option value="">Selecione...</option>
                                <?php foreach($setores as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">Cargo</label>
                            <select name="cargo_id" id="colab_cargo" class="form-select apple-input">
                                <option value="">Aguardando setor...</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">Admissão</label>
                            <input type="date" name="data_admissao" id="colab_admissao" class="form-control apple-input">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">Tipo de Acesso</label>
                            <select name="user_type" id="colab_type" class="form-select apple-input" required>
                                <option value="employee">Colaborador (Restrito)</option>
                                <option value="admin">Administrador (Total)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">Status Inicial</label>
                            <select name="status_colaborador" id="colab_status" class="form-select apple-input">
                                <option value="ativo">Ativo</option>
                                <option value="ferias">Férias</option>
                                <option value="afastado">Afastado</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="pass_container">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">Senha de Acesso</label>
                            <input type="password" name="password" id="colab_pass" class="form-control apple-input" placeholder="••••••••">
                            <small class="text-muted d-block mt-1" id="pass_hint">Deixe em branco para não alterar</small>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-5 fw-bold shadow-sm" id="btnSubmit">Salvar Ficha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    body { background-color: #fff !important; font-family: 'Outfit', sans-serif !important; }
    .display-5 { font-family: 'Outfit', sans-serif; letter-spacing: -2px; }
    .apple-input { border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.75rem 1.1rem; transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1); font-size: 0.9rem; }
    .apple-input:focus { background: #fff; border-color: #000; box-shadow: 0 0 0 5px rgba(0,0,0,0.05); }
    .transition-all { transition: all 0.4s ease; }
    tr:hover { background-color: #fbfbfb !important; }
    .action-btn { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border: 1px solid #f1f5f9; }
    .action-btn:hover { background: #fff !important; border-color: #000 !important; transform: scale(1.1); }
    .avatar-circle { transition: transform 0.3s ease; }
    .employee-row:hover .avatar-circle { transform: scale(1.1) rotate(5deg); }
    .bg-light { background-color: #f8fafc !important; }
</style>

<script src="https://unpkg.com/imask"></script>
<script>
// Initialize variables globally so functions can access them
let modalEl;
let form;

document.addEventListener('DOMContentLoaded', function() {
    // Check if Bootstrap is loaded
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap JS is not loaded. Actions may not work.');
        return;
    }

    const modalTarget = document.getElementById('modalColaborador');
    if (modalTarget) {
        modalEl = new bootstrap.Modal(modalTarget);
        
        // --- TRAVA DE SEGURANÇA: BACKDROP SWEEP ---
        // Garante que o fundo escuro seja removido e o clique restaurado ao fechar a modal
        modalTarget.addEventListener('hidden.bs.modal', function () {
            // Remove qualquer resíduo de backdrop deixado pelo Bootstrap
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            // Restaura o scroll e remove a classe bloqueadora do corpo
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    }
    
    form = document.getElementById('formColaborador');

    // Initialize Masks
    const cpfEl = document.querySelector('.mask-cpf');
    const telEl = document.querySelector('.mask-tel');
    if (cpfEl) IMask(cpfEl, { mask: '000.000.000-00' });
    if (telEl) IMask(telEl, { mask: '(00) 0 0000-0000' });

    // Auto-open modal if action=new is present
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'new') {
        openModalCreate();
    }
});

function openModalCreate() {
    if (!modalEl) return;
    document.getElementById('modalTitle').innerText = 'Novo Contrato';
    document.getElementById('colab_id').value = '';
    form.reset();
    form.action = '/admin/usuarios.php/store';
    document.getElementById('pass_hint').style.display = 'none';
    document.getElementById('colab_pass').required = true;
    modalEl.show();
}

function loadCargos(setorId, currentCargoId = null) {
    if(!setorId) return;
    fetch(`../../../admin/get_cargos.php?setor_id=${setorId}`)
        .then(r => r.json())
        .then(data => {
            let html = '<option value="">Selecione um cargo...</option>';
            data.forEach(c => {
                html += `<option value="${c.id}" ${currentCargoId == c.id ? 'selected' : ''}>${c.nome}</option>`;
            });
            document.getElementById('colab_cargo').innerHTML = html;
        });
}

function editColaborador(id) {
    if (!modalEl) {
        alert('O sistema ainda está carregando os componentes visuais. Por favor, aguarde um segundo e tente novamente.');
        return;
    }
    document.getElementById('modalTitle').innerText = 'Editar Colaborador';
    document.getElementById('pass_hint').style.display = 'block';
    document.getElementById('colab_pass').required = false;
    document.getElementById('colab_pass').value = '';
    
    fetch(`../../../admin/get_usuario_data.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            if(data.error) { alert(data.error); return; }
            
            document.getElementById('colab_id').value = data.id;
            document.getElementById('colab_username').value = data.username;
            document.getElementById('colab_email').value = data.email;
            document.getElementById('colab_cpf').value = data.cpf || '';
            document.getElementById('colab_celular').value = data.celular || '';
            document.getElementById('colab_admissao').value = data.data_admissao || '';
            document.getElementById('colab_type').value = data.user_type;
            document.getElementById('colab_status').value = data.status_colaborador || 'ativo';
            document.getElementById('colab_setor').value = data.setor_id || '';
            
            // Fix dynamic action for update
            form.action = `/admin/usuarios.php/update/${id}`; 
            
            loadCargos(data.setor_id, data.cargo_id);
            modalEl.show();
        })
        .catch(err => {
            console.error('Fetch error:', err);
            alert('Erro ao carregar dados do colaborador. Verifique sua conexão.');
        });
}

function deleteColaborador(id) {
    if(confirm('Atenção: A remoção deste profissional é definitiva no ecossistema setorial. Deseja prosseguir?')) {
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = `/admin/usuarios.php/delete/${id}`;
        document.body.appendChild(f);
        f.submit();
    }
}

function filterTable() {
    let term = document.getElementById('searchTerm').value.toLowerCase();
    document.querySelectorAll('.employee-row').forEach(row => {
        let name = row.querySelector('.searchable-name').innerText.toLowerCase();
        row.style.display = name.includes(term) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
