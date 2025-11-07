<?php
require_once __DIR__ . '/../../config/ui.php';
require __DIR__ . '/../layout/headAdmin.php';
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

<div class="adm-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h5 mb-0">Respaldo y restauración de la base de datos</h2>
    </div>

    <div class="row g-3">
        <!-- Columna izquierda: 2 tarjetas cuadradas -->
        <div class="col-lg-4 d-flex flex-column gap-3">
            <!-- Respaldo -->
            <div class="adm-card backup-left-card">
                <div class="adm-card-body d-flex gap-3">
                    <div class="d-flex align-items-start justify-content-center" style="min-width:64px;">
                        <div class="d-inline-flex rounded-4 bg-light justify-content-center align-items-center"
                            style="width:64px;height:64px;">
                            <i class="bi bi-hdd-stack fs-4 text-primary"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="h6 mb-1">Respaldo</h3>
                        <p class="mb-2 small text-muted">
                            Realiza una copia de seguridad completa de la base de datos en formato SQL.
                        </p>
                        <button type="button" class="btn btn-primary btn-sm px-3" onclick="openModalRespaldo()">
                            <i class="bi bi-cloud-arrow-down me-1"></i> Crear respaldo
                        </button>
                    </div>
                </div>
            </div>

            <!-- Restauración -->
            <div class="adm-card backup-left-card">
                <div class="adm-card-body d-flex gap-3">
                    <div class="d-flex align-items-start justify-content-center" style="min-width:64px;">
                        <div class="d-inline-flex rounded-4"
                            style="background:#fff6db;width:64px;height:64px;justify-content:center;align-items:center;">
                            <i class="bi bi-arrow-counterclockwise fs-4 text-warning"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="h6 mb-1">Restauración</h3>
                        <p class="mb-2 small text-muted">
                            Permite devolver la base de datos al estado registrado en un respaldo anterior.
                            Selecciona un archivo del historial para iniciar el proceso.
                        </p>
                        <span class="badge bg-light text-danger border-0 small">
                            Sobrescribe los datos actuales
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna derecha: historial -->
        <div class="col-lg-8">
            <div class="adm-card backup-history-card">
                <div class="adm-card-header justify-content-between">
                    <h3>Historial de respaldos</h3>
                    <small class="text-muted">Registros más recientes</small>
                </div>
                <div class="adm-card-body backup-history-body">
                    <?php if (empty($respaldos)): ?>
                        <div class="empty">
                            <i class="bi bi-inbox fs-5"></i>
                            <div>No hay respaldos aún. Usa el botón “Crear respaldo”.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($respaldos as $r): ?>
                            <div class="d-flex align-items-center justify-content-between gap-2"
                                style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:.6rem .65rem;">
                                <div style="min-width:0;">
                                    <div class="fw-semibold small text-truncate" style="max-width:380px;">
                                        <?= htmlspecialchars($r['archivo']) ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?= date('d M Y H:i', strtotime($r['creado_at'])) ?>
                                        · <?= !empty($r['tamano_bytes']) ? round($r['tamano_bytes'] / 1024, 1) . ' KB' : '—' ?>
                                        <?php if (!empty($r['realizadoPor_nombre'])): ?>
                                            · <?= htmlspecialchars($r['realizadoPor_nombre']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="d-flex gap-1 flex-wrap">
                                    <a href="<?= BASE_URL ?>/config/app.php?accion=admin.respaldo.descargar&id=<?= (int)$r['idRespaldoDB'] ?>"
                                        class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-download me-1"></i> Descargar
                                    </a>
                                    <button type="button"
                                        class="btn btn-outline-warning btn-sm"
                                        onclick="openModalRestaurar(<?= (int)$r['idRespaldoDB'] ?>,'<?= htmlspecialchars($r['archivo'], ENT_QUOTES) ?>')">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restaurar
                                    </button>
                                    <button type="button"
                                        class="btn btn-outline-danger btn-sm"
                                        onclick="openModalEliminar(<?= (int)$r['idRespaldoDB'] ?>,'<?= htmlspecialchars($r['archivo'], ENT_QUOTES) ?>')">
                                        <i class="bi bi-trash me-1"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: nuevo respaldo -->
<div class="modal fade" id="modalRespaldo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="post" action="<?= BASE_URL ?>/config/app.php?accion=admin.respaldo.ejecutar">
            <div class="modal-header">
                <h5 class="modal-title">Crear respaldo</h5>
                <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre del respaldo (opcional)</label>
                    <input type="text" name="nombre_respaldo" class="form-control"
                        placeholder="ej. previo_actualizacion_mayo">
                    <div class="form-text">Si lo dejas vacío el sistema creará uno con la fecha.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-cloud-arrow-down me-1"></i> Crear respaldo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: restaurar -->
<div class="modal fade" id="modalRestaurar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="post" action="<?= BASE_URL ?>/config/app.php?accion=admin.respaldo.restaurar">
            <div class="modal-header">
                <h5 class="modal-title">Restaurar respaldo</h5>
                <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="idRespaldoDB" id="restaurar-id">
                <div class="alert alert-warning small">
                    Esta acción sobrescribirá la base de datos actual con la del respaldo seleccionado.
                    ¿Seguro que quieres continuar?
                </div>
                <p class="mb-0 small" id="restaurar-nombre"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-warning" type="submit">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Sí, restaurar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="post" action="<?= BASE_URL ?>/config/app.php?accion=admin.respaldo.eliminar">
            <div class="modal-header">
                <h5 class="modal-title">Eliminar respaldo</h5>
                <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="idRespaldoDB" id="eliminar-id">
                <p class="small mb-2">¿Seguro que deseas eliminar este respaldo del sistema?</p>
                <p class="text-muted small" id="eliminar-nombre"></p>
                <div class="alert alert-light border small mb-0">
                    Se borrará el archivo y el registro del historial.
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-danger" type="submit">
                    <i class="bi bi-trash me-1"></i> Eliminar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModalRespaldo() {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRespaldo')).show();
    }

    function openModalRestaurar(id, nombre) {
        document.getElementById('restaurar-id').value = id;
        document.getElementById('restaurar-nombre').textContent = 'Respaldo: ' + (nombre || '');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRestaurar')).show();
    }

    function openModalEliminar(id, nombre) {
        document.getElementById('eliminar-id').value = id;
        document.getElementById('eliminar-nombre').textContent = 'Respaldo: ' + (nombre || '');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEliminar')).show();
    }
</script>