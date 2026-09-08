<?php
$pageTitle = 'Detalle de Impresión #' . $impresion['id'];
$activeNav = 'historial';
$turnos    = [1 => '1 - Mañana', 2 => '2 - Tarde', 3 => '3 - Noche'];
$subLen    = strlen($impresion['subproducto_descripcion'] ?? '');
?>

<div class="row justify-content-center">
<div class="col-lg-8">

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= $basePath ?>/historial" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <?php
    $cls = match($impresion['estado']) {
        'IMPRESO' => 'success', 'ERROR' => 'danger',
        'CANCELADO' => 'secondary', default => 'warning'
    };
    ?>
    <span class="badge bg-<?= $cls ?> fs-6"><?= $impresion['estado'] ?></span>
</div>

<div class="card lp-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-printer me-2"></i>Impresión #<?= $impresion['id'] ?>
            <?php if ($impresion['es_reimpresion']): ?>
            <span class="badge bg-warning text-dark ms-2 fs-6">Reimpresión</span>
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <table class="table table-sm lp-table">
                    <tr><th>Empresa</th><td><strong><?= htmlspecialchars($impresion['nombre_empresa'] ?? '—') ?></strong></td></tr>
                    <tr><th>Producto</th><td><strong><?= htmlspecialchars($impresion['producto_nombre']) ?></strong></td></tr>
                    <tr><th>Color</th><td><code><?= htmlspecialchars($impresion['subproducto_descripcion']) ?></code></td></tr>
                    <tr><th>Cantidad</th><td><?= number_format((int)$impresion['cantidad']) ?></td></tr>
                    <tr><th>Turno</th><td><?= htmlspecialchars($turnos[$impresion['turno']] ?? $impresion['turno']) ?></td></tr>
                    <tr><th>Fecha etiqueta</th><td><?= date('d/m/Y', strtotime($impresion['fecha_etiqueta'])) ?></td></tr>
                    <tr><th>Copias</th><td><?= (int)$impresion['copias'] ?></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm lp-table">
                    <tr><th>Fecha impresión</th><td><?= date('d/m/Y H:i:s', strtotime($impresion['creado_en'])) ?></td></tr>
                    <tr><th>Estado</th><td><span class="badge bg-<?= $cls ?>"><?= $impresion['estado'] ?></span></td></tr>
                    <?php if ($impresion['es_reimpresion'] && $impresion['impresion_original_id']): ?>
                    <tr>
                        <th>Original</th>
                        <td><a href="<?= $basePath ?>/historial/<?= $impresion['impresion_original_id'] ?>">#<?= $impresion['impresion_original_id'] ?></a></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($impresion['error_detalle']): ?>
                    <tr><th>Error</th><td><small class="text-danger"><?= htmlspecialchars($impresion['error_detalle']) ?></small></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Vista previa de la etiqueta — usa datos del snapshot histórico -->
        <hr>
        <h6>Vista previa de la etiqueta</h6>
        <div class="d-flex justify-content-center mt-3">
            <div class="lp-label-preview">
                <div class="lp-label-header">
                    <div class="lp-label-logo">
                        <?php if (!empty($logoEmpresa)): ?>
                        <img src="<?= $basePath ?>/<?= htmlspecialchars($logoEmpresa) ?>" alt="Logo">
                        <?php else: ?>
                        <i class="bi bi-building text-muted"></i>
                        <?php endif; ?>
                    </div>
                    <!-- Nombre de empresa del SNAPSHOT HISTÓRICO, no el actual -->
                    <div class="lp-label-empresa">
                        <?= htmlspecialchars($impresion['nombre_empresa'] ?? 'EMPRESA') ?>
                    </div>
                </div>
                <div class="lp-label-divider"></div>
                <div class="lp-label-row">
                    <span class="lp-label-field-name">PRODUCTO:</span>
                    <span class="lp-label-field-val"><?= htmlspecialchars($impresion['producto_nombre']) ?></span>
                </div>
                <div class="lp-label-divider"></div>
                <div class="lp-label-row">
                    <span class="lp-label-field-name">COLOR:</span>
                    <span class="lp-label-field-val" style="font-size:<?= $subLen > 35 ? '.60rem' : ($subLen > 25 ? '.65rem' : '.72rem') ?>">
                        <?= htmlspecialchars($impresion['subproducto_descripcion']) ?>
                    </span>
                </div>
                <div class="lp-label-divider"></div>
                <div class="lp-label-bottom">
                    <div class="lp-label-kv">
                        <span class="lp-label-k">CANTIDAD</span>
                        <span class="lp-label-v"><?= number_format((int)$impresion['cantidad']) ?></span>
                    </div>
                    <div class="lp-label-kv">
                        <span class="lp-label-k">TURNO</span>
                        <span class="lp-label-v"><?= htmlspecialchars($turnos[$impresion['turno']] ?? '') ?></span>
                    </div>
                    <div class="lp-label-kv">
                        <span class="lp-label-k">FECHA</span>
                        <span class="lp-label-v"><?= date('d/m/Y', strtotime($impresion['fecha_etiqueta'])) ?></span>
                    </div>
                    <div class="lp-label-kv">
                        <span class="lp-label-k">COPIAS</span>
                        <span class="lp-label-v"><?= (int)$impresion['copias'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="d-flex justify-content-center gap-3 mt-4">
            <button type="button" class="btn btn-primary" onclick="reimprimir(<?= $impresion['id'] ?>)">
                <i class="bi bi-arrow-repeat me-2"></i>Reimprimir esta etiqueta
            </button>
            <button type="button" class="btn btn-outline-danger" onclick="confirmarEliminar(<?= $impresion['id'] ?>, '<?= htmlspecialchars(addslashes($impresion['producto_nombre'])) ?>', '<?= htmlspecialchars(addslashes($impresion['nombre_empresa'] ?? '')) ?>')">
                <i class="bi bi-trash me-2"></i>Eliminar registro
            </button>
        </div>
    </div>
</div>

</div>
</div>

<!-- Modal Reimprimir -->
<div class="modal fade" id="modalReimprimir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Reimprimir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Número de copias</label>
                    <input type="number" id="reimprimirCopias" class="form-control" min="1" max="999" value="<?= (int)$impresion['copias'] ?>">
                    <div class="form-text">Se usarán los datos originales del registro, incluyendo la empresa: <strong><?= htmlspecialchars($impresion['nombre_empresa'] ?? '—') ?></strong></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnConfirmarReimprimir" class="btn btn-primary">
                    <i class="bi bi-printer-fill me-2"></i>Reimprimir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Eliminar Impresión</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">¿Está seguro de eliminar este registro de impresión?</p>
                <table class="table table-sm">
                    <tr><th>Producto</th><td id="eliminarProducto">—</td></tr>
                    <tr><th>Empresa</th><td id="eliminarEmpresa">—</td></tr>
                    <tr><th>Fecha</th><td><?= date('d/m/Y', strtotime($impresion['fecha_etiqueta'])) ?></td></tr>
                </table>
                <div class="alert alert-warning py-2 small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Esta acción no se puede deshacer. Solo se elimina el registro de impresión.<br>
                    No se eliminan productos, colores ni la empresa.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnConfirmarEliminar" class="btn btn-danger">
                    <i class="bi bi-trash me-2"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var CSRF = '<?= \App\Core\Session::csrf() ?>';
    var BASE = '<?= $basePath ?>';
    var IMP_ID = <?= (int)$impresion['id'] ?>;

    var modalReimprimir = new bootstrap.Modal(document.getElementById('modalReimprimir'));
    var modalEliminar   = new bootstrap.Modal(document.getElementById('modalEliminar'));

    function toast(msg, type) {
        if (typeof window.showToast === 'function') window.showToast(msg, type);
        else alert(msg);
    }

    function fetchJSON(url, opts) {
        return fetch(url, opts).then(function(r) {
            var ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                throw new Error('Respuesta inesperada del servidor. Verifique la sesión.');
            }
            return r.json();
        });
    }

    // Exponer para onclick inline
    window.reimprimir = function(id) { modalReimprimir.show(); };
    window.confirmarEliminar = function(id, producto, empresa) {
        document.getElementById('eliminarProducto').textContent = producto || '—';
        document.getElementById('eliminarEmpresa').textContent  = empresa  || '—';
        modalEliminar.show();
    };

    // ---- REIMPRIMIR ----
    document.getElementById('btnConfirmarReimprimir').addEventListener('click', function() {
        var btn    = this;
        var copias = document.getElementById('reimprimirCopias').value;

        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando…';

        fetchJSON(BASE + '/historial/' + IMP_ID + '/reimprimir', {
            method:  'POST',
            headers: {
                'Content-Type':     'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '_csrf=' + encodeURIComponent(CSRF) + '&copias=' + encodeURIComponent(copias)
        })
        .then(function(data) {
            modalReimprimir.hide();
            toast(data.message, data.success ? 'success' : 'danger');
        })
        .catch(function(err) {
            modalReimprimir.hide();
            toast('Error: ' + err.message, 'danger');
        })
        .finally(function() {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-printer-fill me-2"></i>Reimprimir';
        });
    });

    // ---- ELIMINAR ----
    document.getElementById('btnConfirmarEliminar').addEventListener('click', function() {
        var btn = this;
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Eliminando…';

        fetchJSON(BASE + '/historial/' + IMP_ID + '/eliminar', {
            method:  'POST',
            headers: {
                'Content-Type':     'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '_csrf=' + encodeURIComponent(CSRF)
        })
        .then(function(data) {
            modalEliminar.hide();
            if (data.success) {
                toast(data.message, 'success');
                // Redirigir al historial tras breve pausa
                setTimeout(function() {
                    window.location.href = BASE + '/historial';
                }, 1200);
            } else {
                toast(data.message || 'No se pudo eliminar.', 'danger');
                btn.disabled  = false;
                btn.innerHTML = '<i class="bi bi-trash me-2"></i>Eliminar';
            }
        })
        .catch(function(err) {
            modalEliminar.hide();
            toast('Error: ' + err.message, 'danger');
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-trash me-2"></i>Eliminar';
        });
    });

})();
</script>
