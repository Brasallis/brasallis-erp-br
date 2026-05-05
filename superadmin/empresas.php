<?php
// superadmin/empresas.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';

// Proteção
checkSuperAdmin();

// Conexão
$conn = connect_db();

// Listar todas as empresas
$stmt = $conn->query("SELECT * FROM empresas ORDER BY created_at DESC");
$empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

    <?php if (isset($_SESSION['msg_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['msg_success']; unset($_SESSION['msg_success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h2 class="page-title">Gestão de Empresas</h2>
            <p class="page-subtitle">Gerencie todos os tenants da plataforma</p>
        </div>
        <a href="nova_empresa.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold"><i class="fas fa-plus me-2"></i> Nova Empresa (Manual)</a>
    </div>

    <div class="premium-table border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Empresa</th>
                        <th>Plano Atual</th>
                        <th>Usuários</th>
                        <th>Utilização de Tokens</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($empresas as $emp): 
                        $uso_tokens = number_format((float)($emp['ai_tokens_used_month'] ?? 0));
                        $limite_tokens = $emp['ai_token_limit'] > 999999 ? '∞' : number_format((float)($emp['ai_token_limit'] ?? 0));
                        $percent = $emp['ai_token_limit'] > 0 ? min(100, round(($emp['ai_tokens_used_month'] / $emp['ai_token_limit']) * 100)) : 0;
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center fw-bold text-secondary" style="width: 40px; height: 40px;">
                                    <?php echo strtoupper(substr($emp['name'], 0, 1)); ?>
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($emp['name']); ?></span>
                                    <span class="text-muted" style="font-size: 0.75rem;">ID: #<?php echo $emp['id']; ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php
                            $plan_colors = [
                                'foundation' => 'secondary',
                                'vision' => 'success',
                                'enterprise_elite' => 'primary'
                            ];
                            $color = $plan_colors[$emp['ai_plan']] ?? 'light';
                            ?>
                            <span class="badge-premium bg-<?php echo $color; ?>">
                                <?php 
                                $plan_names = [
                                    'foundation' => 'Foundation Hub',
                                    'vision' => 'Vision AI Hub',
                                    'enterprise_elite' => 'Enterprise Elite'
                                ];
                                echo $plan_names[$emp['ai_plan']] ?? ucfirst($emp['ai_plan']); 
                                ?>
                            </span>
                        </td>

                        <td class="text-muted fw-bold"><?php echo $emp['max_users'] > 100 ? '∞' : $emp['max_users']; ?></td>
                        <td style="min-width: 150px;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <small class="fw-bold text-dark"><?php echo $percent; ?>%</small>
                                <small class="text-muted ms-auto" style="font-size: 0.7rem;"><?php echo $uso_tokens; ?> / <?php echo $limite_tokens; ?></small>
                            </div>
                            <div class="progress" style="height: 6px; background-color: #f1f5f9;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                        </td>
                        <td>
                            <?php
                            $status_colors = [
                                'active' => 'success',
                                'trial' => 'info',
                                'overdue' => 'warning',
                                'blocked' => 'danger'
                            ];
                            $status_labels = [
                                'active' => 'Ativo',
                                'trial' => 'Degustação',
                                'overdue' => 'Atrasado',
                                'blocked' => 'Bloqueado'
                            ];
                            $s_color = $status_colors[$emp['subscription_status']] ?? 'secondary';
                            $s_label = $status_labels[$emp['subscription_status']] ?? 'Desconhecido';
                            ?>
                            <span class="badge bg-<?php echo $s_color; ?> bg-opacity-10 text-<?php echo $s_color; ?> rounded-pill px-3">
                                <?php echo $s_label; ?>
                            </span>
                        </td>

                        <td class="text-end pe-4">
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm rounded-circle shadow-sm" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v text-muted"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 overflow-hidden p-0">
                                    <li><a class="dropdown-item py-3 px-4" href="editar_empresa.php?id=<?php echo $emp['id']; ?>"><i class="fas fa-edit me-2 text-primary"></i> Editar Empresa</a></li>
                                    <li><a class="dropdown-item py-3 px-4" href="arquiteto.php?id=<?php echo $emp['id']; ?>"><i class="fas fa-cubes me-2 text-info"></i> Arquitetura de Módulos</a></li>
                                    <li><a class="dropdown-item py-3 px-4" href="logs.php?empresa_id=<?php echo $emp['id']; ?>"><i class="fas fa-history me-2 text-muted"></i> Ver Logs de Atividade</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item py-3 px-4 text-danger" href="excluir_empresa.php?id=<?php echo $emp['id']; ?>" onclick="return confirm('ATENÇÃO: Isso removerá a empresa e todos os usuários vinculados. Confirmar?')"><i class="fas fa-trash-alt me-2"></i> Excluir Permanentemente</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>
