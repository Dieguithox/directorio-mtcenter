<?php
$active = 'cambios';
$title = 'Cambios';
require_once __DIR__ . '/../../config/ui.php';
require __DIR__ . '/../layout/headConsultor.php';

$uid  = (int)($_SESSION['usuario']['id'] ?? 0);
$rol  = $_SESSION['usuario']['tipoUsuario'] ?? 'consultor';

$mapaCampos = [
    'nombre'      => 'Nombre',
    'apellidoP'   => 'Apellido paterno',
    'apellidoM'   => 'Apellido materno',
    'email'       => 'Correo electrónico',
    'puestoId'    => 'Puesto',
    'puesto'      => 'Puesto',
    'extensionId' => 'Extensión',
    'extension'   => 'Extensión',
];
?>

<?php if (!empty($flash_texto) && !empty($flash_nivel)): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast text-bg-<?= htmlspecialchars($flash_nivel) ?> border-0 show">
            <div class="d-flex">
                <div class="toast-body">
                    <?= htmlspecialchars($flash_texto) ?>
                </div>
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

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Solicitudes de cambio</h2>
    <div class="header-actions">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSolicitudCambio">
            <i class="bi bi-plus-lg me-1"></i> Nueva solicitud
        </button>
    </div>
</div>

<p class="text-muted mb-3">
    Aquí puedes ver los cambios que has solicitado sobre los contactos del directorio.
    Solo puedes cancelar las solicitudes que aún están pendientes.
</p>

<?php if (empty($solicitudes)): ?>
    <div class="alert alert-secondary">
        <i class="bi bi-info-circle me-1"></i> Aún no has registrado solicitudes de cambio.
    </div>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($solicitudes as $s):
            $propNombre = trim(($s['propietarioNombre'] ?? '') . ' ' . ($s['apellidoP'] ?? '') . ' ' . ($s['apellidoM'] ?? ''));
            $propNombre = $propNombre !== '' ? $propNombre : 'Contacto';

            $estado = $s['estado'] ?? 'pendiente';

            // traducimos el campo
            $campoRaw       = $s['campo'] ?? '';
            $campoTraducido = $mapaCampos[$campoRaw] ?? ucfirst($campoRaw);

            $nuevo  = $s['valor_nuevo'] ?? '';
            $coment = $s['comentario'] ?? '';
            $fecha  = !empty($s['creada_at']) ? date('d/m/Y H:i', strtotime($s['creada_at'])) : '';
        ?>
            <div class="list-group-item py-3 rounded-3 mb-2 border shadow-sm" style="background:#fff;">
                <div class="d-flex align-items-start gap-3">
                    <!-- avatar -->
                    <div class="avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                        style="width:42px;height:42px;font-weight:600;">
                        <?= strtoupper(mb_substr($propNombre, 0, 1)) ?>
                    </div>

                    <!-- contenido -->
                    <div class="flex-grow-1">
                        <!-- fila principal -->
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h6 class="mb-0"><?= htmlspecialchars($propNombre) ?></h6>

                            <span class="badge bg-light text-dark">
                                campo: <?= htmlspecialchars($campoTraducido) ?>
                            </span>

                            <?php if ($estado === 'pendiente'): ?>
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            <?php elseif ($estado === 'aprobado'): ?>
                                <span class="badge bg-success">Aprobado</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Rechazado</span>
                            <?php endif; ?>

                            <?php if ($fecha): ?>
                                <span class="ms-auto text-muted small d-flex align-items-center gap-1">
                                    <i class="bi bi-clock"></i><?= htmlspecialchars($fecha) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- info -->
                        <div class="text-muted small mt-2">
                            Nuevo valor solicitado: <strong><?= htmlspecialchars($nuevo) ?></strong>
                        </div>
                        <?php if ($coment): ?>
                            <div class="text-muted small">
                                Comentario: <?= nl2br(htmlspecialchars($coment)) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($s['motivo_revision'])): ?>
                            <div class="mt-2">
                                <span class="badge bg-secondary">Respuesta del revisor</span>
                                <div class="text-muted small mt-1">
                                    <?= nl2br(htmlspecialchars($s['motivo_revision'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- botón cancelar abajo, a la derecha -->
                        <?php if ($estado === 'pendiente'): ?>
                            <div class="d-flex justify-content-end mt-3">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="openCancelarSolicitud(<?= (int)$s['idSolicitudCambio'] ?>, '<?= htmlspecialchars($propNombre, ENT_QUOTES) ?>')">
                                    <i class="bi bi-x-lg me-1"></i> Cancelar solicitud
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ========== MODAL NUEVA SOLICITUD ========== -->
<div class="modal fade" id="modalSolicitudCambio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="post"
            action="<?= BASE_URL ?>/config/app.php?accion=consultor.solicitud.guardar"
            id="formSolicitudCambio" novalidate>
            <div class="modal-header">
                <h5 class="modal-title">Nueva solicitud de cambio</h5>
                <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
            </div>
            <div class="modal-body">

                <!-- Tarjetita de propietario -->
                <div id="prop-card" class="mb-3 p-3 border rounded bg-light d-none">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="rounded-circle bg-primary text-white d-flex justify-content-center align-items-center"
                            style="width:48px;height:48px;font-weight:600;" id="prop-avatar">P</div>
                        <div>
                            <div class="fw-bold" id="prop-nombre">Nombre del propietario</div>
                            <div class="text-muted small" id="prop-puesto">Contacto del directorio</div>
                            <div class="d-flex gap-2 mt-1 flex-wrap">
                                <span class="badge bg-secondary" id="prop-email">sin correo</span>
                                <span class="badge bg-secondary" id="prop-ext">Ext: —</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- selector de propietario -->
                <div class="mb-3">
                    <label class="form-label">Propietario</label>
                    <select name="propietarioId" class="form-select" id="propietarioSelect" required>
                        <option value="">Selecciona un contacto</option>
                        <?php foreach (($propietariosOpciones ?? []) as $p): ?>
                            <option value="<?= (int)$p['idPropietario'] ?>">
                                <?= htmlspecialchars($p['nombreCompleto']) ?> <?= !empty($p['email']) ? '(' . htmlspecialchars($p['email']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Selecciona un propietario.</div>
                </div>

                <!-- campo -->
                <div class="mb-3">
                    <label class="form-label">Campo a modificar</label>
                    <select name="campo" class="form-select" id="campoSelect" required>
                        <option value="">Selecciona</option>
                        <option value="nombre">Nombre</option>
                        <option value="apellidoP">Apellido paterno</option>
                        <option value="apellidoM">Apellido materno</option>
                        <option value="email">Correo corporativo</option>
                        <option value="extensionId">Extensión</option>
                        <option value="puestoId">Puesto</option>
                    </select>
                    <div class="invalid-feedback">Selecciona el campo que quieres modificar.</div>
                </div>

                <!-- nuevo valor -->
                <div class="mb-3">
                    <label class="form-label" for="valor_nuevo">Nuevo valor</label>
                    <input type="text" name="valor_nuevo" id="valor_nuevo" class="form-control" required>
                    <div class="form-text" id="ayuda-campo">Escribe exactamente cómo debería quedar el dato.</div>
                    <div class="invalid-feedback" id="error-campo">Este campo es obligatorio.</div>
                </div>

                <!-- comentario -->
                <div class="mb-3">
                    <label class="form-label">Comentario para el revisor</label>
                    <textarea name="comentario" class="form-control" rows="3" required
                        placeholder="Ej. La extensión cambió por cambio de oficina."></textarea>
                    <div class="invalid-feedback">Explica por qué debe hacerse el cambio.</div>
                </div>

                <!-- lo mandamos por si quieres guardarlo ya desde el controlador -->
                <input type="hidden" name="valor_anterior" id="valor_anterior" value="">
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" type="submit">Enviar solicitud</button>
            </div>
        </form>
    </div>
</div>

<!-- ========== MODAL CONFIRMAR CANCELACIÓN (centrado) ========== -->
<div class="modal fade" id="modalCancelarSolicitud" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= BASE_URL ?>/config/app.php?accion=consultor.solicitud.eliminar">
            <div class="modal-header">
                <h5 class="modal-title">Cancelar solicitud</h5>
                <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="idSolicitudCambio" id="cancelar-id">
                <p>¿Seguro que quieres cancelar esta solicitud?</p>
                <p class="text-muted small" id="cancelar-detalle"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">No, volver</button>
                <button class="btn btn-danger" type="submit">Sí, cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
    const BASE_URL = "<?= BASE_URL ?>";

    function openCancelarSolicitud(id, nombre) {
        document.getElementById('cancelar-id').value = id;
        document.getElementById('cancelar-detalle').textContent =
            nombre ? ('Solicitud sobre: ' + nombre) : '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCancelarSolicitud')).show();
    }
</script>
<script src="<?= BASE_URL ?>/View/contenido/js/consultor.solicitudCambios.js?v=<?= time() ?>"></script>