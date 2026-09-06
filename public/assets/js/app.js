/* ============================================================
   LABELPRINT — app.js
   Funciones globales del sistema
   ============================================================ */

'use strict';

// ---- Sidebar toggle ----
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

// Cerrar sidebar al hacer click fuera (mobile)
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    const btn = document.querySelector('.lp-toggle-btn');
    if (sidebar && btn && !sidebar.contains(e.target) && !btn.contains(e.target)) {
        sidebar.classList.remove('open');
    }
});

// ---- Auto-cerrar alertas ----
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert.alert-success, .alert.alert-danger');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });
});

// ---- Toggle activo/inactivo con fetch ----
function toggleEstado(url, id, btnEl) {
    if (!confirm('¿Desea cambiar el estado?')) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || window.CSRF || '';

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: '_csrf=' + encodeURIComponent(csrf)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const row = btnEl.closest('tr');
            if (row) {
                const badge = row.querySelector('.badge-estado');
                if (badge) {
                    badge.className = 'badge ' + (data.data.activo ? 'bg-success' : 'bg-secondary') + ' badge-estado';
                    badge.textContent = data.data.activo ? 'Activo' : 'Inactivo';
                }
            }
            btnEl.textContent = data.data.activo ? 'Desactivar' : 'Activar';
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Error al cambiar estado.', 'danger');
        }
    })
    .catch(() => showToast('Error de comunicación.', 'danger'));
}

// ---- Toast notifications ----
function showToast(message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position:fixed;top:16px;right:16px;z-index:9999;min-width:240px;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show shadow`;
    toast.style.marginBottom = '8px';
    toast.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    container.appendChild(toast);

    setTimeout(() => {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(toast);
        if (bsAlert) bsAlert.close();
    }, 4000);
}

// Exponer globalmente
window.showToast = showToast;
window.toggleEstado = toggleEstado;
window.toggleSidebar = toggleSidebar;
