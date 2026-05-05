<!-- Modal de Upgrade -->
<div class="modal fade" id="upgradeModal" tabindex="-1" aria-labelledby="upgradeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="p-3 w-100 text-center">
                    <span class="badge bg-primary bg-opacity-10 text-primary mb-2 px-3 py-2 rounded-pill fw-bold">Upgrade de Plano</span>
                    <h2 class="modal-title fw-bold text-dark w-100" id="upgradeModalLabel">Liberte o Potencial da IA</h2>
                    <p class="text-muted">Aumente seus limites e desbloqueie recursos exclusivos.</p>
                </div>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <!-- Growth Plan -->
                    <div class="col-md-6 p-5 border-end">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h3 class="fw-bold text-primary mb-1">Growth</h3>
                                <span class="badge bg-success">Recomendado</span>
                            </div>
                            <div class="text-end">
                                <span class="d-block display-5 fw-bold text-dark">R$ 99</span>
                                <span class="text-muted small">/mês</span>
                            </div>
                        </div>

                        <p class="lead text-muted mb-4">Ideal para equipes em crescimento que precisam de automação.</p>

                        <ul class="list-unstyled mb-5">
                            <li class="mb-3 d-flex align-items-center gap-3"><i class="fas fa-check-circle text-primary fa-lg"></i> <span><strong>2 Milhões</strong> de Tokens IA</span></li>
                            <li class="mb-3 d-flex align-items-center gap-3"><i class="fas fa-check-circle text-primary fa-lg"></i> <span>Até <strong>5 Usuários</strong></span></li>
                            <li class="mb-3 d-flex align-items-center gap-3"><i class="fas fa-check-circle text-primary fa-lg"></i> <span>Agentes <strong>Personalizados</strong></span></li>
                            <li class="mb-3 d-flex align-items-center gap-3"><i class="fas fa-check-circle text-primary fa-lg"></i> <span>Suporte Prioritário</span></li>
                        </ul>

                        <div class="d-grid">
                            <a href="checkout.php?plan=growth" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm">
                                <i class="fas fa-rocket me-2"></i> Pagar Agora (R$ 99/mês)
                            </a>
                            <small class="text-center text-muted mt-2">Ativação imediata via Pix</small>
                        </div>
                    </div>

                    <!-- Enterprise Plan -->
                    <div class="col-md-6 p-5 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h3 class="fw-bold text-dark mb-1">Enterprise</h3>
                            </div>
                            <div class="text-end">
                                <span class="d-block display-6 fw-bold text-dark">R$ 299</span>
                                <span class="text-muted small">/mês</span>
                            </div>
                        </div>

                        <p class="lead text-muted mb-4">Para operações de grande escala e integrações profundas.</p>

                        <ul class="list-unstyled mb-5">
                            <li class="mb-3 d-flex align-items-center gap-3"><i class="fas fa-check text-dark fa-lg"></i> <span>Tokens IA <strong>Ilimitados</strong></span></li>
                            <li class="mb-3 d-flex align-items-center gap-3"><i class="fas fa-check text-dark fa-lg"></i> <span>Usuários <strong>Ilimitados</strong></span></li>
                            <li class="mb-3 d-flex align-items-center gap-3"><i class="fas fa-check text-dark fa-lg"></i> <span>Agentes <strong>Autônomos</strong> (APIs)</span></li>
                            <li class="mb-3 d-flex align-items-center gap-3"><i class="fas fa-check text-dark fa-lg"></i> <span>Implantação Assistida</span></li>
                        </ul>

                        <div class="d-grid">
                            <a href="checkout.php?plan=enterprise" class="btn btn-outline-dark btn-lg rounded-pill fw-bold">
                                <i class="fas fa-building me-2"></i> Contratar Enterprise
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openUpgradeModal() {
        var myModal = new bootstrap.Modal(document.getElementById('upgradeModal'));
        myModal.show();
    }
    
    // Auto-open if URL has #upgrade
    if(window.location.hash === '#upgrade') {
        document.addEventListener('DOMContentLoaded', function() {
            openUpgradeModal();
        });
    }
</script>
