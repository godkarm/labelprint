<?php
$pageTitle = $accion === 'crear' ? 'Nuevo Producto' : 'Editar Producto';
$activeNav = 'productos';
$isEdit    = $accion === 'editar';
$action    = $isEdit ? $basePath . '/productos/' . $producto['id'] . '/editar' : $basePath . '/productos/crear';
?>

<div class="row justify-content-center">
<div class="col-lg-8">

<div class="card lp-card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-box-seam me-2"></i>
            <?= $isEdit ? 'Editar producto: ' . htmlspecialchars($producto['nombre']) : 'Nuevo Producto' ?>
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="_csrf" value="<?= \App\Core\Session::csrf() ?>">

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Código <span class="text-danger">*</span></label>
                    <input type="text" name="codigo" class="form-control form-control-lg"
                           value="<?= htmlspecialchars($producto['codigo'] ?? '') ?>"
                           maxlength="100" placeholder="CAJA-PALTA-4KG"
                           style="text-transform:uppercase" required>
                    <div class="form-text">Se convertirá a mayúsculas</div>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold">Nombre del producto <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control form-control-lg"
                           value="<?= htmlspecialchars($producto['nombre'] ?? '') ?>"
                           maxlength="255" placeholder="CAJA PALTA 4 KG" required>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Guardar cambios' : 'Crear producto' ?>
                </button>
                <a href="<?= $basePath ?>/productos" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php if ($isEdit && !empty($subproductos)): ?>
<!-- Colores asociados -->
<div class="card lp-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-tags me-2"></i>Colores asociados</h6>
        <span class="badge bg-primary"><?= count($subproductos) ?></span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm lp-table mb-0">
            <thead><tr><th>#</th><th>Descripción</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($subproductos as $s): ?>
            <tr>
                <td class="text-muted"><?= $s['id'] ?></td>
                <td><?= htmlspecialchars($s['descripcion']) ?></td>
                <td><span class="badge <?= $s['activo'] ? 'bg-success' : 'bg-secondary' ?>"><?= $s['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($isEdit && !empty($disponibles)): ?>
<!-- Asociar colores -->
<div class="card lp-card">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Asociar colores</h6></div>
    <div class="card-body">
        <p class="text-muted small">Seleccione colores para asociar a este producto:</p>
        <form method="POST" action="<?= $basePath ?>/productos/<?= $producto['id'] ?>/editar">
            <input type="hidden" name="_csrf" value="<?= \App\Core\Session::csrf() ?>">
            <input type="hidden" name="codigo" value="<?= htmlspecialchars($producto['codigo']) ?>">
            <input type="hidden" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>">
            <?php foreach ($disponibles as $d): ?>
            <div class="form-check mb-1">
                <input class="form-check-input" type="checkbox" name="asociar_sub[]"
                       value="<?= $d['id'] ?>" id="sub_<?= $d['id'] ?>">
                <label class="form-check-label" for="sub_<?= $d['id'] ?>">
                    <?= htmlspecialchars($d['descripcion']) ?>
                </label>
            </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-sm btn-outline-primary mt-2">Guardar asociaciones</button>
        </form>
    </div>
</div>
<?php endif; ?>

</div>
</div>
