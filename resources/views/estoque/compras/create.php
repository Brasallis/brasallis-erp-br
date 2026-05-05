<?php
/**
 * View: estoque/compras/create
 */
$title = "Nova Entrada de Mercadoria";
require BASE_PATH . '/resources/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-navy mb-1">Registrar Entrada</h2>
        <p class="text-secondary mb-0">Lançamento manual de produtos no estoque via compra.</p>
    </div>
    <a href="/estoque/compras" class="btn btn-premium btn-outline-dark">
        <i class="fas fa-arrow-left me-2"></i>Voltar
    </a>
</div>

<form action="/estoque/compras" method="POST" id="formCompra">
    <input type="hidden" name="items_json" id="items_json">
    
    <div class="row g-4">
        <!-- Sidebar: Header Data -->
        <div class="col-lg-4">
            <div class="card-premium border-0 p-4 shadow-sm mb-4">
                <h5 class="fw-bold text-navy mb-4">Dados da Nota</h5>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Fornecedor</label>
                    <select name="supplier_id" class="form-select form-select-premium" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($fornecedores as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Data da Compra</label>
                    <input type="date" name="purchase_date" class="form-control form-control-premium" value="<?= date('Y-m-d') ?>" required>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total de Itens</span>
                    <span class="fw-bold" id="display-qty">0</span>
                </div>
                <div class="d-flex justify-content-between mb-4">
                    <span class="h5 fw-bold text-navy mb-0">Total Geral</span>
                    <span class="h5 fw-bold text-success mb-0" id="display-total">R$ 0,00</span>
                </div>

                <button type="submit" class="btn btn-premium btn-dark w-100 py-3 shadow-sm" id="btn-save" disabled>
                    <i class="fas fa-check-circle me-2"></i>Finalizar Entrada
                </button>
            </div>
        </div>

        <!-- Main: Items List -->
        <div class="col-lg-8">
            <div class="card-premium border-0 p-4 shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold text-navy mb-0">Itens do Pedido</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="addItem()">
                        <i class="fas fa-plus me-1"></i>Adicionar Item
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless align-middle" id="table-items">
                        <thead class="text-secondary small text-uppercase">
                            <tr class="border-bottom">
                                <th>Produto</th>
                                <th style="width: 120px;">Qtd</th>
                                <th style="width: 150px;">P. Custo (UN)</th>
                                <th style="width: 120px;" class="text-end">Subtotal</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Items appear here -->
                        </tbody>
                    </table>
                </div>

                <div id="empty-msg" class="text-center py-5 text-muted small">
                    <i class="fas fa-box-open fa-3x mb-3 opacity-25"></i>
                    <p>Nenhum item adicionado à lista.</p>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
let items = [];

function addItem() {
    const productId = prompt("ID do Produto (vínculo manual temporário):");
    if (!productId) return;

    items.push({
        product_id: productId,
        name: 'Produto #' + productId,
        quantity: 1,
        cost_price: 0
    });
    render();
}

function render() {
    const tbody = document.querySelector('#table-items tbody');
    const empty = document.getElementById('empty-msg');
    
    tbody.innerHTML = '';
    let total = 0;
    let qtyCount = 0;

    if (items.length === 0) {
        empty.classList.remove('d-none');
        document.getElementById('btn-save').disabled = true;
    } else {
        empty.classList.add('d-none');
        document.getElementById('btn-save').disabled = false;
        
        items.forEach((item, idx) => {
            const sub = item.quantity * item.cost_price;
            total += sub;
            qtyCount += parseFloat(item.quantity);

            const tr = document.createElement('tr');
            tr.className = 'border-bottom';
            tr.innerHTML = `
                <td>
                    <div class="fw-bold text-navy">${item.name}</div>
                    <div class="small text-muted">ID: ${item.product_id}</div>
                </td>
                <td>
                    <input type="number" step="any" class="form-control form-control-sm border-0 bg-light" value="${item.quantity}" onchange="updateItem(${idx}, 'quantity', this.value)">
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-0 pe-1">R$</span>
                        <input type="number" step="0.01" class="form-control form-control-sm border-0 bg-light" value="${item.cost_price}" onchange="updateItem(${idx}, 'cost_price', this.value)">
                    </div>
                </td>
                <td class="text-end fw-bold text-navy">R$ ${sub.toFixed(2)}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-link text-danger p-0" onclick="removeItem(${idx})"><i class="fas fa-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    document.getElementById('display-qty').innerText = qtyCount;
    document.getElementById('display-total').innerText = `R$ ${total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
    document.getElementById('items_json').value = JSON.stringify(items);
}

function updateItem(idx, key, val) {
    items[idx][key] = parseFloat(val) || 0;
    render();
}

function removeItem(idx) {
    items.splice(idx, 1);
    render();
}

// Em um sistema real, "addItem" abriria um modal de busca de produtos.
</script>

<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
