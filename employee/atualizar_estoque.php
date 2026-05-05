<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/funcoes.php';
include_once '../includes/cabecalho.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Atualização de Estoque</h1>
    <a href="movimentacoes.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-history me-2"></i>Ver Histórico Completo
    </a>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
<?php endif; ?>

<div class="row g-4">
    <!-- Coluna da Esquerda: Formulário de Atualização -->
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5 class="card-title mb-0">Registrar Movimentação</h5></div>
            <div class="card-body p-4">
                <form id="stockUpdateForm" action="update_stock.php" method="POST">
                    <input type="hidden" name="product_id" id="productId">
                    
                    <div class="mb-3">
                        <label for="productSearch" class="form-label fw-bold">1. Busque o Produto</label>
                        <div class="position-relative">
                            <input type="text" id="productSearch" class="form-control form-control-lg" placeholder="Digite o nome ou SKU...">
                            <div id="productSuggestions" class="list-group mt-1 position-absolute w-100" style="z-index: 1000;"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="quantity" class="form-label fw-bold">2. Informe a Quantidade</label>
                        <input type="number" name="quantity" id="quantity" class="form-control form-control-lg" min="1" required disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">3. Tipo de Movimentação</label>
                        <div class="row g-2">
                            <div class="col">
                                <input type="radio" class="btn-check" name="action" id="actionEntrada" value="entrada" autocomplete="off" onchange="toggleLotFields()">
                                <label class="btn btn-outline-primary w-100 btn-lg" for="actionEntrada"><i class="fas fa-plus-circle me-2"></i>Entrada</label>
                            </div>
                            <div class="col">
                                <input type="radio" class="btn-check" name="action" id="actionSaida" value="saida" autocomplete="off" onchange="toggleLotFields()">
                                <label class="btn btn-outline-danger w-100 btn-lg" for="actionSaida"><i class="fas fa-minus-circle me-2"></i>Saída</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Opção de Venda (Apenas para Saída) -->
                    <div id="saleOption" class="mb-3" style="display: none;">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="isSale" name="is_sale" value="1">
                            <label class="form-check-label" for="isSale">Contabilizar como Venda</label>
                        </div>
                        <small class="text-muted">Se marcado, registrará o valor da venda nos relatórios.</small>
                    </div>

                    <!-- Campos de Lote (Apenas para Entrada) -->
                    <div id="lotFields" style="display: none;" class="p-3 bg-light rounded mb-3 border">
                        <h6 class="text-primary mb-3"><i class="fas fa-box-open me-2"></i>Dados do Lote</h6>
                        <div class="mb-3">
                            <label for="lotNumber" class="form-label small fw-bold">Número do Lote</label>
                            <input type="text" name="lot_number" id="lotNumber" class="form-control" placeholder="Ex: LOTE-2023-A">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="expiryDate" class="form-label small fw-bold">Validade</label>
                                <input type="date" name="expiry_date" id="expiryDate" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="supplier" class="form-label small fw-bold">Fornecedor</label>
                                <input type="text" name="supplier" id="supplier" class="form-control" placeholder="Nome do Fornecedor">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <button type="submit" class="btn btn-success btn-lg w-100" id="submitBtn" disabled>
                            <i class="fas fa-check me-2"></i>Confirmar Movimentação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Coluna da Direita: Detalhes do Produto e Histórico -->
    <div class="col-lg-7">
        <div id="productDetailsPanel" class="card shadow-sm" style="display: none;">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0" id="detailsPanelTitle">Detalhes do Produto</h5>
                <span class="badge bg-primary" id="detailsPanelStock"></span>
            </div>
            <div class="card-body">
                <div id="productInfo" class="mb-4">
                    <!-- Detalhes do produto serão inseridos aqui -->
                </div>
                <h6 class="mb-3">Últimas Movimentações</h6>
                <div id="productHistory" class="table-responsive">
                    <p class="text-muted">Nenhuma movimentação registrada recentemente.</p>
                    <!-- Tabela de histórico será inserida aqui -->
                </div>
            </div>
        </div>
        <div id="productDetailsPlaceholder" class="text-center p-5 bg-light rounded">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <p class="text-muted">Selecione um produto para ver os detalhes e o histórico de movimentações.</p>
        </div>
    </div>
</div>

<?php include_once '../includes/rodape.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('productSearch');
    const suggestionsDiv = document.getElementById('productSuggestions');
    const hiddenProductId = document.getElementById('productId');
    
    const detailsPanel = document.getElementById('productDetailsPanel');
    const detailsPlaceholder = document.getElementById('productDetailsPlaceholder');
    
    const formControls = [document.getElementById('quantity'), ...document.querySelectorAll('button[type="submit"]')];

    let debounceTimer;

    // Função para buscar produtos
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const term = searchInput.value.trim();
        if (term.length < 2) {
            suggestionsDiv.innerHTML = '';
            return;
        }
        debounceTimer = setTimeout(() => {
            const q = encodeURIComponent(term);
            // adiciona in_stock=1 para buscar apenas produtos em estoque (ajuste a API se necessário)
            fetch(`../api/search_products.php?term=${q}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    let suggestionsHTML = '';
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(p => {
                            const qty = (typeof p.quantity !== 'undefined') ? p.quantity : 'N/A';
                            const sku = p.sku || 'N/A';
                            // marca visualmente produtos sem estoque, caso a API retorne quantity = 0
                            const disabledClass = (qty === 0 || qty === '0') ? 'text-muted' : '';
                            suggestionsHTML += `<a href="#" class="list-group-item list-group-item-action ${disabledClass}" data-id="${p.id}" data-qty="${qty}">${p.name} <small class="text-muted">(SKU: ${sku})</small><span class="float-end"><small>Estoque: ${qty}</small></span></a>`;
                        });
                    } else {
                        suggestionsHTML = '<span class="list-group-item">Nenhum produto encontrado.</span>';
                    }
                    suggestionsDiv.innerHTML = suggestionsHTML;
                })
                .catch(err => {
                    console.error('Erro ao buscar produtos:', err);
                    suggestionsDiv.innerHTML = '<span class="list-group-item text-danger">Erro ao buscar produtos.</span>';
                });
        }, 300);
    });

    // Função para selecionar um produto
    suggestionsDiv.addEventListener('click', function(e) {
        e.preventDefault();
        if (e.target.classList.contains('list-group-item-action')) {
            const productId = e.target.getAttribute('data-id');
            hiddenProductId.value = productId;
            searchInput.value = e.target.textContent.split(' (SKU:')[0]; // Apenas o nome
            suggestionsDiv.innerHTML = '';
            
            formControls.forEach(c => c.disabled = false);
            document.getElementById('quantity').focus();

            fetchProductDetails(productId);
        }
    });

    // Função para buscar e exibir detalhes do produto
    function fetchProductDetails(productId) {
        detailsPlaceholder.style.display = 'none';
        detailsPanel.style.display = 'block';
        
        fetch(`../api/get_product_details.php?id=${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }
                
                // Preenche o painel de detalhes
                document.getElementById('detailsPanelTitle').textContent = data.details.name;
                document.getElementById('detailsPanelStock').textContent = `Estoque: ${data.details.quantity}`;
                
                const productInfoDiv = document.getElementById('productInfo');
                productInfoDiv.innerHTML = `
                    <p class="mb-1"><strong>SKU:</strong> ${data.details.sku || 'Não informado'}</p>
                    <p class="mb-1"><strong>Fornecedor:</strong> ${data.details.fornecedor_nome || 'Não informado'}</p>
                    <p class="mb-0"><strong>Descrição:</strong> ${data.details.description || 'Nenhuma.'}</p>
                `;

                // Preenche o histórico
                const historyDiv = document.getElementById('productHistory');
                if (data.history.length > 0) {
                    let historyTable = '<table class="table table-sm table-striped table-hover"><thead><tr><th>Data</th><th>Ação</th><th>Qtd.</th><th>Usuário</th></tr></thead><tbody>';
                    data.history.forEach(h => {
                        const actionClass = h.action === 'entrada' ? 'text-success' : 'text-danger';
                        const actionIcon = h.action === 'entrada' ? 'fa-arrow-up' : 'fa-arrow-down';
                        historyTable += `
                            <tr>
                                <td>${new Date(h.created_at).toLocaleString('pt-BR')}</td>
                                <td class="${actionClass}"><i class="fas ${actionIcon} me-2"></i>${h.action.charAt(0).toUpperCase() + h.action.slice(1)}</td>
                                <td>${h.quantity}</td>
                                <td>${h.username}</td>
                            </tr>
                        `;
                    });
                    historyTable += '</tbody></table>';
                    historyDiv.innerHTML = historyTable;
                } else {
                    historyDiv.innerHTML = '<p class="text-muted">Nenhuma movimentação registrada recentemente.</p>';
                }
            });
    }

    // Função para alternar campos de lote
    window.toggleLotFields = function() {
        const actionEntrada = document.getElementById('actionEntrada');
        const lotFields = document.getElementById('lotFields');
        const submitBtn = document.getElementById('submitBtn');
        
        if (actionEntrada.checked) {
            lotFields.style.display = 'block';
            document.getElementById('lotNumber').required = true;
        } else {
            lotFields.style.display = 'none';
            document.getElementById('lotNumber').required = false;
        }

        const actionSaida = document.getElementById('actionSaida');
        const saleOption = document.getElementById('saleOption');
        if (actionSaida.checked) {
            saleOption.style.display = 'block';
        } else {
            saleOption.style.display = 'none';
            document.getElementById('isSale').checked = false;
        }
        
        // Habilita botão se produto selecionado e quantidade preenchida
        checkFormValidity();
    };

    function checkFormValidity() {
        const qty = document.getElementById('quantity').value;
        const productId = hiddenProductId.value;
        const actionSelected = document.querySelector('input[name="action"]:checked');
        const submitBtn = document.getElementById('submitBtn');
        
        if (productId && qty > 0 && actionSelected) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    }

    document.getElementById('quantity').addEventListener('input', checkFormValidity);
    
    // Esconde sugestões ao clicar fora
    document.addEventListener('click', function(e) {
        if (!suggestionsDiv.contains(e.target) && e.target !== searchInput) {
            suggestionsDiv.innerHTML = '';
        }
    });
});
</script>
