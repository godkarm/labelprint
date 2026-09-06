<?php $pageTitle = 'Acceso'; ?>
<div class="lp-login-wrap">
    <div class="lp-login-card">
        <div class="text-center mb-4">
            <i class="bi bi-printer-fill lp-login-icon"></i>
            <h2 class="lp-login-title">LabelPrint</h2>
            <p class="text-muted small">Sistema de impresión TSC TE200</p>
        </div>

        <?php if ($flash = \App\Core\Session::getFlash('error')): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($flash) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/login">
            <input type="hidden" name="_csrf" value="<?= \App\Core\Session::csrf() ?>">

            <div class="mb-3">
                <label class="form-label">Correo electrónico</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="admin@labelprint.local"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg">
                <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
            </button>
        </form>

        <p class="text-center text-muted small mt-3">
            v1.0.0 — Por defecto: admin@labelprint.local / admin123
        </p>
    </div>
</div>
