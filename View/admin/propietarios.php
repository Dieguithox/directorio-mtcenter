<?php
$active = 'propietario';
$title = 'Propietarios';
require_once __DIR__ . '/../../config/ui.php';
require __DIR__ . '/../layout/headAdmin.php';

/* Reapertura de modal según PRG */
$reabrirCrear  = !empty($errores) && $errores_origen === 'propietarios' && empty($viejo['idPropietario']);
$reabrirEditar = !empty($errores) && $errores_origen === 'propietarios' && !empty($viejo['idPropietario']);

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

<!-- Título + acciones -->
<div class="mb-3">
    <h2 class="h5 mb-2">Gestión de Propietarios</h2>
    <div class="d-flex align-items-center gap-2">
        <form class="input-group" style="max-width: 460px;" method="get" action="<?= BASE_URL ?>/config/app.php">
            <input type="hidden" name="accion" value="propietarios.listar">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input id="buscador" name="q" type="search" class="form-control" placeholder="Buscar por nombre o extensión"
                value="<?= htmlspecialchars($q) ?>">
            <button class="btn btn-outline-secondary">Buscar</button>
        </form>
        <div class="ms-auto">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalPropietario" onclick="openCreate()">
                <i class="bi bi-plus-lg me-1"></i> Agregar propietario
            </button>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tablaPropietarios">
            <thead>
                <tr class="table-primary">
                    <th style="width:260px;">Nombre</th>
                    <th>Correo</th>
                    <th>Área</th>
                    <th>Puesto</th>
                    <th>Extensión</th>
                    <th style="width:180px;" class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($propietarios)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Sin registros</td>
                    </tr>
                    <?php else: foreach ($propietarios as $row): ?>
                        <tr data-id="<?= (int)($row['idPropietario'] ?? 0) ?>">
                            <td class="fw-semibold">
                                <?= htmlspecialchars(trim(($row['nombre'] ?? '') . ' ' . ($row['apellidoP'] ?? '') . ' ' . ($row['apellidoM'] ?? ''))) ?>
                            </td>
                            <td><?= htmlspecialchars($row['email'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($row['areaNombre'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($row['puestoNombre'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($row['extensionNumero'] ?? '—') ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary"
                                        onclick='openEdit(
                    <?= (int)($row["idPropietario"] ?? 0) ?>,
                    <?= json_encode($row["nombre"] ?? "") ?>,
                    <?= json_encode($row["apellidoP"] ?? "") ?>,
                    <?= json_encode($row["apellidoM"] ?? "") ?>,
                    <?= json_encode($row["email"] ?? "") ?>,
                    <?= (int)($row["puestoId"] ?? 0) ?>,
                    <?= json_encode($row["areaIdDelPuesto"] ?? "") ?>,
                    <?= json_encode($row["extensionId"] ?? "") ?>
                )'>
                                        <i class="bi bi-pencil-square me-1"></i> Editar
                                    </button>
                                    <button class="btn btn-outline-danger"
                                        onclick='openConfirmEliminar(<?= (int)($row["idPropietario"] ?? 0) ?>, <?= json_encode(($row["nombre"] ?? "") . " " . ($row["apellidoP"] ?? "")) ?>)'>
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
<div class="modal fade" id="modalPropietario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <!-- DEFAULT action = CREAR -->
        <form class="modal-content needs-validation" novalidate method="post" id="formPropietario"
            action="<?= BASE_URL ?>/config/app.php?accion=propietarios.crear">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nuevo propietario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($errores) && $errores_origen === 'propietarios'): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php foreach ($errores as $m): ?><div><?= htmlspecialchars($m) ?></div><?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <input type="hidden" name="idPropietario" id="idPropietario"
                    value="<?= isset($viejo['idPropietario']) ? (int)$viejo['idPropietario'] : '' ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nombre(s)</label>
                        <input type="text" class="form-control" name="nombre" id="nombre" required minlength="3" maxlength="50" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' ]{3,50}$"
                            value="<?= isset($viejo['nombre']) ? htmlspecialchars($viejo['nombre']) : '' ?>">
                        <div class="invalid-feedback">Campo obligatorio, mínimo 3 caracteres.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Apellido paterno</label>
                        <input type="text" class="form-control" name="apellidoP" id="apellidoP" required minlength="3" maxlength="50" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ'-]{3,50}$"
                            value="<?= isset($viejo['apellidoP']) ? htmlspecialchars($viejo['apellidoP']) : '' ?>">
                        <div class="invalid-feedback">Campo obligatorio, mínimo 3 caracteres.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Apellido materno</label>
                        <input type="text" class="form-control" name="apellidoM" id="apellidoM" required minlength="3" maxlength="50" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ'-]{3,50}$"
                            value="<?= isset($viejo['apellidoM']) ? htmlspecialchars($viejo['apellidoM']) : '' ?>">
                        <div class="invalid-feedback">Campo obligatorio, mínimo 3 caracteres</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Área</label>
                        <select class="form-select" name="areaId" id="areaId">
                            <option value="">Selecciona</option>
                            <?php foreach ($areas as $a): ?>
                                <option value="<?= (int)$a['idArea'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Se usa para filtrar puestos.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Puesto</label>
                        <select class="form-select" name="puestoId" id="puestoId" required>
                            <option value="">Selecciona</option>
                        </select>
                        <div class="invalid-feedback">Seleccione un puesto.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Extensión</label>
                        <select class="form-select" name="extensionId" id="extensionId">
                            <option value="">Selecciona</option>
                            <?php foreach ($extensiones as $e): ?>
                                <option value="<?= (int)$e['idExtension'] ?>"><?= htmlspecialchars($e['numero']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Correo corporativo principal</label>
                        <input type="email" class="form-control" name="correo" id="correo" required maxlength="150" pattern="^[A-Za-z0-9._%+-]+@mtcenter\.com\.mx$"
                            value="<?= isset($viejo['correo']) ? htmlspecialchars($viejo['correo']) : '' ?>">
                        <div class="invalid-feedback">Debe ser @mtcenter.com.mx</div>
                    </div>
                    <!-- ===== Repeater de correos adicionales ===== -->
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between">
                            <label class="form-label mb-0">Correos adicionales</label>
                            <button class="btn btn-sm btn-outline-secondary" type="button" id="btnAddCorreo">
                                <i class="bi bi-plus-lg me-1"></i>Añadir correo
                            </button>
                        </div>
                        <div id="wrapCorreos" class="mt-2"></div>
                        <!-- Template oculto -->
                        <template id="tplCorreo">
                            <div class="input-group mb-2 correo-item">
                                <span class="input-group-text"><i class="bi bi-at"></i></span>
                                <input type="email" name="correos_extra[]" class="form-control" placeholder="nombre@mtcenter.com.mx" maxlength="150" pattern="^[A-Za-z0-9._%+-]+@mtcenter\.com\.mx$">
                                <button class="btn btn-outline-danger btn-remove" type="button" title="Quitar">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                                <div class="invalid-feedback">Debe ser @mtcenter.com.mx</div>
                            </div>
                        </template>
                    </div>
                </div>
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
        <form class="modal-content" method="post" action="<?= BASE_URL ?>/config/app.php?accion=propietarios.eliminar" id="formEliminar">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="idPropietario" id="idEliminar">
                <p class="mb-0">¿Seguro que deseas eliminar a <span class="fw-semibold" id="etiquetaNombre"></span>?</p>
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
    const CATALOGO_PUESTOS = <?= json_encode($puestos, JSON_UNESCAPED_UNICODE) ?>;
    const PROPS_CORREOS = <?= json_encode($mapCorreosProp ?? [], JSON_UNESCAPED_UNICODE) ?>;

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
<script src="<?= BASE_URL ?>/View/contenido/js/admin.propietarios.js?v=<?= time() ?>"></script>