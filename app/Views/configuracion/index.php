<?php $pageTitle = 'Configuración'; $activeNav = 'configuracion'; ?>

<div class="row g-4">
    <!-- Config impresora -->
    <div class="col-lg-8">
        <div class="card lp-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-printer me-2"></i>Configuración de Impresora — TSC TE200</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $basePath ?>/configuracion/impresora" id="formImpresora">
                    <input type="hidden" name="_csrf" value="<?= \App\Core\Session::csrf() ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre de impresora en Windows</label>
                            <input type="text" name="nombre" class="form-control"
                                   value="<?= htmlspecialchars($impresora['nombre'] ?? 'TSC TE200') ?>">
                            <div class="form-text">Debe coincidir <strong>exactamente</strong> con el nombre en
                                Panel de Control → Impresoras.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Modelo</label>
                            <input type="text" name="modelo" class="form-control"
                                   value="<?= htmlspecialchars($impresora['modelo'] ?? 'TE200') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">DPI</label>
                            <select name="dpi" class="form-select">
                                <option value="203" <?= ((int)($impresora['dpi'] ?? 203)) === 203 ? 'selected' : '' ?>>203 DPI</option>
                                <option value="300" <?= ((int)($impresora['dpi'] ?? 203)) === 300 ? 'selected' : '' ?>>300 DPI</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Ancho (mm)</label>
                            <input type="number" name="ancho_mm" class="form-control"
                                   value="<?= htmlspecialchars((string)($impresora['ancho_mm'] ?? 80)) ?>"
                                   step="0.5" min="10" max="200">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Alto (mm)</label>
                            <input type="number" name="alto_mm" class="form-control"
                                   value="<?= htmlspecialchars((string)($impresora['alto_mm'] ?? 40)) ?>"
                                   step="0.5" min="10" max="200">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Velocidad (1-14)</label>
                            <input type="number" name="velocidad" class="form-control"
                                   value="<?= (int)($impresora['velocidad'] ?? 4) ?>" min="1" max="14">
                            <div class="form-text">Recomendado: 4</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Densidad (0-15)</label>
                            <input type="number" name="densidad" class="form-control"
                                   value="<?= (int)($impresora['densidad'] ?? 8) ?>" min="0" max="15">
                            <div class="form-text">Recomendado: 8</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Orientación</label>
                            <select name="orientacion" class="form-select">
                                <option value="horizontal" <?= ($impresora['orientacion'] ?? 'horizontal') === 'horizontal' ? 'selected' : '' ?>>Horizontal (80×40)</option>
                                <option value="vertical"   <?= ($impresora['orientacion'] ?? 'horizontal') === 'vertical'   ? 'selected' : '' ?>>Vertical (40×80)</option>
                            </select>
                        </div>

                        <!-- Tipo de conexión -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Tipo de conexión</label>
                            <div class="d-flex gap-4">
                                <?php foreach (['usb' => 'USB / Windows', 'tcp' => 'TCP/IP (Red)', 'shared' => 'Compartida'] as $val => $label): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_conexion"
                                           value="<?= $val ?>" id="conn_<?= $val ?>"
                                           <?= ($impresora['tipo_conexion'] ?? 'usb') === $val ? 'checked' : '' ?>
                                           onchange="updateConnFields()">
                                    <label class="form-check-label fw-semibold" for="conn_<?= $val ?>"><?= $label ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Campos TCP/IP -->
                        <div id="fieldsTcp" class="col-12"
                             style="<?= ($impresora['tipo_conexion'] ?? 'usb') === 'tcp' ? '' : 'display:none' ?>">
                            <div class="card bg-light border-0 p-3">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">IP de la impresora</label>
                                        <input type="text" name="ip" class="form-control"
                                               value="<?= htmlspecialchars($impresora['ip'] ?? '') ?>"
                                               placeholder="192.168.1.100">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Puerto</label>
                                        <input type="number" name="puerto" class="form-control"
                                               value="<?= (int)($impresora['puerto'] ?? 9100) ?>"
                                               min="1" max="65535">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Campos USB Windows -->
                        <div id="fieldsUsb" class="col-12"
                             style="<?= ($impresora['tipo_conexion'] ?? 'usb') === 'usb' ? '' : 'display:none' ?>">
                            <div class="card bg-light border-0 p-3">
                                <label class="form-label fw-bold">Puerto USB de Windows <span class="text-muted fw-normal">(opcional)</span></label>
                                <input type="text" name="nombre_compartido" class="form-control"
                                       value="<?= htmlspecialchars($impresora['nombre_compartido'] ?? '') ?>"
                                       placeholder="USB001  ó  COM3  ó  dejar vacío">
                                <div class="form-text">
                                    Si dejar el <strong>Nombre de impresora</strong> arriba no funciona, ingrese aquí el puerto
                                    asignado en Windows (ej: <code>USB001</code>, <code>USB002</code>, <code>COM3</code>).
                                    Encuéntrelo en: Propiedades de la impresora → Puertos.
                                </div>
                            </div>
                        </div>

                        <!-- Campos Compartida -->
                        <div id="fieldsShared" class="col-12"
                             style="<?= ($impresora['tipo_conexion'] ?? 'usb') === 'shared' ? '' : 'display:none' ?>">
                            <div class="card bg-light border-0 p-3">
                                <label class="form-label">Nombre de impresora compartida</label>
                                <input type="text" name="nombre_compartido" class="form-control"
                                       value="<?= htmlspecialchars($impresora['nombre_compartido'] ?? '') ?>"
                                       placeholder="\\SERVIDOR\TSC-TE200  ó  nombre local">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Guardar configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Panel derecho -->
    <div class="col-lg-4">

        <!-- Estado -->
        <div class="card lp-card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-wifi me-2"></i>Estado de la impresora</h6></div>
            <div class="card-body">
                <div id="estadoImpresora" class="text-muted small">Haga clic para verificar…</div>
                <button class="btn btn-sm btn-outline-secondary mt-2" onclick="checkEstado()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Verificar estado
                </button>
            </div>
        </div>

        <!-- Prueba de impresión -->
        <div class="card lp-card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-printer me-2"></i>Prueba de impresión</h6></div>
            <div class="card-body">
                <p class="small text-muted mb-3">
                    Envía una etiqueta de prueba con líneas de referencia a la impresora.
                </p>
                <button class="btn btn-outline-primary w-100 mb-2" onclick="pruebaPrinter(this)">
                    <i class="bi bi-printer-fill me-2"></i>Enviar etiqueta de prueba
                </button>
                <a href="<?= $basePath ?>/configuracion/diagnostico" class="btn btn-outline-secondary w-100 mb-2">
                    <i class="bi bi-bug me-2"></i>Diagnóstico paso a paso
                </a>
                <div id="pruebaResult"></div>
                <div id="prnDownload" class="d-none mt-2">
                    <div class="alert alert-info py-2 small mb-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Si no llegó a la impresora, descargue el .prn e imprímalo manualmente:
                        arrástrelo al icono de la impresora en Windows.
                    </div>
                    <a id="prnDownloadLink" href="#" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-download me-1"></i>Descargar archivo .prn
                    </a>
                </div>
            </div>
        </div>

        <!-- Info técnica -->
        <div class="card lp-card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-code-slash me-2"></i>Parámetros TSPL2</h6></div>
            <div class="card-body small text-muted">
                <table class="table table-sm mb-0">
                    <tr><th>DPI</th><td><?= (int)($impresora['dpi'] ?? 203) ?></td></tr>
                    <tr><th>Etiqueta</th><td><?= (float)($impresora['ancho_mm'] ?? 80) ?> × <?= (float)($impresora['alto_mm'] ?? 40) ?> mm</td></tr>
                    <tr><th>Dots W×H</th><td><?= round((float)($impresora['ancho_mm'] ?? 80) * 8.0315) ?> × <?= round((float)($impresora['alto_mm'] ?? 40) * 8.0315) ?></td></tr>
                    <tr><th>Velocidad</th><td><?= (int)($impresora['velocidad'] ?? 4) ?></td></tr>
                    <tr><th>Densidad</th><td><?= (int)($impresora['densidad'] ?? 8) ?></td></tr>
                    <tr><th>Lenguaje</th><td>TSPL2</td></tr>
                    <tr><th>Conexión</th><td><?= strtoupper($impresora['tipo_conexion'] ?? 'usb') ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Guía de conexión USB -->
        <div class="card lp-card border-warning">
            <div class="card-header bg-warning bg-opacity-10">
                <h6 class="mb-0"><i class="bi bi-usb-symbol me-2"></i>Guía USB en Windows</h6>
            </div>
            <div class="card-body small">
                <ol class="mb-0 ps-3">
                    <li>Instale los drivers TSC TE200.</li>
                    <li>En Windows: <strong>Panel de Control → Dispositivos e Impresoras</strong>.</li>
                    <li>Verifique el nombre exacto de la impresora y cópielo en "Nombre de impresora".</li>
                    <li>Haga clic derecho → <strong>Propiedades</strong> → pestaña <strong>Puertos</strong> para ver el puerto (USB001, etc.).</li>
                    <li>Si <em>copy /b</em> falla, use TCP/IP: active la interfaz de red en la impresora (consulte manual TSC).</li>
                    <li>TCP/IP es el método más confiable desde XAMPP.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Modal TSPL -->
<div class="modal fade" id="modalTspl" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-code-slash me-2"></i>TSPL2 generado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="tsplContent" class="bg-dark text-light p-3 rounded small" style="max-height:400px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
var CSRF = '<?= \App\Core\Session::csrf() ?>';
var BASE = '<?= $basePath ?>';
var modalTspl = new bootstrap.Modal(document.getElementById('modalTspl'));

function updateConnFields() {
    var tipo = document.querySelector('input[name="tipo_conexion"]:checked')?.value;
    document.getElementById('fieldsTcp').style.display    = tipo === 'tcp'    ? '' : 'none';
    document.getElementById('fieldsUsb').style.display    = tipo === 'usb'    ? '' : 'none';
    document.getElementById('fieldsShared').style.display = tipo === 'shared' ? '' : 'none';
}

function checkEstado() {
    var el = document.getElementById('estadoImpresora');
    el.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Verificando…';
    fetch(BASE + '/api/impresora/estado', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var s = data.data || {};
            el.innerHTML = (s.available ? '🟢 ' : '🔴 ') + (s.message || '');
        })
        .catch(function() { el.innerHTML = '🔴 Error al verificar.'; });
}

function pruebaPrinter(btn) {
    var res = document.getElementById('pruebaResult');
    var dl  = document.getElementById('prnDownload');
    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando…';
    res.innerHTML = '';
    dl.classList.add('d-none');

    fetch(BASE + '/configuracion/prueba', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body:    '_csrf=' + encodeURIComponent(CSRF)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var cls = data.success ? 'success' : 'warning';
        res.innerHTML = '<div class="alert alert-' + cls + ' py-2 small">' + (data.message || '') + '</div>';

        // Mostrar TSPL generado
        if (data.tspl) {
            document.getElementById('tsplContent').textContent = data.tspl;
            res.innerHTML += '<button class="btn btn-sm btn-outline-secondary mt-1" onclick="modalTspl.show()">' +
                             '<i class="bi bi-code-slash me-1"></i>Ver TSPL generado</button>';
        }

        // Mostrar descarga .prn si hay archivo
        if (data.prn_file) {
            document.getElementById('prnDownloadLink').href = BASE + '/storage/temp/' + data.prn_file;
            dl.classList.remove('d-none');
        }
    })
    .catch(function(err) {
        res.innerHTML = '<div class="alert alert-danger py-2 small">Error de comunicación: ' + err.message + '</div>';
    })
    .finally(function() {
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-printer-fill me-2"></i>Enviar etiqueta de prueba';
    });
}
</script>
