<?php $pageTitle = 'Historial de Impresiones'; $activeNav = 'historial'; ?>

<!-- Filtros -->
<div class="card lp-card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="<?= $basePath ?>/historial" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="buscar" class="form-control"
                       placeholder="Buscar producto o color..."
                       value="<?= htmlspecialchars($buscar) ?>">
            </div>
            <div class="col-md-2">
                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <?php foreach (['PENDIENTE','IMPRESO','ERROR','CANCELADO'] as $e): ?>
                    <option value="<?= $e ?>" <?= $estado === $e ? 'selected' : '' ?>><?= $e ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($fecha) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
                <a href="<?= $basePath ?>/historial" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<!-- Resultados -->
<div class="card lp-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Impresiones</h5>
        <span class="badge bg-secondary"><?= number_format((int)$total) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($impresiones)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-printer fs-1 d-block mb-2"></i>No hay registros.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover lp-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Empresa</th>
                        <th>Producto</th>
                        <th>Color</th>
                        <th>Cant.</th>
                        <th>Turno</th>
                        <th>Fecha etiq.</th>
                        <th>Copias</th>
                        <th>Estado</th>
                        <th>Fecha imp.</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $turnos = [1 => 'Mañana', 2 => 'Tarde', 3 => 'Noche'];
                foreach ($impresiones as $imp):
                    $cls = match($imp['estado']) {
                        'IMPRESO' => 'success', 'ERROR' => 'danger',
                        'CANCELADO' => 'secondary', default => 'warning'
                    };
                ?>
                <tr data-id="<?= $imp['id'] ?>">
                    <td class="text-muted">
                        <?= $imp['id'] ?>
                        <?php if ($imp['es_reimpresion']): ?>
                        <span class="badge bg-warning text-dark ms-1" title="Reimpresión">R</span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><strong><?= htmlspecialchars($imp['nombre_empresa'] ?? '—') ?></strong></td>
                    <td><strong><?= htmlspecialchars($imp['producto_nombre']) ?></strong></td>
                    <td class="small text-muted" style="max-width:180px;">
                        <span title="<?= htmlspecialchars($imp['subproducto_descripcion']) ?>">
                            <?= htmlspecialchars(mb_strimwidth($imp['subproducto_descripcion'], 0, 30, '…')) ?>
                        </span>
                    </td>
                    <td><?= number_format((int)$imp['cantidad']) ?></td>
                    <td><?= htmlspecialchars($turnos[$imp['turno']] ?? $imp['turno']) ?></td>
                    <td><?= date('d/m/Y', strtotime($imp['fecha_etiqueta'])) ?></td>
                    <td><?= (int)$imp['copias'] ?></td>
                    <td><span class="badge bg-<?= $cls ?>"><?= $imp['estado'] ?></span></td>
                    <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($imp['creado_en'])) ?></td>
                    <td>
                        <a href="<?= $basePath ?>/historial/<?= $imp['id'] ?>"
                           class="btn btn-sm btn-outline-secondary me-1" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                onclick="reimprimir(<?= $imp['id'] ?>)" title="Reimprimir">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="confirmarEliminar(<?= $imp['id'] ?>, '<?= htmlspecialchars(addslashes($imp['producto_nombre'])) ?>', '<?= htmlspecialchars(addslashes($imp['nombre_empresa'] ?? '')) ?>', '<?= date('d/m/Y', strtotime($imp['fecha_etiqueta'])) ?>')"
                                title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center py-3">
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link"
                           href="?page=<?= $i ?>&buscar=<?= urlencode($buscar) ?>&estado=<?= urlencode($estado) ?>&fecha=<?= urlencode($fecha) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>

        <?php endif; ?>
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
                    <input type="number" id="reimprimirCopias" class="form-control" min="1" max="999" value="1">
                    <div class="form-text">Se usarán los datos originales del registro.</div>
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
                <p class="mb-3">¿Está seguro de eliminar este registro?</p>
                <table class="table table-sm">
                    <tr><th>Producto</th><td id="eliminarProducto">—</td></tr>
                    <tr><th>Empresa</th><td id="eliminarEmpresa">—</td></tr>
                    <tr><th>Fecha</th><td id="eliminarFecha">—</td></tr>
                </table>
                <div class="alert alert-warning py-2 small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Esta acción no se puede deshacer. Solo se elimina el registro de impresión.
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
    var reimprimirId = null;
    var eliminarId   = null;

    // Inicializar modales Bootstrap (Bootstrap ya está en HEAD)
    var modalReimprimir = new bootstrap.Modal(document.getElementById('modalReimprimir'));
    var modalEliminar   = new bootstrap.Modal(document.getElementById('modalEliminar'));

    // Exponer funciones para los onclick inline del HTML
    window.reimprimir = function(id) {
        reimprimirId = id;
        document.getElementById('reimprimirCopias').value = 1;
        modalReimprimir.show();
    };

    window.confirmarEliminar = function(id, producto, empresa, fecha) {
        eliminarId = id;
        document.getElementById('eliminarProducto').textContent = producto  || '—';
        document.getElementById('eliminarEmpresa').textContent  = empresa   || '—';
        document.getElementById('eliminarFecha').textContent    = fecha     || '—';
        modalEliminar.show();
    };

    // Helper: mostrar toast (usa showToast de app.js que ya está cargado)
    function toast(msg, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(msg, type);
        } else {
            alert(msg);
        }
    }

    // Helper: fetch JSON con detección de respuesta no-JSON
    function fetchJSON(url, opts) {
        return fetch(url, opts).then(function(r) {
            var ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                throw new Error('Respuesta inesperada del servidor (no JSON). Verifique la sesión.');
            }
            return r.json();
        });
    }

    // ---- REIMPRIMIR ----
    document.getElementById('btnConfirmarReimprimir').addEventListener('click', function() {
        if (!reimprimirId) return;
        var btn   = this;
        var copias = document.getElementById('reimprimirCopias').value;

        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando…';

        fetchJSON(BASE + '/historial/' + reimprimirId + '/reimprimir', {
            method:  'POST',
            headers: {
                'Content-Type':      'application/x-www-form-urlencoded',
                'X-Requested-With':  'XMLHttpRequest'
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
        if (!eliminarId) return;
        var btn = this;

        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Eliminando…';

        fetchJSON(BASE + '/historial/' + eliminarId + '/eliminar', {
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
                // Eliminar la fila de la tabla sin recargar
                var fila = document.querySelector('tr[data-id="' + eliminarId + '"]');
                if (fila) {
                    fila.style.transition = 'opacity 0.4s';
                    fila.style.opacity    = '0';
                    setTimeout(function() { fila.remove(); }, 420);
                }
                toast(data.message, 'success');
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
