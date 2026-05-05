<?php
/**
 * View: pdv/index (Elite Edition)
 */
$title = "PDV Elite — Frente de Caixa";
require BASE_PATH . '/resources/views/layouts/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">

<div class="pdv-wrapper h-100 p-3">
    <div class="row g-4 h-100">
        <!-- LEFT: Catalog & Search (Main Area) -->
        <div class="col-lg-8 d-flex flex-column">
            <div class="m3-panel flex-grow-1 p-4 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center">
                        <div class="m3-icon-box bg-navy me-3">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div>
                            <h3 class="fw-black mb-0 text-navy ls-tight">Frente de Caixa</h3>
                            <p class="text-muted extra-small mb-0 text-uppercase fw-bold">Modo de Operação: Varejo Elite</p>
                        </div>
                    </div>
                    <div class="clock text-navy fw-bold small bg-light px-3 py-1 rounded-pill" id="pdv-clock">00:00:00</div>
                </div>

                <!-- Search Bar (M3 Style) -->
                <div class="search-container mb-4">
                    <div class="m3-search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="pdv-search" placeholder="Escaneie o código ou busque pelo nome (F2)..." autocomplete="off" autofocus>
                        <span class="badge bg-light text-navy fw-bold border ms-2">F2</span>
                    </div>
                </div>

                <!-- Results Grid -->
                <div id="pdv-results" class="row g-3 overflow-auto custom-scrollbar flex-grow-1" style="max-height: calc(100vh - 350px);">
                    <!-- Results populated by JS -->
                </div>
            </div>
        </div>

        <!-- RIGHT: Cart & Actions -->
        <div class="col-lg-4 d-flex flex-column">
            <div class="m3-panel-cart shadow-lg d-flex flex-column">
                <!-- Cart Header -->
                <div class="cart-header p-4 text-white bg-navy">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h4 class="fw-bold mb-0">Carrinho</h4>
                        <span class="m3-badge-white" id="cart-qty">0 itens</span>
                    </div>
                    <div class="extra-small opacity-75 d-flex align-items-center">
                        <i class="fas fa-user-circle me-1"></i> Operador: <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                    </div>
                </div>

                <!-- Customer Selection -->
                <div class="p-3 bg-light border-bottom">
                    <div class="m3-input-group small">
                        <i class="fas fa-user-plus text-muted me-2"></i>
                        <input type="text" id="customer-search" placeholder="Identificar Cliente (CPF/Nome)..." autocomplete="off">
                    </div>
                    <div id="customer-results" class="customer-dropdown-results"></div>
                    <div id="selected-customer-info"></div>
                </div>

                <!-- Cart Items Area -->
                <div id="cart-container" class="flex-grow-1 overflow-auto p-3 custom-scrollbar" style="max-height: 40vh;">
                    <!-- Items populated by JS -->
                </div>

                <!-- Totals & Checkout -->
                <div class="p-4 bg-white mt-auto border-top">
                    <div class="discount-area mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small fw-bold">DESCONTO</span>
                            <div class="input-group input-group-sm w-50">
                                <span class="input-group-text bg-white border-0 small">R$</span>
                                <input type="number" id="cart-discount-input" class="form-control border-0 bg-light rounded-3 text-end" value="0.00" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="summary-line d-flex justify-content-between mb-1">
                        <span class="text-muted small">Subtotal</span>
                        <span class="fw-bold text-navy" id="cart-subtotal">R$ 0,00</span>
                    </div>
                    <div class="total-line d-flex justify-content-between align-items-center mb-4 pt-2 border-top">
                        <span class="h5 fw-black text-navy mb-0">TOTAL</span>
                        <span class="h2 fw-black text-success mb-0 ls-tight" id="cart-total">R$ 0,00</span>
                    </div>

                    <button class="m3-btn-checkout w-100" id="btn-open-payment" disabled onclick="PDV.openPaymentModal()">
                        <i class="fas fa-wallet me-2"></i> IR PARA O PAGAMENTO <span class="small opacity-50 ms-2">(F9)</span>
                    </button>

                    <div class="text-center mt-3">
                        <button class="btn btn-link text-danger text-decoration-none extra-small fw-bold text-uppercase" onclick="PDV.clearCart()">Limpar Carrinho (ESC)</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PAYMENT MODAL (Material 3 Refined) -->
<div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content m3-modal-content">
      <div class="modal-header m3-modal-header text-white">
        <h5 class="modal-title fw-bold"><i class="fas fa-check-double me-2"></i> Finalizar Venda</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="row g-0">
            <!-- Payment Form -->
            <div class="col-md-7 p-4 bg-light">
                <div class="m3-total-display text-center mb-4">
                    <span class="text-muted d-block extra-small fw-bold text-uppercase mb-1">Total a Pagar</span>
                    <span class="h1 fw-black text-navy mb-0 ls-tight" id="modal-total-sale">R$ 0,00</span>
                </div>

                <h6 class="fw-bold text-navy mb-3 small text-uppercase ls-wide">Método de Pagamento</h6>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <select class="m3-select" id="payment-method-select">
                            <option value="dinheiro">Dinheiro</option>
                            <option value="pix">PIX</option>
                            <option value="cartao_debito">Cartão de Débito</option>
                            <option value="cartao_credito">Cartão de Crédito</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <div class="input-group m3-payment-input">
                            <input type="number" class="form-control" id="payment-value-input" step="0.01" placeholder="Valor">
                            <button class="btn btn-navy" type="button" onclick="PDV.addPayment()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="payments-log">
                    <h6 class="fw-bold text-navy mb-2 extra-small text-uppercase ls-wide">Pagamentos Registrados</h6>
                    <ul class="list-group list-group-flush" id="payments-list">
                        <!-- Filled by JS -->
                    </ul>
                </div>
            </div>

            <!-- Summary & Action -->
            <div class="col-md-5 p-4 bg-white border-start d-flex flex-column">
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted fw-bold small">PAGO</span>
                        <span class="fw-bold text-success fs-5" id="modal-total-paid">R$ 0,00</span>
                    </div>
                    <hr class="my-3 opacity-10">
                    
                    <div id="remaining-container" class="m3-status-box warning">
                        <span class="fw-bold d-block mb-1 extra-small"><i class="fas fa-info-circle"></i> FALTA</span>
                        <span class="h3 fw-black mb-0" id="modal-remaining">R$ 0,00</span>
                    </div>

                    <div id="change-container" class="m3-status-box success d-none">
                        <span class="fw-bold d-block mb-1 extra-small"><i class="fas fa-hand-holding-usd"></i> TROCO</span>
                        <span class="h3 fw-black mb-0" id="modal-change">R$ 0,00</span>
                    </div>
                </div>

                <button class="m3-btn-confirm w-100 mt-auto disabled" id="btn-confirm-sale" onclick="PDV.confirmSale()">
                    <i class="fas fa-rocket me-2"></i> FINALIZAR <span class="small opacity-50 ms-1">(ENTER)</span>
                </button>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* PDV Elite Styles - Material 3 Inspired */
:root {
    --navy: #0A2647;
    --navy-light: #144272;
    --success: #10b981;
    --warning: #f59e0b;
    --bg-soft: #f8fafc;
    --m3-radius: 24px;
    --m3-radius-sm: 12px;
}

body { font-family: 'Outfit', sans-serif; background-color: #f1f5f9; }

.fw-black { font-weight: 800; }
.ls-tight { letter-spacing: -1.5px; }
.ls-wide { letter-spacing: 1px; }
.extra-small { font-size: 0.7rem; }
.bg-navy { background-color: var(--navy) !important; }
.text-navy { color: var(--navy) !important; }

.m3-panel { background: #fff; border-radius: var(--m3-radius); border: none; }
.m3-panel-cart { background: #fff; border-radius: var(--m3-radius); overflow: hidden; height: 100%; border: none; }

.m3-icon-box { width: 50px; height: 50px; border-radius: 16px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.2rem; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

/* Search Bar */
.m3-search-bar { background: #f1f5f9; border-radius: 16px; padding: 12px 20px; display: flex; align-items: center; border: 2px solid transparent; transition: all 0.3s; }
.m3-search-bar:focus-within { background: #fff; border-color: var(--navy); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
.m3-search-bar input { border: none; background: transparent; flex: 1; outline: none; font-weight: 600; padding: 0 15px; color: var(--navy); }
.m3-search-bar i { color: #94a3b8; }

/* Product Cards */
.m3-product-card { background: #fff; border-radius: 20px; border: 2px solid #f1f5f9; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; height: 100%; position: relative; }
.m3-product-card:hover { transform: translateY(-5px); border-color: var(--navy); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
.m3-product-card.selected { border-color: var(--navy); background: #f0f7ff; }
.product-image-placeholder { background: #f1f5f9; border-radius: 14px; height: 100px; display: flex; align-items: center; justify-content: center; font-size: 2rem; }
.stock-badge { font-size: 0.65rem; font-weight: 800; padding: 4px 8px; border-radius: 8px; background: #e2e8f0; color: #475569; }
.stock-badge.low { background: #fee2e2; color: #ef4444; }
.price-tag { font-weight: 800; color: var(--success); font-size: 0.9rem; }

/* Cart Components */
.m3-item-card { background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; }
.qty-badge { background: #f1f5f9; color: var(--navy); font-weight: 800; padding: 4px 10px; border-radius: 10px; font-size: 0.8rem; }
.btn-icon-delete { background: none; border: none; color: #cbd5e1; transition: 0.2s; padding: 5px; }
.btn-icon-delete:hover { color: #ef4444; transform: scale(1.2); }

/* Customer Search */
.m3-input-group { background: #fff; border-radius: 10px; display: flex; align-items: center; padding: 0 10px; }
.m3-input-group input { border: none; outline: none; background: transparent; flex: 1; padding: 8px 0; font-size: 0.8rem; }
.customer-dropdown-results { position: absolute; z-index: 100; background: #fff; width: calc(100% - 30px); border-radius: 10px; box-shadow: 0 10px 20px rgba(0,0,0,0.1); max-height: 200px; overflow-y: auto; }

/* Checkout Buttons */
.m3-btn-checkout { background: linear-gradient(135deg, var(--success), #059669); color: #fff; border: none; border-radius: 20px; padding: 18px; font-weight: 800; letter-spacing: 1px; transition: all 0.3s; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2); }
.m3-btn-checkout:hover:not(:disabled) { transform: scale(1.02); box-shadow: 0 15px 30px rgba(16, 185, 129, 0.3); }
.m3-btn-checkout:disabled { opacity: 0.5; filter: grayscale(1); transform: none; }

/* Modal Styles */
.m3-modal-content { border-radius: 30px; overflow: hidden; border: none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
.m3-modal-header { background: var(--navy); padding: 25px; border: none; }
.m3-total-display { background: #fff; border-radius: 20px; padding: 20px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
.m3-select { width: 100%; border-radius: 14px; border: 2px solid #e2e8f0; padding: 12px; font-weight: 600; outline: none; appearance: none; background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-chevron-down' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E") no-repeat right 15px center; }
.m3-payment-input input { border-radius: 14px 0 0 14px !important; border: 2px solid #e2e8f0; border-right: none; padding: 12px; font-weight: 800; }
.m3-payment-input .btn { border-radius: 0 14px 14px 0; background: var(--navy); color: #fff; padding: 0 20px; }

.m3-status-box { padding: 20px; border-radius: 20px; transition: all 0.3s; }
.m3-status-box.warning { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
.m3-status-box.success { background: #ecfdf5; color: #047857; border: 1px solid #d1fae5; }

.m3-btn-confirm { background: var(--navy); color: #fff; border: none; border-radius: 18px; padding: 20px; font-weight: 800; letter-spacing: 1px; transition: 0.3s; }
.m3-btn-confirm:hover:not(.disabled) { background: var(--navy-light); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }

/* Animations */
.slide-in { animation: m3-slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes m3-slideIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }

.m3-toast { position: fixed; top: 20px; right: 20px; z-index: 9999; padding: 16px 24px; border-radius: 16px; min-width: 300px; transition: all 0.5s; }
.fade-out { opacity: 0; transform: translateY(-20px); }

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<script>
// Time Clock
setInterval(() => {
    document.getElementById('pdv-clock').innerText = new Date().toLocaleTimeString();
}, 1000);
</script>

<script src="/assets/js/pdv_elite.js"></script>

<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
