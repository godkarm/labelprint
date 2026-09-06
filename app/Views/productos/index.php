<?php $pageTitle = 'Productos'; $activeNav = 'productos'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= $basePath ?>/productos/crear" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Nuevo Producto
        </a>
    </div>
    <form method="GET" action="<?= $basePath ?>/productos" class="d-flex gap-2">
        <input type="text" name="buscar" class="form-control"
               placeholder="Buscar..." value="<?= htmlspecialchars($buscar) ?>" style="width:220px">
        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
        <?php if ($buscar): ?>
        <a href="<?= $basePath ?>/productos" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
        <?php endif; ?>
    </form>
</div>

<div class="card lp-card">
    <div class="card-body p-0">
        <?php if (empty($productos)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
            No hay productos registrados.
            <a href="<?= $basePath ?>/productos/crear">Crear el primero</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover lp-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Colores</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($productos as $p): ?>
                <tr>
                    <td class="text-muted"><?= $p['id'] ?></td>
                    <td><code><?= htmlspecialchars($p['codigo']) ?></code></td>
                    <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                    <td><span class="badge bg-info"><?= (int)$p['total_subproductos'] ?></span></td>
                    <td>
                        <span class="badge <?= $p['activo'] ? 'bg-success' : 'bg-secondary' ?> badge-estado">
                            <?= $p['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= $basePath ?>/productos/<?= $p['id'] ?>/editar"
                           class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="toggleEstado('<?= $basePath ?>/productos/<?= $p['id'] ?>/toggle', <?= $p['id'] ?>, this)">
                            <?= $p['activo'] ? 'Desactivar' : 'Activar' ?>
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
