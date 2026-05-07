<?php
// employee/pdv.php — PDV NEXUS v5.1 (Fixed & Formatted)
require_once '../includes/funcoes.php';
checkAuth();

$title = "Caixa — PDV Nexus";
$conn_pdv = connect_db();

// Injetar estilos no head
$extra_css = '
<link rel="stylesheet" href="/assets/css/toasts.css?v=' . time() . '">
<link rel="stylesheet" href="/assets/css/pdv_nexus_v5.css?v=' . time() . '">';

include_once '../includes/cabecalho.php';
?>

<div class="pdv-app">
    <!-- 1. Catalog Section -->
    <div class="pdv-catalog-col">
        <div class="pdv-search-section">
            <div class="pdv-search-bar">
                <i class="fas fa-search pdv-search-icon"></i>
                <input type="text" id="pdv-search-input" class="pdv-search-input" placeholder="Buscar produto ou SKU..." autocomplete="off">
            </div>
        </div>

        <div class="pdv-chips-row" id="pdv-chips">
            <!-- Rendered via JS -->
        </div>

        <div id="product-grid" class="pdv-product-grid">
            <!-- Rendered via JS -->
        </div>
    </div>

    <!-- 2. Cart Column (Desktop) -->
    <div class="pdv-cart-col">
        <div class="pdv-cart-panel">
            <div class="pdv-cart-header">
                <h2>Carrinho</h2>
                <div class="pdv-customer-input-wrap mt-3">
                    <i class="fas fa-user-plus"></i>
                    <input type="text" id="customer-search" placeholder="Vincular cliente..." autocomplete="off">
                </div>
                <div id="selected-customer-info"></div>
            </div>

            <div id="cart-container" class="pdv-cart-items">
                <!-- Cart Items -->
            </div>

            <div class="pdv-cart-footer">
                <div class="pdv-summary-row mb-2">
                    <span class="pdv-footer-label">Subtotal</span>
                    <span id="cart-subtotal">R$ 0,00</span>
                </div>
                <div class="pdv-total-row">
                    <span>Total</span>
                    <span id="cart-total" class="pdv-total-amount">R$ 0,00</span>
                </div>
                <button class="pdv-checkout-btn" id="btn-open-payment" onclick="PDV.openPaymentModal()" disabled>
                    Finalizar Venda
                </button>
                <button class="pdv-clear-btn" onclick="PDV.clearCart()">Limpar Carrinho</button>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Bottom Sheet -->
<div class="pdv-sheet-backdrop" id="sheet-backdrop" onclick="PDV.collapseSheet()"></div>
<div class="pdv-sheet" id="pdv-sheet">
    <div class="pdv-sheet-handle-bar">
        <div class="pdv-sheet-handle"></div>
    </div>
    <div class="pdv-sheet-peek" onclick="PDV.toggleSheet()">
        <span class="pdv-sheet-total" id="sheet-total">R$ 0,00</span>
        <button class="pdv-sheet-checkout-btn">Pagar</button>
    </div>
    <div class="pdv-sheet-body">
        <div id="sheet-cart-items" class="pdv-sheet-items"></div>
        <div class="pdv-total-row mt-4">
            <span>Total</span>
            <span id="sheet-final-total">R$ 0,00</span>
        </div>
        <button class="pdv-checkout-btn mt-3" onclick="PDV.openPaymentModal()">Finalizar Venda</button>
    </div>
</div>

<!-- Mobile FAB -->
<div class="pdv-cart-fab" id="pdv-cart-fab" onclick="PDV.toggleSheet()">
    <i class="fas fa-shopping-basket"></i>
</div>

<!-- PAYMENT MODAL -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pdv-modal-payment">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <span class="text-muted small fw-bold">TOTAL A PAGAR</span>
                <h1 class="display-4 fw-black text-navy my-2" id="modal-total-sale">R$ 0,00</h1>
                
                <div class="pdv-payment-methods-grid mt-4">
                    <button class="pdv-method-card" onclick="PDV.confirmSale('dinheiro')">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Dinheiro</span>
                    </button>
                    <button class="pdv-method-card" onclick="PDV.confirmSale('pix')">
                        <i class="fab fa-pix"></i>
                        <span>PIX</span>
                    </button>
                    <button class="pdv-method-card" onclick="PDV.confirmSale('cartao_debito')">
                        <i class="fas fa-credit-card"></i>
                        <span>Débito</span>
                    </button>
                    <button class="pdv-method-card" onclick="PDV.confirmSale('cartao_credito')">
                        <i class="fas fa-credit-card"></i>
                        <span>Crédito</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SUCCESS MODAL -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-5">
            <div class="text-success mb-4">
                <i class="fas fa-check-circle display-1"></i>
            </div>
            <h2 class="fw-bold">Venda Finalizada!</h2>
            <p class="text-muted">A transação foi registrada com sucesso.</p>
            <div class="d-grid gap-2 mt-4">
                <button class="btn btn-primary btn-lg" onclick="PDV.closeSuccessModal()">Nova Venda</button>
                <button class="btn btn-outline-secondary" id="btn-print-receipt">Imprimir Cupom</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="/assets/js/pdv_nexus_v5.js?v=<?= time() ?>"></script>

<?php include_once '../includes/rodape.php'; ?>
