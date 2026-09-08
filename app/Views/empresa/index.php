<?php $pageTitle = 'Empresa'; $activeNav = 'empresa'; ?>

<div class="row g-4">
    <!-- Nombre empresa -->
    <div class="col-md-6">
        <div class="card lp-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-building me-2"></i>Información de Empresa</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $basePath ?>/empresa">
                    <input type="hidden" name="_csrf" value="<?= \App\Core\Session::csrf() ?>">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre de la empresa</label>
                        <input type="text" name="nombre" class="form-control form-control-lg"
                               value="<?= htmlspecialchars($empresa['nombre'] ?? '') ?>"
                               maxlength="255" placeholder="Mi Empresa S.A." required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Guardar nombre
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Logo -->
    <div class="col-md-6">
        <div class="card lp-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-image me-2"></i>Logo de la Empresa</h5>
            </div>
            <div class="card-body">
                <!-- Vista previa actual -->
                <div class="mb-3 d-flex align-items-center gap-3">
                    <div class="lp-logo-preview">
                        <?php if (!empty($empresa['logo'])): ?>
                        <img src="<?= $basePath ?>/<?= htmlspecialchars($empresa['logo']) ?>"
                             alt="Logo actual" id="logoPreviewImg">
                        <?php else: ?>
                        <i class="bi bi-building fs-2 text-muted" id="logoPlaceholder"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="text-muted small">
                            <?php if (!empty($empresa['logo'])): ?>
                            <span class="text-success"><i class="bi bi-check-circle me-1"></i>Logo cargado</span>
                            <?php else: ?>
                            <span>Sin logo</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted small mt-1">PNG, JPG, JPEG, WEBP · Máx. 5 MB</div>
                    </div>
                </div>

                <!-- Subir logo -->
                <form method="POST" action="<?= $basePath ?>/empresa/logo" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?= \App\Core\Session::csrf() ?>">
                    <div class="mb-3">
                        <input type="file" name="logo" id="logoInput" class="form-control"
                               accept=".png,.jpg,.jpeg,.webp" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-1"></i>Subir logo
                        </button>

                        <?php if (!empty($empresa['logo'])): ?>
                        <button type="button" class="btn btn-outline-danger"
                                onclick="eliminarLogo()">
                            <i class="bi bi-trash me-1"></i>Eliminar
                        </button>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Eliminar logo (form oculto) -->
                <?php if (!empty($empresa['logo'])): ?>
                <form id="formDeleteLogo" method="POST" action="<?= $basePath ?>/empresa/logo/delete" class="d-none">
                    <input type="hidden" name="_csrf" value="<?= \App\Core\Session::csrf() ?>">
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Vista previa etiqueta con logo -->
    <div class="col-12">
        <div class="card lp-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-eye me-2"></i>Vista previa de logo en etiqueta</h5>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center py-4">
                <div class="lp-label-preview">
                    <div class="lp-label-header">
                        <div class="lp-label-logo">
                            <?php if (!empty($empresa['logo'])): ?>
                            <img src="<?= $basePath ?>/<?= htmlspecialchars($empresa['logo']) ?>" alt="Logo">
                            <?php else: ?>
                            <i class="bi bi-building text-muted"></i>
                            <?php endif; ?>
                        </div>
                        <div class="lp-label-empresa"><?= htmlspecialchars($empresa['nombre'] ?? 'EMPRESA') ?></div>
                    </div>
                    <div class="lp-label-divider"></div>
                    <div class="lp-label-row">
                        <span class="lp-label-field-name">PRODUCTO:</span>
                        <span class="lp-label-field-val">EJEMPLO PRODUCTO</span>
                    </div>
                    <div class="lp-label-row">
                        <span class="lp-label-field-name">COLOR:</span>
                        <span class="lp-label-field-val">501B - NATURAL CCX1103000</span>
                    </div>
                    <div class="lp-label-divider"></div>
                    <div class="lp-label-divider"></div>
                    <div class="lp-label-bottom">
                        <div class="lp-label-kv">
                            <span class="lp-label-k">CANTIDAD</span>
                            <span class="lp-label-v">500</span>
                        </div>
                        <div class="lp-label-kv">
                            <span class="lp-label-k">TURNO</span>
                            <span class="lp-label-v">1 - Manana</span>
                        </div>
                        <div class="lp-label-kv">
                            <span class="lp-label-k">FECHA</span>
                            <span class="lp-label-v"><?= date('d/m/Y') ?></span>
                        </div>
                        <div class="lp-label-kv">
                            <span class="lp-label-k">COPIAS</span>
                            <span class="lp-label-v">1</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Preview de imagen antes de subir
document.getElementById('logoInput')?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        let img = document.getElementById('logoPreviewImg');
        const placeholder = document.getElementById('logoPlaceholder');
        if (!img) {
            img = document.createElement('img');
            img.id = 'logoPreviewImg';
            img.alt = 'Vista previa';
            img.style.cssText = 'max-width:100%;max-height:100%;object-fit:contain;';
            document.querySelector('.lp-logo-preview').innerHTML = '';
            document.querySelector('.lp-logo-preview').appendChild(img);
        }
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
});

function eliminarLogo() {
    if (!confirm('¿Eliminar el logo actual?')) return;
    document.getElementById('formDeleteLogo')?.submit();
}
</script>
