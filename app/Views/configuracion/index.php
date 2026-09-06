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
                            <label class="form-label fw-bold">Nombre</label>
                            <input type="text" name="nombre" class="form-control"
                                   value="<?= htmlspecialchars($impresora['nombre'] ?? 'TSC TE200') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Modelo</label>
                            <input type="text" name="modelo" class="form-control"
                                   value="<?= htmlspecialchars($impresora['modelo'] ?? 'TE200') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">DPI</label>
                            <select name="dpi" class="form-select">
                                <option value="203" <?= ($impresora['dpi'] ?? 203) == 203 ? 'selected' : '' ?>>203 DPI</option>
                                <option value="300" <?= ($impresora['dpi'] ?? 203) == 300 ? 'selected' : '' ?>>300 DPI</option>
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
                                   value="<?= (int)($impresora['velocidad'] ?? 4) ?>"
                                   min="1" max="14">
                            <div class="form-text">Pulg/seg. Recomendado: 4</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Densidad (0-15)</label>
                            <input type="number" name="densidad" class="form-control"
                                   value="<?= (int)($impresora['densidad'] ?? 8) ?>"
                                   min="0" max="15">
                            <div class="form-text">Oscuridad. Recomendado: 8</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Orientación</label>
                            <select name="orientacion" class="form-select">
                                <option value="horizontal" <?= ($impresora['orientacion'] ?? 'horizontal') === 'horizontal' ? 'selected' : '' ?>>Horizontal (80×40)</option>
                                <option value="vertical"   <?= ($impresora['orientacion'] ?? 'horizontal') === 'vertical'   ? 'selected' : '' ?>>Vertical (40×80)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Tipo de conexión</label>
                            <div class="d-flex gap-3">
                                <?php foreach (['usb' => 'USB', 'tcp' => 'TCP/IP (Red)', 'shared' => 'Impresora compartida'] as $val => $label): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_conexion"
                                           value="<?= $val ?>" id="conn_<?= $val ?>"
                                           <?= ($impresora['tipo_conexion'] ?? 'usb') === $val ? 'checked' : '' ?>
                                           onchange="updateConnFields()">
                                    <label class="form-check-label" for="conn_<?= $val ?>"><?= $label ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div id="fieldsTcp" class="col-12" style="<?= ($impresora['tipo_conexion'] ?? 'usb') === 'tcp' ? '' : 'display:none' ?>">
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

                        <div id="fieldsShared" class="col-12" style="<?= ($impresora['tipo_conexion'] ?? 'usb') === 'shared' ? '' : 'display:none' ?>">
                            <label class="form-label">Nombre de la impresora compartida</label>
                            <input type="text" name="nombre_compartido" class="form-control"
                                   value="<?= htmlspecialchars($impresora['nombre_compartido'] ?? '') ?>"
                                   placeholder="\\SERVIDOR\TSC-TE200 o nombre local">
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

    <!-- Panel de pruebas -->
    <div class="col-lg-4">
        <div class="card lp-card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-wifi me-2"></i>Estado de la impresora</h6></div>
            <div class="card-body">
                <div id="estadoImpresora" class="text-muted small">Haga clic para verificar...</div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="checkEstado()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Verificar estado
                </button>
            </div>
        </div>

        <div class="card lp-card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-printer me-2"></i>Prueba de impresión</h6></div>
            <div class="card-body">
                <p class="small text-muted">Imprime una etiqueta de prueba con información de la impresora y líneas de referencia.</p>
                <button type="button" class="btn btn-outline-primary w-100" onclick="pruebaPrinter()">
                    <i class="bi bi-printer-fill me-2"></i>Imprimir etiqueta de prueba
                </button>
                <div id="pruebaResult" class="mt-2"></div>
            </div>
        </div>

        <div class="card lp-card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-rulers me-2"></i>Datos TSPL2</h6></div>
            <div class="card-body small text-muted">
                <table class="table table-sm mb-0">
                    <tr><th>DPI</th><td><?= (int)($impresora['dpi'] ?? 203) ?></td></tr>
                    <tr><th>Etiqueta</th><td><?= (float)($impresora['ancho_mm'] ?? 80) ?> × <?= (float)($impresora['alto_mm'] ?? 40) ?> mm</td></tr>
                    <tr><th>Dots W</th><td><?= round((float)($impresora['ancho_mm'] ?? 80) * 8.0315) ?></td></tr>
                    <tr><th>Dots H</th><td><?= round((float)($impresora['alto_mm'] ?? 40) * 8.0315) ?></td></tr>
                    <tr><th>Velocidad</th><td><?= (int)($impresora['velocidad'] ?? 4) ?></td></tr>
                    <tr><th>Densidad</th><td><?= (int)($impresora['densidad'] ?? 8) ?></td></tr>
                    <tr><th>Lenguaje</th><td>TSPL2</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF = '<?= \App\Core\Session::csrf() ?>';
const BASE = '<?= $basePath ?>';

function updateConnFields() {
    const tipo = document.querySelector('input[name="tipo_conexion"]:checked')?.value;
    document.getElementById('fieldsTcp').style.display    = tipo === 'tcp'    ? '' : 'none';
    document.getElementById('fieldsShared').style.display = tipo === 'shared' ? '' : 'none';
}

function checkEstado() {
    const el = document.getElementById('estadoImpresora');
    el.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Verificando...';

    fetch(`${BASE}/api/impresora/estado`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const status = data.data;
        const icon   = status.available ? '🟢' : '🔴';
        el.innerHTML = `${icon} ${status.message}`;
    })
    .catch(() => el.innerHTML = '🔴 Error al verificar.');
}

function pruebaPrinter() {
    const btn = event.target.closest('button');
    const res = document.getElementById('pruebaResult');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

    fetch(`${BASE}/configuracion/prueba`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: `_csrf=${encodeURIComponent(CSRF)}`
    })
    .then(r => r.json())
    .then(data => {
        res.innerHTML = `<div class="alert alert-${data.success ? 'success' : 'danger'} py-1 small">${data.message}</div>`;
    })
    .catch(() => { res.innerHTML = '<div class="alert alert-danger py-1 small">Error de comunicación.</div>'; })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-printer-fill me-2"></i>Imprimir etiqueta de prueba';
    });
}
</script>
