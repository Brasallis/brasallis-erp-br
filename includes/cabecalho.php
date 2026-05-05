<?php
// includes/cabecalho.php - TOTAL RESCUE
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// No Docker, os caminhos partem da raiz
$base_url = '/';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brasallis Hub - Inteligência Operacional</title>
    <link rel="icon" type="image/png" href="/assets/img/pureza.png">
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Signature (Cache Buster ativo) -->
    <link rel="stylesheet" href="/assets/css/brasallis-hub.css?v=<?php echo time(); ?>">

    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f8fafc; 
            margin: 0; padding: 0;
            overflow-x: hidden;
        }
    </style>
</head>
<body class="bg-light">
<?php
// Carrega a navegação v6.0
require __DIR__ . '/navigation-brasallis.php';

// Busca avisos globais ativos
$conn_avisos = connect_db();
if ($conn_avisos) {
    $stmt_avisos = $conn_avisos->prepare("SELECT * FROM avisos_globais WHERE active = 1 ORDER BY created_at DESC");
    $stmt_avisos->execute();
    $avisos_ativos = $stmt_avisos->fetchAll(PDO::FETCH_ASSOC);

    foreach ($avisos_ativos as $aviso):
        $alertClass = match($aviso['tipo']) {
            'info' => 'info',
            'warning' => 'warning',
            'danger' => 'danger',
            'success' => 'success',
            default => 'primary'
        };
        $icon = match($aviso['tipo']) {
            'info' => 'fa-info-circle',
            'warning' => 'fa-exclamation-triangle',
            'danger' => 'fa-radiation',
            'success' => 'fa-bullhorn',
            default => 'fa-bell'
        };
    ?>
    <div class="alert alert-<?= $alertClass ?> alert-dismissible fade show rounded-0 mb-0 border-0 shadow-sm py-3 px-5 text-center aviso-global" role="alert" data-aviso-id="<?= $aviso['id'] ?>" style="z-index: 1000; position: relative; display: none;">
        <div class="d-flex align-items-center justify-content-center gap-3">
            <i class="fas <?= $icon ?> fs-5"></i>
            <div class="fw-bold">
                <span class="text-uppercase small me-2"><?= htmlspecialchars($aviso['titulo']) ?>:</span>
                <span class="fw-normal"><?= htmlspecialchars($aviso['mensagem']) ?></span>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" onclick="dismissAvisoGlobal(<?= $aviso['id'] ?>)"></button>
    </div>
    <?php endforeach; 
} ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.aviso-global').forEach(function(el) {
        const id = el.getAttribute('data-aviso-id');
        if (!localStorage.getItem('aviso_dismissed_' + id)) {
            el.style.display = 'block';
        }
    });
});

function dismissAvisoGlobal(id) {
    localStorage.setItem('aviso_dismissed_' + id, 'true');
}
</script>

<main class="brasallis-main">
    <div class="flex-grow-1 p-3 p-md-4 pt-md-0">
