<?php $pageTitle = 'Diagnóstico de Impresión'; $activeNav = 'configuracion'; ?>

<div class="row justify-content-center">
<div class="col-lg-9">

<div class="alert alert-info d-flex gap-2 align-items-start">
    <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
    <div>
        Esta herramienta diagnostica el flujo completo de impresión <strong>paso a paso</strong>
        para identificar exactamente dónde falla. Use esta página si la impresora no imprime.
    </div>
</div>

<!-- Resultado global -->
<div id="resultadoGlobal" class="d-none mb-4"></div>

<!-- Pasos del diagnóstico -->
<div class="card lp-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-search me-2"></i>Diagnóstico paso a paso</h5>
        <button class="btn btn-primary" onclick="ejecutarDiagnostico()">
            <i class="bi bi-play-fill me-1"></i>Ejecutar diagnóstico
        </button>
    </div>
    <div class="card-body p-0">
        <table class="table lp-table mb-0" id="tablaDiag">
            <thead>
                <tr><th style="width:35px">#</th><th>Paso</th><th style="width:120px">Estado</th><th>Detalle</th></tr>
            </thead>
            <tbody>
                <tr data-paso="1"><td>1</td><td>Configuración de impresora en BD</td><td><span class="badge bg-secondary">Pendiente</span></td><td class="text-muted small">—</td></tr>
                <tr data-paso="2"><td>2</td><td>Generación del TSPL2</td><td><span class="badge bg-secondary">Pendiente</span></td><td class="text-muted small">—</td></tr>
                <tr data-paso="3"><td>3</td><td>Guardado del archivo .prn</td><td><span class="badge bg-secondary">Pendiente</span></td><td class="text-muted small">—</td></tr>
                <tr data-paso="4"><td>4</td><td>Envío a la impresora</td><td><span class="badge bg-secondary">Pendiente</span></td><td class="text-muted small">—</td></tr>
                <tr data-paso="5"><td>5</td><td>Respuesta del backend</td><td><span class="badge bg-secondary">Pendiente</span></td><td class="text-muted small">—</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- TSPL generado -->
<div class="card lp-card mb-4 d-none" id="cardTspl">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-code-slash me-2"></i>TSPL2 generado</h6></div>
    <div class="card-body">
        <pre id="tsplContent" class="bg-dark text-light p-3 rounded small mb-0"
             style="max-height:300px;overflow-y:auto;white-space:pre;font-size:.75rem;"></pre>
    </div>
</div>

<!-- Acciones manuales -->
<div class="card lp-card mb-4 d-none" id="cardAcciones">
    <div class="card-header bg-warning bg-opacity-10">
        <h6 class="mb-0"><i class="bi bi-tools me-2"></i>Acciones manuales</h6>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-3">
            Si el envío automático falló, descargue el archivo y envíelo manualmente a la impresora:
        </p>
        <div id="accionesContenido"></div>

        <hr>
        <h6 class="small fw-bold">¿Por qué Apache no puede imprimir por USB?</h6>
        <p class="small text-muted mb-2">
            Apache/XAMPP en Windows corre como servicio bajo <code>NT AUTHORITY\SYSTEM</code>.
            Esa cuenta <strong>no tiene acceso</strong> a las impresoras instaladas para el usuario interactivo.
        </p>
        <p class="small mb-2"><strong>Soluciones (en orden de facilidad):</strong></p>
        <ol class="small text-muted">
            <li class="mb-1">
                <strong>TCP/IP (recomendado):</strong> Active la interfaz de red en la TSC TE200
                (consulte el manual → menú "Interface") y configure la IP en
                <a href="<?= $basePath ?>/configuracion">Configuración → Impresora</a>.
            </li>
            <li class="mb-1">
                <strong>Cambiar cuenta de Apache:</strong><br>
                Abrir → <code>Servicios de Windows</code> (services.msc)<br>
                → Buscar <code>Apache2.4</code><br>
                → Propiedades → Inicio de sesión<br>
                → Marcar <em>"Esta cuenta"</em> → Ingresar su usuario y contraseña de Windows<br>
                → Reiniciar Apache.
            </li>
            <li class="mb-1">
                <strong>Impresión manual:</strong> Descargue el .prn y arrástrelo al icono
                de la impresora en Windows → la impresora lo procesa directamente.
            </li>
        </ol>
    </div>
</div>

</div>
</div>

<script>
var CSRF = '<?= \App\Core\Session::csrf() ?>';
var BASE = '<?= $basePath ?>';

function actualizarPaso(n, estado, detalle) {
    var fila = document.querySelector('tr[data-paso="' + n + '"]');
    if (!fila) return;
    var clases = { 'OK': 'success', 'ERROR': 'danger', 'WARN': 'warning', 'INFO': 'info', 'Ejecutando...': 'primary' };
    var cls = clases[estado] || 'secondary';
    fila.cells[2].innerHTML = '<span class="badge bg-' + cls + '">' + estado + '</span>';
    fila.cells[3].innerHTML = '<span class="small">' + detalle + '</span>';
}

function ejecutarDiagnostico() {
    // Reset
    document.getElementById('resultadoGlobal').className = 'd-none mb-4';
    document.getElementById('cardTspl').classList.add('d-none');
    document.getElementById('cardAcciones').classList.add('d-none');

    [1,2,3,4,5].forEach(function(n) {
        actualizarPaso(n, 'Ejecutando...', '...');
    });

    fetch(BASE + '/configuracion/diagnostico-run', {
        method:  'POST',
        headers: {
            'Content-Type':     'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: '_csrf=' + encodeURIComponent(CSRF)
    })
    .then(function(r) {
        var ct = r.headers.get('content-type') || '';
        if (!ct.includes('application/json')) throw new Error('Respuesta no JSON. ¿Sesión expirada?');
        return r.json();
    })
    .then(function(data) {
        var pasos = data.pasos || [];
        pasos.forEach(function(p) { actualizarPaso(p.n, p.estado, p.detalle); });

        // Mostrar TSPL
        if (data.tspl) {
            document.getElementById('tsplContent').textContent = data.tspl;
            document.getElementById('cardTspl').classList.remove('d-none');
        }

        // Resultado global
        var rg = document.getElementById('resultadoGlobal');
        rg.className = 'mb-4 alert alert-' + (data.success ? 'success' : 'danger');
        rg.innerHTML = (data.success ? '✅ ' : '❌ ') + '<strong>' + (data.message || '') + '</strong>';

        // Acciones manuales si hay prn_file
        if (data.prn_file || !data.success) {
            var ac = document.getElementById('accionesContenido');
            var html = '';
            if (data.prn_file) {
                html += '<a href="' + BASE + '/storage/temp/' + data.prn_file + '" download ' +
                        'class="btn btn-outline-secondary mb-3">' +
                        '<i class="bi bi-download me-1"></i>Descargar ' + data.prn_file + '</a><br>';
            }
            if (data.detail) {
                html += '<div class="bg-light rounded p-2 small text-muted"><pre style="white-space:pre-wrap;margin:0">' +
                        data.detail + '</pre></div>';
            }
            ac.innerHTML = html;
            document.getElementById('cardAcciones').classList.remove('d-none');
        }
    })
    .catch(function(err) {
        [1,2,3,4,5].forEach(function(n) { actualizarPaso(n, 'ERROR', 'Error de comunicación'); });
        var rg = document.getElementById('resultadoGlobal');
        rg.className = 'mb-4 alert alert-danger';
        rg.innerHTML = '❌ Error: ' + err.message;
        rg.classList.remove('d-none');
    });
}
</script>
