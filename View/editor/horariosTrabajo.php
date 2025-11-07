<?php
$active = 'horario';
$title = 'Horario de Trabajo';
require_once __DIR__ . '/../../config/ui.php';
require __DIR__ . '/../layout/headEditor.php';

$reabrirCrear  = !empty($errores) && $errores_origen === 'horarios' && empty($viejo['idHorario']);
$reabrirEditar = !empty($errores) && $errores_origen === 'horarios' && !empty($viejo['idHorario']);
$q = trim($_GET['q'] ?? '');

$listaEmpleados = [];
if (isset($empleados) && is_iterable($empleados)) {
    $listaEmpleados = $empleados;
} elseif (isset($propietarios) && is_iterable($propietarios)) {
    $listaEmpleados = $propietarios;
}
?>

<?php if (!empty($flash_texto) && !empty($flash_nivel)): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080">
        <div class="toast text-bg-<?= htmlspecialchars($flash_nivel) ?> border-0 show">
            <div class="d-flex">
                <div class="toast-body"><?= htmlspecialchars($flash_texto) ?></div>
                <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
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

<div class="mb-3 d-flex align-items-center gap-2">
    <h2 class="h5 mb-0">Horarios de trabajo</h2>
    <form class="d-flex flex-grow-1 ms-3" method="get" action="<?= BASE_URL ?>/config/app.php">
        <input type="hidden" name="accion" value="horarios.listar">
        <div class="input-group" style="max-width:420px">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input type="search" name="q" class="form-control" placeholder="Buscar por empleado o correo" value="<?= htmlspecialchars($q) ?>">
        </div>
    </form>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalHorario" onclick="openCreate()">
        <i class="bi bi-calendar-plus me-1"></i> Nuevo horario
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light fw-semibold">
                <tr>
                    <th>Empleado</th>
                    <th class="text-center">Días</th>
                    <th class="text-center">Entrada</th>
                    <th class="text-center">Salida</th>
                    <th class="text-end" style="width:180px">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($horarios)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Sin registros</td>
                    </tr>
                    <?php else: foreach ($horarios as $i => $h):
                        // $h viene AGRUPADO: idRepresentante, propietarioId, propNombre, propEmail, horaEntrada, horaSalida, dias (array '1'..'7')
                        $diasTexto = \Horario::diasATexto($h['dias']); // “Lun–Vie, Sáb”
                        $badgeClass = ['badge-a', 'badge-b', 'badge-c'][$i % 3];
                    ?>
                        <tr data-id="<?= (int)$h['idRepresentante'] ?>">
                            <td>
                                <span class="avatar-mini"><?= strtoupper(substr($h['propNombre'], 0, 1)) ?></span>
                                <div class="d-inline-block align-middle ms-2">
                                    <div class="fw-semibold"><?= htmlspecialchars($h['propNombre']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($h['propEmail']) ?></div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge-day <?= $badgeClass ?>"><?= htmlspecialchars($diasTexto) ?></span>
                            </td>
                            <td class="text-center"><span class="pill-time" data-time="<?= htmlspecialchars($h['horaEntrada']) ?>"></span></td>
                            <td class="text-center"><span class="pill-time" data-time="<?= htmlspecialchars($h['horaSalida']) ?>"></span></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary"
                                        onclick='openEdit(<?= (int)$h["idRepresentante"] ?>, <?= (int)$h["propietarioId"] ?>, <?= json_encode($h["dias"]) ?>, <?= json_encode($h["horaEntrada"]) ?>, <?= json_encode($h["horaSalida"]) ?>, <?= json_encode($h["propNombre"]) ?>)'>
                                        <i class="bi bi-pencil-square me-1"></i> Editar
                                    </button>
                                    <button class="btn btn-outline-danger"
                                        onclick='openDelete(<?= (int)$h["idRepresentante"] ?>, <?= (int)$h["propietarioId"] ?>, <?= json_encode($h["horaEntrada"]) ?>, <?= json_encode($h["horaSalida"]) ?>, <?= json_encode($h["propNombre"]) ?>)'>
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
<div class="modal fade" id="modalHorario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content needs-validation" novalidate method="post" id="formHorario"
            action="<?= BASE_URL ?>/config/app.php?accion=horarios.crear">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nuevo horario</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($errores) && $errores_origen === 'horarios'): ?>
                    <div class="alert alert-danger mb-3">
                        <?php foreach ($errores as $m): ?><div><?= htmlspecialchars($m) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <!-- Identificadores para edición de bloque -->
                <input type="hidden" name="idHorario" id="idHorario">
                <input type="hidden" name="old_he" id="old_he">
                <input type="hidden" name="old_hs" id="old_hs">

                <div class="mb-3">
                    <label class="form-label">Empleado</label>
                    <select class="form-select" name="propietarioId" id="propietarioId" required>
                        <option value="">Seleccione</option>
                        <?php if (!empty($listaEmpleados)): foreach ($listaEmpleados as $e): ?>
                                <option value="<?= (int)$e['idPropietario'] ?>">
                                    <?= htmlspecialchars($e['nombre']) ?> (<?= htmlspecialchars($e['email']) ?>)
                                </option>
                        <?php endforeach;
                        endif; ?>
                    </select>
                    <div class="form-text" id="subtituloEmpleado" style="display:none"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Días</label>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-check">
                                <input type="checkbox" class="form-check-input" id="chkGeneral">
                                <span class="ms-1">Horario general (todos los días)</span>
                            </label>
                        </div>
                        <?php
                        $diasForm = [['1', 'Lunes'], ['2', 'Martes'], ['3', 'Miércoles'], ['4', 'Jueves'], ['5', 'Viernes'], ['6', 'Sábado'], ['7', 'Domingo']];
                        foreach ($diasForm as [$v, $t]): ?>
                            <div class="col-6 col-md-3">
                                <label class="form-check">
                                    <input type="checkbox" class="form-check-input dia" name="dias[]" value="<?= $v ?>"> <span class="ms-1"><?= $t ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="invalid-feedback d-block" id="diasError" style="display:none">Selecciona al menos un día.</div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Entrada</label>
                        <input type="time" class="form-control" name="horaEntrada" id="horaEntrada" required step="60">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Salida</label>
                        <input type="time" class="form-control" name="horaSalida" id="horaSalida" required step="60">
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" type="submit">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal eliminar (bloque completo) -->
<div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= BASE_URL ?>/config/app.php?accion=horarios.eliminar">
            <div class="modal-header">
                <h5 class="modal-title">Eliminar horario</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="idHorario" id="delId">
                <input type="hidden" name="propietarioId" id="delProp">
                <input type="hidden" name="old_he" id="delOldHe">
                <input type="hidden" name="old_hs" id="delOldHs">
                <p>¿Eliminar el bloque de horario de <strong id="delNombre"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-danger" type="submit">Sí, eliminar</button>
            </div>
        </form>
    </div>
</div>

<script>
    const BASE_URL = "<?= BASE_URL ?>";
    // Horarios actuales para validar traslapes en cliente
    const HORARIOS = <?= json_encode(array_map(function ($h) {
                            return [
                                'id'            => (int)($h['idRepresentante'] ?? 0),
                                'propietarioId' => (int)($h['propietarioId']   ?? 0),
                                'dias'          => array_values($h['dias'] ?? []), // ['1','2',...]
                                'he'            => (string)($h['horaEntrada'] ?? '09:00:00'),
                                'hs'            => (string)($h['horaSalida']  ?? '18:00:00'),
                                'propNombre'    => (string)($h['propNombre']  ?? '')
                            ];
                        }, $horarios ?? []), JSON_UNESCAPED_UNICODE) ?>;
    <?php if ($reabrirCrear): ?>
        var PR_MODO = 'crear';
        var PR_DATA = <?= json_encode($viejo ?? [], JSON_UNESCAPED_UNICODE) ?>;
    <?php elseif ($reabrirEditar): ?>
        var PR_MODO = 'editar';
        var PR_DATA = <?= json_encode($viejo ?? [], JSON_UNESCAPED_UNICODE) ?>;
    <?php else: ?>
        var PR_MODO = null,
            PR_DATA = null;
    <?php endif; ?>
</script>
<script src="<?= BASE_URL ?>/View/contenido/js/admin.horariosTrabajo.js?v=<?= time() ?>"></script>