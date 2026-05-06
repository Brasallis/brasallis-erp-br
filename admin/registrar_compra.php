<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}
require_once '../includes/funcoes.php';
require_once __DIR__ . '/../src/Services/TaxIntelligenceService.php';

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// --- PROCESSAMENTO DO FORMULÁRIO (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_purchase') {
    $supplier_id = $_POST['supplier_id'];
    $purchase_date = $_POST['purchase_date'];
    $items = json_decode($_POST['items_json'] ?? '[]', true);

    if (empty($supplier_id) || empty($items)) {
        $_SESSION['message'] = 'Erro: Fornecedor e itens são obrigatórios.';
        $_SESSION['message_type'] = 'danger';
    } else {
        try {
            // 0. Proteção CSRF
            verify_csrf_token($_POST['csrf_token'] ?? '');
            
            $conn->beginTransaction();
                $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
                $file_ext = strtolower(pathinfo($_FILES['fiscal_note']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_ext, $allowed_extensions)) {
                    throw new Exception("Tipo de arquivo não permitido. Apenas PDF, JPG e PNG.");
                }

                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                $new_file_name = 'compra_' . uniqid() . '.' . $file_ext;
                if (move_uploaded_file($_FILES['fiscal_note']['tmp_name'], $upload_dir . $new_file_name)) {
                    $fiscal_note_path = 'uploads/' . $new_file_name;
                }
            }

            // 2. Calcular Total
            $total_amount = 0;
            foreach ($items as $item) {
                $total_amount += $item['quantity'] * $item['cost_price'];
            }

            // 3. Criar Compra
            $stmt = $conn->prepare("INSERT INTO compras (empresa_id, supplier_id, purchase_date, user_id, fiscal_note_path, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$empresa_id, $supplier_id, $purchase_date, $_SESSION['user_id'], $fiscal_note_path, $total_amount]);
            $purchase_id = $conn->lastInsertId();

            // 4. Processar Itens
            $taxService = new \App\Services\TaxIntelligenceService($conn);
            
            $item_stmt = $conn->prepare("INSERT INTO itens_compra (purchase_id, product_id, quantity, unit_price, stock_at_purchase) VALUES (?, ?, ?, ?, ?)");
            $update_stmt = $conn->prepare("UPDATE produtos SET quantity = quantity + ?, cost_price = ? WHERE id = ? AND empresa_id = ?");
            $hist_stmt = $conn->prepare("INSERT INTO historico_estoque (empresa_id, product_id, user_id, action, quantity, created_at, details) VALUES (?, ?, ?, 'entrada', ?, NOW(), ?)");
            $stock_query = $conn->prepare("SELECT quantity FROM produtos WHERE id = ?");

            foreach ($items as $item) {
                // Verificar se é item novo (product_id null ou 'new')
                // (Nesta versão simplificada, assumimos que usuário vinculou tudo. Se product_id for null, pulamos ou criamos on-the-fly? 
                //  Vamos assumir que o frontend obriga seleção. Se vier null, logamos erro)
                if (empty($item['product_id'])) continue;

                // Snapshot estoque
                $stock_query->execute([$item['product_id']]);
                $current_stock = $stock_query->fetchColumn();

                // Inserir Item Compra
                $item_stmt->execute([$purchase_id, $item['product_id'], $item['quantity'], $item['cost_price'], $current_stock]);

                // Atualizar Estoque
                $update_stmt->execute([$item['quantity'], $item['cost_price'], $item['product_id'], $empresa_id]);

                // Histórico
                $hist_stmt->execute([$empresa_id, $item['product_id'], $_SESSION['user_id'], $item['quantity'], "Compra #$purchase_id"]);

                // 5. Inteligência Tributária
                // Analisar e Salvar
                $ncm = $item['ncm'] ?? '';
                $cfop = $item['cfop'] ?? '';
                $valorTotalItem = $item['quantity'] * $item['cost_price'];
                
                $analise = $taxService->analyzeItem($ncm, $cfop, $valorTotalItem);
                
                $itemData = [
                    'name' => $item['name'], // Nome original
                    'ncm' => $ncm,
                    'cfop' => $cfop,
                    'cst' => $item['cst'] ?? ''
                ];
                $taxService->saveAnalysis($purchase_id, $item['product_id'], $itemData, $analise);
            }

            // 6. Integração Fiscal Automática
            require_once __DIR__ . '/../src/Services/FiscalIntegrator.php';
            $fiscalService = new \App\Services\FiscalIntegrator($conn);
            $fiscalService->createFromPurchase($purchase_id, $empresa_id, $_SESSION['user_id']);

            $conn->commit();
            // Marcar status da NF como processado se veio de upload
            // (Opcional, mas boa prática)
            
            $_SESSION['message'] = 'Compra registrada com sucesso! Análise fiscal concluída.';
            $_SESSION['message_type'] = 'success';
            header("Location: detalhes_compra.php?id=" . $purchase_id);
            exit;

        } catch (Exception $e) {
            $conn->rollBack();
            registrar_erro_sistema($e->getMessage(), 'critical', 'Purchase Module', $e->getTraceAsString());
            $_SESSION['message'] = 'Ops! Não conseguimos processar esta entrada de estoque agora. Nossa equipe já foi notificada.';
            $_SESSION['message_type'] = 'danger';
        }
    }
}

// --- DADOS PARA A VIEW ---
$suppliers_stmt = $conn->prepare("SELECT id, name FROM fornecedores WHERE empresa_id = ? ORDER BY name ASC");
$suppliers_stmt->execute([$empresa_id]);
$suppliers = $suppliers_stmt->fetchAll(PDO::FETCH_ASSOC);

$categories_stmt = $conn->prepare("SELECT id, nome FROM categorias WHERE empresa_id = ? ORDER BY nome ASC");
$categories_stmt->execute([$empresa_id]);
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

include_once '../includes/cabecalho.php';
?>

<style>
    .upload-zone {
        border: 2px dashed #e2e8f0;
        border-radius: var(--radius-lg);
        background: #f8fafc;
        transition: var(--brasallis-transition);
        min-height: 400px;
    }
    .upload-zone:hover { border-color: var(--brasallis-primary); background: var(--brasallis-primary-soft); }
    
    .item-card {
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: var(--radius-md);
        transition: var(--brasallis-transition);
        background: #fff;
    }
    .item-card.border-success { border-left: 4px solid #10b981; }
    .item-card.border-warning { border-left: 4px solid #f59e0b; }
    
    .badge-ai {
        background: var(--brasallis-primary-soft);
        color: var(--brasallis-primary);
        font-weight: 700;
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    @media (max-width: 991px) {
        .page-container { padding-bottom: 120px; }
    }
</style>

<div class="page-container">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show shadow-sm mb-4 border-0 rounded-4" role="alert">
            <i class="fas fa-info-circle me-2"></i><?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div class="page-title-group">
            <h1>Entrada de Nota Fiscal</h1>
            <p>Utilize nossa IA para extrair dados automaticamente ou lance manualmente.</p>
        </div>
        <a href="compras.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fas fa-times me-2"></i>Cancelar
        </a>
    </div>

    <form id="purchaseForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="register_purchase">
        <input type="hidden" name="items_json" id="itemsJson">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

        <div class="row g-4" style="min-height: 75vh;">
            
            <!-- LEFT PANE: REAL-TIME INVOICE VIEWER -->
            <div class="col-lg-5 position-relative">
                <div class="card shadow-sm border-0 h-100" style="position: sticky; top: 20px;">
                    <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center" style="background-color: #0A2647 !important;">
                        <h6 class="m-0 fw-bold"><i class="fas fa-file-invoice me-2"></i>Documento Original</h6>
                        <button type="button" class="btn btn-sm btn-outline-light d-none" id="btnNovaNota" onclick="resetFile()"><i class="fas fa-sync-alt me-1"></i>Trocar Nota</button>
                    </div>
                    
                    <div class="card-body p-0 d-flex flex-column align-items-center justify-content-center bg-light" id="viewerContainer" style="min-height: 60vh; overflow: hidden;">
                        
                        <!-- Upload State -->
                        <div class="upload-zone text-center p-5 w-100 h-100 d-flex flex-column justify-content-center align-items-center" id="dropZone" style="border: 2px dashed #cbd5e1; cursor: pointer; transition: 0.2s;">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted fw-bold">Anexe a Nota Fiscal</h5>
                            <p class="text-muted small mb-3">Arraste um PDF ou Imagem, ou clique aqui.</p>
                            <input type="file" name="fiscal_note" id="fiscalNoteInput" class="d-none" accept=".pdf, .jpg, .jpeg, .png">
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('fiscalNoteInput').click()">Selecionar Arquivo do Computador</button>
                        </div>

                        <!-- Viewer State -->
                        <div id="fileViewerWrapper" class="w-100 h-100 d-none bg-dark position-relative">
                            <!-- Injected iframe or img -->
                        </div>
                        
                    </div>
                    
                    <!-- AI Processing Footer -->
                    <div class="card-footer bg-white p-3 border-top text-center" id="aiActionFooter" style="display: none;">
                        <div id="aiStatus" class="d-none mb-3">
                            <div class="d-flex align-items-center justify-content-center text-primary mb-2" style="color: #2C7865 !important;">
                                <div class="spinner-border spinner-border-sm me-2"></div>
                                <strong>Analisando nota fiscal com I.A... Aguarde.</strong>
                            </div>
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 100%"></div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-success w-100 btn-lg shadow-sm" id="processAiBtn" style="background-color: #2C7865; border-color: #2C7865;">
                            <i class="fas fa-robot me-2"></i>Ler Dados Automaticamente
                        </button>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANE: DATA VALIDATION & ITEMS -->
            <div class="col-lg-7">
                
                <!-- Error Banner -->
                <div class="alert alert-danger d-none shadow-sm mb-3" id="aiErrorBanner">
                    <h6 class="fw-bold mb-1"><i class="fas fa-exclamation-circle me-2"></i>Falha na Leitura Inteligente</h6>
                    <span id="aiErrorText"></span>
                </div>

                <!-- Card 2: Header Data -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold text-dark">Propriedades da Transação</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-7 mb-3">
                                <label class="form-label small text-uppercase text-muted fw-bold">Fornecedor Identificado</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-truck"></i></span>
                                    <select name="supplier_id" class="form-select" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($suppliers as $s): ?>
                                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5 mb-3">
                                <label class="form-label small text-uppercase text-muted fw-bold">Data de Emissão</label>
                                <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold" style="color: #0A2647;">Itens Extraídos & Classificação Fiscal</h6>
                        <span class="badge bg-light text-dark border px-3 py-2" id="itemCountBadge">0 Itens</span>
                    </div>
                    
                    <!-- Manual Add Toolbar -->
                    <div class="p-3 bg-light border-bottom">
                        <div class="input-group">
                            <input type="text" id="manualSearch" class="form-control" placeholder="Adicionar item manualmente se a IA não identificou (Nome/SKU)...">
                            <button class="btn btn-outline-secondary" type="button" id="manualAddBtn"><i class="fas fa-plus"></i> Manual</button>
                        </div>
                    </div>

                    <div class="card-body p-0" style="max-height: 50vh; overflow-y: auto; background-color: #f8f9fa;">
                        <!-- Items Container -->
                        <div id="itemsContainer" class="p-3">
                            <div class="text-center text-muted py-5" id="emptyState">
                                <div class="avatar bg-white border shadow-sm mx-auto mb-3" style="width:60px; height:60px; display:flex; align-items:center; justify-content:center; border-radius:50%;">
                                    <i class="fas fa-boxes fa-2x text-muted opacity-50"></i>
                                </div>
                                <h6 class="fw-bold">Nenhum Produto Carregado</h6>
                                <p class="small mb-0">Envie a nota fiscal para o robô preencher automaticamente,<br>ou insira manualmente no botão acima.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Summary -->
                    <div class="card-footer bg-white p-4 border-top">
                        <div class="row align-items-center mb-3">
                            <div class="col-md-6">
                                <h5 class="mb-0 text-muted">Total a Lançar</h5>
                            </div>
                            <div class="col-md-6 text-end">
                                <h2 class="mb-0 fw-bold text-navy" id="displayTotal">R$ 0,00</h2>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-lg w-100 text-white fw-bold hover-lift" style="background-color: var(--navy);">
                            <i class="fas fa-check-circle me-2"></i>Registrar Título e Atualizar Estoque
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Template for Item Card (Hidden) -->
<template id="itemCardTemplate">
    <div class="card mb-3 border-0 shadow-sm item-card">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Icon & Status -->
                <div class="col-auto">
                    <div class="avatar bg-light rounded px-2 py-2 text-center" style="width: 50px;">
                        <i class="fas fa-box text-primary"></i>
                    </div>
                </div>
                
                <!-- Main Info -->
                <div class="col">
                    <h6 class="mb-1 fw-bold item-name text-truncate">Produto Nome</h6>
                    <div class="d-flex gap-2">
                        <span class="badge bg-secondary bg-opacity-10 text-secondary item-ncm">NCM: -</span>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary item-cst">CST: -</span>
                    </div>
                </div>

                <!-- Inputs -->
                <div class="col-auto" style="width: 250px;">
                    <div class="input-group input-group-sm mb-1">
                        <span class="input-group-text">Qtd</span>
                        <input type="number" class="form-control item-qty" step="any">
                        <span class="input-group-text">R$</span>
                        <input type="number" class="form-control item-cost" step="0.01">
                    </div>
                    <!-- Product Match Select -->
                    <select class="form-select form-select-sm item-match-select border-primary" style="font-size: 0.8rem;">
                        <option value="">Buscar vínculo...</option>
                    </select>
                </div>

                <!-- Remove -->
                <div class="col-auto">
                    <button type="button" class="btn btn-link text-danger p-0 item-remove"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>
            <!-- Alert Area -->
            <div class="alert alert-warning mt-2 mb-0 py-1 px-2 small d-none item-alert">
                <i class="fas fa-exclamation-triangle me-1"></i> <span class="alert-msg"></span>
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fiscalNoteInput = document.getElementById('fiscalNoteInput');
    const dropZone = document.getElementById('dropZone');
    const fileViewerWrapper = document.getElementById('fileViewerWrapper');
    const aiActionFooter = document.getElementById('aiActionFooter');
    const btnNovaNota = document.getElementById('btnNovaNota');

    const processAiBtn = document.getElementById('processAiBtn');
    const aiStatus = document.getElementById('aiStatus');
    const aiErrorBanner = document.getElementById('aiErrorBanner');
    const aiErrorText = document.getElementById('aiErrorText');
    
    const itemsContainer = document.getElementById('itemsContainer');
    const emptyState = document.getElementById('emptyState');
    const itemsJsonInput = document.getElementById('itemsJson');
    const displayTotal = document.getElementById('displayTotal');
    const itemCountBadge = document.getElementById('itemCountBadge');
    
    let items = []; // Array to store current items
    
    // Configura UI quando tiver arquivo
    function setupViewer(file) {
        const fileURL = URL.createObjectURL(file);
        
        dropZone.classList.add('d-none');
        fileViewerWrapper.classList.remove('d-none');
        aiActionFooter.style.display = 'block';
        btnNovaNota.classList.remove('d-none');
        aiErrorBanner.classList.add('d-none'); // Limpa erros antigos

        if (file.type === 'application/pdf') {
            fileViewerWrapper.innerHTML = `<iframe src="${fileURL}" class="w-100 h-100 border-0" style="min-height: 60vh;"></iframe>`;
        } else if (file.type.startsWith('image/')) {
            fileViewerWrapper.innerHTML = `<img src="${fileURL}" class="img-fluid w-100 h-100 object-fit-contain p-2" alt="Preview da Nota">`;
        } else {
            fileViewerWrapper.innerHTML = `<div class="d-flex w-100 h-100 align-items-center justify-content-center bg-dark text-white p-4 text-center">Formato não suportado para visualização rica, mas poderá ser enviado.</div>`;
        }
    }

    // Reset UI global
    window.resetFile = function() {
        fiscalNoteInput.value = '';
        dropZone.classList.remove('d-none');
        fileViewerWrapper.classList.add('d-none');
        fileViewerWrapper.innerHTML = '';
        aiActionFooter.style.display = 'none';
        btnNovaNota.classList.add('d-none');
    };

    // 1. File Selection Handler
    fiscalNoteInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            setupViewer(this.files[0]);
        }
    });

    // 2. AI Processing
    processAiBtn.addEventListener('click', function() {
        const file = fiscalNoteInput.files[0];
        if(!file) return;

        const formData = new FormData();
        formData.append('file', file);

        aiStatus.classList.remove('d-none');
        processAiBtn.disabled = true;
        aiErrorBanner.classList.add('d-none');

        fetch('../api/process_invoice_upload.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            aiStatus.classList.add('d-none');
            processAiBtn.disabled = false;

            if (data.success) {
                // Checa se a chave do Google devolveu erro como dado
                if (data.data.error) {
                    showAiError(data.data.error);
                } else {
                    handleAiResult(data.data);
                }
            } else {
                showAiError(data.error || 'Erro desconhecido de execução.');
            }
        })
        .catch(err => {
            aiStatus.classList.add('d-none');
            processAiBtn.disabled = false;
            showAiError('Falha de Rede/Conexão: ' + err.message);
        });
    });

    function showAiError(msg) {
        aiErrorText.textContent = msg;
        aiErrorBanner.classList.remove('d-none');
    }

    // 3. Handle AI Data
    function handleAiResult(data) {
        // Fill Header
        if (data.data_emissao) {
            document.querySelector('input[name="purchase_date"]').value = data.data_emissao;
        }
        
        // Auto-select supplier (Match by CNPJ or Name)
        if (data.cnpj_fornecedor) {
            const select = document.querySelector('select[name="supplier_id"]');
            // Mock matching: in production we would match actual CNPJ field if it exists
            // Let's search for the name as fallback
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].text.toLowerCase().includes(data.nome_fornecedor.toLowerCase())) {
                    select.selectedIndex = i;
                    break;
                }
            }
        }

        // Add Items
        if (data.itens && data.itens.length > 0) {
            data.itens.forEach(aiItem => {
                addItem({
                    name: aiItem.descricao,
                    quantity: parseFloat(aiItem.quantidade) || 1,
                    cost_price: parseFloat(aiItem.valor_unitario) || 0,
                    ncm: aiItem.ncm || '',
                    cst: aiItem.cst_csosn || '',
                    cfop: aiItem.cfop || '',
                    product_id: null, // Needs matching
                    temp_id: Date.now() + Math.random() // Unique ID for UI handling
                });
            });
            // Try to auto-match items
            autoMatchItems();
        }
    }

    // 4. Add Item Logic
    function addItem(item) {
        items.push(item);
        render();
    }

    // 5. Render List
    function render() {
        itemsContainer.innerHTML = '';
        let total = 0;

        if (items.length === 0) {
            emptyState.style.display = 'block';
            itemCountBadge.textContent = '0 Itens';
            displayTotal.textContent = 'R$ 0,00';
            itemsJsonInput.value = '';
            return;
        }
        emptyState.style.display = 'none';

        const template = document.getElementById('itemCardTemplate');

        items.forEach((item, index) => {
            const clone = template.content.cloneNode(true);
            const card = clone.querySelector('.item-card');
            
            // Populate
            card.querySelector('.item-name').textContent = item.name;
            card.querySelector('.item-qty').value = item.quantity;
            card.querySelector('.item-cost').value = item.cost_price;
            
            const ncmBadge = card.querySelector('.item-ncm');
            ncmBadge.textContent = item.ncm ? `NCM: ${item.ncm}` : 'NCM: -';
            if(item.ncm) ncmBadge.classList.replace('text-secondary', 'text-primary');

            const cstBadge = card.querySelector('.item-cst');
            cstBadge.textContent = item.cst ? `CST: ${item.cst}` : 'CST: -';

            // Events
            card.querySelector('.item-qty').addEventListener('change', (e) => { items[index].quantity = parseFloat(e.target.value); updateStats(); });
            card.querySelector('.item-cost').addEventListener('change', (e) => { items[index].cost_price = parseFloat(e.target.value); updateStats(); });
            card.querySelector('.item-remove').addEventListener('click', () => { items.splice(index, 1); render(); });

            // Product Match Select
            const select = card.querySelector('.item-match-select');
            
            // If already matched, show it
            if (item.product_id) {
                const opt = document.createElement('option');
                opt.value = item.product_id;
                opt.text = item.matched_name || 'Produto Vinculado';
                opt.selected = true;
                select.add(opt);
                card.classList.add('border-success'); // Visual check
                // Enable Validation badge
                card.querySelector('.avatar').innerHTML = '<i class="fas fa-check text-success"></i>';
            } else {
                 card.classList.add('border-warning'); // Visual warning
                 // Fill with top suggestions (Mocked here, would need async fetch per item which is heavy. 
                 // Better: One search bar or Global fetch. For now, let's allow manual search on click or simple populate)
            }
            
            // Allow manual matching via click on select (Mock up for now, complex to implement fully inline without Select2)
            // Ideally, we run `autoMatchItems` which populates this.

            itemsContainer.appendChild(clone);
            total += (item.quantity * item.cost_price);
        });

        itemCountBadge.textContent = `${items.length} Itens`;
        displayTotal.textContent = `R$ ${total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
        itemsJsonInput.value = JSON.stringify(items);
    }
    
    function updateStats() {
       let total = 0;
       items.forEach(i => total += (i.quantity * i.cost_price));
       displayTotal.textContent = `R$ ${total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
       itemsJsonInput.value = JSON.stringify(items);
    }

    // 6. Auto Match Logic (Mocked logic for simplicity request)
    // In real world: Loop items, fetch search API, update item.product_id
    function autoMatchItems() {
       items.forEach((item, idx) => {
           if(item.product_id) return;
           
           // Fetch match
           fetch(`../api/search_products.php?term=${encodeURIComponent(item.name)}`)
            .then(res => res.json())
            .then(data => {
                if(data && data.length > 0) {
                    // Match found!
                    items[idx].product_id = data[0].id;
                    items[idx].matched_name = data[0].name;
                    items[idx].cost_price = parseFloat(data[0].cost_price); // Update cost? Maybe explicit user action better.
                    render(); // Re-render to show green state
                }
            });
       });
    }

    // Manual Add Support
    const manualAddBtn = document.getElementById('manualAddBtn');
    manualAddBtn.addEventListener('click', () => {
        addItem({
            name: 'Item Manual',
            quantity: 1,
            cost_price: 0,
            ncm: '',
            product_id: null
        });
    });

    // Form Submit
    document.getElementById('purchaseForm').addEventListener('submit', (e) => {
        if(items.length === 0) {
            e.preventDefault();
            alert('Adicione itens.');
            return;
        }
        // Check unlinked
        const unlinked = items.filter(i => !i.product_id);
        if(unlinked.length > 0) {
            e.preventDefault();
            alert('Atenção: Existem itens não vinculados a produtos cadastrados. Por favor, vincule-os (automaticamente ou manualmente) antes de salvar.');
            // In a real app we would allow creating new products on fly here.
        }
    });

});
</script>
