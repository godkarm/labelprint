/* ============================================================
   LABELPRINT — etiquetas.js  v1.5.0
   Pantalla principal de impresión de etiquetas
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', function () {

    // ---- Verificar Bootstrap ----
    if (typeof bootstrap === 'undefined') {
        document.getElementById('formErrors').textContent =
            'Error crítico: Bootstrap no cargó. Verifique assets/js/bootstrap.bundle.min.js';
        document.getElementById('formErrors').classList.remove('d-none');
        return;
    }

    // ---- Elementos del formulario ----
    var selProducto    = document.getElementById('productoSelect');
    var selSubproducto = document.getElementById('subproductoSelect');
    var inpCantidad    = document.getElementById('cantidadInput');
    var inpCopias      = document.getElementById('copiasInput');
    var selTurno       = document.getElementById('turnoSelect');
    var inpFecha       = document.getElementById('fechaInput');
    var btnImprimir    = document.getElementById('btnImprimir');
    var btnPreview     = document.getElementById('btnPreview');
    var btnNuevoSub    = document.getElementById('btnNuevoSub');

    // ---- Vista previa ----
    var previewProducto    = document.getElementById('previewProducto');
    var previewSubproducto = document.getElementById('previewSubproducto');
    var previewCantidad    = document.getElementById('previewCantidad');
    var previewTurno       = document.getElementById('previewTurno');
    var previewFecha       = document.getElementById('previewFecha');

    // ---- Alertas ----
    var formErrors  = document.getElementById('formErrors');
    var formSuccess = document.getElementById('formSuccess');

    // ---- Modales ----
    var modalConfirmar  = new bootstrap.Modal(document.getElementById('modalConfirmar'));
    var modalNuevoSub   = new bootstrap.Modal(document.getElementById('modalNuevoSub'));
    var btnConfirmarImp = document.getElementById('btnConfirmarImprimir');
    var inputNuevoSub   = document.getElementById('nuevoSubInput');
    var btnGuardarSub   = document.getElementById('btnGuardarSub');
    var errorNuevoSub   = document.getElementById('nuevoSubError');

    // ---- Estado ----
    var productoNombre         = '';
    var subproductoDescripcion = '';
    var isLoading              = false;

    // ================================================================
    // INICIALIZACIÓN — cargar todos los subproductos al inicio
    // ================================================================
    cargarSubproductos(null);

    // ================================================================
    // FETCH helper — detecta respuestas no-JSON (sesión expirada etc.)
    // ================================================================
    function fetchJSON(url, opts) {
        return fetch(url, opts).then(function (r) {
            var ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                throw new Error('Respuesta no válida del servidor. ¿Expiró la sesión? Recargue la página.');
            }
            return r.json();
        });
    }

    // ================================================================
    // CARGA DE SUBPRODUCTOS
    // null → todos los activos; número → filtrado por producto
    // ================================================================
    function cargarSubproductos(productoId) {
        var url = productoId
            ? (BASE + '/api/subproductos?producto_id=' + productoId)
            : (BASE + '/api/subproductos');

        selSubproducto.innerHTML = '<option value="">— Cargando… —</option>';
        selSubproducto.disabled  = true;
        btnNuevoSub.disabled     = true;

        fetchJSON(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (data) {
                selSubproducto.innerHTML = '<option value="">— Seleccione color/subproducto —</option>';
                if (data && data.success && data.data && data.data.length > 0) {
                    data.data.forEach(function (sub) {
                        var opt = document.createElement('option');
                        opt.value = sub.id;
                        opt.textContent = sub.descripcion;
                        opt.dataset.descripcion = sub.descripcion;
                        selSubproducto.appendChild(opt);
                    });
                } else {
                    selSubproducto.innerHTML = '<option value="">— Sin colores disponibles —</option>';
                }
            })
            .catch(function (err) {
                selSubproducto.innerHTML = '<option value="">— Error al cargar —</option>';
                console.error('Error cargando subproductos:', err.message);
            })
            .finally(function () {
                selSubproducto.disabled = false;
                btnNuevoSub.disabled    = false;
                subproductoDescripcion  = '';
                updatePreview();
            });
    }

    // ================================================================
    // EVENTOS DE CAMBIO
    // ================================================================
    selProducto.addEventListener('change', function () {
        productoNombre = this.options[this.selectedIndex]
            ? (this.options[this.selectedIndex].dataset.nombre || this.options[this.selectedIndex].text.split('—').pop().trim())
            : '';
        subproductoDescripcion = '';
        cargarSubproductos(this.value ? parseInt(this.value) : null);
    });

    selSubproducto.addEventListener('change', function () {
        var opt = this.options[this.selectedIndex];
        subproductoDescripcion = opt ? (opt.dataset.descripcion || opt.textContent.trim()) : '';
        if (subproductoDescripcion === '— Seleccione color/subproducto —' ||
            subproductoDescripcion === '— Sin colores disponibles —') {
            subproductoDescripcion = '';
        }
        updatePreview();
    });

    [inpCantidad,selTurno, inpFecha].forEach(function (el) {
        el.addEventListener('change', updatePreview);
        el.addEventListener('input',  updatePreview);
    });

    btnPreview.addEventListener('click', function () {
        var opt = selSubproducto.options[selSubproducto.selectedIndex];
        if (opt && opt.value) {
            subproductoDescripcion = opt.dataset.descripcion || opt.textContent.trim();
        }
        updatePreview();
    });

    // ================================================================
    // ACTUALIZAR VISTA PREVIA
    // ================================================================
    function updatePreview() {
        previewProducto.textContent    = productoNombre || '—';
        previewSubproducto.textContent = subproductoDescripcion || '—';
        previewCantidad.textContent    = inpCantidad.value
            ? parseInt(inpCantidad.value).toLocaleString() : '—';
        previewTurno.textContent = TURNOS[selTurno.value] || '—';

        if (inpFecha.value) {
            var parts = inpFecha.value.split('-');
            previewFecha.textContent = parts[2] + '/' + parts[1] + '/' + parts[0];
        } else {
            previewFecha.textContent = '—';
        }

        var len = subproductoDescripcion.length;
        previewSubproducto.style.fontSize =
            len > 35 ? '.60rem' : len > 25 ? '.65rem' : '.72rem';
    }

    updatePreview();

    // ================================================================
    // BOTÓN IMPRIMIR → modal de confirmación
    // ================================================================
    btnImprimir.addEventListener('click', function () {
        clearAlerts();

        // Re-leer subproducto por si el usuario cambió sin disparar evento
        var optSub = selSubproducto.options[selSubproducto.selectedIndex];
        if (optSub && optSub.value && !subproductoDescripcion) {
            subproductoDescripcion = optSub.dataset.descripcion || optSub.textContent.trim();
        }

        var errors = validate();
        if (errors.length) { showErrors(errors); return; }

        document.getElementById('confirmProducto').textContent    = productoNombre;
        document.getElementById('confirmSubproducto').textContent = subproductoDescripcion;
        document.getElementById('confirmCantidad').textContent    = inpCantidad.value;
        document.getElementById('confirmTurno').textContent       = TURNOS[selTurno.value] || selTurno.value;
        document.getElementById('confirmFecha').textContent       = previewFecha.textContent;
        document.getElementById('confirmCopias').textContent      = inpCopias.value + ' etiqueta(s)';

        modalConfirmar.show();
    });

    // ================================================================
    // CONFIRMAR → ENVIAR A IMPRESORA
    // ================================================================
    btnConfirmarImp.addEventListener('click', function () {
        if (isLoading) return;
        isLoading = true;

        var btn = this;
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando…';

        var payload = {
            _csrf:          CSRF,
            producto_id:    selProducto.value,
            subproducto_id: selSubproducto.value,
            cantidad:       inpCantidad.value,
            turno:          selTurno.value,
            fecha:          inpFecha.value,
            copias:         inpCopias.value
        };

        fetchJSON(BASE + '/api/imprimir', {
            method:  'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token':     CSRF
            },
            body: JSON.stringify(payload)
        })
        .then(function (data) {
            modalConfirmar.hide();

            if (data.success) {
                var msg = '✓ ' + data.message;
                if (data.impresion_id) msg += ' (ID: ' + data.impresion_id + ')';
                showSuccess(msg);
            } else {
                // Mostrar errores de validación o error de impresión
                if (data.errors) {
                    showErrors(Object.values(data.errors));
                } else {
                    var errLines = [data.message || 'Error al enviar la impresión.'];

                    // Si hay archivo .prn descargable, ofrecer descarga manual
                    if (data.prn_file) {
                        errLines.push('');
                        errLines.push('💡 Se generó el archivo de impresión. Puede descargarlo e imprimirlo manualmente:');
                        showErrorsWithPrn(errLines, data.prn_file);
                        return;
                    }
                    showErrors(errLines);
                }
            }
        })
        .catch(function (err) {
            modalConfirmar.hide();
            showErrors(['Error de comunicación: ' + err.message]);
        })
        .finally(function () {
            isLoading = false;
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-printer-fill me-2"></i>Imprimir';
        });
    });

    // ================================================================
    // NUEVO SUBPRODUCTO RÁPIDO
    // ================================================================
    btnGuardarSub.addEventListener('click', function () {
        var desc = inputNuevoSub.value.trim();
        errorNuevoSub.classList.add('d-none');

        if (!desc) {
            errorNuevoSub.textContent = 'La descripción es obligatoria.';
            errorNuevoSub.classList.remove('d-none');
            return;
        }

        var btn = this;
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

        fetchJSON(BASE + '/api/subproductos/crear', {
            method:  'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token':     CSRF
            },
            body: JSON.stringify({ descripcion: desc })
        })
        .then(function (data) {
            if (data.success) {
                var opt = document.createElement('option');
                opt.value = data.data.id;
                opt.textContent = data.data.descripcion;
                opt.dataset.descripcion = data.data.descripcion;
                selSubproducto.appendChild(opt);
                selSubproducto.value   = data.data.id;
                subproductoDescripcion = data.data.descripcion;
                modalNuevoSub.hide();
                inputNuevoSub.value = '';
                updatePreview();
                if (window.showToast) showToast('Color/subproducto creado y seleccionado.', 'success');
            } else {
                errorNuevoSub.textContent = data.message || 'Error al crear.';
                errorNuevoSub.classList.remove('d-none');
            }
        })
        .catch(function (err) {
            errorNuevoSub.textContent = err.message || 'Error de comunicación.';
            errorNuevoSub.classList.remove('d-none');
        })
        .finally(function () {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-save me-1"></i>Guardar y Seleccionar';
        });
    });

    // ================================================================
    // VALIDACIÓN FRONTEND
    // ================================================================
    function validate() {
        var errors = [];
        if (!selProducto.value)    errors.push('Seleccione un producto.');
        if (!selSubproducto.value) errors.push('Seleccione un color/subproducto.');
        if (!inpCantidad.value || parseInt(inpCantidad.value) <= 0)
            errors.push('Ingrese una cantidad válida (mayor que cero).');
        if (!inpCopias.value || parseInt(inpCopias.value) <= 0)
            errors.push('Ingrese el número de copias (mayor que cero).');
        if (!selTurno.value) errors.push('Seleccione un turno.');
        if (!inpFecha.value)  errors.push('Ingrese una fecha.');
        return errors;
    }

    function showErrors(errors) {
        formErrors.innerHTML = errors.map(function(e) {
            return e ? '<div>' + e + '</div>' : '<br>';
        }).join('');
        formErrors.classList.remove('d-none');
        formSuccess.classList.add('d-none');
        formErrors.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function showErrorsWithPrn(errors, prnFile) {
        var html = errors.map(function(e) {
            return e ? '<div>' + e + '</div>' : '<br>';
        }).join('');
        html += '<div class="mt-2">' +
                '<a href="' + BASE + '/storage/temp/' + prnFile + '" class="btn btn-sm btn-outline-secondary" download>' +
                '<i class="bi bi-download me-1"></i>Descargar ' + prnFile + '</a>' +
                '</div>';
        formErrors.innerHTML = html;
        formErrors.classList.remove('d-none');
        formSuccess.classList.add('d-none');
        formErrors.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function showSuccess(msg) {
        formSuccess.textContent = msg;
        formSuccess.classList.remove('d-none');
        formErrors.classList.add('d-none');
        formSuccess.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function clearAlerts() {
        formErrors.classList.add('d-none');
        formSuccess.classList.add('d-none');
    }

}); // fin DOMContentLoaded
