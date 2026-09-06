<?php
$pageTitle = 'Imprimir Etiqueta';
$activeNav = 'etiquetas';
$turnos = [1 => '1 - Mañana', 2 => '2 - Tarde', 3 => '3 - Noche'];
?>

<div class="row g-4">
    <!-- FORMULARIO -->
    <div class="col-lg-6">
        <div class="card lp-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-tag-fill me-2"></i>Datos de la Etiqueta</h5>
            </div>
            <div class="card-body">
                <div id="formErrors" class="alert alert-danger d-none"></div>
                <div id="formSuccess" class="alert alert-success d-none"></div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Producto <span class="text-danger">*</span></label>
                    <select id="productoSelect" class="form-select form-select-lg" required>
                        <option value="">— Seleccione un producto —</option>
                        <?php foreach ($productos as $p): ?>
                        <option value="<?= $p['id'] ?>" data-nombre="<?= htmlspecialchars($p['nombre']) ?>">
                            <?= htmlspecialchars($p['codigo']) ?> — <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Color <span class="text-danger">*</span></label>
                    <select id="subproductoSelect" class="form-select form-select-lg" required>
                        <option value="">— Cargando colores… —</option>
                    </select>
                    <div class="mt-2 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnNuevoSub"
                                data-bs-toggle="modal" data-bs-target="#modalNuevoSub">
                            <i class="bi bi-plus-circle"></i> Nuevo color
                        </button>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" id="cantidadInput" class="form-control form-control-lg"
                               min="1" placeholder="500" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">Copias <span class="text-danger">*</span></label>
                        <input type="number" id="copiasInput" class="form-control form-control-lg"
                               min="1" max="999" value="1" required>
                        <div class="form-text">Etiquetas físicas a imprimir</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold">Turno <span class="text-danger">*</span></label>
                        <select id="turnoSelect" class="form-select form-select-lg" required>
                            <option value="">— Turno —</option>
                            <?php foreach ($turnos as $val => $label): ?>
                            <option value="<?= $val ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">Fecha <span class="text-danger">*</span></label>
                        <input type="date" id="fechaInput" class="form-control form-control-lg"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="button" id="btnImprimir" class="btn btn-primary btn-lg">
                        <i class="bi bi-printer-fill me-2"></i>Imprimir Etiqueta
                    </button>
                    <button type="button" id="btnPreview" class="btn btn-outline-secondary">
                        <i class="bi bi-eye me-2"></i>Actualizar Vista Previa
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- VISTA PREVIA -->
    <div class="col-lg-6">
        <div class="card lp-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-eye me-2"></i>Vista Previa</h5>
                <span class="badge bg-secondary">80 × 40 mm</span>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center" style="min-height:300px;">
                <div id="labelPreview" class="lp-label-preview">
                    <div class="lp-label-header">
                        <div class="lp-label-logo" id="previewLogo">
                            <?php if (!empty($empresa['logo'])): ?>
                            <img src="<?= $basePath ?>/<?= htmlspecialchars($empresa['logo']) ?>" alt="Logo">
                            <?php else: ?>
                            <i class="bi bi-building text-muted"></i>
                            <?php endif; ?>
                        </div>
                        <div class="lp-label-empresa" id="previewEmpresa">
                            <?= htmlspecialchars($empresa['nombre'] ?? 'EMPRESA') ?>
                        </div>
                    </div>
                    <div class="lp-label-divider"></div>
                    <div class="lp-label-row">
                        <span class="lp-label-field-name">PRODUCTO:</span>
                        <span class="lp-label-field-val" id="previewProducto">—</span>
                    </div>
                    <div class="lp-label-row">
                        <span class="lp-label-field-name">COLOR:</span>
                        <span class="lp-label-field-val" id="previewSubproducto">—</span>
                    </div>
                    <div class="lp-label-divider"></div>
                    <div class="lp-label-bottom">
                        <div class="lp-label-kv">
                            <span class="lp-label-k">Cantidad</span>
                            <span class="lp-label-v" id="previewCantidad">—</span>
                        </div>
                        <div class="lp-label-kv">
                            <span class="lp-label-k">Turno</span>
                            <span class="lp-label-v" id="previewTurno">—</span>
                        </div>
                        <div class="lp-label-kv">
                            <span class="lp-label-k">Fecha</span>
                            <span class="lp-label-v" id="previewFecha">—</span>
                        </div>
                    </div>
                </div>
                <div class="mt-2 text-muted small text-center">
                    Vista previa aproximada — El resultado real depende de la configuración de la impresora.
                </div>
            </div>
        </div>

        <!-- Info de impresión -->
        <div class="card lp-card mt-3">
            <div class="card-body py-2">
                <div class="d-flex gap-3 small text-muted">
                    <span><i class="bi bi-printer me-1"></i>TSC TE200</span>
                    <span><i class="bi bi-arrows-fullscreen me-1"></i>80×40 mm</span>
                    <span><i class="bi bi-circle-fill me-1"></i>203 DPI</span>
                    <span><i class="bi bi-file-code me-1"></i>TSPL2</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Nuevo Color rápido -->
<div class="modal fade" id="modalNuevoSub" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-tags me-2"></i>Nuevo Color</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Descripción del color</label>
                    <input type="text" id="nuevoSubInput" class="form-control"
                           placeholder="Ej: 501B - NATURAL CCX1103000" maxlength="500">
                    <div class="form-text">Ejemplo: <code>501B - NATURAL CCX1103000</code></div>
                </div>
                <div id="nuevoSubError" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarSub" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Guardar y Seleccionar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Confirmar impresión -->
<div class="modal fade" id="modalConfirmar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-printer-fill me-2"></i>Confirmar Impresión</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Se enviará la etiqueta a la impresora <strong>TSC TE200</strong>.</p>
                <table class="table table-sm">
                    <tr><th>Producto</th><td id="confirmProducto">—</td></tr>
                    <tr><th>Color</th><td id="confirmSubproducto">—</td></tr>
                    <tr><th>Cantidad</th><td id="confirmCantidad">—</td></tr>
                    <tr><th>Turno</th><td id="confirmTurno">—</td></tr>
                    <tr><th>Fecha</th><td id="confirmFecha">—</td></tr>
                    <tr><th>Copias</th><td id="confirmCopias">—</td></tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnConfirmarImprimir" class="btn btn-primary btn-lg">
                    <i class="bi bi-printer-fill me-2"></i>Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF = '<?= \App\Core\Session::csrf() ?>';
const BASE = '<?= $basePath ?>';
const TURNOS = {1:'1 - Mañana', 2:'2 - Tarde', 3:'3 - Noche'};
</script>
<script src="<?= $basePath ?>/assets/js/etiquetas.js"></script>
