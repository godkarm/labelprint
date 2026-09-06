<?php $pageTitle = 'Verificar Sistema'; $activeNav = 'check'; ?>

<div class="row justify-content-center">
<div class="col-lg-8">

<div class="alert <?= $allOk ? 'alert-success' : 'alert-danger' ?> d-flex align-items-center gap-2">
    <i class="bi bi-<?= $allOk ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> fs-5"></i>
    <strong><?= $allOk ? 'El sistema está listo.' : 'Hay problemas que corregir antes de usar el sistema.' ?></strong>
</div>

<div class="card lp-card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>Estado del Entorno</h5>
    </div>
    <div class="card-body p-0">
        <table class="table lp-table mb-0">
            <thead><tr><th>Componente</th><th>Estado</th><th>Detalle</th></tr></thead>
            <tbody>
            <?php foreach ($checks as $c): ?>
            <tr>
                <td><strong><?= htmlspecialchars($c['label']) ?></strong></td>
                <td>
                    <?php if ($c['ok']): ?>
                    <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>OK</span>
                    <?php else: ?>
                    <span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i>Error</span>
                    <?php endif; ?>
                </td>
                <td class="small text-muted"><?= htmlspecialchars($c['msg']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <a href="<?= $basePath ?>/check" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-clockwise me-1"></i>Volver a verificar
    </a>
    <a href="<?= $basePath ?>/dashboard" class="btn btn-primary">
        <i class="bi bi-speedometer2 me-1"></i>Ir al Dashboard
    </a>
</div>

<div class="mt-3 alert alert-warning py-2 small">
    <i class="bi bi-info-circle me-1"></i>
    También puede acceder directamente a <a href="check.php" target="_blank"><code>check.php</code></a> sin necesidad de autenticarse.
</div>

</div>
</div>
