<?php
// views/widgets/setores_card.php
// Requires $conn, $empresa_id

try {
    $stmtSetores = $conn->prepare("
        SELECT s.*, 
               (SELECT COUNT(*) FROM usuario_setor us WHERE us.setor_id = s.id) as total_users
        FROM setores s 
        WHERE s.empresa_id = ? 
        ORDER BY s.nome ASC 
        LIMIT 6
    ");
    $stmtSetores->execute([$empresa_id]);
    $setoresWidget = $stmtSetores->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $setoresWidget = [];
}
?>
<div class="col-12 col-xl-4" data-id="setores_card">
    <div class="card card-dashboard h-100 border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold m-0 text-navy">Meus Setores</h5>
            <a href="../admin/organizacao.php" class="btn btn-sm btn-light rounded-pill px-3">Ver Todos</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($setoresWidget)): ?>
                <div class="text-center p-4 text-muted">
                    <i class="fas fa-layer-group fa-2x mb-2 opacity-50"></i>
                    <p class="small mb-0">Nenhum setor criado.</p>
                    <a href="../admin/organizacao.php" class="btn btn-sm btn-link">Criar agora</a>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach($setoresWidget as $s): ?>
                    <a href="../admin/setor_dashboard.php?id=<?= $s['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-4 py-3 border-light">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle text-white d-flex align-items-center justify-content-center shadow-sm" 
                                 style="width: 35px; height: 35px; background-color: <?= $s['cor_hex'] ?>; font-size: 0.9rem;">
                                 <i class="fas fa-folder"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($s['nome']) ?></h6>
                                <small class="text-muted"><?= $s['total_users'] ?> membro(s)</small>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-muted small"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
