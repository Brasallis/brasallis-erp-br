<?php
// admin/vendas.php - FLUXO DE CAIXA E HISTÓRICO
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../bootstrap.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    header("Location: ../login.php");
    exit();
}

use App\Repository\VendaRepository;
use App\Core\Database;

$empresa_id = $_SESSION['empresa_id'];
$vendaRepo = new VendaRepository(Database::getInstance(), $empresa_id);

// Filtros
$date_filter = $_GET['data'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

// Controle de Acesso:
// Administradores e Super Admins veem todas as vendas. Funcionários/Operadores veem apenas as suas (seu próprio caixa).
$user_type = $_SESSION['user_type'] ?? 'employee';
$filter_user_id = ($user_type === 'admin' || $user_type === 'super_admin') ? null : $_SESSION['user_id'];

// Busca de Vendas via Repositório
$vendas = $vendaRepo->getVendasByDate($date_filter, $search, $filter_user_id);

include_once '../includes/cabecalho.php';
?>

<style>
    .caixa-header { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 30px; }
    .venda-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #f1f1f1; transition: all 0.2s; margin-bottom: 15px; }
    .venda-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
    .bg-closed { background: #e6fffa; color: #234e52; }
</style>

<div class="container-fluid">
    <div class="caixa-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h3 class="fw-bold m-0"><i class="fas fa-cash-register text-primary me-2"></i> Fluxo de Caixa</h3>
            <p class="text-muted m-0">Gerencie e consulte todas as vendas realizadas</p>
        </div>
        <form class="d-flex gap-2">
            <input type="date" name="data" class="form-control rounded-pill border-light bg-light px-4" value="<?= $date_filter ?>" onchange="this.form.submit()">
            <input type="text" name="search" class="form-control rounded-pill border-light bg-light px-4" placeholder="Buscar ID da Venda..." value="<?= $search ?>">
            <button class="btn btn-primary rounded-pill px-4">Filtrar</button>
        </form>
    </div>

    <?php if (empty($vendas)): ?>
        <div class="text-center py-5">
            <img src="https://cdn-icons-png.flaticon.com/512/4076/4076432.png" width="120" class="opacity-25 mb-4">
            <h4 class="text-muted">Nenhuma venda encontrada para este filtro.</h4>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($vendas as $v): ?>
                <div class="col-12 col-xl-6">
                    <div class="venda-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="text-muted small fw-bold">VENDA #<?= str_pad((string)(string)$v['id'], 6, '0', STR_PAD_LEFT) ?></span>
                                <h5 class="fw-bold m-0 text-navy">R$ <?= number_format($v['total_amount'], 2, ',', '.') ?></h5>
                                <p class="small text-muted m-0 mt-1"><i class="far fa-clock me-1"></i> <?= date('d/m/Y H:i', strtotime($v['created_at'])) ?> • Operador: <?= $v['username'] ?></p>
                            </div>
                            <span class="status-badge bg-closed">Venda Concluída</span>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button class="btn btn-light btn-sm rounded-pill px-3 fw-bold border" onclick="viewDetails(<?= $v['id'] ?>)">
                                <i class="fas fa-list me-1"></i> Ver Itens
                            </button>
                            <button class="btn btn-dark btn-sm rounded-pill px-3 fw-bold" onclick="reprintReceipt(<?= $v['id'] ?>)">
                                <i class="fas fa-print me-1"></i> Reimprimir Cupom
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Detalhes -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold m-0">Itens da Venda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="detailsContent">
                <!-- Conteúdo via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
function reprintReceipt(id) {
    const win = window.open('../employee/imprimir_venda.php?id=' + id, '_blank', 'width=400,height=600');
    win.focus();
}

function viewDetails(id) {
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    document.getElementById('detailsContent').innerHTML = '<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    modal.show();

    // Podemos criar uma API simples para os itens ou carregar aqui
    fetch(`../api/get_sale_details.php?id=${id}`)
    .then(r => r.json())
    .then(data => {
        let html = '<div class="list-group list-group-flush">';
        data.forEach(i => {
            html += `
                <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold">${i.name}</div>
                        <div class="small text-muted">${i.quantity} un x R$ ${parseFloat(i.unit_price).toFixed(2)}</div>
                    </div>
                    <div class="fw-bold">R$ ${(i.quantity * i.unit_price).toFixed(2)}</div>
                </div>
            `;
        });
        html += '</div>';
        document.getElementById('detailsContent').innerHTML = html;
    });
}
</script>

<?php include_once '../includes/rodape.php'; ?>
