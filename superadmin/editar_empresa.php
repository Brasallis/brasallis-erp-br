<?php
// superadmin/editar_empresa.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';

// Proteção
checkSuperAdmin();

$conn = connect_db();
$id = $_GET['id'] ?? 0;
$message = '';

// Buscar dados da empresa
$stmt = $conn->prepare("SELECT * FROM empresas WHERE id = ?");
$stmt->execute([$id]);
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    header("Location: empresas.php");
    exit;
}

// Processar Atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan = $_POST['ai_plan'] ?? 'foundation';
    $status = $_POST['status'] ?? 'active';

    // Configurar limites baseado no novo plano
    require_once '../includes/planos_config.php';
    $central_config = get_planos_config();
    
    // Normalizar slug do plano
    $plano_key = (strpos($plan, 'enterprise') !== false) ? 'enterprise' : $plan;
    $plan_info = $central_config['planos'][$plano_key] ?? $central_config['planos']['foundation'];

    $ai_token_limit = $plan_info['ai_token_limit'];
    $max_users = $plan_info['users_limit'];
    $support_level = ($plano_key === 'enterprise') ? 'dedicated' : (($plano_key === 'vision') ? 'priority' : 'community');
    
    // Sincronizar Módulos Ativos com o novo plano (Full Set do novo plano)
    $active_modules_json = json_encode($plan_info['modulos']);

    // Se o SuperAdmin está ativando uma empresa que estava bloqueada ou atrasada,
    // vamos renovar a data de faturamento para +30 dias para garantir o desbloqueio real.
    $sql_update = "UPDATE empresas SET ai_plan = ?, ai_token_limit = ?, max_users = ?, support_level = ?, active_modules = ?, subscription_status = ?";
    $params = [$plano_key, $ai_token_limit, $max_users, $support_level, $active_modules_json, $status];

    if ($status === 'active' && ($empresa['subscription_status'] === 'blocked' || $empresa['subscription_status'] === 'overdue')) {
        $sql_update .= ", next_billing_at = DATE_ADD(NOW(), INTERVAL 30 DAY)";
    }

    $sql_update .= " WHERE id = ?";
    $params[] = $id;

    $update = $conn->prepare($sql_update);
    
    if ($update->execute($params)) {
        $message = "Empresa atualizada e ciclo de faturamento renovado com sucesso!";
        // Recarregar dados
        $stmt->execute([$id]);
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = "Erro ao atualizar.";
    }
}


?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empresa | Super Admin</title>
    <link rel="icon" type="image/png" href="/assets/img/pureza.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex align-items-center mb-4">
                <a href="empresas.php" class="btn btn-light rounded-circle me-3"><i class="fas fa-arrow-left"></i></a>
                <h2 class="fw-bold m-0">Editar Empresa</h2>
            </div>

            <?php if($message): ?>
                <div class="alert alert-success rounded-3 mb-4"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white p-4 border-bottom">
                    <h5 class="fw-bold m-0"><?php echo htmlspecialchars($empresa['name']); ?></h5>
                    <small class="text-muted">ID: #<?php echo $empresa['id']; ?></small>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">Plano de Assinatura</label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="ai_plan" id="plan_foundation" value="foundation" <?php echo $empresa['ai_plan'] === 'foundation' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-secondary w-100 p-3 rounded-3" for="plan_foundation">
                                        Foundation Hub
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="ai_plan" id="plan_vision" value="vision" <?php echo $empresa['ai_plan'] === 'vision' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-success w-100 p-3 rounded-3 fw-bold" for="plan_vision">
                                        Vision AI Hub
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="ai_plan" id="plan_enterprise_elite" value="enterprise_elite" <?php echo $empresa['ai_plan'] === 'enterprise_elite' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary w-100 p-3 rounded-3 fw-bold" for="plan_enterprise_elite">
                                        Enterprise Elite
                                    </label>
                                </div>
                            </div>
                        </div>


                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-muted">Limite de Tokens (Automático)</label>
                                <input type="text" class="form-control bg-light" value="<?php echo number_format($empresa['ai_token_limit']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-muted">Máximo de Usuários</label>
                                <input type="text" class="form-control bg-light" value="<?php echo $empresa['max_users']; ?>" readonly>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">Status da Assinatura</label>
                            <select name="status" class="form-select rounded-3">
                                <option value="trial" <?php echo $empresa['subscription_status'] === 'trial' ? 'selected' : ''; ?>>Degustação (Trial)</option>
                                <option value="active" <?php echo $empresa['subscription_status'] === 'active' ? 'selected' : ''; ?>>Ativo (Pago)</option>
                                <option value="overdue" <?php echo $empresa['subscription_status'] === 'overdue' ? 'selected' : ''; ?>>Atrasado</option>
                                <option value="blocked" <?php echo $empresa['subscription_status'] === 'blocked' ? 'selected' : ''; ?>>Bloqueado</option>
                            </select>
                        </div>


                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
