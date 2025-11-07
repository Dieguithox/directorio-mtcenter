<?php
$active = 'extension';
$title = 'Extensiones';
require_once __DIR__ . '/../../config/ui.php';
require __DIR__ . '/../layout/headAdmin.php';

/* Reapertura de modal según PRG */
$reabrirCrear  = !empty($errores) && $errores_origen === 'extensiones' && empty($viejo['idExtension']);
$reabrirEditar = !empty($errores) && $errores_origen === 'extensiones' && !empty($viejo['idExtension']);
?>
<?php if (!empty($flash_texto) && !empty($flash_nivel)): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080">
        <div class="toast text-bg-<?= htmlspecialchars($flash_nivel) ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="8000">
            <div class="d-flex">
                <div class="toast-body"><?= htmlspecialchars($flash_texto) ?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
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

<!-- Título + Fila: buscador izq / botón azul der -->
<div class="mb-3">
    <h2 class="h5 mb-2">Extensiones del sistema</h2>
    <div class="d-flex align-items-center gap-2">
        <div class="input-group" style="max-width: 420px;">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input id="buscador" type="search" class="form-control" style="max-width:360px" placeholder="Buscar extensión o descripción">
        </div>
        <div class="ms-auto">
            <button id="btnNuevaExtension" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalExtension" onclick="openCreate()">
                <i class="bi bi-plus-lg me-1"></i> Agregar extensión
            </button>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tablaExt">
            <thead>
                <tr class="table-primary">
                    <th style="width:160px;">Extensión</th>
                    <th>Descripción</th>
                    <th style="width:180px;" class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($extensiones)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">Sin registros</td>
                    </tr>
                    <?php else: foreach ($extensiones as $e): ?>
                        <tr data-id="<?= (int)$e['idExtension'] ?>">
                            <td class="fw-semibold"><?= htmlspecialchars($e['numero']) ?></td>
                            <td><?= htmlspecialchars($e['descripcion']) ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary"
                                        onclick='openEdit(<?= (int)$e["idExtension"] ?>, <?= json_encode($e["numero"]) ?>, <?= json_encode($e["descripcion"]) ?>)'>
                                        <i class="bi bi-pencil-square me-1"></i> Editar
                                    </button>
                                    <button class="btn btn-outline-danger"
                                        onclick='openConfirmEliminar(<?= (int)$e["idExtension"] ?>, <?= json_encode($e["numero"]) ?>)'>
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
<div class="modal fade" id="modalExtension" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content needs-validation" novalidate method="post" id="formExtension"
            action="<?= BASE_URL ?>/config/app.php?accion=extensiones.crear"><!-- default crear -->
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nueva extensión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($errores) && $errores_origen === 'extensiones'): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php foreach ($errores as $m): ?><div><?= htmlspecialchars($m) ?></div><?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <input type="hidden" name="idExtension" id="idExtension"
                    value="<?= isset($viejo['idExtension']) ? (int)$viejo['idExtension'] : '' ?>">

                <div class="mb-3">
                    <label class="form-label">Extensión</label>
                    <input type="text" class="form-control" name="numero" id="numero" required
                        pattern="^\d{1,3}$" placeholder="Solo dígitos (1-3)"
                        value="<?= isset($viejo['numero']) ? htmlspecialchars($viejo['numero']) : '' ?>">
                    <div class="invalid-feedback">Solo dígitos, de 1 a 3.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <input type="text" class="form-control" name="descripcion" id="descripcion" required maxlength="100"
                        value="<?= isset($viejo['descripcion']) ? htmlspecialchars($viejo['descripcion']) : '' ?>">
                    <div class="invalid-feedback">Descripción obligatoria.</div>
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
        <form class="modal-content" method="post" action="<?= BASE_URL ?>/config/app.php?accion=extensiones.eliminar" id="formEliminar">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="idExtension" id="idEliminar">
                <p class="mb-0">¿Seguro que deseas eliminar la extensión <span class="fw-semibold" id="etiquetaNumero"></span>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-outline-danger">Sí, eliminar</button>
            </div>
        </form>
    </div>
</div>

<!-- Variables para el JS externo -->
<script>
    const BASE_URL = "<?= BASE_URL ?>";
    const PR_REABRIR = <?= json_encode($reabrirCrear ? 'crear' : ($reabrirEditar ? 'editar' : null)) ?>;
    const PR_DATOS = <?= json_encode($viejo ?? []) ?>;
</script>
<!-- Tu JS externo -->
<script src="<?= BASE_URL ?>/View/contenido/js/admin.extensiones.js?v=<?= time() ?>"></script>