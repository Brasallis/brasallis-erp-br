<?php
// views/widgets/developer_card.php
?>
<div class="col-12 col-md-6 col-xl-4" data-id="developer_card">
    <div class="card card-dashboard h-100 p-4 border-0 shadow-sm position-relative overflow-hidden" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
        
        <!-- Decoration -->
        <i class="fas fa-code position-absolute text-white opacity-10" style="font-size: 8rem; right: -20px; top: -20px;"></i>
        
        <div class="d-flex flex-column h-100 position-relative z-1 text-white">
            <div class="mb-4">
                <span class="badge bg-info bg-opacity-25 text-info border border-info border-opacity-25 mb-2">Developers</span>
                <h4 class="fw-bold mb-1">API & Integrações</h4>
                <p class="text-white-50 small mb-0">Conecte seu E-commerce ou ERP diretamente ao sistema.</p>
            </div>
            
            <div class="mt-auto">
                <a href="../developers.php" class="btn btn-sm btn-light text-dark fw-bold w-100 d-flex justify-content-between align-items-center">
                    <span>Ver Documentação</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
                <div class="mt-3 d-flex align-items-center gap-3 small text-white-50">
                    <div><i class="fas fa-key text-warning me-1"></i> Chaves API</div>
                    <div class="vr opacity-25"></div>
                    <div><i class="fas fa-book me-1"></i> V1.0</div>
                </div>
            </div>
        </div>
    </div>
</div>
