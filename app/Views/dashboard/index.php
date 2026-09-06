<?php $pageTitle = 'Dashboard'; $activeNav = 'dashboard'; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="lp-stat-card lp-stat-primary">
            <div class="lp-stat-icon"><i class="bi bi-tag-fill"></i></div>
            <div class="lp-stat-value"><?= number_format((int)$stats['impresas_hoy']) ?></div>
            <div class="lp-stat-label">Etiquetas hoy</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="lp-stat-card lp-stat-success">
            <div class="lp-stat-icon"><i class="bi bi-printer"></i></div>
            <div class="lp-stat-value"><?= number_format((int)$stats['impresas_mes']) ?></div>
            <div class="lp-stat-label">Este mes</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="lp-stat-card lp-stat-warning">
            <div class="lp-stat-icon"><i class="bi bi-box-seam"></i></div>
            <div class="lp-stat-value"><?= (int)$stats['productos'] ?></div>
            <div class="lp-stat-label">Productos activos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="lp-stat-card lp-stat-info">
            <div class="lp-stat-icon"><i class="bi bi-tags"></i></div>
            <div class="lp-stat-value"><?= (int)$stats['subproductos'] ?></div>
            <div class="lp-stat-label">Colores</div>
        </div>
    </div>
</div>

<!-- Quick Action -->
<div class="mb-4">
    <a href="<?= $basePath ?>/etiquetas" class="btn btn-primary btn-lg">
        <i class="bi bi-tag-fill me-2"></i>Nueva Etiqueta
    </a>
</div>

<!-- Últimas impresiones -->
<div class="card lp-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Últimas Impresiones</h5>
        <a href="<?= $basePath ?>/historial" class="btn btn-sm btn-outline-secondary">Ver todo</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($ultimasImpresiones)): ?>
        <div class="text-center text-muted py-4">No hay impresiones registradas aún.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0 lp-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Empresa</th>
                        <th>Producto</th>
                        <th>Color</th>
                        <th>Cant.</th>
                        <th>Turno</th>
                        <th>Fecha</th>
                        <th>Copias</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ultimasImpresiones as $imp): ?>
                    <?php $turnos = [1 => 'Mañana', 2 => 'Tarde', 3 => 'Noche']; ?>
                    <tr>
                        <td class="text-muted"><?= $imp['id'] ?></td>
                        <td class="small"><?= htmlspecialchars($imp['nombre_empresa'] ?? '—') ?></td>
                        <td><strong><?= htmlspecialchars($imp['producto_nombre']) ?></strong></td>
                        <td class="text-muted small"><?= htmlspecialchars($imp['subproducto_descripcion']) ?></td>
                        <td><?= number_format((int)$imp['cantidad']) ?></td>
                        <td><?= htmlspecialchars($turnos[$imp['turno']] ?? $imp['turno']) ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($imp['fecha_etiqueta']))) ?></td>
                        <td><?= (int)$imp['copias'] ?></td>
                        <td><?php
                            $cls = match($imp['estado']) {
                                'IMPRESO' => 'success', 'ERROR' => 'danger',
                                'CANCELADO' => 'secondary', default => 'warning'
                            };
                        ?>
                        <span class="badge bg-<?= $cls ?>"><?= $imp['estado'] ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
