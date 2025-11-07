<?php
$active = 'areas';
$titulo = 'Áreas';
require_once __DIR__ . '/../../config/ui.php';
require __DIR__ . '/../layout/headAdmin.php';
/* Reapertura de modal según PRG */
$reabrirCrear  = !empty($errores) && $errores_origen === 'areas' && empty($viejo['idArea']);
$reabrirEditar = !empty($errores) && $errores_origen === 'areas' && !empty($viejo['idArea']);
/* Buscador (opcional por GET) */
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
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

<!-- Título + acción -->
<div class="mb-3">
    <h2 class="h5 mb-2">Áreas de la empresa</h2>
    <div class="d-flex align-items-center gap-2">
        <form class="input-group" style="max-width: 460px;" method="get" action="<?= BASE_URL ?>/config/app.php">
            <input type="hidden" name="accion" value="areas.listar">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input id="buscador" name="q" type="search" class="form-control" placeholder="Buscar área o descripción" value="<?= htmlspecialchars($q) ?>">
            <button class="btn btn-outline-secondary">Buscar</button>
        </form>
        <div class="ms-auto">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalArea" onclick="openCreate()">
                <i class="bi bi-plus-lg me-1"></i> Agregar área
            </button>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tablaAreas">
            <thead>
                <tr class="table-primary">
                    <th style="width:260px;">Nombre</th>
                    <th>Correo corporativo</th>
                    <th>Descripción</th>
                    <th class="text-center" style="width: 180px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($areas)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Sin registros</td>
                    </tr>
                    <?php else: foreach ($areas as $a): ?>
                        <tr data-id="<?= (int)$a['idArea'] ?>">
                            <td class="fw-semibold"><?= htmlspecialchars($a['nombre']) ?></td>
                            <td><?= htmlspecialchars($a['email'] ?? '—') ?></td>
                            <td class="text-truncate" style="max-width:380px;"><?= htmlspecialchars($a['descripcion'] ?? '') ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary"
                                        onclick='openEdit(
                                        <?= (int)$a["idArea"] ?>,
                                        <?= json_encode($a["nombre"]) ?>,
                                        <?= json_encode($a["email"] ?? "") ?>,
                                        <?= json_encode($a["descripcion"] ?? "") ?>
                                    )'>
                                        <i class="bi bi-pencil-square me-1"></i> Editar
                                    </button>
                                    <button class="btn btn-outline-danger"
                                        onclick='openConfirmEliminar(<?= (int)$a["idArea"] ?>, <?= json_encode($a["nombre"]) ?>)'>
                                        <i class="bi bi-trash me-1"></i> Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                <?php endforeach;
                endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear/Editar -->
<div class="modal fade" id="modalArea" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <!-- DEFAULT action = CREAR -->
        <form class="modal-content needs-validation" novalidate method="post" id="formArea"
            action="<?= BASE_URL ?>/config/app.php?accion=areas.crear">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nueva área</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($errores) && $errores_origen === 'areas'): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php foreach ($errores as $m): ?><div><?= htmlspecialchars($m) ?></div><?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <input type="hidden" name="idArea" id="idArea"
                    value="<?= isset($viejo['idArea']) ? (int)$viejo['idArea'] : '' ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre del área</label>
                        <input type="text" class="form-control" name="nombre" id="nombre" required maxlength="50"
                            placeholder="Ej. Recursos Humanos"
                            value="<?= isset($viejo['nombre']) ? htmlspecialchars($viejo['nombre']) : '' ?>">
                        <div class="invalid-feedback">Campo obligatorio, mínimo 3 caracteres.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Correo corporativo principal</label>
                        <input type="email" class="form-control" name="email" id="email" maxlength="100"
                            placeholder="area@mtcenter.com.mx"
                            value="<?= isset($viejo['email']) ? htmlspecialchars($viejo['email']) : '' ?>">
                        <div class="invalid-feedback">Usa correo corporativo @mtcenter.com.mx</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripción</label>
                        <input type="text" class="form-control" name="descripcion" id="descripcion" maxlength="255"
                            value="<?= isset($viejo['descripcion']) ? htmlspecialchars($viejo['descripcion']) : '' ?>">
                    </div>
                </div>

                <hr class="my-3">

                <!-- Correos adicionales (repeater) -->
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">Correos adicionales</h6>
                    <button class="btn btn-sm btn-outline-primary" type="button" id="btnAddCorreoExtra">
                        <i class="bi bi-plus-lg"></i> Añadir correo
                    </button>
                </div>

                <div id="correosExtraWrap" class="mt-2">
                    <?php
                    // Si hubo PRG con viejo['correos_extra'], repoblar
                    $viejos_correos = [];
                    if (!empty($viejo['correos_extra']) && is_array($viejo['correos_extra'])) {
                        foreach ($viejo['correos_extra'] as $c) {
                            $c = trim((string)$c);
                            if ($c !== '') $viejos_correos[] = $c;
                        }
                    }
                    ?>
                    <?php if (!empty($viejos_correos)): ?>
                        <?php foreach ($viejos_correos as $c): ?>
                            <div class="input-group mb-2 correo-extra-item">
                                <span class="input-group-text"><i class="bi bi-at"></i></span>
                                <input type="email" class="form-control" name="correos_extra[]" value="<?= htmlspecialchars($c) ?>" placeholder="otro-correo@mtcenter.com.mx">
                                <button type="button" class="btn btn-outline-danger btnRemoveCorreoExtra"><i class="bi bi-x-lg"></i></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Template oculto -->
                <template id="tplCorreoExtra">
                    <div class="input-group mb-2 correo-extra-item">
                        <span class="input-group-text"><i class="bi bi-at"></i></span>
                        <input type="email" class="form-control" name="correos_extra[]" placeholder="otro-correo@mtcenter.com.mx">
                        <button type="button" class="btn btn-outline-danger btnRemoveCorreoExtra"><i class="bi bi-x-lg"></i></button>
                        <div class="invalid-feedback">Correo corporativo @mtcenter.com.mx</div>
                    </div>
                </template>
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal" type="button">Cancelar</button>
                <button class="btn btn-outline-primary" type="submit" id="submitBtn">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Confirmar eliminación -->
<div class="modal fade" id="modalConfirmEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= BASE_URL ?>/config/app.php?accion=areas.eliminar" id="formEliminar">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="idArea" id="idEliminar">
                <p class="mb-0">¿Seguro que deseas eliminar el área <span class="fw-semibold" id="etiquetaNombre"></span>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-outline-danger">Sí, eliminar</button>
            </div>
        </form>
    </div>
</div>

<script>
    const BASE_URL = "<?= BASE_URL ?>";
    const AREAS_CORREOS = <?= json_encode($mapCorreos ?? [], JSON_UNESCAPED_UNICODE) ?>;

    <?php if ($reabrirCrear): ?>
        var PR_REABRIR = 'crear';
        var PR_DATOS = <?= json_encode($viejo ?? []) ?>;
    <?php elseif ($reabrirEditar): ?>
        var PR_REABRIR = 'editar';
        var PR_DATOS = <?= json_encode($viejo ?? []) ?>;
    <?php else: ?>
        var PR_REABRIR = null,
            PR_DATOS = null;
    <?php endif; ?>
</script>
<script src="<?= BASE_URL ?>/View/contenido/js/admin.areas.js?v=<?= time() ?>"></script>