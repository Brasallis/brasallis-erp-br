<?php
// employee/pdv.php — PDV NEXUS v2.0 (Google-Grade UX)
// Layout adaptativo: Desktop Split | Tablet Stack | Mobile App Nativo

require_once '../includes/funcoes.php';
checkAuth();

$title = "Caixa — PDV Nexus";
$hide_bottom_nav = false; // MANTÉM ORIGINALIDADE
$hide_sidebar = false;    // MANTÉM ORIGINALIDADE
$hide_topbar = false;     // MANTÉM ORIGINALIDADE

// Buscar categorias dinâmicas do banco
$conn_pdv = connect_db();
$categories_stmt = $conn_pdv->prepare("SELECT id, nome FROM categorias WHERE empresa_id = ? ORDER BY nome ASC");
$categories_stmt->execute([$_SESSION['empresa_id']]);
$pdv_categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Injetar estilos no head para garantir a fluidez da interface
$extra_css = '
<style>
    /* INTEGRAÇÃO PREMIUM — ESTILO GOOGLE HUB */
    
    /* 1. Trava o scroll da página inteira */
    html, body {
        overflow: hidden !important;
        margin: 0; padding: 0;
        width: 100%; height: 100%;
    }

    /* 2. O main container precisa ocupar o espaço total */
    .brasallis-main {
        padding: 0 !important;
        margin: 0 !important;
        position: absolute;
        top: 64px; /* Abaixo do topbar */
        left: 72px; /* Ao lado da sidebar */
        right: 0;
        bottom: 0;
        display: flex;
    }
    
    .brasallis-main > .flex-grow-1 {
        flex: 1;
        display: flex;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* 3. PDV App Layout Absoluto */
    .pdv-app {
        flex: 1;
        display: flex;
        width: 100%;
        height: 100%;
        background: #f0f4f9;
    }

    @media (min-width: 992px) {
        .pdv-app {
            flex-direction: row !important;
        }
        .pdv-catalog-col { flex: 1 !important; height: 100%; min-width: 0; }
        .pdv-cart-col { 
            width: 320px !important; /* Estreitado para não parecer que toma metade da tela */
            flex-shrink: 0 !important; 
            height: 100%;
            border-left: 1px solid #e2e8f0;
            background: #ffffff;
            box-shadow: -4px 0 15px rgba(0,0,0,0.02); /* Sombra suave para separar */
        }
    }

    @media (max-width: 991px) {
        .brasallis-main {
            left: 0; /* Mobile não tem sidebar fixa na esquerda */
            top: 64px;
            bottom: 72px; /* Espaço para o bottom nav global */
        }
        .pdv-app {
            flex-direction: column !important;
        }
        
        /* Mobile Sheet Super Estável */
        .pdv-sheet {
            position: fixed !important;
            bottom: -100% !important; /* Esconde totalmente fora da tela inicialmente */
            left: 0; right: 0;
            height: 80vh !important;
            background: #ffffff;
            z-index: 5000 !important;
            border-radius: 24px 24px 0 0;
            box-shadow: 0 -10px 40px rgba(0,0,0,0.15);
            transition: bottom 0.4s cubic-bezier(0.2, 0, 0, 1) !important;
            display: flex !important;
            flex-direction: column;
        }
        
        .pdv-sheet.expanded {
            bottom: 72px !important; /* Sobe para ficar exatamente acima do menu global */
        }
        
        .pdv-cart-fab {
            bottom: 100px !important; /* Acima do menu global */
            z-index: 5001 !important;
        }
    }
</style>
<link rel="stylesheet" href="/assets/css/toasts.css?v=' . time() . '">
<link rel="stylesheet" href="/assets/css/pdv_nexus_v2.css?v=' . time() . '">';

include_once '../includes/cabecalho.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- =====================================================
     PDV NEXUS — APP SHELL
     ===================================================== -->

<div class="pdv-app">
    <!-- 1. Catalog Section (Esquerda no Desktop) -->
    <div class="pdv-catalog-col">

        <!-- SEARCH SECTION -->
        <div class="pdv-search-section">
            <div class="pdv-search-bar">
                <i class="fas fa-search pdv-search-icon"></i>
                <input type="text" id="pdv-search" class="pdv-search-input" placeholder="Buscar produto ou SKU..." autocomplete="off">
                <button id="search-clear-btn" class="pdv-search-clear" onclick="PDV.clearSearch()" style="display:none;">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
        </div>

        <!-- CATEGORIES CHIPS -->
        <div class="pdv-chips-row">
            <div class="pdv-chip active" onclick="PDV.filterCategory('all', this)">Todos</div>
            <?php foreach ($pdv_categories as $cat): ?>
                <div class="pdv-chip" onclick="PDV.filterCategory(<?= $cat['id'] ?>, this)"><?= htmlspecialchars($cat['nome']) ?></div>
            <?php endforeach; ?>
            <?php if (empty($pdv_categories)): ?>
            <div class="pdv-chip" data-cat="all">Geral</div>
            <?php endif; ?>
        </div>

        <!-- PRODUCT GRID -->
        <div id="pdv-results" class="pdv-product-grid">
            <!-- Renderizado via JS -->
            <div class="pdv-empty-state" id="pdv-empty-state">
                <div class="pdv-empty-icon"><i class="fas fa-box-open"></i></div>
                <h3>Carregando catálogo...</h3>
            </div>
        </div>
    </div>

    <!-- Cart Column (Desktop) -->
    <div class="pdv-cart-col">
        <div class="pdv-cart-panel">
            <div class="pdv-cart-header">
                <div class="pdv-cart-header-title">
                    <h2>Carrinho</h2>
                    <span class="pdv-cart-count" id="cart-count-badge">0 itens</span>
                </div>
                
                <!-- Customer Search -->
                <div class="pdv-customer-input-wrap">
                    <i class="fas fa-user-plus text-muted"></i>
                    <input type="text" id="customer-search" placeholder="Vincular cliente (F2)" autocomplete="off">
                    <div id="customer-results" class="pdv-customer-results"></div>
                </div>
                <div id="selected-customer-info"></div>
            </div>

            <div id="cart-container" class="pdv-cart-items">
                <!-- Itens do carrinho -->
            </div>

            <div class="pdv-cart-footer">
                <div class="pdv-discount-row">
                    <span class="pdv-footer-label">Desconto</span>
                    <div class="pdv-discount-input-wrap">
                        <span class="pdv-currency">R$</span>
                        <input type="number" id="cart-discount-input" step="0.01" value="0.00">
                    </div>
                </div>
                <div class="pdv-summary-row mb-2">
                    <span class="pdv-footer-label">Subtotal</span>
                    <span id="cart-subtotal">R$ 0,00</span>
                </div>
                <div class="pdv-total-row">
                    <span>TOTAL</span>
                    <span id="cart-total" class="pdv-total-amount">R$ 0,00</span>
                </div>

                <button class="pdv-checkout-btn" id="btn-open-payment" onclick="PDV.openPaymentModal()" disabled>
                    <i class="fas fa-check-circle"></i>
                    <span>PAGAR (F9)</span>
                </button>

                <button class="pdv-clear-btn" onclick="PDV.clearCart()">
                    <i class="fas fa-trash-alt me-1"></i>Limpar Carrinho
                </button>
            </div>
        </div>
    </aside>

</div><!-- /.pdv-app -->

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
            <div class="pdv-sheet-summary-row align-items-center">
                <span>Desconto (R$)</span>
                <input type="number" id="sheet-discount-input" class="form-control form-control-sm text-end w-auto border-0 bg-light fw-bold" step="0.01" value="0.00" style="max-width: 100px; border-radius: 8px;">
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

<!-- 2. BOTÃO FLUTUANTE DO CARRINHO (Mobile Only) -->
<div class="pdv-cart-fab" id="pdv-mobile-fab" onclick="PDV.toggleSheet()">
    <i class="fas fa-shopping-basket"></i>
    <span class="pdv-cart-badge" id="topbar-cart-badge">0</span>
</div>


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

<script src="/assets/js/pdv_nexus_v2.js?v=<?= time() ?>"></script>

<?php include_once '../includes/rodape.php'; ?>
