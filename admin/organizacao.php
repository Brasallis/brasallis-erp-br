<?php
// admin/organizacao.php - APPLE PURE EDITION
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/funcoes.php';

// Check Auth & Admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$msg = '';

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // 1. ADD SECTOR
        if ($_POST['action'] === 'add_setor') {
            $nome = sanitize_input($_POST['nome']);
            $cor = $_POST['cor_hex'] ?? '#000000';
            if ($nome) {
                $stmt = $conn->prepare("INSERT INTO setores (empresa_id, nome, cor_hex) VALUES (?, ?, ?)");
                $stmt->execute([$empresa_id, $nome, $cor]);
                $msg = '<div class="alert alert-success border-0 shadow-sm animate__animated animate__fadeInDown" style="border-radius:16px;">Setor criado com sucesso!</div>';
            }
        }
        
        // 2. DELETE SECTOR
        if ($_POST['action'] === 'delete_setor') {
            $id = (int)$_POST['id'];
            // Verify ownership
            $check = $conn->prepare("SELECT id FROM setores WHERE id = ? AND empresa_id = ?");
            $check->execute([$id, $empresa_id]);
            if ($check->fetch()) {
                $stmt = $conn->prepare("DELETE FROM setores WHERE id = ?");
                $stmt->execute([$id]);
                $msg = '<div class="alert alert-info border-0 shadow-sm animate__animated animate__fadeInDown" style="border-radius:16px;">Setor removido da organização.</div>';
            }
        }
    }
}

// --- FETCH DATA ---
$stmt = $conn->prepare("SELECT s.*, 
    (SELECT COUNT(*) FROM usuario_setor us WHERE us.setor_id = s.id) as total_users,
    (SELECT COUNT(*) FROM cargos c WHERE c.setor_id = s.id) as total_cargos
    FROM setores s WHERE s.empresa_id = ? ORDER BY s.nome ASC");
$stmt->execute([$empresa_id]);
$setores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// General Stats
$total_colaboradores = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id = ?");
$total_colaboradores->execute([$empresa_id]);
$total_colab = $total_colaboradores->fetchColumn();

require_once __DIR__ . '/../includes/cabecalho.php';
?>

<div class="container-fluid py-4 bg-white min-vh-100">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-end mb-5 px-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2" style="letter-spacing: 0.5px; opacity: 0.6;">
                    <li class="breadcrumb-item small"><a href="painel_admin.php" class="text-decoration-none text-dark">CORE</a></li>
                    <li class="breadcrumb-item small active fw-bold" aria-current="page">ESTRUTURA</li>
                </ol>
            </nav>
            <h1 class="display-5 fw-bold text-dark mb-0" style="letter-spacing: -1.5px;">Design <span class="text-primary">Organizacional</span></h1>
            <p class="text-secondary mt-2">Mapeamento de departamentos e topologia da empresa.</p>
        </div>
        <button class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddSetor">
            <i class="fas fa-plus me-2"></i>Novo Departamento
        </button>
    </div>

    <?= $msg ?>

    <!-- Bento Stats Grid -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 p-4 h-100 shadow-sm animate__animated animate__fadeInUp" style="background: #f8fafc; border-radius: 24px;">
                <h6 class="text-muted small fw-bold text-uppercase mb-3" style="letter-spacing: 1px;">Departamentos</h6>
                <div class="d-flex align-items-center">
                    <h2 class="fw-bold mb-0 me-3"><?= count($setores) ?></h2>
                    <div class="badge bg-primary bg-opacity-10 text-primary border-0 rounded-pill px-3 py-2 fw-bold" style="font-size: 0.7rem;">Mapeados</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 p-4 h-100 shadow-sm animate__animated animate__fadeInUp" style="background: #f0fdf4; border-radius: 24px; animation-delay: 0.1s;">
                <h6 class="text-success small fw-bold text-uppercase mb-3" style="letter-spacing: 1px;">Capital Humano</h6>
                <div class="d-flex align-items-center">
                    <h2 class="fw-bold mb-0 me-3 text-success"><?= $total_colab ?></h2>
                    <div class="badge bg-success bg-opacity-25 text-success border-0 rounded-pill px-3 py-2 fw-bold" style="font-size: 0.7rem;">Alocados</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 p-4 h-100 shadow-sm animate__animated animate__fadeInUp" style="background: #000; border-radius: 24px; animation-delay: 0.2s;">
                <h6 class="text-white-50 small fw-bold text-uppercase mb-3" style="letter-spacing: 1px;">Hierarquia</h6>
                <div class="d-flex align-items-center">
                    <h2 class="fw-bold mb-0 text-white me-3">ATIVO</h2>
                    <i class="fas fa-check-circle text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Setores Grid -->
    <div class="row g-4">
        <?php if(empty($setores)): ?>
            <div class="col-12 text-center py-5">
                <img src="https://illustrations.popsy.co/gray/team-building.svg" alt="Sem setores" style="max-width: 200px; opacity: 0.5;">
                <h5 class="mt-4 fw-bold text-dark">Sua organização ainda não tem setores.</h5>
                <p class="text-muted small">Comece criando departamentos como "Financeiro", "RH" ou "Vendas".</p>
                <button class="btn btn-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalAddSetor">Criar Primeiro Setor</button>
            </div>
        <?php else: ?>
            <?php foreach($setores as $s): ?>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 transition-all card-setor" style="border-radius: 28px; border: 1px solid #f1f5f9 !important;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div class="icon-box rounded-circle shadow-sm p-4 d-flex align-items-center justify-content-center" 
                                     style="background: <?= $s['cor_hex'] ?>; width: 64px; height: 64px;">
                                    <i class="fas fa-folder-open text-white fa-lg"></i>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-light rounded-circle p-0 d-flex align-items-center justify-content-center shadow-none" 
                                            data-bs-toggle="dropdown" style="width: 32px; height: 32px;">
                                        <i class="fas fa-ellipsis-h text-muted small"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2" style="border-radius: 16px;">
                                        <li><a class="dropdown-item rounded-3 small py-2" href="setor_config.php?id=<?= $s['id'] ?>"><i class="fas fa-cog me-2 text-primary"></i>Configuração</a></li>
                                        <li><hr class="dropdown-divider opacity-10"></li>
                                        <li>
                                            <form method="POST" onsubmit="return confirm('Excluir este setor e todas as suas configurações?')">
                                                <input type="hidden" name="action" value="delete_setor">
                                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                <button type="submit" class="dropdown-item rounded-3 small py-2 text-danger"><i class="fas fa-trash-alt me-2"></i>Remover</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <h4 class="fw-bold text-dark mb-1" style="letter-spacing: -0.5px;"><?= htmlspecialchars($s['nome']) ?></h4>
                            <p class="text-muted small mb-4">Unidade de Negócio</p>

                            <div class="d-flex gap-3 align-items-center mt-auto">
                                <div class="flex-grow-1 bg-light rounded-pill p-2 text-center">
                                    <div class="text-muted" style="font-size: 0.65rem; font-weight: 800; text-uppercase;">Cargos</div>
                                    <div class="fw-bold text-dark fs-5"><?= $s['total_cargos'] ?></div>
                                </div>
                                <div class="flex-grow-1 bg-light rounded-pill p-2 text-center text-primary">
                                    <div class="text-primary-50" style="font-size: 0.65rem; font-weight: 800; text-uppercase;">Equipe</div>
                                    <div class="fw-bold fs-5"><?= $s['total_users'] ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 p-4 pt-0">
                            <a href="setor_config.php?id=<?= $s['id'] ?>" class="btn btn-outline-dark w-100 rounded-pill fw-bold small py-2 transition-all hover-black">
                                Estrutura & Níveis <i class="fas fa-chevron-right ms-2 small"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Add Setor (iOS Sheet Style) -->
<div class="modal fade" id="modalAddSetor" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg p-3" style="border-radius: 28px;">
            <form method="POST">
                <input type="hidden" name="action" value="add_setor">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">Novo Setor</h4>
                        <p class="text-muted small mb-0">Expanda a topologia da sua corporação.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase mb-2" style="letter-spacing: 1px;">Identificação do Departamento</label>
                        <input type="text" name="nome" class="form-control apple-input" placeholder="Ex: Engenharia, Marketing, Suporte..." required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted small fw-bold text-uppercase mb-2" style="letter-spacing: 1px;">Sinalização Visual (Cor)</label>
                        <div class="d-flex gap-3 flex-wrap">
                            <?php 
                            $cores = ['#000000', '#007AFF', '#34C759', '#FF3B30', '#5856D6', '#AF52DE', '#FF9500', '#555555'];
                            foreach($cores as $c): ?>
                                <div class="color-option">
                                    <input type="radio" name="cor_hex" value="<?= $c ?>" id="c_<?= str_replace('#','',$c) ?>" class="d-none" <?= $c == '#000000' ? 'checked' : '' ?>>
                                    <label for="c_<?= str_replace('#','',$c) ?>" class="rounded-circle" style="width: 32px; height: 32px; background-color: <?= $c ?>; cursor: pointer; border: 3px solid transparent; transition: all 0.2s ease;"></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm">Instanciar Setor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    body { background-color: #fff !important; font-family: 'Outfit', sans-serif !important; }
    .display-5 { font-family: 'Outfit', sans-serif; }
    .apple-input { border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.8rem 1rem; transition: all 0.25s ease; }
    .apple-input:focus { background: #fff; border-color: #000; box-shadow: 0 0 0 4px rgba(0,0,0,0.05); }
    .card-setor:hover { transform: translateY(-8px); border-color: #000 !important; }
    .color-option input:checked + label { border-color: #000 !important; transform: scale(1.2); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .transition-all { transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); }
    .hover-black:hover { background-color: #000 !important; color: #fff !important; }
</style>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>
