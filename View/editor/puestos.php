<?php
$active = 'puestos';
$title = 'Puestos';
require_once __DIR__ . '/../../config/ui.php';
require __DIR__ . '/../layout/headEditor.php';

$reabrirCrear  = !empty($errores) && $errores_origen === 'puestos' && empty($viejo['idPuesto']);
$reabrirEditar = !empty($errores) && $errores_origen === 'puestos' && !empty($viejo['idPuesto']);
$q = trim($_GET['q'] ?? '');

if (!empty($flash_texto) && !empty($flash_nivel)): ?>
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
<?php endif;
?>

<!-- Título + buscador -->
<div class="mb-3">
    <h2 class="h5 mb-2">Puestos</h2>
    <div class="d-flex align-items-center gap-2">
        <form class="d-flex flex-grow-1" method="get" action="<?= BASE_URL ?>/config/app.php">
        <input type="hidden" name="accion" value="puestos.listar">
        <div class="input-group" style="max-width:420px">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input type="search" name="q" class="form-control" placeholder="Buscar por nombre, descripción o área"
                value="<?= htmlspecialchars($q) ?>">
        </div>
        </form>
        <div class="ms-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPuesto" onclick="openCreate()">
            <i class="bi bi-briefcase me-1"></i> Nuevo puesto
        </button>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
        <thead>
            <tr class="table-light">
            <th>Nombre</th>
            <th class="text-center">Área</th>
            <th class="text-center">Descripción</th>
            <th class="text-center" style="width:180px">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($puestos)): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">Sin registros</td></tr>
            <?php else: foreach ($puestos as $p): ?>
            <tr data-id="<?= (int)$p['idPuesto'] ?>">
                <td class="fw-semibold"><?= htmlspecialchars($p['nombre']) ?></td>
                <td class="text-center"><?= htmlspecialchars($p['areaNombre']) ?></td>
                <td class="text-center"><?= htmlspecialchars($p['descripcion']) ?></td>
                <td class="text-end">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary"
                    onclick='openEdit(<?= (int)$p["idPuesto"] ?>, <?= json_encode($p["nombre"]) ?>, <?= json_encode($p["descripcion"]) ?>, <?= (int)$p["areaId"] ?>)'>
                    <i class="bi bi-pencil-square me-1"></i> Editar
                    </button>
                    <button class="btn btn-outline-danger"
                    onclick='openConfirmEliminar(<?= (int)$p["idPuesto"] ?>, <?= json_encode($p["nombre"]) ?>)'>
                    <i class="bi bi-trash me-1"></i> Eliminar
                    </button>
                </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear/Editar -->
<div class="modal fade" id="modalPuesto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content needs-validation" novalidate method="post" id="formPuesto"
            action="<?= BASE_URL ?>/config/app.php?accion=puestos.crear">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Nuevo puesto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

            <?php if (!empty($errores) && $errores_origen === 'puestos'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php foreach ($errores as $m): ?><div><?= htmlspecialchars($m) ?></div><?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <input type="hidden" name="idPuesto" id="idPuesto" value="<?= isset($viejo['idPuesto']) ? (int)$viejo['idPuesto'] : '' ?>">

            <div class="mb-2">
            <label class="form-label">Nombre del puesto</label>
            <input type="text" class="form-control" name="nombre" id="nombre" required
                    value="<?= isset($viejo['nombre']) ? htmlspecialchars($viejo['nombre']) : '' ?>">
            <div class="invalid-feedback">Nombre obligatorio.</div>
            </div>

            <div class="mb-2">
            <label class="form-label">Área</label>
            <select class="form-select" name="areaId" id="areaId" required>
                <option value=""> Selecciona un área </option>
                <?php foreach (($areas ?? []) as $a): ?>
                <option value="<?= (int)$a['idArea'] ?>"
                    <?= (isset($viejo['areaId']) && (int)$viejo['areaId'] === (int)$a['idArea']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($a['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">El área es obligatoria.</div>
            </div>

            <div class="mb-2">
            <label class="form-label">Descripción</label>
            <input type="text" class="form-control" name="descripcion" id="descripcion" required maxlength="100"
                    value="<?= isset($viejo['descripcion']) ? htmlspecialchars($viejo['descripcion']) : '' ?>">
            <div class="invalid-feedback">Descripción obligatoria.</div>
            </div>

        </div>
        <div class="modal-footer">
            <button class="btn btn-light" data-bs-dismiss="modal" type="button">Cancelar</button>
            <button class="btn btn-primary" type="submit" id="submitBtn">Guardar</button>
        </div>
        </form>
    </div>
</div>

<!-- Modal Confirmar eliminación -->
<div class="modal fade" id="modalConfirmEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= BASE_URL ?>/config/app.php?accion=puestos.eliminar" id="formEliminar">
        <div class="modal-header">
            <h5 class="modal-title">Confirmar eliminación</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="idPuesto" id="idEliminar">
            <p class="mb-0">¿Seguro que deseas eliminar el puesto <span class="fw-semibold" id="etiquetaNombre"></span>?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger">Sí, eliminar</button>
        </div>
        </form>
    </div>
</div>

<script>
    // Val. Bootstrap
    document.querySelectorAll('.needs-validation').forEach(f=>{
        f.addEventListener('submit',e=>{ if(!f.checkValidity()){ e.preventDefault(); e.stopPropagation(); } f.classList.add('was-validated'); });
    });

    function openCreate(){
        const f = document.getElementById('formPuesto');
        f.action = "<?= BASE_URL ?>/config/app.php?accion=puestos.crear";
        document.getElementById('modalTitle').textContent = "Nuevo puesto";
        document.getElementById('idPuesto').value = "";
        <?php if (!$reabrirCrear): ?>
        document.getElementById('nombre').value = "";
        document.getElementById('descripcion').value = "";
        document.getElementById('areaId').value = "";
        <?php endif; ?>
    }

    function openEdit(id, nombre, descripcion, areaId){
        const f = document.getElementById('formPuesto');
        f.action = "<?= BASE_URL ?>/config/app.php?accion=puestos.actualizar";
        document.getElementById('modalTitle').textContent = "Editar puesto";
        document.getElementById('idPuesto').value = id;
        document.getElementById('nombre').value = nombre;
        document.getElementById('descripcion').value = descripcion;
        document.getElementById('areaId').value = areaId;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPuesto')).show();
    }

    function openConfirmEliminar(id, nombre){
        document.getElementById('idEliminar').value = id;
        document.getElementById('etiquetaNombre').textContent = nombre ?? '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmEliminar')).show();
    }

    // Reabrir modal por PRG
    <?php if ($reabrirCrear): ?>
        openCreate();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPuesto')).show();
    <?php endif; ?>
    <?php if ($reabrirEditar): ?>
        openEdit(<?= (int)($viejo['idPuesto'] ?? 0) ?>, <?= json_encode($viejo['nombre'] ?? '') ?>, <?= json_encode($viejo['descripcion'] ?? '') ?>, <?= (int)($viejo['areaId'] ?? 0) ?>);
    <?php endif; ?>
</script>

<script src="<?= BASE_URL ?>/View/js/admin.puestos.js"></script>