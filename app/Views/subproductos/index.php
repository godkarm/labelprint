<?php $pageTitle = 'Colores'; $activeNav = 'subproductos'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="<?= $basePath ?>/subproductos/crear" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Color
    </a>
    <form method="GET" action="<?= $basePath ?>/subproductos" class="d-flex gap-2">
        <input type="text" name="buscar" class="form-control"
               placeholder="Buscar..." value="<?= htmlspecialchars($buscar) ?>" style="width:280px">
        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
        <?php if ($buscar): ?>
        <a href="<?= $basePath ?>/subproductos" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
        <?php endif; ?>
    </form>
</div>

<div class="card lp-card">
    <div class="card-body p-0">
        <?php if (empty($subproductos)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-tags fs-1 d-block mb-2"></i>
            No hay colores registrados.
            <a href="<?= $basePath ?>/subproductos/crear">Crear el primero</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover lp-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Descripción del Color</th>
                        <th>Productos asociados</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($subproductos as $s): ?>
                <tr>
                    <td class="text-muted"><?= $s['id'] ?></td>
                    <td>
                        <code class="text-dark"><?= htmlspecialchars($s['descripcion']) ?></code>
                    </td>
                    <td>
                        <span class="text-muted small">
                            <?= htmlspecialchars($s['productos_asociados'] ?? '—') ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $s['activo'] ? 'bg-success' : 'bg-secondary' ?> badge-estado">
                            <?= $s['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= $basePath ?>/subproductos/<?= $s['id'] ?>/editar"
                           class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="toggleEstado('<?= $basePath ?>/subproductos/<?= $s['id'] ?>/toggle', <?= $s['id'] ?>, this)">
                            <?= $s['activo'] ? 'Desactivar' : 'Activar' ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>window.CSRF = '<?= \App\Core\Session::csrf() ?>';</script>
