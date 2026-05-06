<?php
// employee/pdv.php — PDV NEXUS v2.0 (Google-Grade UX)
// Layout adaptativo: Desktop Split | Tablet Stack | Mobile App Nativo

require_once '../includes/funcoes.php';
checkAuth();

$title = "Caixa — PDV Nexus";
$hide_bottom_nav = true;
$hide_sidebar = true;

// Buscar categorias dinâmicas do banco
$conn_pdv = connect_db();
$categories_stmt = $conn_pdv->prepare("SELECT id, nome FROM categorias WHERE empresa_id = ? ORDER BY nome ASC");
$categories_stmt->execute([$_SESSION['empresa_id']]);
$pdv_categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

include_once '../includes/cabecalho.php';
?>
<style>
    /* =====================================================
       PDV NEXUS — RESET & APP SHELL
       ===================================================== */
    .brasallis-sidebar,
    .brasallis-bottom-nav,
    .brasallis-topbar { display: none !important; }
    .brasallis-main { padding: 0 !important; margin: 0 !important; }
    body {
        overflow: hidden;
        height: 100dvh;
        background: #f0f4f9;
        font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif;
        -webkit-tap-highlight-color: transparent;
    }
</style>
<link rel="stylesheet" href="<?= $base_url ?>assets/css/pdv_nexus.css?v=<?= filemtime(__DIR__.'/../assets/css/pdv_nexus.css') ?>">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- =====================================================
     PDV NEXUS — APP SHELL
     ===================================================== -->
<div class="pdv-app" id="pdv-app">

    <!-- ═══════════════════════════════════════════════════
         TOP APP BAR (Mobile/Tablet only — Google M3)
         ═══════════════════════════════════════════════════ -->
    <header class="pdv-top-bar" id="pdv-topbar">
        <div class="pdv-topbar-left">
            <a href="../admin/painel_admin.php" class="pdv-back-btn" title="Voltar ao Hub">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="pdv-topbar-title">
                <span class="pdv-title-label">Brasallis PDV</span>
                <span class="pdv-operator-name"><?= htmlspecialchars($_SESSION['user_nome'] ?? $_SESSION['username'] ?? 'Operador') ?></span>
            </div>
        </div>
        <div class="pdv-topbar-right">
            <div class="pdv-cart-fab" id="topbar-cart-btn" onclick="PDV.toggleSheet()">
                <i class="fas fa-shopping-cart"></i>
                <span class="pdv-cart-badge" id="topbar-cart-badge">0</span>
            </div>
        </div>
    </header>

    <!-- ═══════════════════════════════════════════════════
         LEFT COLUMN — Search + Catalog (Desktop = persistent)
         ═══════════════════════════════════════════════════ -->
    <div class="pdv-catalog-col" id="pdv-catalog-col">

        <!-- Search Bar (M3 Search) -->
        <div class="pdv-search-section">
            <div class="pdv-search-bar" id="pdv-search-bar-wrapper">
                <i class="fas fa-search pdv-search-icon"></i>
                <input
                    type="text"
                    id="pdv-search"
                    class="pdv-search-input"
                    placeholder="Buscar produto por nome ou código..."
                    autocomplete="off"
                    inputmode="search"
                    autofocus>
                <button class="pdv-search-clear" id="search-clear-btn" onclick="PDV.clearSearch()" style="display:none">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
        </div>

        <!-- Category Chips Scroller (M3 Style) -->
        <div class="pdv-chips-row" id="pdv-chips-row">
            <div class="pdv-chip active" data-cat="all" onclick="PDV.filterCategory('all', this)">
                <i class="fas fa-border-all me-1"></i>Todos
            </div>
            <?php foreach ($pdv_categories as $cat): ?>
            <div class="pdv-chip" data-cat="<?= $cat['id'] ?>" onclick="PDV.filterCategory('<?= $cat['id'] ?>', this)">
                <?= htmlspecialchars($cat['nome']) ?>
            </div>
            <?php endforeach; ?>
            <?php if (empty($pdv_categories)): ?>
            <div class="pdv-chip" data-cat="all">Geral</div>
            <?php endif; ?>
        </div>

        <!-- Product Grid / Results -->
        <div class="pdv-product-grid" id="pdv-results">
            <!-- Estado inicial: vazio com placeholder -->
            <div class="pdv-empty-state" id="pdv-empty-state">
                <div class="pdv-empty-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>Busque um produto</h3>
                <p>Digite o nome ou código acima para encontrar itens rapidamente.</p>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         RIGHT COLUMN — Cart (Desktop: always visible)
         ═══════════════════════════════════════════════════ -->
    <aside class="pdv-cart-col" id="pdv-cart-col">
        <div class="pdv-cart-panel">

            <!-- Cart Header -->
            <div class="pdv-cart-header">
                <div class="pdv-cart-header-title">
                    <h2>Carrinho</h2>
                    <span class="pdv-cart-count" id="cart-count-badge">0 itens</span>
                </div>
                <!-- Customer Quick ID -->
                <div class="pdv-customer-input-wrap">
                    <i class="fas fa-user-circle"></i>
                    <input type="text" id="customer-search" placeholder="Cliente (opcional)..." autocomplete="off">
                    <div id="customer-results" class="pdv-customer-dropdown"></div>
                    <div id="selected-customer-info"></div>
                </div>
            </div>

            <!-- Cart Items List -->
            <div class="pdv-cart-items" id="cart-container">
                <div class="pdv-cart-empty" id="cart-empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Carrinho vazio</p>
                </div>
            </div>

            <!-- Cart Footer -->
            <div class="pdv-cart-footer">
                <!-- Discount Row -->
                <div class="pdv-discount-row">
                    <span class="pdv-footer-label"><i class="fas fa-tag me-1"></i>Desconto</span>
                    <div class="pdv-discount-input-wrap">
                        <span>R$</span>
                        <input type="number" id="cart-discount-input" value="0.00" step="0.01" min="0">
                    </div>
                </div>

                <!-- Total Row -->
                <div class="pdv-total-row">
                    <span>TOTAL</span>
                    <span class="pdv-total-amount" id="cart-total">R$ 0,00</span>
                </div>

                <!-- Checkout Button -->
                <button class="pdv-checkout-btn" id="btn-open-payment" disabled onclick="PDV.openPaymentModal()">
                    <i class="fas fa-check-circle me-2"></i>
                    <span>Finalizar Venda</span>
                    <kbd class="pdv-kbd">F9</kbd>
                </button>

                <button class="pdv-clear-btn" onclick="PDV.clearCart()">
                    <i class="fas fa-trash-alt me-1"></i>Limpar Carrinho
                </button>
            </div>
        </div>
    </aside>

    <!-- ═══════════════════════════════════════════════════
         MOBILE BOTTOM SHEET (Cart — Google Bottom Sheet)
         ═══════════════════════════════════════════════════ -->
    <div class="pdv-sheet-backdrop" id="sheet-backdrop" onclick="PDV.collapseSheet()"></div>

    <div class="pdv-sheet" id="pdv-sheet">
        <!-- Sheet Handle Bar -->
        <div class="pdv-sheet-handle-bar" id="sheet-handle" ontouchstart="PDV.sheetDragStart(event)">
            <div class="pdv-sheet-handle"></div>
        </div>

        <!-- Sheet Collapsed Peek: Total + Checkout Button -->
        <div class="pdv-sheet-peek" onclick="PDV.toggleSheet()">
            <div class="pdv-sheet-peek-info">
                <span class="pdv-sheet-label">Total</span>
                <span class="pdv-sheet-total" id="sheet-total">R$ 0,00</span>
            </div>
            <button class="pdv-sheet-checkout-btn" id="sheet-checkout-btn" onclick="event.stopPropagation(); PDV.openPaymentModal()">
                Pagar <span class="pdv-sheet-qty-badge" id="sheet-qty">0</span>
            </button>
        </div>

        <!-- Sheet Full Content (when expanded) -->
        <div class="pdv-sheet-body" id="sheet-body">
            <div class="pdv-sheet-section-title">Seu Pedido</div>
            <div id="sheet-cart-items" class="pdv-sheet-items"></div>

            <!-- Summary -->
            <div class="pdv-sheet-summary">
                <div class="pdv-sheet-summary-row">
                    <span>Desconto</span>
                    <span class="text-danger" id="sheet-discount">- R$ 0,00</span>
                </div>
                <div class="pdv-sheet-summary-row pdv-sheet-total-row">
                    <span>Total Final</span>
                    <span id="sheet-final-total">R$ 0,00</span>
                </div>
            </div>

            <button class="pdv-sheet-full-checkout" onclick="PDV.openPaymentModal()">
                <i class="fas fa-lock me-2"></i>Finalizar Pagamento
            </button>
            <button class="pdv-sheet-continue" onclick="PDV.collapseSheet()">
                Continuar Comprando
            </button>
        </div>
    </div>

</div><!-- /.pdv-app -->


<!-- ═══════════════════════════════════════════════════════
     PAYMENT MODAL — Material Design Bottom Sheet Style
     ═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered pdv-payment-dialog">
        <div class="modal-content pdv-payment-content">

            <!-- Header -->
            <div class="pdv-payment-header">
                <button type="button" class="pdv-payment-close" data-bs-dismiss="modal">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="pdv-payment-title-group">
                    <span class="pdv-payment-label">TOTAL A PAGAR</span>
                    <h1 class="pdv-payment-amount" id="modal-total-sale">R$ 0,00</h1>
                </div>
            </div>

            <!-- Body -->
            <div class="pdv-payment-body">

                <!-- Fast Payment Tiles (M3 Card Style) -->
                <div class="pdv-payment-section-label">Pagamento Rápido</div>
                <div class="pdv-payment-methods-grid">
                    <button class="pdv-method-tile" onclick="PDV.fastCheckout('dinheiro')" id="method-dinheiro">
                        <div class="pdv-method-icon" style="background:#dcfce7; color:#16a34a">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <span>Dinheiro</span>
                    </button>
                    <button class="pdv-method-tile" onclick="PDV.fastCheckout('pix')" id="method-pix">
                        <div class="pdv-method-icon" style="background:#ede9fe; color:#7c3aed">
                            <i class="fab fa-pix"></i>
                        </div>
                        <span>PIX</span>
                    </button>
                    <button class="pdv-method-tile" onclick="PDV.fastCheckout('cartao_debito')" id="method-debito">
                        <div class="pdv-method-icon" style="background:#dbeafe; color:#1d4ed8">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <span>Débito</span>
                    </button>
                    <button class="pdv-method-tile" onclick="PDV.fastCheckout('cartao_credito')" id="method-credito">
                        <div class="pdv-method-icon" style="background:#fef3c7; color:#d97706">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <span>Crédito</span>
                    </button>
                </div>

                <!-- Divider -->
                <div class="pdv-payment-divider">
                    <span>ou pagamento parcial / múltiplo</span>
                </div>

                <!-- Split Payment -->
                <div class="pdv-split-payment">
                    <select class="pdv-select" id="payment-method-select">
                        <option value="dinheiro">💵 Dinheiro</option>
                        <option value="pix">💜 PIX</option>
                        <option value="cartao_debito">💳 Cartão Débito</option>
                        <option value="cartao_credito">💳 Cartão Crédito</option>
                    </select>
                    <div class="pdv-split-input-row">
                        <div class="pdv-amount-input-wrap">
                            <span class="pdv-currency">R$</span>
                            <input type="number" class="pdv-amount-input" id="payment-value-input" step="0.01" placeholder="0,00" inputmode="decimal">
                        </div>
                        <button class="pdv-add-payment-btn" onclick="PDV.addPayment()">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </div>
                </div>

                <!-- Payments Log -->
                <ul class="pdv-payments-list" id="payments-list"></ul>

                <!-- Summary Box -->
                <div class="pdv-payment-summary">
                    <div class="pdv-summary-row">
                        <span>Recebido</span>
                        <span class="pdv-summary-paid" id="modal-total-paid">R$ 0,00</span>
                    </div>
                    <div class="pdv-summary-row" id="remaining-container">
                        <span>Falta</span>
                        <span class="pdv-summary-remaining" id="modal-remaining">R$ 0,00</span>
                    </div>
                    <div class="pdv-summary-row d-none" id="change-container">
                        <span>Troco</span>
                        <span class="pdv-summary-change" id="modal-change">R$ 0,00</span>
                    </div>
                </div>
            </div>

            <!-- Confirm Button -->
            <div class="pdv-payment-footer">
                <button class="pdv-confirm-btn disabled" id="btn-confirm-sale" onclick="PDV.confirmSale()">
                    <i class="fas fa-check-circle me-2"></i>
                    Confirmar e Fechar Venda
                </button>
            </div>
        </div>
    </div>
</div>


<!-- SUCCESS MODAL -->
<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered pdv-success-dialog">
        <div class="modal-content pdv-success-content">
            <div class="pdv-success-icon">
                <div class="pdv-success-checkmark">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <h2 class="pdv-success-title">Venda Concluída!</h2>
            <p class="pdv-success-subtitle">Registrada com sucesso no sistema.</p>

            <div class="pdv-success-actions">
                <button class="pdv-print-btn" id="btn-print-receipt" onclick="PDV.printReceipt()">
                    <i class="fas fa-print me-2"></i>Imprimir Cupom
                </button>
                <button class="pdv-next-btn" onclick="PDV.closeSuccessModal()">
                    <i class="fas fa-plus me-2"></i>Nova Venda
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base_url ?>assets/js/pdv_nexus.js?v=<?= filemtime(__DIR__.'/../assets/js/pdv_nexus.js') ?>"></script>

<?php include_once '../includes/rodape.php'; ?>
