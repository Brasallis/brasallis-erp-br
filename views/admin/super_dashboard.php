<?php
/**
 * View: SuperAdmin Master Central (Brasallis Command Center v6.0)
 */
$title = "Central Mestre - Brasallis Hub";
require_once __DIR__ . '/../../includes/cabecalho.php';
?>

<style>
    :root {
        --super-bg: #f4f7fa;
        --super-navy: #0A2647;
        --super-blue: #1a73e8;
        --super-accent: #20C997;
    }

    body { background-color: var(--super-bg); }

    .super-sidebar-header {
        background: var(--super-navy);
        padding: 30px;
        color: white;
        border-radius: 0 0 40px 0;
        margin-bottom: 30px;
    }

    .nav-tabs-custom {
        border: none;
        margin-bottom: 30px;
        background: white;
        padding: 10px;
        border-radius: 20px;
        display: inline-flex;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .nav-tabs-custom .nav-link {
        border: none;
        border-radius: 15px;
        padding: 10px 25px;
        color: #64748b;
        font-weight: 600;
        transition: 0.3s;
    }
    .nav-tabs-custom .nav-link.active {
        background: var(--super-blue);
        color: white;
    }

    .glass-card {
        background: white;
        border-radius: 24px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        padding: 24px;
        height: 100%;
    }

    .table-custom thead th {
        background: #f8fafc;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 1px;
        color: #64748b;
        padding: 15px;
        border: none;
    }
    .table-custom tbody td {
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.9rem;
    }

    .status-badge {
        padding: 5px 12px;
        border-radius: 100px;
        font-size: 0.75rem;
        font-weight: 700;
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0" style="color: var(--super-navy);">Central Mestre <span class="badge bg-primary-subtle text-primary ms-2" style="font-size: 0.8rem;">Global</span></h2>
            <p class="text-muted small m-0">Monitorando a infraestrutura Brasallis Hub em tempo real.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm"><i class="fas fa-sync-alt me-2"></i>Atualizar Dados</button>
        </div>
    </div>

    <!-- TABS NAVIGATION -->
    <ul class="nav nav-tabs nav-tabs-custom" id="superTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="dash-tab" data-bs-toggle="tab" data-bs-target="#tab-dash" type="button">Dashboard</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="companies-tab" data-bs-toggle="tab" data-bs-target="#tab-companies" type="button">Empresas (Clientes)</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="billing-tab" data-bs-toggle="tab" data-bs-target="#tab-billing" type="button">Faturamento & Cobrança</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#tab-logs" type="button">Logs do Sistema</button>
        </li>
    </ul>

    <div class="tab-content" id="superTabContent">
        
        <!-- TAB: DASHBOARD -->
        <div class="tab-pane fade show active" id="tab-dash" role="tabpanel">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="glass-card">
                        <h6 class="text-muted small fw-bold">TOTAL GMV</h6>
                        <h3 class="fw-bold m-0">R$ <?= number_format($stats['faturamento_total'], 2, ',', '.') ?></h3>
                        <div class="progress mt-3" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 75%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-card">
                        <h6 class="text-muted small fw-bold">EMPRESAS ATIVAS</h6>
                        <h3 class="fw-bold m-0"><?= $stats['empresas_ativas'] ?> / <?= $stats['total_empresas'] ?></h3>
                        <p class="small text-primary mt-2 mb-0">Taxa de atividade: <?= round(($stats['empresas_ativas']/$stats['total_empresas'])*100) ?>%</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-card">
                        <h6 class="text-muted small fw-bold">USUÁRIOS NA REDE</h6>
                        <h3 class="fw-bold m-0"><?= number_format($stats['total_usuarios'], 0, '', '.') ?></h3>
                        <p class="small text-muted mt-2 mb-0">Crescimento de +5% hoje</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-card bg-primary text-white">
                        <h6 class="small fw-bold opacity-75">SAÚDE DO SISTEMA</h6>
                        <h3 class="fw-bold m-0">ÓTIMA</h3>
                        <p class="small mt-2 mb-0 opacity-75"><i class="fas fa-check-circle me-1"></i> Todos os serviços online</p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="glass-card">
                        <h5 class="fw-bold mb-4">Volume de Transações na Plataforma</h5>
                        <canvas id="superRevenueChart" style="height: 300px;"></canvas>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="glass-card">
                        <h5 class="fw-bold mb-4">Planos Contratados</h5>
                        <?php foreach ($stats['planos'] as $p): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-secondary fw-bold"><?= ucfirst($p['ai_plan']) ?></span>
                            <span class="badge bg-primary rounded-pill"><?= $p['qtd'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: EMPRESAS -->
        <div class="tab-pane fade" id="tab-companies" role="tabpanel">
            <div class="glass-card p-0 overflow-hidden">
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-light">
                    <h5 class="fw-bold m-0">Portfólio de Clientes (Organizações)</h5>
                    <input type="text" class="form-control form-control-sm rounded-pill px-3" placeholder="Filtrar por nome ou CNPJ..." style="width: 250px;">
                </div>
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Plano</th>
                                <th>Usuários</th>
                                <th>GMV Gerado</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_companies as $c): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($c['name']) ?></div>
                                    <small class="text-muted">ID: #<?= $c['id'] ?></small>
                                </td>
                                <td><span class="status-badge bg-info-subtle text-info"><?= strtoupper($c['ai_plan']) ?></span></td>
                                <td><?= $c['total_users'] ?> / <?= $c['max_users'] ?></td>
                                <td class="fw-bold">R$ <?= number_format($c['gmv'] ?: 0, 2, ',', '.') ?></td>
                                <td><span class="status-badge bg-success-subtle text-success">Ativa</span></td>
                                <td>
                                    <button class="btn btn-sm btn-light rounded-circle shadow-sm"><i class="fas fa-edit text-primary"></i></button>
                                    <button class="btn btn-sm btn-light rounded-circle shadow-sm ms-1"><i class="fas fa-sign-in-alt text-secondary"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: COBRANÇA -->
        <div class="tab-pane fade" id="tab-billing" role="tabpanel">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="glass-card border-start border-success border-4">
                        <h1 class="fw-bold m-0"><?= $billing['ativas'] ?></h1>
                        <p class="text-muted fw-bold small">ASSINATURAS ATIVAS</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card border-start border-warning border-4">
                        <h1 class="fw-bold m-0"><?= $billing['trial'] ?></h1>
                        <p class="text-muted fw-bold small">EM PERÍODO TRIAL</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card border-start border-danger border-4">
                        <h1 class="fw-bold m-0"><?= $billing['atrasadas'] ?></h1>
                        <p class="text-muted fw-bold small">COBRANÇAS EM ATRASO</p>
                    </div>
                </div>
            </div>
            <div class="mt-4 glass-card text-center p-5">
                <i class="fas fa-file-invoice-dollar fa-4x text-muted opacity-25 mb-3"></i>
                <h5>Módulo de Faturamento Automático</h5>
                <p class="text-muted">O processamento de notas e faturas mensais ocorre todo dia 01 de cada mês.</p>
                <button class="btn btn-primary rounded-pill px-4">Configurar Gateways de Pagamento</button>
            </div>
        </div>

        <!-- TAB: LOGS -->
        <div class="tab-pane fade" id="tab-logs" role="tabpanel">
            <div class="glass-card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>Data/Hora</th>
                                <th>Usuário</th>
                                <th>Evento</th>
                                <th>Origem (IP)</th>
                                <th>Severidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="5" class="text-center p-5 text-muted">Nenhum log registrado para o período.</td></tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td><?= $log['usuario'] ?></td>
                                    <td><?= $log['mensagem'] ?></td>
                                    <td><?= $log['ip_address'] ?></td>
                                    <td><span class="badge bg-secondary"><?= $log['level'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('superRevenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= $chart_labels ?>,
            datasets: [{
                label: 'Faturamento Plataforma (R$)',
                data: <?= $chart_values ?>,
                borderColor: '#1a73e8',
                backgroundColor: 'rgba(26, 115, 232, 0.1)',
                borderWidth: 4,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/rodape.php'; ?>
