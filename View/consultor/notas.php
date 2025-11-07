<?php
$active = 'notas';
$title = 'Notas';
require_once __DIR__ . '/../../config/ui.php';
require __DIR__ . '/../layout/headConsultor.php';

$uid  = (int)($_SESSION['usuario']['id'] ?? 0);
$rol  = $_SESSION['usuario']['tipoUsuario'] ?? 'consultor';
$esAdmin = ($rol === 'consultor');

/* Separar mis notas vs otras */
$misNotas = [];
$otrasNotas = [];
foreach (($notas ?? []) as $n) {
    $autorId = (int)($n['autorId'] ?? $n['usuarioId'] ?? 0);
    if ($autorId === $uid) $misNotas[] = $n; else $otrasNotas[] = $n;
}

/* Helper avatar */
function avatarInicial(string $nombre): string {
    $i = strtoupper(mb_substr(trim($nombre ?: 'U'), 0, 1));
    return '<div class="note-avatar">'.$i.'</div>';
}
?>

<?php if (!empty($flash_texto) && !empty($flash_nivel)): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div class="toast text-bg-<?= htmlspecialchars($flash_nivel) ?> border-0 show">
        <div class="d-flex">
        <div class="toast-body"><?= htmlspecialchars($flash_texto) ?></div>
        <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<script> (function(){const el=document.querySelector('.toast'); if(el) new bootstrap.Toast(el).show();})(); </script>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Notas de contacto</h2>
    <div class="header-actions">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNota" onclick="openCreate()">
        <i class="bi bi-plus-lg me-1"></i> Nueva nota
        </button>
    </div>
</div>

<form class="mb-3 search-wrap" method="get" action="<?= BASE_URL ?>/config/app.php">
    <input type="hidden" name="accion" value="notas.listar">
    <div class="input-group">
        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
        <input type="search" name="q" class="form-control" placeholder="Buscar por contacto, texto o correo"
            value="<?= htmlspecialchars(trim($_GET['q'] ?? '')) ?>">
    </div>
</form>

<!-- ===== MIS NOTAS ===== -->
<div class="notes-wrap mb-4">
    <div class="section-subtle">
        <div class="section-title">Mis notas</div>
        <?php if (empty($misNotas)): ?>
        <div class="empty mt-2"><i class="bi bi-sticky"></i> No tienes notas aún.</div>
        <?php else: foreach ($misNotas as $idx=>$n):
        $propNombre = $n['propNombre'] ?? 'Contacto';
        $propEmail  = $n['propEmail']  ?? '';
        $texto      = $n['texto']      ?? '';
        $puesto     = $n['propPuesto'] ?? null;
        $ext        = $n['propExtension'] ?? null;
        $creada     = date('d/m/Y H:i', strtotime($n['creada_at'] ?? 'now'));
        ?>
        <div class="note-card mb-3">
            <div class="note-row">
            <?= avatarInicial($propNombre) ?>
            <div class="note-main">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="fw-bold"><?= htmlspecialchars($propNombre) ?></div>
                <div class="note-badges">
                    <?php if ($puesto): ?><span class="badge-chip"><?= htmlspecialchars($puesto) ?></span><?php endif; ?>
                    <?php if ($ext): ?><span class="badge-chip">Ext. <?= htmlspecialchars($ext) ?></span><?php endif; ?>
                    <span class="badge-chip">Autor: <?= htmlspecialchars($_SESSION['usuario']['nombre'] ?? 'Tú') ?></span>
                </div>
                <div class="ms-auto note-meta">
                    <span class="pill-time"><?= htmlspecialchars($creada) ?></span>
                </div>
                </div>
                <div class="note-meta mt-1">
                <?php if ($propEmail): ?><span><?= htmlspecialchars($propEmail) ?></span>·<?php endif; ?>
                <span>visible para todos</span>
                </div>
                <div class="note-text"><?= nl2br(htmlspecialchars($texto)) ?></div>
                <div class="d-flex gap-2 justify-content-end mt-2 note-actions">
                <button class="btn btn-outline-primary"
                    onclick='openEdit(<?= (int)$n["idNota"] ?>, <?= (int)($n["idPropietario"] ?? $n["propietarioId"] ?? 0) ?>, <?= json_encode($texto) ?>)'>
                    <i class="bi bi-pencil-square me-1"></i> Editar
                </button>
                <button class="btn btn-outline-danger"
                    onclick='openDelete(<?= (int)$n["idNota"] ?>, <?= json_encode($propNombre) ?>)'>
                    <i class="bi bi-trash me-1"></i> Eliminar
                </button>
                </div>
            </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- ===== NOTAS DE OTROS ===== -->
<div class="notes-wrap mb-4">
    <div class="section-subtle">
        <div class="section-title">Notas de otros</div>
        <?php if (empty($otrasNotas)): ?>
        <div class="empty"><i class="bi bi-people"></i> No hay notas de otros usuarios.</div>
        <?php else: foreach ($otrasNotas as $n):
        $propNombre = $n['propNombre'] ?? 'Contacto';
        $propEmail  = $n['propEmail']  ?? '';
        $texto      = $n['texto']      ?? '';
        $autorNom   = $n['autorNombre'] ?? $n['autorEmail'] ?? 'Desconocido';
        $creada     = date('d/m/Y H:i', strtotime($n['creada_at'] ?? 'now'));
        ?>
        <div class="note-card mb-3">
            <div class="note-row">
            <?= avatarInicial($propNombre) ?>
            <div class="note-main">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="fw-bold"><?= htmlspecialchars($propNombre) ?></div>
                <span class="badge-chip">Autor: <?= htmlspecialchars($autorNom) ?></span>
                <div class="ms-auto note-meta">
                    <span class="pill-time"><?= htmlspecialchars($creada) ?></span>
                </div>
                </div>
                <div class="note-meta mt-1">
                <?php if ($propEmail): ?><span><?= htmlspecialchars($propEmail) ?></span>·<?php endif; ?>
                <span>solo lectura</span>
                </div>
                <div class="note-text"><?= nl2br(htmlspecialchars($texto)) ?></div>
                <div class="d-flex gap-2 justify-content-end mt-2 note-actions">
                <button class="btn btn-outline-secondary" disabled>
                    <i class="bi bi-pencil-square me-1"></i> Editar
                </button>
                <button class="btn btn-outline-secondary" disabled>
                    <i class="bi bi-trash me-1"></i> Eliminar
                </button>
                </div>
            </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- ===== MODAL CREAR/EDITAR ===== -->
<div class="modal fade" id="modalNota" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content needs-validation" novalidate method="post" id="formNota"
            action="<?= BASE_URL ?>/config/app.php?accion=notas.crear">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Nueva nota</h5>
            <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="idNota" id="idNota">
            <div class="mb-3">
            <label class="form-label">Contacto</label>
            <select class="form-select" name="propietarioId" id="propietarioId" required>
                <option value="">Seleccione</option>
                <?php foreach (($propietarios ?? []) as $p): ?>
                <option value="<?= (int)$p['idPropietario'] ?>">
                    <?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['email'] ?? '') ?>)
                </option>
                <?php endforeach; ?>
            </select>
            </div>
            <div class="mb-2">
            <label class="form-label">Nota</label>
            <textarea class="form-control" name="texto" id="texto" rows="5" maxlength="2000" required
                        placeholder="Escribe la nota para uso interno..."></textarea>
            <div class="form-text"><span id="cnt">0</span>/2000</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-primary" type="submit" id="btnGuardar">Guardar</button>
        </div>
        </form>
    </div>
</div>

<!-- ===== MODAL ELIMINAR ===== -->
<div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= BASE_URL ?>/config/app.php?accion=notas.eliminar">
        <div class="modal-header">
            <h5 class="modal-title">Eliminar nota</h5>
            <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="idNota" id="delId">
            <p>¿Eliminar la nota de <strong id="delNombre"></strong>?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-danger" type="submit">Sí, eliminar</button>
        </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.needs-validation').forEach(f=>{
    f.addEventListener('submit', e=>{
        if (!f.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        f.classList.add('was-validated');
    });
});
const ta = document.getElementById('texto'), cnt = document.getElementById('cnt');
if (ta && cnt) ta.addEventListener('input', ()=> cnt.textContent = ta.value.length);

function openCreate(){
    const f = document.getElementById('formNota');
    f.action = "<?= BASE_URL ?>/config/app.php?accion=notas.crear";
    document.getElementById('modalTitle').textContent = "Nueva nota";
    document.getElementById('idNota').value = "";
    document.getElementById('propietarioId').value = "";
    document.getElementById('texto').value = "";
    cnt.textContent = "0";
}
function openEdit(idNota, propietarioId, texto){
    const f = document.getElementById('formNota');
    f.action = "<?= BASE_URL ?>/config/app.php?accion=notas.actualizar";
    document.getElementById('modalTitle').textContent = "Editar nota";
    document.getElementById('idNota').value = idNota;
    document.getElementById('propietarioId').value = propietarioId;
    document.getElementById('texto').value = texto;
    cnt.textContent = (texto ?? '').length;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalNota')).show();
}
function openDelete(id, nombre){
    document.getElementById('delId').value = id;
    document.getElementById('delNombre').textContent = nombre;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDelete')).show();
}
</script>