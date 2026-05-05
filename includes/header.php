<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Brasallis'; ?></title>
    <link rel="icon" type="image/png" href="/assets/img/pureza.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Font: Roboto (Google Standard) -->
    <link rel="preconnect" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom CSS -->
    <link href="assets/css/landing.css?v=14.0" rel="stylesheet">
</head>
<body>

    <?php if (isset($show_legacy_nav) && $show_legacy_nav): ?>
    <nav class="navbar navbar-expand-lg navbar-trust sticky-top">
        <!-- (REMOVED LEGACY NAV CONTENT TO UNIFY BRASALLIS 360) -->
    </nav>
    <?php endif; ?>
