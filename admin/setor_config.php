<?php
// admin/setor_config.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../includes/cabecalho.php';

// Check Auth & Admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$setor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Optional: Pre-select a role if passed via URL, else default to first or none
$selected_cargo_id = isset($_GET['cargo_id']) ? (int)$_GET['cargo_id'] : 0;

if (!$setor_id) { header('Location: organizacao.php'); exit; }

// Verify Sector
$stmt = $conn->prepare("SELECT * FROM setores WHERE id = ? AND empresa_id = ?");
$stmt->execute([$setor_id, $empresa_id]);
$setor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$setor) {
    echo "<div class='container pt-5'><div class='alert alert-danger'>Setor não encontrado.</div></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$msg = '';

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {

        // 1. ADD ROLE
        if ($_POST['action'] === 'add_cargo') {
            $nomeCargo = sanitize_input($_POST['nome_cargo']);
            $nivelHierarquia = (int)$_POST['nivel_hierarquia'];
            if ($nomeCargo) {
                $stmt = $conn->prepare("INSERT INTO cargos (setor_id, nome, nivel_hierarquia) VALUES (?, ?, ?)");
                $stmt->execute([$setor_id, $nomeCargo, $nivelHierarquia]);
                $msg = '<div class="alert alert-success">Cargo adicionado!</div>';
            }
        }

        // 2. DELETE ROLE
        if ($_POST['action'] === 'delete_cargo') {
            $cargoId = (int)$_POST['cargo_id'];
            $stmt = $conn->prepare("DELETE FROM cargos WHERE id = ? AND setor_id = ?");
            $stmt->execute([$cargoId, $setor_id]);
            $msg = '<div class="alert alert-success">Cargo removido!</div>';
            if ($selected_cargo_id == $cargoId) $selected_cargo_id = 0;
        }

        // 3. SAVE PERMISSIONS FOR A ROLE
        if ($_POST['action'] === 'save_role_permissions') {
            $targetCargoId = (int)$_POST['cargo_id'];
            try {
                $conn->beginTransaction();
                
                // Clear existing permissions for this role
                $stmt = $conn->prepare("DELETE FROM permissoes_cargo WHERE cargo_id = ?");
                $stmt->execute([$targetCargoId]);
                
                // Add new permissions
                if (!empty($_POST['modulos'])) {
                    $insertStmt = $conn->prepare("INSERT INTO permissoes_cargo (cargo_id, modulo_id, nivel_acesso) VALUES (?, ?, ?)");
                    foreach ($_POST['modulos'] as $moduloId) {
                        $nivel = $_POST['nivel_acesso'][$moduloId] ?? 'leitura';
                        $insertStmt->execute([$targetCargoId, $moduloId, $nivel]);
                    }
                }
                
                $conn->commit();
                $msg = '<div class="alert alert-success fw-bold"><i class="fas fa-check-circle me-1"></i> Permissões salvas para este cargo!</div>';
                $selected_cargo_id = $targetCargoId; // Keep selected
            } catch (Exception $e) {
                $conn->rollBack();
                $msg = '<div class="alert alert-danger">Erro: ' . $e->getMessage() . '</div>';
            }
        }

        // 4. ADD MEMBER TO ROLE
        if ($_POST['action'] === 'add_member') {
            $targetUserId = (int)$_POST['user_id'];
            $targetCargoId = (int)$_POST['cargo_id'];
            
            try {
                // Verify if user belongs to company
                $check = $conn->prepare("SELECT id FROM usuarios WHERE id = ? AND empresa_id = ?");
                $check->execute([$targetUserId, $empresa_id]);
                if ($check->fetch()) {
                     // Remove previous assignment (Enforce single role logic for now to keep it simple and clean)
                     $conn->prepare("DELETE FROM usuario_setor WHERE user_id = ?")->execute([$targetUserId]);
                     
                     // Add new assignment
                     $stmt = $conn->prepare("INSERT INTO usuario_setor (user_id, setor_id, cargo_id) VALUES (?, ?, ?)");
                     $stmt->execute([$targetUserId, $setor_id, $targetCargoId]);
                     
                     $msg = '<div class="alert alert-success">Usuário adicionado ao cargo com sucesso!</div>';
                     $selected_cargo_id = $targetCargoId;
                }
            } catch (Exception $e) {
                $msg = '<div class="alert alert-danger">Erro ao adicionar membro: ' . $e->getMessage() . '</div>';
            }
        }

        // 5. REMOVE MEMBER FROM ROLE
        if ($_POST['action'] === 'remove_member') {
            $targetUserId = (int)$_POST['user_id'];
            $targetCargoId = (int)$_POST['cargo_id']; // For context, though user_id is unique enough usually
            
            try {
                $conn->prepare("DELETE FROM usuario_setor WHERE user_id = ?")->execute([$targetUserId]);
                $msg = '<div class="alert alert-success">Usuário removido do cargo.</div>';
                $selected_cargo_id = $targetCargoId;
            } catch(Exception $e) {
                 $msg = '<div class="alert alert-danger">Erro: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// --- FETCH DATA ---
$cargos = $conn->prepare("SELECT * FROM cargos WHERE setor_id = ? ORDER BY nivel_hierarquia ASC, nome ASC");
$cargos->execute([$setor_id]);
$listaCargos = $cargos->fetchAll(PDO::FETCH_ASSOC);

// If no role selected but roles exist, ideally let user pick or select first? 
// Google style: shows list on left, blank on right until selected.

$modulos = $conn->query("SELECT * FROM modulos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Permissions for Selected Role
$rolePerms = [];
if ($selected_cargo_id) {
    $stmtP = $conn->prepare("SELECT modulo_id, nivel_acesso FROM permissoes_cargo WHERE cargo_id = ?");
    $stmtP->execute([$selected_cargo_id]);
    while ($row = $stmtP->fetch(PDO::FETCH_ASSOC)) {
        $rolePerms[$row['modulo_id']] = $row['nivel_acesso'];
    }
}
?>

<div class="container-fluid py-4 min-vh-100 bg-light">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
            <a href="organizacao.php" class="btn btn-white border shadow-sm me-3 rounded-circle" style="width: 40px; height: 40px; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-arrow-left text-secondary"></i>
            </a>
            <div>
                <h4 class="fw-bold text-navy mb-0"><?= htmlspecialchars($setor['nome']) ?></h4>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <span class="badge" style="background-color: <?= $setor['cor_hex'] ?>">Setor</span>
                    <span class="text-muted small">Gerenciamento de Acessos e Cargos</span>
                </div>
            </div>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddCargo">
            <i class="fas fa-plus me-2"></i>Novo Cargo
        </button>
    </div>

    <?= $msg ?>

    <div class="row g-0 border rounded-3 overflow-hidden shadow-sm bg-white" style="min-height: 600px;">
        <!-- LEFT: ROLES LIST -->
        <div class="col-md-3 border-end bg-light">
            <div class="p-3 border-bottom bg-white">
                <h6 class="fw-bold text-muted mb-0 small text-uppercase">Cargos do Setor</h6>
            </div>
            <div class="list-group list-group-flush overflow-auto" style="max-height: 70vh;">
                <?php if(empty($listaCargos)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-users-slash mb-2"></i><br>
                        <small>Nenhum cargo.</small>
                    </div>
                <?php else: ?>
                    <?php foreach($listaCargos as $cargo): 
                        $active = ($cargo['id'] == $selected_cargo_id) ? 'active-role bg-white border-start border-4 border-primary' : 'text-secondary';
                        $uCount = $conn->query("SELECT COUNT(*) FROM usuario_setor WHERE cargo_id = {$cargo['id']}")->fetchColumn();
                    ?>
                        <a href="?id=<?= $setor_id ?>&cargo_id=<?= $cargo['id'] ?>" class="list-group-item list-group-item-action py-3 <?= $active ?> d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($cargo['nome']) ?></div>
                                <small class="text-muted" style="font-size: 0.75rem;">Nível <?= $cargo['nivel_hierarquia'] ?> • <?= $uCount ?> usuários</small>
                            </div>
                            <?php if($active): ?><i class="fas fa-chevron-right text-primary"></i><?php endif; ?>
                            
                            <!-- Delete Button (Mini) -->
                            <form method="POST" onsubmit="return confirm('Excluir?');" class="position-absolute end-0 top-0 mt-1 me-1" style="display:none;">
                                <input type="hidden" name="action" value="delete_cargo">
                                <input type="hidden" name="cargo_id" value="<?= $cargo['id'] ?>">
                                <button class="btn btn-sm text-danger"><i class="fas fa-times"></i></button>
                            </form>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: PERMISSIONS EDITOR -->
        <div class="col-md-9 bg-white position-relative">
            <?php if ($selected_cargo_id): 
                // Find selected cargo name
                $crtCargo = array_filter($listaCargos, fn($c) => $c['id'] == $selected_cargo_id);
                $crtCargo = reset($crtCargo);
            ?>
                <div class="p-4 h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-4 border-bottom pb-3">
                        <div>
                            <h5 class="fw-bold text-navy mb-1">Permissões para: <span class="text-primary"><?= htmlspecialchars($crtCargo['nome']) ?></span></h5>
                            <p class="text-muted small mb-0">Defina quais módulos este cargo pode acessar e o nível de controle.</p>
                        </div>
                        <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir o cargo <?= htmlspecialchars($crtCargo['nome']) ?>?');">
                             <input type="hidden" name="action" value="delete_cargo">
                             <input type="hidden" name="cargo_id" value="<?= $selected_cargo_id ?>">
                             <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash me-2"></i>Excluir Cargo</button>
                        </form>
                    </div>

                    <!-- TABS FOR PERMISSIONS AND MEMBERS -->
                    <ul class="nav nav-tabs mb-3 border-bottom-0">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold small" data-bs-toggle="tab" data-bs-target="#tab-permissions">
                                <i class="fas fa-key me-2"></i>Permissões
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold small" data-bs-toggle="tab" data-bs-target="#tab-members">
                                <i class="fas fa-users me-2"></i>Membros da Equipe <span class="badge bg-secondary ms-1 rounded-pill small"><?= $uCount ?></span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content flex-grow-1 d-flex flex-column">
                        
                        <!-- TAB 1: PERMISSIONS -->
                        <div class="tab-pane fade show active h-100 d-flex flex-column" id="tab-permissions">
                             <form method="POST" class="flex-grow-1 d-flex flex-column">
                                <input type="hidden" name="action" value="save_role_permissions">
                                <input type="hidden" name="cargo_id" value="<?= $selected_cargo_id ?>">

                                <div class="row g-3 overflow-auto mb-3" style="flex: 1; max-height: 500px;">
                                    <?php foreach ($modulos as $mod): 
                                        $isEnabled = isset($rolePerms[$mod['id']]);
                                        $level = $rolePerms[$mod['id']] ?? 'leitura';
                                    ?>
                                    <div class="col-xl-6">
                                        <div class="card h-100 border transition-hover <?= $isEnabled ? 'border-primary bg-primary bg-opacity-10' : 'border-light bg-light' ?>" id="card_<?= $mod['id'] ?>">
                                            <div class="card-body d-flex align-items-center">
                                                <div class="form-check form-switch me-3">
                                                    <input class="form-check-input" type="checkbox" name="modulos[]" value="<?= $mod['id'] ?>" 
                                                           id="mod_<?= $mod['id'] ?>" <?= $isEnabled ? 'checked' : '' ?> 
                                                           onchange="toggleModule(<?= $mod['id'] ?>)">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <i class="<?= $mod['icone'] ?> me-2 <?= $isEnabled ? 'text-primary' : 'text-muted' ?>" id="icon_<?= $mod['id'] ?>"></i>
                                                        <label class="fw-bold mb-0 cursor-pointer text-dark" for="mod_<?= $mod['id'] ?>"><?= $mod['nome'] ?></label>
                                                    </div>
                                                    <small class="text-muted d-block lh-sm mb-2"><?= $mod['descricao'] ?></small>
                                                    
                                                    <select name="nivel_acesso[<?= $mod['id'] ?>]" id="select_<?= $mod['id'] ?>" 
                                                            class="form-select form-select-sm border-0 shadow-none <?= $isEnabled ? '' : 'd-none' ?>" 
                                                            style="background-color: rgba(255,255,255,0.7);">
                                                        <option value="leitura" <?= $level == 'leitura' ? 'selected' : '' ?>>👁️ Visualizar</option>
                                                        <option value="escrita" <?= $level == 'escrita' ? 'selected' : '' ?>>✏️ Editar/Criar</option>
                                                        <option value="admin" <?= $level == 'admin' ? 'selected' : '' ?>>🛠️ Controle Total</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="border-top pt-3 text-end">
                                    <button type="submit" class="btn btn-primary px-4 py-2 fw-bold shadow-sm">
                                        <i class="fas fa-save me-2"></i>Salvar Permissões
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- TAB 2: MEMBERS -->
                        <div class="tab-pane fade h-100" id="tab-members">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-muted fw-bold mb-0">Usuários neste Cargo</h6>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAddMember">
                                    <i class="fas fa-user-plus me-2"></i>Adicionar Membro
                                </button>
                            </div>
                            
                            <div class="list-group overflow-auto" style="max-height: 500px;">
                                <?php 
                                    $members = $conn->prepare("
                                        SELECT u.id, u.username, u.email 
                                        FROM usuarios u 
                                        JOIN usuario_setor us ON u.id = us.user_id 
                                        WHERE us.cargo_id = ?
                                    ");
                                    $members->execute([$selected_cargo_id]);
                                    $memberList = $members->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                
                                <?php if(empty($memberList)): ?>
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-user-clock mb-2 fa-2x opacity-25"></i>
                                        <p>Nenhum usuário ocupando este cargo.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($memberList as $m): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                    <span class="fw-bold text-primary"><?= substr($m['username'], 0, 1) ?></span>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($m['username']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($m['email']) ?></small>
                                                </div>
                                            </div>
                                            <form method="POST" onsubmit="return confirm('Remover usuário deste cargo?');">
                                                <input type="hidden" name="action" value="remove_member">
                                                <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
                                                <input type="hidden" name="cargo_id" value="<?= $selected_cargo_id ?>">
                                                <button class="btn btn-sm text-danger hover-bg-light" title="Remover do Cargo">
                                                    <i class="fas fa-user-minus"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Modal Add Member -->
                <div class="modal fade" id="modalAddMember" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <input type="hidden" name="action" value="add_member">
                                <input type="hidden" name="cargo_id" value="<?= $selected_cargo_id ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Adicionar Usuário ao Cargo</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted small">Selecione um usuário para atribuir a este cargo. Isso mudará o cargo atual dele.</p>
                                    <div class="mb-3">
                                        <label class="form-label">Usuário</label>
                                        <select name="user_id" class="form-select" required>
                                            <option value="">Selecione...</option>
                                            <?php 
                                            // List all users from company
                                            $allUsers = $conn->prepare("SELECT id, username, email FROM usuarios WHERE empresa_id = ? ORDER BY username ASC");
                                            $allUsers->execute([$empresa_id]);
                                            while($u = $allUsers->fetch(PDO::FETCH_ASSOC)):
                                            ?>
                                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?> (<?= $u['email'] ?>)</option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Adicionar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Empty State -->
                <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted opacity-50">
                    <i class="fas fa-user-tag fa-4x mb-3"></i>
                    <h5>Selecione um Cargo</h5>
                    <p>Clique em um cargo à esquerda para configurar seus acessos.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Add Cargo -->
<div class="modal fade" id="modalAddCargo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_cargo">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Novo Cargo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome do Cargo</label>
                        <input type="text" name="nome_cargo" class="form-control" placeholder="Ex: Analista Financeiro" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nível Hierárquico (1-10)</label>
                        <input type="number" name="nivel_hierarquia" class="form-control" value="1" min="1" max="10">
                        <small class="text-muted">1 = Operacional, 10 = Diretoria/Gerência</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Cargo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleModule(id) {
    const card = document.getElementById('card_' + id);
    const select = document.getElementById('select_' + id);
    const icon = document.getElementById('icon_' + id);
    const checkbox = document.getElementById('mod_' + id);

    if (checkbox.checked) {
        card.classList.remove('border-light', 'bg-light');
        card.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
        select.classList.remove('d-none');
        icon.classList.remove('text-muted');
        icon.classList.add('text-primary');
    } else {
        card.classList.add('border-light', 'bg-light');
        card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
        select.classList.add('d-none');
        icon.classList.add('text-muted');
        icon.classList.remove('text-primary');
    }
}
</script>

<style>
.active-role { background-color: white !important; font-weight: 500; color: #0A2647; }
.transition-hover { transition: all 0.2s ease-in-out; }
</style>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>
