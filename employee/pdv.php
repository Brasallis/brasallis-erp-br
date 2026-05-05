<?php
// employee/pdv.php - PDV ZEN (Restauração e Melhoria)
// Ponto de entrada oficial do Frente de Caixa integrado ao Brasallis Hub.

require_once '../includes/funcoes.php';
checkAuth(); // Garante que o usuário está logado

$title = "Frente de Caixa — PDV Zen";
include_once '../includes/cabecalho.php';
?>

<!-- Estilos específicos do PDV Zen -->
<link rel="stylesheet" href="../assets/css/pdv_zen.css?v=<?php echo time(); ?>">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">

<div class="pdv-zen-container">
    <div class="row g-4">
        <!-- ÁREA ESQUERDA: Busca e Catálogo -->
        <div class="col-lg-8">
            <div class="zen-panel search-panel mb-4">
                <div class="zen-search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="pdv-search" placeholder="O que vamos vender agora? (F2)" autocomplete="off" autofocus>
                    <div class="shortcut-hint">F2</div>
                </div>
            </div>

            <div id="pdv-results" class="row g-3 results-grid">
                <!-- Resultados preenchidos via JS -->
                <div class="col-12 text-center py-5 text-muted opacity-50">
                    <i class="fas fa-barcode fa-3x mb-3"></i>
                    <p>Escaneie um produto ou digite o nome para começar.</p>
                </div>
            </div>
        </div>

        <!-- ÁREA DIREITA: Carrinho e Checkout -->
        <div class="col-lg-4">
            <div class="zen-panel cart-panel shadow-sm">
                <div class="cart-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Carrinho</h5>
                        <span class="badge bg-navy-soft text-navy" id="cart-qty">0 itens</span>
                    </div>
                </div>

                <!-- Identificação do Cliente -->
                <div class="customer-section p-3 border-bottom">
                    <div class="zen-input-group">
                        <i class="fas fa-user-circle text-muted"></i>
                        <input type="text" id="customer-search" placeholder="Identificar Cliente (Opcional)" autocomplete="off">
                    </div>
                    <div id="customer-results" class="customer-dropdown-results"></div>
                    <div id="selected-customer-info"></div>
                </div>

                <!-- Lista de Itens -->
                <div id="cart-container" class="cart-items-area custom-scrollbar">
                    <!-- Itens via JS -->
                </div>

                <!-- Resumo e Ação -->
                <div class="cart-footer p-4 border-top mt-auto">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Subtotal</span>
                        <span class="fw-bold" id="cart-subtotal">R$ 0,00</span>
                    </div>
                    
                    <div class="discount-row d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small">Desconto</span>
                        <div class="input-group input-group-sm w-50">
                            <span class="input-group-text bg-transparent border-0">R$</span>
                            <input type="number" id="cart-discount-input" class="form-control text-end border-0 bg-light rounded-3" value="0.00" step="0.01">
                        </div>
                    </div>

                    <div class="total-row d-flex justify-content-between align-items-center mb-4">
                        <span class="h5 fw-bold mb-0">TOTAL</span>
                        <span class="h3 fw-black text-navy mb-0" id="cart-total">R$ 0,00</span>
                    </div>

                    <button class="btn btn-navy btn-lg w-100 py-3 fw-bold" id="btn-open-payment" disabled onclick="PDV.openPaymentModal()">
                        FINALIZAR VENDA (F9)
                    </button>
                    
                    <button class="btn btn-link text-danger w-100 mt-2 extra-small text-decoration-none fw-bold" onclick="PDV.clearCart()">
                        LIMPAR CARRINHO (ESC)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE PAGAMENTO ZEN -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg">
            <div class="modal-header bg-navy text-white p-4">
                <h5 class="modal-title fw-bold">Pagamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="text-center mb-4">
                    <span class="text-muted small text-uppercase fw-bold ls-wide">Total a Pagar</span>
                    <h2 class="fw-black text-navy mb-0 ls-tight" id="modal-total-sale">R$ 0,00</h2>
                </div>

                <div class="fast-checkout-options mb-4">
                    <div class="row g-2">
                        <div class="col-6"><button class="btn btn-outline-success w-100 py-3 fw-bold" onclick="PDV.fastCheckout('dinheiro')"><i class="fas fa-money-bill-wave d-block mb-1 fs-4"></i>Dinheiro</button></div>
                        <div class="col-6"><button class="btn btn-outline-primary w-100 py-3 fw-bold" onclick="PDV.fastCheckout('pix')"><i class="fab fa-pix d-block mb-1 fs-4"></i>PIX</button></div>
                        <div class="col-6"><button class="btn btn-outline-info w-100 py-3 fw-bold" onclick="PDV.fastCheckout('cartao_debito')"><i class="fas fa-credit-card d-block mb-1 fs-4"></i>Débito</button></div>
                        <div class="col-6"><button class="btn btn-outline-warning w-100 py-3 fw-bold" onclick="PDV.fastCheckout('cartao_credito')"><i class="fas fa-credit-card d-block mb-1 fs-4"></i>Crédito</button></div>
                    </div>
                </div>

                <div class="text-center mb-3">
                    <span class="badge bg-light text-muted border px-3 py-2 rounded-pill">OU PAGAMENTO PARCIAL / MÚLTIPLO</span>
                </div>

                <div class="payment-selection mb-4">
                    <div class="row g-2">
                        <div class="col-6">
                            <select class="form-select zen-select" id="payment-method-select">
                                <option value="dinheiro">Dinheiro</option>
                                <option value="pix">PIX</option>
                                <option value="cartao_debito">C. Débito</option>
                                <option value="cartao_credito">C. Crédito</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <div class="input-group">
                                <input type="number" class="form-control zen-input" id="payment-value-input" step="0.01" placeholder="Valor">
                                <button class="btn btn-navy" type="button" onclick="PDV.addPayment()"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="payments-log mb-4">
                    <ul class="list-group list-group-flush rounded-3 border overflow-hidden" id="payments-list">
                        <!-- Lista de pagamentos via JS -->
                    </ul>
                </div>

                <div class="summary-box p-3 rounded-4 bg-white shadow-sm">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="small text-muted fw-bold">RECEBIDO</span>
                        <span class="fw-bold text-success" id="modal-total-paid">R$ 0,00</span>
                    </div>
                    <div id="remaining-container" class="d-flex justify-content-between">
                        <span class="small text-muted fw-bold">FALTA</span>
                        <span class="fw-bold text-danger" id="modal-remaining">R$ 0,00</span>
                    </div>
                    <div id="change-container" class="d-flex justify-content-between d-none">
                        <span class="small text-muted fw-bold">TROCO</span>
                        <span class="fw-bold text-primary" id="modal-change">R$ 0,00</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer p-4 border-top bg-white">
                <button class="btn btn-navy btn-lg w-100 fw-bold disabled" id="btn-confirm-sale" onclick="PDV.confirmSale()">
                    CONFIRMAR E FINALIZAR (ENTER)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE SUCESSO -->
<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-4 shadow-lg text-center p-4">
            <div class="mb-3">
                <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                <h4 class="fw-bold text-navy">Compra Finalizada!</h4>
                <p class="text-muted small">A venda foi registrada com sucesso.</p>
            </div>
            
            <div class="bg-light rounded-3 p-3 mb-4">
                <p class="fw-bold mb-1">Deseja imprimir o cupom?</p>
                <span class="text-muted extra-small">Você pode imprimir depois no histórico.</span>
            </div>
            
            <div class="d-flex flex-column gap-2">
                <button class="btn btn-primary fw-bold py-2" id="btn-print-receipt" onclick="PDV.printReceipt()">
                    <i class="fas fa-print me-2"></i>Sim, Imprimir Recibo
                </button>
                <button class="btn btn-light fw-bold py-2 border text-muted" onclick="PDV.closeSuccessModal()">
                    Não, Próxima Venda
                </button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/pdv_elite.js?v=<?php echo time(); ?>"></script>


<?php include_once '../includes/rodape.php'; ?>
