<?php
// admin/checkout.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';
checkAuth();

$plan = $_GET['plan'] ?? 'growth';
$prices = [
    'growth' => ['price' => 99.00, 'name' => 'Plano Growth', 'perks' => ['2 Milhões de Tokens', 'Suporte Prioritário', '5 Usuários']],
    'enterprise' => ['price' => 299.00, 'name' => 'Plano Enterprise',  'perks' => ['Tokens Ilimitados', 'Gerente de Conta', 'API Acesso']]
];

if (!array_key_exists($plan, $prices)) {
    die("Plano inválido.");
}

$selectedPlan = $prices[$plan];

include_once '../includes/cabecalho.php';
?>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
    }
    .features-list li {
        margin-bottom: 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        color: #555;
    }
    .checkout-step {
        width: 32px;
        height: 32px;
        background: #0d6efd;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
</style>

<div class="container py-5">
    <div class="row g-5 justify-content-center">
        <!-- Order Summary -->
        <div class="col-lg-5 order-lg-1">
            <h4 class="d-flex justify-content-between align-items-center mb-4">
                <span class="text-primary fw-bold">Seu Pedido</span>
                <span class="badge bg-primary rounded-pill">1 item</span>
            </h4>
            <ul class="list-group mb-4 shadow-sm rounded-4 border-0 overflow-hidden">
                <li class="list-group-item d-flex justify-content-between lh-sm p-4 border-0">
                    <div>
                        <h5 class="my-0 mb-2 fw-bold"><?php echo $selectedPlan['name']; ?></h5>
                        <small class="text-muted">Assinatura Mensal</small>
                        <ul class="list-unstyled mt-3 features-list small">
                            <?php foreach($selectedPlan['perks'] as $perk): ?>
                                <li><i class="fas fa-check-circle text-success"></i> <?php echo $perk; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <span class="text-muted fw-bold">R$ <?php echo number_format($selectedPlan['price'], 2, ',', '.'); ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between p-4 bg-light border-0">
                    <span class="fw-bold">Total (BRL)</span>
                    <strong class="fs-4 text-success">R$ <?php echo number_format($selectedPlan['price'], 2, ',', '.'); ?></strong>
                </li>
            </ul>
            
            <div class="text-center text-muted small mt-4">
                <i class="fas fa-shield-alt text-primary me-1"></i> Ambiente 100% Seguro
            </div>
        </div>

        <!-- Payment Interface -->
        <div class="col-lg-6 order-lg-2">
            <div class="glass-card p-5 rounded-4 h-100">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="checkout-step">1</div>
                    <h4 class="mb-0 fw-bold">Pagamento via Pix</h4>
                </div>

                <div class="alert alert-info border-0 bg-info-subtle rounded-3 mb-4">
                    <i class="fas fa-bolt me-2"></i> Liberação imediata após o pagamento.
                </div>

                <div id="paymentArea">
                    <div class="d-grid gap-3 mb-4">
                        <button id="btnPayPix" class="btn btn-outline-primary btn-lg rounded-pill p-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm transition-all">
                            <i class="fa-brands fa-pix"></i> Pagar com Pix (Instantâneo)
                        </button>
                        <button id="btnPayCard" class="btn btn-primary btn-lg rounded-pill p-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm transition-all">
                            <i class="fas fa-credit-card"></i> Pagar com Cartão (Checkout Pro)
                        </button>
                    </div>

                    <div id="loading" class="text-center d-none py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted animate-pulse">Gerando sua cobrança segura...</p>
                    </div>

                    <div id="pixContainer" class="d-none text-center fade-in">
                        <div class="card border-0 bg-white shadow-sm p-4 rounded-4 mb-3">
                            <h6 class="text-muted text-uppercase small fw-bold mb-3">Escaneie no App do Banco</h6>
                            <img id="qrCodeImage" src="" class="img-fluid rounded-3 border mb-3 mx-auto d-block" style="max-width: 220px;">
                            
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-copy"></i></span>
                                <input type="text" class="form-control bg-light border-0 font-monospace small" id="copyPasteCode" readonly value="">
                                <button class="btn btn-dark" type="button" onclick="copyCode()">Copiar</button>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center justify-content-center gap-2 text-primary mt-3">
                            <span class="spinner-grow spinner-grow-sm"></span>
                            <span class="small fw-bold">Aguardando confirmação...</span>
                        </div>
                    </div>
                    
                    <div id="errorMessage" class="alert alert-danger d-none mt-3 rounded-3"></div>
                </div>

                <hr class="my-5 op-1">
                <div class="text-center opacity-50">
                    <img src="https://logopng.com.br/logos/mercado-pago-212.png" height="20" alt="Mercado Pago">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Pix Handler
document.getElementById('btnPayPix').addEventListener('click', function() {
    startLoading(this);
    fetch('processa_pix.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'plan=<?php echo $plan; ?>'
    })
    .then(response => response.json())
    .then(data => handlePixResponse(data))
    .catch(err => handleError(err));
});

// Card Handler
document.getElementById('btnPayCard').addEventListener('click', function() {
    startLoading(this);
    fetch('processa_preference.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'plan=<?php echo $plan; ?>'
    })
    .then(response => response.json())
    .then(data => {
        if(data.error) throw new Error(data.error);
        // Redirect to Mercado Pago
        window.location.href = data.sandbox_init_point; // Use init_point for Prod
    })
    .catch(err => handleError(err));
});

function startLoading(btn) {
    document.querySelectorAll('button').forEach(b => b.classList.add('d-none'));
    document.getElementById('loading').classList.remove('d-none');
    document.getElementById('errorMessage').classList.add('d-none');
}

function handleError(err) {
    console.error(err);
    document.getElementById('loading').classList.add('d-none');
    document.querySelectorAll('button').forEach(b => b.classList.remove('d-none'));
    const errorMsg = document.getElementById('errorMessage');
    errorMsg.textContent = err.message || "Erro de conexão.";
    errorMsg.classList.remove('d-none');
}

function handlePixResponse(data) {
    document.getElementById('loading').classList.add('d-none');
    
    if(data.error) {
        handleError(new Error(data.error));
        return;
    }

    const pixContainer = document.getElementById('pixContainer');
    pixContainer.classList.remove('d-none');
    pixContainer.style.opacity = 0;
    setTimeout(() => { pixContainer.style.transition = 'opacity 0.5s'; pixContainer.style.opacity = 1; }, 50);

    document.getElementById('qrCodeImage').src = 'data:image/png;base64,' + data.qr_code_base64;
    document.getElementById('copyPasteCode').value = data.qr_code;
    
    // Polling
    const pollInterval = setInterval(() => {
        fetch('check_status.php?id=' + data.payment_id)
            .then(res => res.json())
            .then(status => {
                if(status.approved) {
                    clearInterval(pollInterval);
                    window.location.href = 'sucesso.php';
                }
            });
    }, 4000);
}

function copyCode() {
    const copyText = document.getElementById("copyPasteCode");
    copyText.select();
    document.execCommand("copy");
    const btn = event.target;
    const originalText = btn.innerText;
    btn.innerText = "Copiado!";
    btn.classList.replace('btn-dark', 'btn-success');
    setTimeout(() => {
        btn.innerText = originalText;
        btn.classList.replace('btn-success', 'btn-dark');
    }, 2000);
}
</script>

<?php include_once '../includes/rodape.php'; ?>
