<?php
// employee/pdv.php — PDV NEXUS v4.5 (Google-Grade UX)
require_once '../includes/funcoes.php';
checkAuth();

$title = "Caixa — PDV Nexus";
$conn_pdv = connect_db();

// Injetar estilos no head
$extra_css = '
<link rel="stylesheet" href="/assets/css/toasts.css?v=' . time() . '">
<link rel="stylesheet" href="/assets/css/pdv_nexus_v4_5.css?v=' . time() . '">';

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
            </div>

            <div id="cart-container" class="pdv-cart-items">
                <!-- Cart Items -->
            </div>

            <div class="pdv-cart-footer">
                <div class="pdv-total-row">
                    <span>Total</span>
                    <span id="cart-total" class="pdv-total-amount">R$ 0,00</span>
                </div>
                <button class="pdv-checkout-btn" id="btn-open-payment" onclick="PDV.openPaymentModal()" disabled>
                    Pagar Venda
                </button>
                <button class="pdv-clear-btn" onclick="PDV.clearCart()">Limpar Carrinho</button>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Bottom Sheet -->
<div class="pdv-sheet-backdrop" id="sheet-backdrop" onclick="PDV.collapseSheet()"></div>
<div class="pdv-sheet" id="pdv-sheet">
    <div class="pdv-sheet-peek" onclick="PDV.toggleSheet()">
        <span class="pdv-sheet-total" id="sheet-total">R$ 0,00</span>
        <button class="pdv-sheet-checkout-btn">Pagar</button>
    </div>
    <div class="pdv-sheet-body">
        <div id="sheet-cart-items"></div>
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

<!-- Scripts -->
<script src="/assets/js/pdv_nexus_v4_5.js?v=<?= time() ?>"></script>

<?php include_once '../includes/rodape.php'; ?>
