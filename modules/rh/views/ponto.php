<?php
// modules/rh/views/ponto.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

// Check Auth & Permission
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }

// Permissões do RH
// Admins ou usuários com 'escrita' em RH podem ver tudo. 
// Funcionários comuns só podem ver/bater o próprio ponto.
$is_admin_rh = check_permission('rh', 'escrita') || $_SESSION['user_type'] === 'admin';
$conn = connect_db();
$empresa_id = $_SESSION['user_type'] === 'super_admin' ? 1 : $_SESSION['empresa_id'];
$user_id = $_SESSION['user_id'];
$hoje = date('Y-m-d');
$hora_atual = date('H:i:s');

// --- Ação: Bater Ponto ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bater_ponto') {
    try {
        // Find today's record
        $stmt = $conn->prepare("SELECT * FROM rh_ponto WHERE usuario_id = ? AND data_registro = ?");
        $stmt->execute([$user_id, $hoje]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            // Check-in (Entrada)
            $stmtInsert = $conn->prepare("INSERT INTO rh_ponto (empresa_id, usuario_id, data_registro, hora_entrada) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$empresa_id, $user_id, $hoje, $hora_atual]);
            header("Location: ponto.php?msg=entrada_ok");
            exit;
        } else {
            // Update cycle
            if (is_null($registro['hora_saida_pausa'])) {
                $stmtUp = $conn->prepare("UPDATE rh_ponto SET hora_saida_pausa = ? WHERE id = ?");
                $stmtUp->execute([$hora_atual, $registro['id']]);
                header("Location: ponto.php?msg=pausa_ok");
                exit;
            } elseif (is_null($registro['hora_retorno_pausa'])) {
                $stmtUp = $conn->prepare("UPDATE rh_ponto SET hora_retorno_pausa = ? WHERE id = ?");
                $stmtUp->execute([$hora_atual, $registro['id']]);
                header("Location: ponto.php?msg=retorno_ok");
                exit;
            } elseif (is_null($registro['hora_saida'])) {
                $stmtUp = $conn->prepare("UPDATE rh_ponto SET hora_saida = ? WHERE id = ?");
                $stmtUp->execute([$hora_atual, $registro['id']]);
                header("Location: ponto.php?msg=saida_ok");
                exit;
            } else {
                header("Location: ponto.php?msg=jornada_completa");
                exit;
            }
        }
    } catch (Exception $e) {
        $error = "Erro ao registrar ponto: " . $e->getMessage();
    }
}

// Fetch Current User's Record for Today
try {
    $stmtMeuPonto = $conn->prepare("SELECT * FROM rh_ponto WHERE usuario_id = ? AND data_registro = ?");
    $stmtMeuPonto->execute([$user_id, $hoje]);
    $meu_ponto_hoje = $stmtMeuPonto->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $meu_ponto_hoje = false; }


// Fetch Records (List)
$data_filtro = isset($_GET['data']) ? $_GET['data'] : $hoje;
try {
    if ($is_admin_rh) {
        // Admin vê de todos
        $stmtList = $conn->prepare("
            SELECT r.*, u.username as nome, u.username 
            FROM rh_ponto r 
            JOIN usuarios u ON r.usuario_id = u.id 
            WHERE r.empresa_id = ? AND r.data_registro = ? 
            ORDER BY u.username ASC
        ");
        $stmtList->execute([$empresa_id, $data_filtro]);
    } else {
        // Funcionário vê os próprios dos últimos 30 dias
        $stmtList = $conn->prepare("
            SELECT r.*, u.username as nome, u.username 
            FROM rh_ponto r 
            JOIN usuarios u ON r.usuario_id = u.id 
            WHERE r.usuario_id = ? AND r.data_registro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY r.data_registro DESC
        ");
        $stmtList->execute([$user_id]);
    }
    $registros = $stmtList->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $registros = [];
}

require_once __DIR__ . '/../../../includes/cabecalho.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-navy mb-1"><i class="fas fa-clock me-2 text-primary"></i>Controle de Ponto</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="../../../admin/painel_admin.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Recursos Humanos</a></li>
                    <li class="breadcrumb-item active">Ponto Eletrônico</li>
                </ol>
            </nav>
        </div>
        <div>
            <?php if ($is_admin_rh): ?>
            <form method="GET" class="d-flex gx-2">
                <input type="date" name="data" class="form-control" value="<?= htmlspecialchars($data_filtro) ?>" onchange="this.form.submit()">
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Feedback Messages -->
    <?php if (isset($_GET['msg'])): ?>
        <?php 
            $msgMap = [
                'entrada_ok' => 'Entrada registrada com sucesso!',
                'pausa_ok' => 'Início de pausa registrado!',
                'retorno_ok' => 'Retorno de pausa registrado!',
                'saida_ok' => 'Jornada encerrada com sucesso!',
                'jornada_completa' => 'Sua jornada diária já está completa.'
            ];
            if (isset($msgMap[$_GET['msg']])):
        ?>
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= $msgMap[$_GET['msg']] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (isset($error)): ?>
         <div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Interactive Time Clock (Bater Ponto) -->
    <div class="card border-0 shadow-sm mb-4 bg-navy text-white text-center py-5" style="border-radius: 16px;">
        <div class="card-body">
            <h5 class="text-white-50 mb-4 fw-bold text-uppercase">Relógio Ponto - <?= date('d/m/Y') ?></h5>
            <h1 class="display-3 fw-bold mb-4" id="digitalClock"><?= date('H:i:s') ?></h1>
            
            <form method="POST">
                <input type="hidden" name="action" value="bater_ponto">
                
                <?php if (!$meu_ponto_hoje): ?>
                    <button type="submit" class="btn btn-success btn-lg rounded-pill px-5 py-3 fw-bold shadow">
                        <i class="fas fa-sign-in-alt me-2"></i>Registrar Entrada
                    </button>
                    <p class="mt-3 text-white-50 small">Sua jornada será iniciada ao clicar.</p>

                <?php elseif (empty($meu_ponto_hoje['hora_saida_pausa'])): ?>
                    <button type="submit" class="btn btn-warning btn-lg rounded-pill px-5 py-3 fw-bold shadow text-dark">
                        <i class="fas fa-mug-hot me-2"></i>Iniciar Pausa / Almoço
                    </button>
                    <p class="mt-3 text-white-50 small">Entrada registrada às <?= $meu_ponto_hoje['hora_entrada'] ?></p>

                <?php elseif (empty($meu_ponto_hoje['hora_retorno_pausa'])): ?>
                    <button type="submit" class="btn btn-info btn-lg rounded-pill px-5 py-3 fw-bold shadow text-white">
                        <i class="fas fa-undo me-2"></i>Retornar da Pausa
                    </button>
                    <p class="mt-3 text-white-50 small">Pausa iniciada às <?= $meu_ponto_hoje['hora_saida_pausa'] ?></p>

                <?php elseif (empty($meu_ponto_hoje['hora_saida'])): ?>
                    <button type="submit" class="btn btn-danger btn-lg rounded-pill px-5 py-3 fw-bold shadow">
                        <i class="fas fa-sign-out-alt me-2"></i>Encerrar Jornada (Saída)
                    </button>
                    <p class="mt-3 text-white-50 small">Retorno registrado às <?= $meu_ponto_hoje['hora_retorno_pausa'] ?></p>

                <?php else: ?>
                    <button type="button" class="btn btn-secondary btn-lg rounded-pill px-5 py-3 fw-bold" disabled>
                        <i class="fas fa-check-double me-2"></i>Jornada Concluída
                    </button>
                    <p class="mt-3 text-success fw-bold small">Jornada diária finalizada com sucesso!</p>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Registros Table -->
    <h5 class="fw-bold text-navy mb-3">
        <?= $is_admin_rh ? "Registros de Ponto - " . date('d/m/Y', strtotime($data_filtro)) : "Meus Últimos Registros" ?>
    </h5>
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="bg-light">
                    <tr>
                        <?php if($is_admin_rh): ?>
                        <th class="py-3 px-4 text-start text-secondary text-uppercase" style="font-size: 0.8rem;">Colaborador</th>
                        <?php else: ?>
                        <th class="py-3 px-4 text-start text-secondary text-uppercase" style="font-size: 0.8rem;">Data</th>
                        <?php endif; ?>
                        
                        <th class="py-3 px-2 text-secondary text-uppercase" style="font-size: 0.8rem;">Entrada</th>
                        <th class="py-3 px-2 text-secondary text-uppercase" style="font-size: 0.8rem;">Início Pausa</th>
                        <th class="py-3 px-2 text-secondary text-uppercase" style="font-size: 0.8rem;">Fim Pausa</th>
                        <th class="py-3 px-2 text-secondary text-uppercase" style="font-size: 0.8rem;">Saída</th>
                        <th class="py-3 px-4 text-secondary text-uppercase" style="font-size: 0.8rem;">Horas Trabalhadas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                        <tr><td colspan="<?= $is_admin_rh ? 6 : 6 ?>" class="text-center py-5 text-muted">Nenhum registro encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach($registros as $r): 
                            // Calc approx worked hours
                            $total_worked = '-';
                            if(!empty($r['hora_entrada']) && !empty($r['hora_saida'])) {
                                $t_entr = strtotime($r['hora_entrada']);
                                $t_sai = strtotime($r['hora_saida']);
                                $worked_sec = $t_sai - $t_entr;

                                if(!empty($r['hora_saida_pausa']) && !empty($r['hora_retorno_pausa'])) {
                                    $p_sai = strtotime($r['hora_saida_pausa']);
                                    $p_ret = strtotime($r['hora_retorno_pausa']);
                                    $worked_sec -= ($p_ret - $p_sai);
                                }
                                $h = floor($worked_sec / 3600);
                                $m = floor(($worked_sec / 60) % 60);
                                $total_worked = sprintf("%02dh %02dm", $h, $m);
                            }
                        ?>
                        <tr>
                            <?php if($is_admin_rh): ?>
                            <td class="py-3 px-4 text-start fw-bold text-dark"><?= htmlspecialchars($r['nome'] ?: $r['username']) ?></td>
                            <?php else: ?>
                            <td class="py-3 px-4 text-start fw-bold text-dark"><?= date('d/m/Y', strtotime($r['data_registro'])) ?></td>
                            <?php endif; ?>
                            
                            <td class="py-3 px-2 text-primary fw-bold"><?= $r['hora_entrada'] ?: '-' ?></td>
                            <td class="py-3 px-2 text-warning fw-bold"><?= $r['hora_saida_pausa'] ?: '-' ?></td>
                            <td class="py-3 px-2 text-info fw-bold"><?= $r['hora_retorno_pausa'] ?: '-' ?></td>
                            <td class="py-3 px-2 text-danger fw-bold"><?= $r['hora_saida'] ?: '-' ?></td>
                            <td class="py-3 px-4 fw-bold text-success"><?= $total_worked ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Live clock script
    function updateClock() {
        const now = new Date();
        const d = String(now.getHours()).padStart(2, '0') + ':' + 
                  String(now.getMinutes()).padStart(2, '0') + ':' + 
                  String(now.getSeconds()).padStart(2, '0');
        document.getElementById('digitalClock').textContent = d;
    }
    setInterval(updateClock, 1000);
</script>

<style>
    .text-navy { color: #0A2647; }
    .bg-navy { background-color: #0A2647; }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
