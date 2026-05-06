<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../includes/funcoes.php';
use App\Repository\UsuarioRepository;
use App\Core\Database;

$empresa_id = $_SESSION['empresa_id'];
$repo = new UsuarioRepository(Database::getInstance(), $empresa_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') { $repo->add($_POST); $_SESSION['message'] = 'Colaborador cadastrado!'; }
        if ($action === 'edit') { $repo->update($_POST); $_SESSION['message'] = 'Dados atualizados!'; }
        if ($action === 'delete') { $repo->delete($_POST['id']); $_SESSION['message'] = 'Colaborador removido!'; }
    } catch (Exception $e) { reportar_erro($e, 'Usuarios'); }
    header("Location: usuarios.php"); exit;
}

include_once '../includes/cabecalho.php';
$search = $_GET['search'] ?? '';
$usuarios = $repo->getAll($search);

// Buscar módulos ativos da empresa para exibir no formulário
$stmt_modules = Database::getInstance()->prepare("SELECT active_modules FROM empresas WHERE id = ?");
$stmt_modules->execute([$empresa_id]);
$emp_modules_raw = $stmt_modules->fetchColumn();
$emp_modules = json_decode($emp_modules_raw ?? '[]', true);
if (empty($emp_modules)) { $emp_modules = ['estoque', 'rh', 'relatorios', 'pdv']; }

$module_names = [
    'estoque' => '📦 Estoque',
    'rh' => '👥 Equipe/RH',
    'financeiro' => '💰 Financeiro',
    'crm' => '🎯 CRM/Vendas',
    'ai_hub' => '🤖 IA Hub',
    'fiscal' => '📄 Fiscal',
    'relatorios' => '📊 BI/Relatórios',
    'pdv' => '🛒 PDV/Caixa'
];
?>

<style>
    .user-card {
        background: #fff; border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 12px;
        border: 1px solid rgba(0,0,0,0.05); transition: var(--brasallis-transition); display: flex; align-items: center; gap: 16px;
        cursor: pointer;
    }
    .user-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); border-color: var(--brasallis-primary); }

    .user-avatar {
        width: 44px; height: 44px; background: var(--brasallis-primary-soft); border-radius: 12px;
        display: flex; align-items: center; justify-content: center; color: var(--brasallis-primary); flex-shrink: 0;
        font-weight: 700; font-size: 1.1rem;
    }

    .user-name { font-size: 0.95rem; font-weight: 600; color: var(--navy); margin-bottom: 2px; }
    .user-email { font-size: 0.8rem; color: var(--slate-400); }

    .badge-google { background: var(--surface); color: var(--slate-600); padding: 4px 10px; border-radius: 8px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; }
    .badge-admin { background: var(--brasallis-primary-soft); color: var(--brasallis-primary); }

    @media (max-width: 768px) {
        .desktop-only { display: none !important; }
        .page-container { padding-bottom: 100px; }
    }
</style>

<div class="page-container">
    <div class="page-header">
        <div class="page-title-group">
            <h1>Gestão de Equipe</h1>
            <p><?= count($usuarios) ?> colaboradores ativos na organização</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 fw-bold desktop-only" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-user-plus me-2"></i>Adicionar Funcionário
        </button>
    </div>

    <!-- FILTRO -->
    <div class="section-card mb-4">
        <form action="usuarios.php" method="GET">
            <div class="input-group bg-light rounded-pill px-3 py-1">
                <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" class="form-control bg-transparent border-0 shadow-none" placeholder="Buscar por nome, email ou CPF..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </form>
    </div>

    <!-- LISTA DE USUÁRIOS -->
    <div class="row g-3">
        <?php foreach ($usuarios as $u): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="user-card" onclick="openEdit(<?= $u['id'] ?>)">
                    <div class="user-avatar">
                        <?= strtoupper(substr($u['username'], 0, 1)) ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($u['username']) ?></div>
                        <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
                        <div class="mt-2 d-flex gap-2">
                            <span class="badge-google <?= $u['user_type'] == 'admin' ? 'badge-admin' : '' ?>"><?= $u['user_type'] ?></span>
                            <span class="badge-google <?= $u['status_colaborador'] == 'ativo' ? 'badge-active' : '' ?>"><?= $u['status_colaborador'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- MODAL ADICIONAR -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-mobile">
        <div class="modal-content">
            <form action="usuarios.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header border-0 shadow-sm">
                    <h5 class="fw-bold m-0">Novo Funcionário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label small fw-bold text-muted">NOME COMPLETO</label><input type="text" name="username" class="form-control" required></div>
                        <div class="col-12"><label class="form-label small fw-bold text-muted">E-MAIL (LOGIN)</label><input type="email" name="email" class="form-control" required></div>
                        <div class="col-12"><label class="form-label small fw-bold text-muted">SENHA INICIAL</label><input type="password" name="password" class="form-control" required></div>
                        <div class="col-6"><label class="form-label small fw-bold text-muted">CPF</label><input type="text" name="cpf" class="form-control"></div>
                        <div class="col-6"><label class="form-label small fw-bold text-muted">CELULAR</label><input type="text" name="celular" class="form-control"></div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">CARGO / ACESSO</label>
                            <select name="user_type" class="form-select">
                                <option value="employee">Funcionário (PDV)</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">STATUS</label>
                            <select name="status_colaborador" class="form-select">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="fw-bold small text-muted text-uppercase mb-3">Permissões de Módulo</h6>
                        <div class="bg-light p-3 rounded-4 border">
                            <div class="row g-3">
                                <?php foreach ($emp_modules as $mod): ?>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark"><?= $module_names[$mod] ?? ucfirst($mod) ?></label>
                                        <select name="permissions[<?= $mod ?>]" class="form-select form-select-sm">
                                            <option value="0" selected>Nível 0: Bloqueado</option>
                                            <option value="1">Nível 1: Operador (Leitura)</option>
                                            <option value="2">Nível 2: Supervisor (Edição)</option>
                                            <option value="3">Nível 3: Gerente (Total)</option>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Criar Conta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-mobile">
        <div class="modal-content">
            <form action="usuarios.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editUserId">
                <div class="modal-header border-0 shadow-sm">
                    <h5 class="fw-bold m-0">Editar Colaborador</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label small fw-bold text-muted">NOME</label><input type="text" name="username" id="editUserName" class="form-control" required></div>
                        <div class="col-12"><label class="form-label small fw-bold text-muted">E-MAIL</label><input type="email" name="email" id="editUserEmail" class="form-control" required></div>
                        <div class="col-12"><label class="form-label small fw-bold text-muted">NOVA SENHA (DEIXE EM BRANCO PARA MANTER)</label><input type="password" name="password" class="form-control"></div>
                        <div class="col-6"><label class="form-label small fw-bold text-muted">CPF</label><input type="text" name="cpf" id="editUserCpf" class="form-control"></div>
                        <div class="col-6"><label class="form-label small fw-bold text-muted">CELULAR</label><input type="text" name="celular" id="editUserCelular" class="form-control"></div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">CARGO</label>
                            <select name="user_type" id="editUserType" class="form-select">
                                <option value="employee">Funcionário</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">STATUS</label>
                            <select name="status_colaborador" id="editUserStatus" class="form-select">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="fw-bold small text-muted text-uppercase mb-3">Permissões de Módulo</h6>
                        <div class="bg-light p-3 rounded-4 border" id="editPermissionsContainer">
                            <div class="row g-3">
                                <?php foreach ($emp_modules as $mod): ?>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark"><?= $module_names[$mod] ?? ucfirst($mod) ?></label>
                                        <select name="permissions[<?= $mod ?>]" id="perm-<?= $mod ?>" class="form-select form-select-sm">
                                            <option value="0">Nível 0: Bloqueado</option>
                                            <option value="1">Nível 1: Operador (Leitura)</option>
                                            <option value="2">Nível 2: Supervisor (Edição)</option>
                                            <option value="3">Nível 3: Gerente (Total)</option>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 p-3 bg-light rounded-4 d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-muted">ZONA CRÍTICA</span>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="confirmDelete()">Remover Acesso</button>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/rodape.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModalEl = document.getElementById('editUserModal');
    const editModal = new bootstrap.Modal(editModalEl);

    window.openEdit = function(id) {
        fetch(`../api/get_usuario.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('editUserId').value = data.id;
            document.getElementById('editUserName').value = data.username;
            document.getElementById('editUserEmail').value = data.email;
            document.getElementById('editUserCpf').value = data.cpf || '';
            document.getElementById('editUserCelular').value = data.celular || '';
            document.getElementById('editUserType').value = data.user_type;
            document.getElementById('editUserStatus').value = data.status_colaborador || 'ativo';
            
            // Resetar e carregar permissões
            const perms = data.permissions ? JSON.parse(data.permissions) : {};
            const availableModules = <?= json_encode($emp_modules) ?>;
            availableModules.forEach(mod => {
                const select = document.getElementById('perm-' + mod);
                if (select) select.value = perms[mod] || '0';
            });

            editModal.show();
        });
    }

    window.confirmDelete = function() {
        const name = document.getElementById('editUserName').value;
        const type = document.getElementById('editUserType').value;

        if (type === 'admin') {
            alert('Por questões de segurança, usuários Administradores não podem ser removidos por esta tela. Altere o cargo para Funcionário primeiro se desejar remover.');
            return;
        }

        if(confirm(`Remover acesso de "${name}"? Ele não poderá mais logar no sistema.`)) {
            const id = document.getElementById('editUserId').value;
            const f = document.createElement('form');
            f.method = 'POST'; f.action = 'usuarios.php';
            f.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}">`;
            document.body.appendChild(f); f.submit();
        }
    }
});
</script>
