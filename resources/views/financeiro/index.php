<?php
/**
 * View: financeiro/index
 */
$title = "Painel Financeiro";
require BASE_PATH . '/resources/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-navy mb-1">Fluxo de Caixa</h2>
        <p class="text-secondary mb-0">Visão geral das entradas e saídas financeiras.</p>
    </div>
    <div class="dropdown">
        <button class="btn btn-premium btn-outline-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="fas fa-calendar me-2"></i>Período: Hoje
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2">
            <li><a class="dropdown-item rounded" href="#">Últimos 7 dias</a></li>
            <li><a class="dropdown-item rounded" href="#">Este Mês</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item rounded" href="#">Personalizado</a></li>
        </ul>
    </div>
</div>

<!-- Financial Summaries -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card-premium p-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #2C7865 0%, #399D85 100%); color: white;">
            <div class="d-flex justify-content-between mb-3">
                <div class="bg-white bg-opacity-10 p-2 rounded-3">
                    <i class="fas fa-arrow-up fa-lg"></i>
                </div>
                <div class="small opacity-75">Entradas</div>
            </div>
            <div class="h3 fw-bold mb-1">R$ <?= number_format($resumo['entradas'], 2, ',', '.') ?></div>
            <div class="small opacity-75">Total recebido hoje</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-premium p-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #AE445A 0%, #C63D2F 100%); color: white;">
            <div class="d-flex justify-content-between mb-3">
                <div class="bg-white bg-opacity-10 p-2 rounded-3">
                    <i class="fas fa-arrow-down fa-lg"></i>
                </div>
                <div class="small opacity-75">Saídas</div>
            </div>
            <div class="h3 fw-bold mb-1">R$ <?= number_format($resumo['saidas'], 2, ',', '.') ?></div>
            <div class="small opacity-75">Total pago (compras)</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-premium p-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #0A2647 0%, #1e3a5f 100%); color: white;">
            <div class="d-flex justify-content-between mb-3">
                <div class="bg-white bg-opacity-10 p-2 rounded-3">
                    <i class="fas fa-balance-scale fa-lg"></i>
                </div>
                <div class="small opacity-75">Saldo Líquido</div>
            </div>
            <div class="h3 fw-bold mb-1">R$ <?= number_format($resumo['saldo'], 2, ',', '.') ?></div>
            <div class="small opacity-75">Disponibilidade em caixa</div>
        </div>
    </div>
</div>

<!-- Activity List -->
<div class="card-premium border-0 shadow-sm">
    <div class="card-header-premium border-0 p-4">
        <h5 class="fw-bold text-navy mb-0">Movimentações Recentes</h5>
        <button class="btn btn-sm btn-light border fw-bold text-muted px-3">Ver Relatório Completo</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-secondary small text-uppercase fw-bold">
                <tr>
                    <th class="ps-4">Data / Hora</th>
                    <th>Tipo</th>
                    <th>Detalhes</th>
                    <th class="text-end pe-4">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movimentacoes as $m): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="small text-navy fw-bold"><?= date('d/m/Y', strtotime($m['data'])) ?></div>
                            <div class="small text-muted"><?= date('H:i', strtotime($m['data'])) ?></div>
                        </td>
                        <td>
                            <?php if ($m['tipo'] == 'venda'): ?>
                                <span class="badge bg-success bg-opacity-10 text-success px-3 py-1">Recebimento</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-1">Pagamento</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?= ucfirst($m['detalhe']) ?>
                        </td>
                        <td class="text-end pe-4">
                            <div class="fw-bold <?= $m['tipo'] == 'venda' ? 'text-success' : 'text-danger' ?>">
                                <?= $m['tipo'] == 'venda' ? '+' : '-' ?> R$ <?= number_format($m['valor'], 2, ',', '.') ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
