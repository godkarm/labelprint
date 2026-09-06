<?php
$pageTitle = $accion === 'crear' ? 'Nuevo Color' : 'Editar Color';
$activeNav = 'subproductos';
$isEdit    = $accion === 'editar';
$action    = $isEdit ? $basePath . '/subproductos/' . $subproducto['id'] . '/editar' : $basePath . '/subproductos/crear';
$asociadosIds = $asociadosIds ?? [];
?>

<div class="row justify-content-center">
<div class="col-lg-7">

<div class="card lp-card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-tags me-2"></i>
            <?= $isEdit ? 'Editar: ' . htmlspecialchars($subproducto['descripcion']) : 'Nuevo Color' ?>
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="_csrf" value="<?= \App\Core\Session::csrf() ?>">

            <div class="mb-4">
                <label class="form-label fw-bold">
                    Descripción del Color <span class="text-danger">*</span>
                </label>
                <input type="text" name="descripcion" class="form-control form-control-lg"
                       value="<?= htmlspecialchars($subproducto['descripcion'] ?? '') ?>"
                       maxlength="500"
                       placeholder="Ej: 501B - NATURAL CCX1103000"
                       required>
                <div class="form-text">
                    Ingrese la descripción completa del color tal como debe aparecer en la etiqueta.
                    Máximo 500 caracteres.
                </div>
            </div>

            <?php if (!empty($productos)): ?>
            <div class="mb-4">
                <label class="form-label fw-bold">Productos asociados</label>
                <div class="border rounded p-3" style="max-height:200px;overflow-y:auto;">
                    <?php foreach ($productos as $p): ?>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox"
                               name="productos[]"
                               value="<?= $p['id'] ?>"
                               id="prod_<?= $p['id'] ?>"
                               <?= in_array($p['id'], $asociadosIds) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="prod_<?= $p['id'] ?>">
                            <code><?= htmlspecialchars($p['codigo']) ?></code>
                            — <?= htmlspecialchars($p['nombre']) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-text">Asocie este color a uno o más productos.</div>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Guardar cambios' : 'Crear color' ?>
                </button>
                <a href="<?= $basePath ?>/subproductos" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
