<?php
/**
 * View: rh/usuarios/index - APPLE PURE EDITION
 */
require_once BASE_PATH . '/includes/cabecalho.php';
?>

<div class="container-fluid py-4 min-vh-100 bg-white">
    <!-- Mensagens de Feedback (Style: Apple Notification) -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?? 'info' ?> border-0 shadow-sm mb-4 animate__animated animate__fadeInDown" role="alert" style="border-radius: 16px; background: rgba(255,255,255,0.8); backdrop_filter: blur(10px); border: 1px solid rgba(0,0,0,0.05) !important;">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-3 fa-lg text-<?= $_SESSION['message_type'] ?? 'info' ?>"></i>
                <div class="fw-bold text-dark"><?= $_SESSION['message'] ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>

    <!-- Header Section (Style: Airy & Modern) -->
    <div class="d-flex justify-content-between align-items-end mb-5 px-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2" style="letter-spacing: 0.5px; opacity: 0.6;">
                    <li class="breadcrumb-item small"><a href="/admin/painel_admin.php" class="text-decoration-none text-dark">CORE</a></li>
                    <li class="breadcrumb-item small active fw-bold" aria-current="page">RECURSOS HUMANOS</li>
                </ol>
            </nav>
            <h1 class="display-5 fw-bold text-dark mb-0" style="letter-spacing: -1.5px;">Gestão de <span class="text-primary">Equipe</span></h1>
            <p class="text-secondary mt-2">Personalize a estrutura organizacional e níveis de segurança.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark rounded-pill px-4 fw-bold" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddUsuario">
                <i class="fas fa-user-plus me-2"></i>Novo Contrato
            </button>
        </div>
    </div>

    <!-- Stats Bento Grid -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 p-4 h-100" style="background: #f8fafc; border-radius: 24px;">
                <div class="text-muted small fw-bold text-uppercase mb-2" style="letter-spacing: 1px;">Efetivo</div>
                <div class="d-flex align-items-baseline">
                    <h2 class="fw-bold mb-0 me-2"><?= count($usuarios) ?></h2>
                    <span class="text-success small fw-bold"><i class="fas fa-caret-up"></i> 100%</span>
                </div>
                <p class="text-muted small mt-2 mb-0">Usuários registrados no sistema</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 p-4 h-100" style="background: #f0fdf4; border-radius: 24px;">
                <div class="text-success small fw-bold text-uppercase mb-2" style="letter-spacing: 1px;">Admins</div>
                <div class="d-flex align-items-baseline">
                    <h2 class="fw-bold mb-0 text-success"><?= count(array_filter($usuarios, fn($u) => $u['user_type'] === 'admin')) ?></h2>
                </div>
                <p class="text-success-50 small mt-2 mb-0">Controle total de privilégios</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 p-4 h-100" style="background: #eff6ff; border-radius: 24px;">
                <div class="text-primary small fw-bold text-uppercase mb-2" style="letter-spacing: 1px;">Departamentos</div>
                <div class="d-flex align-items-baseline">
                    <h2 class="fw-bold mb-0 text-primary"><?= count($setores) ?></h2>
                </div>
                <p class="text-primary-50 small mt-2 mb-0">Setores ativos e configurados</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 p-4 h-100 bg-dark text-white shadow-lg" style="border-radius: 24px;">
                <div class="text-white-50 small fw-bold text-uppercase mb-2" style="letter-spacing: 1px;">Status de Ponto</div>
                <div class="d-flex align-items-baseline">
                    <h2 class="fw-bold mb-0">88%</h2>
                </div>
                <p class="text-white-50 small mt-2 mb-0">Presença média mensal</p>
            </div>
        </div>
    </div>

    <!-- Apple Style Table List -->
    <div class="card border-0 shadow-sm" style="border-radius: 24px; overflow: hidden; border: 1px solid #f1f5f9 !important;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small text-uppercase fw-bold">
                    <tr>
                        <th class="ps-5 py-4 border-0">Colaborador / Documento</th>
                        <th class="py-4 border-0">Função & Setor</th>
                        <th class="py-4 border-0">Contato</th>
                        <th class="py-4 border-0 text-center">Data Admissão</th>
                        <th class="py-4 border-0 text-center">Status</th>
                        <th class="text-end pe-5 py-4 border-0">Ações</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if (empty($usuarios)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Aguardando seu primeiro colaborador...</td></tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr class="transition-all">
                                <td class="ps-5 py-4" data-label="Colaborador">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-circle bg-dark text-white fw-bold shadow-sm d-none d-md-flex" style="width: 48px; height: 48px; border-radius: 16px; align-items: center; justify-content: center; font-size: 1.2rem;">
                                            <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($u['username'] ?? 'Usuário') ?></div>
                                            <div class="text-muted small" style="font-size: 0.75rem;">
                                                <i class="fas fa-id-card me-1 opacity-50"></i> <?= ($u['cpf'] ?? 'Sem CPF') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Função & Setor">
                                    <div class="fw-bold text-dark small mb-1"><?= $u['user_type'] == 'admin' ? 'Administrador' : 'Operacional' ?></div>
                                    <span class="text-muted small px-2 py-1 bg-light rounded-pill" style="font-size: 0.7rem;">SETOR GERAL</span>
                                </td>
                                <td data-label="Contato">
                                    <div class="text-dark fw-medium small"><?= htmlspecialchars($u['email']) ?></div>
                                    <div style="font-size: 0.75rem;" class="text-muted"><?= $u['celular'] ?: 'Sem telefone' ?></div>
                                </td>
                                <td data-label="Admissão" class="text-md-center">
                                    <span class="text-dark small fw-medium">
                                        <?= (!empty($u['data_admissao'])) ? date('d/m/Y', strtotime($u['data_admissao'])) : '-' ?>
                                    </span>
                                </td>
                                <td data-label="Status" class="text-md-center">
                                    <?php 
                                        $statusClass = [
                                            'ativo' => 'bg-success text-success',
                                            'ferias' => 'bg-info text-info',
                                            'afastado' => 'bg-warning text-warning',
                                            'inativo' => 'bg-danger text-danger'
                                        ];
                                        $s = $u['status_colaborador'] ?? 'ativo';
                                    ?>
                                    <span class="badge rounded-pill bg-opacity-10 <?= $statusClass[$s] ?? 'bg-secondary text-secondary' ?> px-3 py-2 fw-bold" style="font-size: 0.7rem;">
                                        <?= strtoupper($s) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-5" data-label="Ações">
                                    <div class="btn-group gap-1">
                                        <button class="btn btn-light btn-sm rounded-circle shadow-none" onclick="editUsuario(<?= $u['id'] ?>)" style="width: 32px; height: 32px;">
                                            <i class="fas fa-external-link-alt text-primary small"></i>
                                        </button>
                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-light btn-sm rounded-circle shadow-none" onclick="deleteUsuario(<?= $u['id'] ?>)" style="width: 32px; height: 32px;">
                                                <i class="fas fa-trash-can text-danger small"></i>
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

<!-- Modal Moderno (Style: iOS Sheet) -->
<div class="modal fade" id="modalAddUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg p-3" style="border-radius: 28px;">
            <form action="/admin/usuarios.php/store" method="POST">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">Novo Contrato</h4>
                        <p class="text-muted small mb-0">Abertura de ficha funcional e acesso ao ecossistema.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">Nome Completo</label>
                            <input type="text" name="username" class="form-control apple-input" placeholder="Ex: João da Silva" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">E-mail Corporativo</label>
                            <input type="email" name="email" class="form-control apple-input" placeholder="exemplo@brasallis.com" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">CPF</label>
                            <input type="text" name="cpf" class="form-control apple-input" placeholder="000.000.000-00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">Celular</label>
                            <input type="text" name="celular" class="form-control apple-input" placeholder="(00) 0 0000-0000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">Data de Admissão</label>
                            <input type="date" name="data_admissao" class="form-control apple-input">
                        </div>
                        
                        <div class="col-12 py-2"><hr class="my-0 opacity-10"></div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">Nível de Segurança</label>
                            <select name="user_type" class="form-select apple-input" required>
                                <option value="employee">Operacional (Limitado)</option>
                                <option value="admin">Administrativo (Total)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">Status Inicial</label>
                            <select name="status_colaborador" class="form-select apple-input">
                                <option value="ativo">Ativo</option>
                                <option value="afastado">Afastado</option>
                                <option value="ferias">Férias</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">Senha Temporária</label>
                            <input type="text" name="password" class="form-control apple-input" value="<?= substr(md5(time()), 0, 8) ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm">Ativar Colaborador</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    body { background-color: #fff !important; font-family: 'Outfit', sans-serif !important; }
    .display-5 { font-family: 'Outfit', sans-serif; }
    .apple-input { border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.75rem 1rem; transition: all 0.25s ease; font-size: 0.9rem; }
    .apple-input:focus { background: #fff; border-color: #000; box-shadow: 0 0 0 4px rgba(0,0,0,0.05); }
    .btn-white { background: #fff; border: 1px solid #e2e8f0; }
    .btn-white:hover { background: #f8fafc; }
    .transition-all { transition: all 0.3s ease; }
    tr:hover { background-color: #fbfbfb !important; }
    .avatar-circle { transition: transform 0.3s ease; }
    tr:hover .avatar-circle { transform: scale(1.1); }
</style>

<script>
function deleteUsuario(id) {
    if (confirm('Deseja realmente remover este colaborador do sistema? Esta ação é irreversível.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/usuarios.php/delete/${id}`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once BASE_PATH . '/includes/rodape.php'; ?>


