<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>LabelPrint · TSC TE200</title>
    <!-- CSS -->
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css">
    <!-- JS global cargado en HEAD para que esté disponible en scripts inline del body -->
    <script src="<?= $basePath ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $basePath ?>/assets/js/app.js"></script>
</head>
<body class="lp-body">

<!-- SIDEBAR -->
<nav class="lp-sidebar" id="sidebar">
    <div class="lp-sidebar-header">
        <i class="bi bi-printer-fill lp-sidebar-icon"></i>
        <span class="lp-sidebar-title">LabelPrint</span>
    </div>
    <ul class="lp-nav">
        <li><a href="<?= $basePath ?>/dashboard" class="lp-nav-link <?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a></li>
        <li><a href="<?= $basePath ?>/etiquetas" class="lp-nav-link <?= ($activeNav ?? '') === 'etiquetas' ? 'active' : '' ?>">
            <i class="bi bi-tag-fill"></i> Imprimir Etiqueta
        </a></li>
        <li><a href="<?= $basePath ?>/historial" class="lp-nav-link <?= ($activeNav ?? '') === 'historial' ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i> Historial
        </a></li>
        <li class="lp-nav-divider"></li>
        <li class="lp-nav-label">Catálogos</li>
        <li><a href="<?= $basePath ?>/productos" class="lp-nav-link <?= ($activeNav ?? '') === 'productos' ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i> Productos
        </a></li>
        <li><a href="<?= $basePath ?>/subproductos" class="lp-nav-link <?= ($activeNav ?? '') === 'subproductos' ? 'active' : '' ?>">
            <i class="bi bi-tags"></i> Colores
        </a></li>
        <li class="lp-nav-divider"></li>
        <li class="lp-nav-label">Sistema</li>
        <li><a href="<?= $basePath ?>/empresa" class="lp-nav-link <?= ($activeNav ?? '') === 'empresa' ? 'active' : '' ?>">
            <i class="bi bi-building"></i> Empresa
        </a></li>
        <li><a href="<?= $basePath ?>/configuracion" class="lp-nav-link <?= ($activeNav ?? '') === 'configuracion' ? 'active' : '' ?>">
            <i class="bi bi-gear-fill"></i> Configuración
        </a></li>
        <li><a href="<?= $basePath ?>/configuracion/diagnostico" class="lp-nav-link <?= ($activeNav ?? '') === 'diagnostico' ? 'active' : '' ?>">
            <i class="bi bi-bug"></i> Diagnóstico impresión
        </a></li>
        <li><a href="<?= $basePath ?>/check" class="lp-nav-link <?= ($activeNav ?? '') === 'check' ? 'active' : '' ?>">
            <i class="bi bi-shield-check"></i> Verificar sistema
        </a></li>
    </ul>
    <div class="lp-sidebar-footer">
        <span class="text-muted small"><?= htmlspecialchars(\App\Core\Session::userName()) ?></span>
        <a href="<?= $basePath ?>/logout" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</nav>

<!-- MAIN CONTENT -->
<main class="lp-main" id="mainContent">
    <div class="lp-topbar">
        <button class="lp-toggle-btn" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="lp-page-title"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard' ?></h1>
    </div>

    <div class="lp-content">
        <?php if ($flash = \App\Core\Session::getFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flash) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if ($flash = \App\Core\Session::getFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($flash) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?= $content ?>
    </div>
</main>

</body>
</html>
