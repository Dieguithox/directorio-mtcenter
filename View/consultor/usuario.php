<?php
$active = 'config';
$title = 'Reportes del sistema';
require_once __DIR__ . '/../../config/ui.php';
require __DIR__ . '/../layout/headConsultor.php';

/** el controlador te pasó $row con los datos del usuario */
$nombreVal = htmlspecialchars($row['nombre'] ?? '', ENT_QUOTES);
$emailVal  = htmlspecialchars($row['email'] ?? '', ENT_QUOTES);
?>
<?php if (!empty($flash_texto) && !empty($flash_nivel)): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080">
        <div class="toast text-bg-<?= htmlspecialchars($flash_nivel) ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="8000">
            <div class="d-flex">
                <div class="toast-body"><?= htmlspecialchars($flash_texto) ?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    <script>
        (function() {
            const el = document.querySelector('.toast');
            if (el) new bootstrap.Toast(el).show();
        })();
    </script>
<?php endif; ?>

<?php if (!empty($errores) && $errores_origen === 'perfil'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php foreach ($errores as $m): ?><div><?= htmlspecialchars($m) ?></div><?php endforeach; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="container-fluid d-flex justify-content-center align-items-start py-4">
    <div class="mtc-card" style="width: min(520px, 100%); padding:1.3rem 1.2rem 1.4rem;">
        <h3 class="mb-2" style="display:flex;align-items:center;gap:.5rem;">
            <i class="bi bi-person-gear" style="color:#0348bf;"></i>
            Configuración de cuenta
        </h3>

        <!-- recuadro de info -->
        <div class="alert alert-info py-2 mb-3">
            Aquí puedes actualizar tu nombre y correo. La contraseña es opcional, solo llénala si quieres cambiarla.
        </div>

        <form id="perfilForm" action="<?= BASE_URL ?>/config/app.php?accion=consultor.perfil.guardar" method="post" class="vstack gap-3" novalidate>
            <!-- Nombre -->
            <div>
                <label class="form-label fw-semibold" for="nombre">Nombre</label>
                <input type="text" name="nombre" id="nombre" class="form-control" maxlength="50"
                    value="<?= $nombreVal ?>" required>
                <!-- ocultos por defecto -->
                <div class="invalid-feedback" id="err-nombre" style="display:none;"></div>
                <div class="text-success small mt-1" id="ok-nombre" style="display:none;">✓ Nombre válido</div>
            </div>

            <!-- Correo -->
            <div>
                <label class="form-label fw-semibold" for="email">Correo corporativo</label>
                <input type="email" name="email" id="email" class="form-control" maxlength="100"
                    value="<?= $emailVal ?>" required>
                <div class="invalid-feedback" id="err-email" style="display:none;"></div>
                <div class="text-success small mt-1" id="ok-email" style="display:none;">✓ Correo válido</div>
            </div>

            <!-- Contraseña -->
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="pass">Contraseña nueva</label>
                    <div class="position-relative">
                        <input type="password" name="contrasenia" id="pass" class="form-control pe-5" autocomplete="new-password">
                        <button type="button" class="btn btn-sm btn-link position-absolute top-50 end-0 translate-middle-y me-1" id="togglePass">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback" id="err-pass" style="display:none;"></div>
                    <div class="text-success small mt-1" id="ok-pass" style="display:none;">✓ Contraseña válida</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="pass2">Confirmar contraseña</label>
                    <div class="position-relative">
                        <input type="password" name="contrasenia_confirm" id="pass2" class="form-control pe-5" autocomplete="new-password">
                        <button type="button" class="btn btn-sm btn-link position-absolute top-50 end-0 translate-middle-y me-1" id="togglePass2">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback" id="err-pass2" style="display:none;"></div>
                    <div class="text-success small mt-1" id="ok-pass2" style="display:none;">✓ Coinciden</div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button id="btnGuardar" class="mtc-chip mtc-chip--primary" type="submit">
                    <i class="bi bi-check2"></i> Guardar cambios
                </button>
                <a class="mtc-chip mtc-chip--ghost" href="<?= BASE_URL ?>/config/app.php?accion=home.dashboard">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    const PERFIL_CFG = {
        baseUrl: "<?= BASE_URL ?>",
        dominio: "@mtcenter.com.mx"
    };
</script>
<script src="<?= BASE_URL ?>/View/contenido/js/consultor.perfil.js?v=<?= time() ?>"></script>