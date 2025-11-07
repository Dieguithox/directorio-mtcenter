<?php
$active = 'cambios';
$title = 'Solicitudes de cambio';
require_once __DIR__ . '/../../config/ui.php';
require __DIR__ . '/../layout/headAdmin.php';

$estadoActual = $_GET['estado'] ?? '';

// mapa para mostrar nombres bonitos
$mapaCampos = [
    'nombre'        => 'Nombre',
    'apellidoP'     => 'Apellido paterno',
    'apellidoM'     => 'Apellido materno',
    'email'         => 'Correo electrónico',
    'puestoId'      => 'Puesto',
    'puesto'        => 'Puesto',
    'extensionId'   => 'Extensión',
    'extension'     => 'Extensión',
];
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
    <h2 class="h5 mb-0">Solicitudes de cambio</h2>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= BASE_URL ?>/config/app.php?accion=admin.solicitudes.cambio"
            class="btn btn-sm <?= $estadoActual === '' ? 'btn-primary' : 'btn-outline-secondary' ?>">Todas</a>
        <a href="<?= BASE_URL ?>/config/app.php?accion=admin.solicitudes.cambio&estado=pendiente"
            class="btn btn-sm <?= $estadoActual === 'pendiente' ? 'btn-warning text-dark' : 'btn-outline-secondary' ?>">Pendientes</a>
        <a href="<?= BASE_URL ?>/config/app.php?accion=admin.solicitudes.cambio&estado=aprobado"
            class="btn btn-sm <?= $estadoActual === 'aprobado' ? 'btn-success' : 'btn-outline-secondary' ?>">Aprobadas</a>
        <a href="<?= BASE_URL ?>/config/app.php?accion=admin.solicitudes.cambio&estado=rechazado"
            class="btn btn-sm <?= $estadoActual === 'rechazado' ? 'btn-danger' : 'btn-outline-secondary' ?>">Rechazadas</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tablaSolicitudes">
            <thead class="table-light fw-semibold">
                <tr>
                    <th>Propietario</th>
                    <th>Campo</th>
                    <th>Valor solicitado</th>
                    <th>Solicitante</th>
                    <th>Fecha</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end" style="width:240px">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($solicitudes)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No hay solicitudes.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($solicitudes as $s): ?>
                        <?php
                        $propNombre = trim(($s['propietarioNombre'] ?? '') . ' ' . ($s['propietarioApellidoP'] ?? '') . ' ' . ($s['propietarioApellidoM'] ?? ''));
                        $solNombre  = $s['solicitanteNombre'] ?? ($s['solicitanteEmail'] ?? 'N/D');
                        $estado     = $s['estado'] ?? 'pendiente';

                        $campoRaw   = $s['campo'] ?? '';
                        $campoLabel = $mapaCampos[$campoRaw] ?? ucfirst($campoRaw);

                        $badgeClass = match ($estado) {
                            'aprobado'  => 'bg-success',
                            'rechazado' => 'bg-danger',
                            default     => 'bg-warning text-dark'
                        };
                        ?>
                        <tr
                            data-id="<?= (int)$s['idSolicitudCambio'] ?>"
                            data-propietario="<?= htmlspecialchars($propNombre !== '' ? $propNombre : 'Propietario #' . (int)$s['propietarioId']) ?>"
                            data-campo="<?= htmlspecialchars($campoLabel) ?>"
                            data-campo-raw="<?= htmlspecialchars($campoRaw) ?>"
                            data-valor-anterior="<?= htmlspecialchars($s['valor_anterior'] ?? '-') ?>"
                            data-valor-nuevo="<?= htmlspecialchars($s['valor_nuevo'] ?? '-') ?>"
                            data-comentario="<?= htmlspecialchars($s['comentario'] ?? '-') ?>"
                            data-motivo="<?= htmlspecialchars($s['motivo_revision'] ?? '-') ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary text-white d-inline-flex justify-content-center align-items-center"
                                        style="width:34px;height:34px;">
                                        <?= strtoupper(substr($propNombre !== '' ? $propNombre : 'P', 0, 1)) ?>
                                    </div>
                                    <div class="ms-2">
                                        <div class="fw-semibold">
                                            <?= $propNombre !== '' ? htmlspecialchars($propNombre) : 'Propietario #' . (int)$s['propietarioId'] ?>
                                        </div>
                                        <?php if (!empty($s['propietarioEmail'])): ?>
                                            <div class="text-muted small"><?= htmlspecialchars($s['propietarioEmail']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($campoLabel) ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width:180px"
                                    title="<?= htmlspecialchars($s['valor_nuevo'] ?? '') ?>">
                                    <?= htmlspecialchars($s['valor_nuevo'] ?? '') ?>
                                </div>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width:140px"><?= htmlspecialchars($solNombre) ?></div>
                            </td>
                            <td>
                                <span class="small text-muted">
                                    <?= !empty($s['creada_at']) ? date('d/m/Y H:i', strtotime($s['creada_at'])) : '' ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $badgeClass ?>"><?= ucfirst($estado) ?></span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-ver">
                                        <i class="bi bi-eye me-1"></i> Ver
                                    </button>
                                    <?php if ($estado === 'pendiente'): ?>
                                        <button type="button" class="btn btn-outline-success btn-aprobar">
                                            <i class="bi bi-check2 me-1"></i> Aprobar
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-rechazar">
                                            <i class="bi bi-x-lg me-1"></i> Rechazar
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-secondary" disabled>
                                            <i class="bi bi-check2-all me-1"></i> Atendida
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL DETALLE -->
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de solicitud</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white d-inline-flex justify-content-center align-items-center me-3"
                                    style="width:44px;height:44px;" id="detAvatar">P</div>
                                <div>
                                    <h6 class="mb-0" id="detPropietario">Propietario</h6>
                                    <small class="text-muted" id="detCampo">Campo</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent fw-semibold">
                                Cambio solicitado
                            </div>
                            <div class="card-body">
                                <p class="mb-1 text-muted small">Valor anterior</p>
                                <p class="fw-semibold" id="detValorAnterior">-</p>
                                <p class="mb-1 text-muted small">Valor nuevo</p>
                                <p class="fw-semibold text-primary" id="detValorNuevo">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent fw-semibold">
                                Comentario del solicitante
                            </div>
                            <div class="card-body">
                                <p id="detComentario" class="mb-0 text-wrap">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12" id="detMotivoWrap" style="display:none;">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent fw-semibold">
                                Motivo de revisión
                            </div>
                            <div class="card-body">
                                <p id="detMotivo" class="mb-0">-</p>
                            </div>
                        </div>
                    </div>
                </div> <!-- row -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL APROBAR -->
<div class="modal fade" id="modalAprobar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content needs-validation" novalidate
            method="post"
            action="<?= BASE_URL ?>/config/app.php?accion=admin.solicitud.aprobar"
            id="formAprobar">
            <div class="modal-header">
                <h5 class="modal-title">Aprobar solicitud</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="idSolicitudCambio" id="apId">
                <div class="alert alert-info small mb-3">
                    Estás aprobando el cambio del campo <strong id="apCampo"></strong>.
                </div>
                <div class="mb-2">
                    <p class="mb-1 text-muted small">Valor anterior</p>
                    <p class="fw-semibold" id="apValorAnterior">-</p>
                    <p class="mb-1 text-muted small">Valor nuevo</p>
                    <p class="fw-semibold text-success" id="apValorNuevo">-</p>
                </div>
                <div class="mb-3">
                    <label class="form-label">Motivo / nota de aprobación</label>
                    <textarea name="motivo_revision" id="apMotivo" rows="3" class="form-control" required
                        placeholder="Ej. Se validó con el área correspondiente."></textarea>
                    <div class="invalid-feedback">Este campo es obligatorio.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-success" type="submit">Aprobar solicitud</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL RECHAZAR -->
<div class="modal fade" id="modalRechazar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content needs-validation" novalidate
            method="post"
            action="<?= BASE_URL ?>/config/app.php?accion=admin.solicitud.rechazar"
            id="formRechazar">
            <div class="modal-header">
                <h5 class="modal-title">Rechazar solicitud</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="idSolicitudCambio" id="reId">
                <div class="alert alert-warning small mb-3">
                    Estás rechazando el cambio del campo <strong id="reCampo"></strong>.
                </div>
                <div class="mb-2">
                    <p class="mb-1 text-muted small">Valor anterior</p>
                    <p class="fw-semibold" id="reValorAnterior">-</p>
                    <p class="mb-1 text-muted small">Valor solicitado</p>
                    <p class="fw-semibold text-danger" id="reValorNuevo">-</p>
                </div>
                <div class="mb-3">
                    <label class="form-label">Motivo del rechazo</label>
                    <textarea name="motivo_revision" id="reMotivo" rows="3" class="form-control" required
                        placeholder="Ej. No coincide con los datos del sistema."></textarea>
                    <div class="invalid-feedback">Debes escribir el motivo del rechazo.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-danger" type="submit">Rechazar solicitud</button>
            </div>
        </form>
    </div>
</div>

<script>
    const BASE_URL = "<?= BASE_URL ?>";
</script>
<script src="<?= BASE_URL ?>/View/contenido/js/admin.solicitudes.js?v=<?= time() ?>"></script>