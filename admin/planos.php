<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}
require_once '../includes/funcoes.php';
include_once '../includes/cabecalho.php';

$config = get_planos_config();
$planos = $config['detalhes'];
$plano_atual = get_user_plan();

?>

<h1 class="mb-4">Planos e Assinatura</h1>
<p class="lead">Veja os detalhes do seu plano atual e as opções de upgrade para desbloquear mais funcionalidades.</p>

<div class="row justify-content-center mt-5">
    <?php foreach ($planos as $key => $plano): ?>
        <div class="col-lg-4 mb-4">
            <div class="card h-100 shadow-sm <?php echo ($key === $plano_atual) ? 'border-primary border-2' : ''; ?>">
                <div class="card-header text-center <?php echo ($key === $plano_atual) ? 'bg-primary text-white' : 'bg-light'; ?>">
                    <h4 class="my-0 fw-normal"><?php echo $plano['nome']; ?></h4>
                </div>
                <div class="card-body d-flex flex-column">
                    <h1 class="card-title pricing-card-title text-center"><?php echo $plano['preco']['valor']; ?><small class="text-muted fw-light"><?php echo $plano['preco']['periodicidade']; ?></small></h1>
                    <ul class="list-unstyled mt-3 mb-4 text-center">
                        <?php foreach ($plano['features'] as $feature): ?>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php echo $feature; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="mt-auto text-center">
                        <?php if ($key === $plano_atual): ?>
                            <button type="button" class="w-100 btn btn-lg btn-success" disabled>Seu Plano Atual</button>
                        <?php else: ?>
                            <a href="checkout.php?plan=<?php echo $key; ?>" class="w-100 btn btn-lg btn-outline-primary">Fazer Upgrade</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
include_once '../includes/rodape.php';
?>
