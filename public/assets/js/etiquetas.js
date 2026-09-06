/* ============================================================
   LABELPRINT — etiquetas.js
   Lógica de la pantalla de creación de etiquetas
   ============================================================ */

'use strict';

// Esperar a que Bootstrap y el DOM estén listos
document.addEventListener('DOMContentLoaded', function () {

    // ---- Verificar que Bootstrap esté disponible ----
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap no está cargado. Verificar public/assets/js/bootstrap.bundle.min.js');
        document.getElementById('formErrors').textContent = 'Error: Bootstrap no cargó. Verificar los assets.';
        document.getElementById('formErrors').classList.remove('d-none');
        return;
    }

    // ---- Elementos del formulario ----
    const selProducto    = document.getElementById('productoSelect');
    const selSubproducto = document.getElementById('subproductoSelect');
    const inpCantidad    = document.getElementById('cantidadInput');
    const inpCopias      = document.getElementById('copiasInput');
    const selTurno       = document.getElementById('turnoSelect');
    const inpFecha       = document.getElementById('fechaInput');
    const btnImprimir    = document.getElementById('btnImprimir');
    const btnPreview     = document.getElementById('btnPreview');
    const btnNuevoSub    = document.getElementById('btnNuevoSub');

    // ---- Elementos de vista previa ----
    const previewProducto    = document.getElementById('previewProducto');
    const previewSubproducto = document.getElementById('previewSubproducto');
    const previewCantidad    = document.getElementById('previewCantidad');
    const previewTurno       = document.getElementById('previewTurno');
    const previewFecha       = document.getElementById('previewFecha');

    // ---- Alertas ----
    const formErrors  = document.getElementById('formErrors');
    const formSuccess = document.getElementById('formSuccess');

    // ---- Modales ----
    const modalConfirmar  = new bootstrap.Modal(document.getElementById('modalConfirmar'));
    const modalNuevoSub   = new bootstrap.Modal(document.getElementById('modalNuevoSub'));
    const btnConfirmarImp = document.getElementById('btnConfirmarImprimir');
    const inputNuevoSub   = document.getElementById('nuevoSubInput');
    const btnGuardarSub   = document.getElementById('btnGuardarSub');
    const errorNuevoSub   = document.getElementById('nuevoSubError');

    // ---- Estado ----
    let productoNombre         = '';
    let subproductoDescripcion = '';
    let isLoading              = false;

    // ----------------------------------------------------------------
    // INICIALIZACIÓN — habilitar subproducto y cargar todos al inicio
    // ----------------------------------------------------------------
    // El select de subproducto comienza habilitado con TODOS los subproductos.
    // Al seleccionar un producto, se filtra por los asociados.
    cargarSubproductos(null);

    // ----------------------------------------------------------------
    // CARGA DE SUBPRODUCTOS
    // null = todos los activos; número = filtrado por producto
    // ----------------------------------------------------------------
    function cargarSubproductos(productoId) {
        const url = productoId
            ? `${BASE}/api/subproductos?producto_id=${productoId}`
            : `${BASE}/api/subproductos`;

        selSubproducto.innerHTML = '<option value="">— Cargando… —</option>';
        selSubproducto.disabled  = true;
        btnNuevoSub.disabled     = true;

        fetchJson(url)
            .then(data => {
                selSubproducto.innerHTML = '<option value="">— Seleccione subproducto —</option>';

                if (data && data.success && Array.isArray(data.data)) {
                    if (data.data.length === 0) {
                        selSubproducto.innerHTML = '<option value="">— Sin subproductos disponibles —</option>';
                    } else {
                        data.data.forEach(sub => {
                            const opt = document.createElement('option');
                            opt.value = sub.id;
                            opt.textContent = sub.descripcion;
                            opt.dataset.descripcion = sub.descripcion;
                            selSubproducto.appendChild(opt);
                        });
                    }
                } else {
                    selSubproducto.innerHTML = '<option value="">— Error al cargar subproductos —</option>';
                }
            })
            .catch(() => {
                selSubproducto.innerHTML = '<option value="">— Error de conexión —</option>';
            })
            .finally(() => {
                selSubproducto.disabled = false;
                btnNuevoSub.disabled    = false;
                subproductoDescripcion  = '';
                updatePreview();
            });
    }

    // ----------------------------------------------------------------
    // FETCH con manejo de respuestas no-JSON (ej: redirect a login)
    // ----------------------------------------------------------------
    function fetchJson(url, options) {
        return fetch(url, options)
            .then(r => {
                const ct = r.headers.get('content-type') || '';
                if (!ct.includes('application/json')) {
                    // El servidor devolvió HTML (probablemente redirect a login)
                    throw new Error('Sesión expirada. Recargue la página.');
                }
                return r.json();
            });
    }

    // ----------------------------------------------------------------
    // CAMBIO DE PRODUCTO → filtrar subproductos
    // ----------------------------------------------------------------
    selProducto.addEventListener('change', function () {
        const pid = this.value;
        productoNombre = this.options[this.selectedIndex]?.dataset.nombre || '';
        subproductoDescripcion = '';

        if (!pid) {
            // Sin producto → mostrar todos los subproductos
            cargarSubproductos(null);
        } else {
            // Con producto → filtrar por asociación
            cargarSubproductos(pid);
        }
    });

    // ----------------------------------------------------------------
    // CAMBIO DE SUBPRODUCTO → actualizar preview
    // ----------------------------------------------------------------
    selSubproducto.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        subproductoDescripcion = opt?.dataset.descripcion || opt?.textContent || '';
        // Si el option no tiene data-descripcion, usar textContent
        if (!subproductoDescripcion && opt && opt.value) {
            subproductoDescripcion = opt.textContent.trim();
        }
        updatePreview();
    });

    // ----------------------------------------------------------------
    // ACTUALIZAR PREVIEW en tiempo real
    // ----------------------------------------------------------------
    [inpCantidad, inpCopias, selTurno, inpFecha].forEach(el => {
        el.addEventListener('change', updatePreview);
        el.addEventListener('input',  updatePreview);
    });

    btnPreview.addEventListener('click', function () {
        // Forzar re-lectura del subproducto seleccionado
        const opt = selSubproducto.options[selSubproducto.selectedIndex];
        if (opt && opt.value) {
            subproductoDescripcion = opt.dataset.descripcion || opt.textContent.trim();
        }
        updatePreview();
    });

    function updatePreview() {
        previewProducto.textContent    = productoNombre || '—';
        previewSubproducto.textContent = subproductoDescripcion || '—';
        previewCantidad.textContent    = inpCantidad.value ? parseInt(inpCantidad.value).toLocaleString() : '—';
        previewTurno.textContent       = TURNOS[selTurno.value] || '—';

        if (inpFecha.value) {
            const [y, m, d] = inpFecha.value.split('-');
            previewFecha.textContent = `${d}/${m}/${y}`;
        } else {
            previewFecha.textContent = '—';
        }

        // Reducir fuente si el subproducto es largo
        const len = subproductoDescripcion.length;
        if (len > 35) {
            previewSubproducto.style.fontSize = '.60rem';
        } else if (len > 25) {
            previewSubproducto.style.fontSize = '.65rem';
        } else {
            previewSubproducto.style.fontSize = '.78rem';
        }
    }

    // Inicializar preview con fecha actual
    updatePreview();

    // ----------------------------------------------------------------
    // BOTÓN IMPRIMIR → modal de confirmación
    // ----------------------------------------------------------------
    btnImprimir.addEventListener('click', function () {
        clearAlerts();

        // Re-leer subproducto por si cambió sin disparar el evento
        const optSub = selSubproducto.options[selSubproducto.selectedIndex];
        if (optSub && optSub.value && !subproductoDescripcion) {
            subproductoDescripcion = optSub.dataset.descripcion || optSub.textContent.trim();
        }

        const errors = validate();
        if (errors.length) {
            showErrors(errors);
            return;
        }

        // Llenar modal con los datos
        document.getElementById('confirmProducto').textContent    = productoNombre;
        document.getElementById('confirmSubproducto').textContent = subproductoDescripcion;
        document.getElementById('confirmCantidad').textContent    = inpCantidad.value;
        document.getElementById('confirmTurno').textContent       = TURNOS[selTurno.value] || selTurno.value;
        document.getElementById('confirmFecha').textContent       = previewFecha.textContent;
        document.getElementById('confirmCopias').textContent      = inpCopias.value + ' etiqueta(s)';

        modalConfirmar.show();
    });

    // ----------------------------------------------------------------
    // CONFIRMAR → ENVIAR A IMPRESORA
    // ----------------------------------------------------------------
    btnConfirmarImp.addEventListener('click', function () {
        if (isLoading) return;
        isLoading = true;

        btnConfirmarImp.disabled = true;
        btnConfirmarImp.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando…';

        const payload = {
            _csrf:          CSRF,
            producto_id:    selProducto.value,
            subproducto_id: selSubproducto.value,
            cantidad:       inpCantidad.value,
            turno:          selTurno.value,
            fecha:          inpFecha.value,
            copias:         inpCopias.value,
        };

        fetchJson(`${BASE}/api/imprimir`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': CSRF,
            },
            body: JSON.stringify(payload)
        })
        .then(data => {
            modalConfirmar.hide();
            if (data.success) {
                showSuccess('✓ ' + data.message + (data.impresion_id ? ` (ID: ${data.impresion_id})` : ''));
            } else {
                if (data.errors) {
                    showErrors(Object.values(data.errors));
                } else {
                    showErrors([data.message || 'Error al enviar la impresión.']);
                }
            }
        })
        .catch(err => {
            modalConfirmar.hide();
            showErrors(['Error: ' + err.message]);
        })
        .finally(() => {
            isLoading = false;
            btnConfirmarImp.disabled = false;
            btnConfirmarImp.innerHTML = '<i class="bi bi-printer-fill me-2"></i>Imprimir';
        });
    });

    // ----------------------------------------------------------------
    // NUEVO SUBPRODUCTO RÁPIDO
    // ----------------------------------------------------------------
    btnGuardarSub.addEventListener('click', function () {
        const desc = inputNuevoSub.value.trim();
        errorNuevoSub.classList.add('d-none');

        if (!desc) {
            errorNuevoSub.textContent = 'La descripción es obligatoria.';
            errorNuevoSub.classList.remove('d-none');
            return;
        }

        btnGuardarSub.disabled = true;
        btnGuardarSub.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

        fetchJson(`${BASE}/api/subproductos/crear`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': CSRF,
            },
            body: JSON.stringify({ descripcion: desc })
        })
        .then(data => {
            if (data.success) {
                // Agregar al select y seleccionarlo
                const opt = document.createElement('option');
                opt.value = data.data.id;
                opt.textContent = data.data.descripcion;
                opt.dataset.descripcion = data.data.descripcion;
                selSubproducto.appendChild(opt);
                selSubproducto.value   = data.data.id;
                subproductoDescripcion = data.data.descripcion;

                modalNuevoSub.hide();
                inputNuevoSub.value = '';
                updatePreview();
                if (typeof showToast === 'function') {
                    showToast('Subproducto creado y seleccionado.', 'success');
                }
            } else {
                errorNuevoSub.textContent = data.message || 'Error al crear subproducto.';
                errorNuevoSub.classList.remove('d-none');
            }
        })
        .catch(err => {
            errorNuevoSub.textContent = err.message || 'Error de comunicación.';
            errorNuevoSub.classList.remove('d-none');
        })
        .finally(() => {
            btnGuardarSub.disabled = false;
            btnGuardarSub.innerHTML = '<i class="bi bi-save me-1"></i>Guardar y Seleccionar';
        });
    });

    // ----------------------------------------------------------------
    // VALIDACIÓN FRONTEND
    // ----------------------------------------------------------------
    function validate() {
        const errors = [];
        if (!selProducto.value)    errors.push('Seleccione un producto.');
        if (!selSubproducto.value) errors.push('Seleccione un subproducto.');
        if (!inpCantidad.value || parseInt(inpCantidad.value) <= 0)
            errors.push('Ingrese una cantidad válida (mayor que cero).');
        if (!inpCopias.value || parseInt(inpCopias.value) <= 0)
            errors.push('Ingrese el número de copias (mayor que cero).');
        if (!selTurno.value) errors.push('Seleccione un turno.');
        if (!inpFecha.value)  errors.push('Ingrese una fecha.');
        return errors;
    }

    function showErrors(errors) {
        formErrors.innerHTML = '<ul class="mb-0">' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
        formErrors.classList.remove('d-none');
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
