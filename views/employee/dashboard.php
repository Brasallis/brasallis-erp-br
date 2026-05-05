<?php include_once __DIR__ . '/../../includes/cabecalho.php'; ?>

<h1 class="mb-4">Painel Operacional</h1>

<!-- Cards de Métricas -->
<div class="row">
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card shadow-sm h-100 card-metric">
            <div class="card-body"><h5 class="card-title text-muted">Itens com Estoque Baixo</h5><h3 class="display-4 text-danger" id="metric-low-stock"><?php echo $low_stock_items ?? 0; ?></h3></div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card shadow-sm h-100 card-metric">
            <div class="card-body"><h5 class="card-title text-muted">Movimentações Hoje</h5><h3 class="display-4 text-info" id="metric-movements-today"><?php echo $movements_today ?? 0; ?></h3></div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card shadow-sm h-100 card-metric">
            <div class="card-body"><h5 class="card-title text-muted">Total de Produtos</h5><h3 class="display-4" id="metric-total-products"><?php echo $total_products ?? 0; ?></h3></div>
        </div>
    </div>
</div>

<!-- Gráfico de Atividade do Usuário -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Minhas Movimentações (Últimos 7 dias)</h5>
            </div>
            <div class="card-body">
                <canvas id="userActivityChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tabelas de Ação Rápida -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h5 class="card-title mb-0">Atenção: Estoque Baixo</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Produto</th><th>Estoque Atual</th></tr></thead>
                        <tbody id="table-low-stock-body">
                            <?php if (empty($low_stock_products)): ?>
                                <tr><td colspan="2" class="text-center text-muted">Nenhum item com estoque baixo.</td></tr>
                            <?php else: ?>
                                <?php foreach ($low_stock_products as $product): ?>
                                    <?php
                                        $status_class = ($product['quantity'] <= 0) ? 'out' : 'low';
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="status-dot <?php echo $status_class; ?>"></span>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        </td>
                                        <td><span class="badge bg-<?php echo ($status_class === 'out') ? 'danger' : 'warning'; ?>"><?php echo $product['quantity']; ?> / min: <?php echo $product['minimum_stock']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h5 class="card-title mb-0">Últimas Movimentações</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Produto</th><th>Ação</th><th>Data</th></tr></thead>
                        <tbody id="table-latest-movements-body">
                            <?php if (empty($latest_movements)): ?>
                                <tr><td colspan="3" class="text-center text-muted">Nenhuma movimentação recente.</td></tr>
                            <?php else: ?>
                                <?php foreach ($latest_movements as $movement): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($movement['product_name']); ?></strong></td>
                                        <td><span class="badge bg-<?php echo $movement['action'] === 'entrada' ? 'success' : 'warning'; ?>"><?php echo ucfirst($movement['action']); ?> (<?php echo $movement['quantity']; ?>)</span></td>
                                        <td class="text-muted"><small><?php echo date('d/m/Y H:i', strtotime($movement['created_at'])); ?></small></td>
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

<?php include_once __DIR__ . '/../../includes/rodape.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        // --- Inicialização do Gráfico ---
        const ctx = document.getElementById('userActivityChart').getContext('2d');
        const userActivityChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($user_chart_labels); ?>,
                datasets: [
                    {
                        label: 'Entradas',
                        data: <?php echo json_encode($user_chart_entradas); ?>,
                        backgroundColor: 'rgba(52, 168, 83, 0.8)',
                    },
                    {
                        label: 'Saídas',
                        data: <?php echo json_encode($user_chart_saidas); ?>,
                        backgroundColor: 'rgba(234, 67, 53, 0.8)',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // --- Lógica de Atualização em Tempo Real (Polling) ---
        function fetchDashboardData() {
            fetch('../../api/dashboard_metrics.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Erro na API:', data.error);
                        return;
                    }

                    // 1. Atualizar Métricas (Cards)
                    if (data.metrics) {
                        updateElementText('metric-low-stock', data.metrics.low_stock);
                        updateElementText('metric-movements-today', data.metrics.movements_today);
                        updateElementText('metric-total-products', data.metrics.total_products);
                    }

                    // 2. Atualizar Lista de Últimas Movimentações
                    if (data.latest_movements) {
                        updateMovementsTable(data.latest_movements);
                    }

                    // 2.1 Atualizar Lista de Estoque Baixo
                    if (data.low_stock_list) {
                        updateLowStockTable(data.low_stock_list);
                    }

                    // 3. Atualizar Gráfico
                    if (data.chart_data && userActivityChart) {
                        userActivityChart.data.labels = data.chart_data.labels;
                        userActivityChart.data.datasets[0].data = data.chart_data.entradas;
                        userActivityChart.data.datasets[1].data = data.chart_data.saidas;
                        userActivityChart.update(); // Renderizar novamente
                    }
                })
                .catch(err => console.error('Erro ao buscar dados do dashboard:', err));
        }

        // Helper para atualizar texto apenas se mudou (embora innerText lide bem com isso)
        function updateElementText(id, value) {
            const el = document.getElementById(id);
            if (el) el.innerText = value;
        }

        // Helper para recriar a tabela de movimentações
        function updateMovementsTable(movements) {
            const tbody = document.getElementById('table-latest-movements-body');
            if (!tbody) return;

            if (movements.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Nenhuma movimentação recente.</td></tr>';
                return;
            }

            let html = '';
            movements.forEach(mov => {
                const badgeClass = mov.action === 'entrada' ? 'success' : 'warning';
                html += `
                    <tr>
                        <td><strong>${mov.product_name}</strong></td>
                        <td><span class="badge bg-${badgeClass}">${mov.action_label} (${mov.quantity})</span></td>
                        <td class="text-muted"><small>${mov.formatted_date}</small></td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        // Helper para recriar a tabela de estoque baixo
        function updateLowStockTable(products) {
            const tbody = document.getElementById('table-low-stock-body');
            if (!tbody) return;

            if (products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">Nenhum item com estoque baixo.</td></tr>';
                return;
            }

            let html = '';
            products.forEach(product => {
                const statusClass = (product.quantity <= 0) ? 'out' : 'low';
                const badgeClass = (statusClass === 'out') ? 'danger' : 'warning';
                
                html += `
                    <tr>
                        <td>
                            <span class="status-dot ${statusClass}"></span>
                            <strong>${product.name}</strong>
                        </td>
                        <td><span class="badge bg-${badgeClass}">${product.quantity} / min: ${product.minimum_stock}</span></td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        // Iniciar Polling a cada 30 segundos
        setInterval(fetchDashboardData, 30000);
        
        // Opcional: Buscar imediatamente após 5s para garantir dados frescos
        // setTimeout(fetchDashboardData, 5000); 
    });
</script>
