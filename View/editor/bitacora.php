<?php
require_once __DIR__ . '/../../config/ui.php';
$active = $active ?? 'bitacora';
$title  = $title  ?? 'Bitácora del sistema';
require __DIR__ . '/../layout/headEditor.php';

/* Utilidad para color de acción */
function badgeClassForAction(?string $accion): string
{
    $a = strtolower((string)$accion);
    if ($a === 'creacion' || $a === 'registro' || $a === 'crear' || $a === 'alta') {
        return 'bg-success-subtle text-success-emphasis border border-success-subtle';
    }
    if ($a === 'actualizacion' || $a === 'actualizar' || $a === 'update') {
        return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
    }
    if ($a === 'eliminacion' || $a === 'eliminar' || $a === 'cierre_sesion' || $a === 'logout') {
        return 'bg-danger-subtle text-danger-emphasis border border-danger-subtle';
    }
    if ($a === 'inicio_sesion_ok' || $a === 'login') {
        return 'bg-primary-subtle text-primary-emphasis border border-primary-subtle';
    }
    return 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle';
}

/* Formatea fecha y hora apiladas */
function renderFechaHora(?string $ts): string
{
    if (!$ts) return '-';
    $t = strtotime($ts);
    if ($t === false) return htmlspecialchars($ts);
    $f = date('d/m/Y', $t);
    $h = date('H:i:s', $t);
    return '<div class="small">' . htmlspecialchars($f) . '<br><span class="text-muted">' . htmlspecialchars($h) . '</span></div>';
}

// Variables que vienen del controlador:
$logs             = $logs             ?? [];
$total_registros  = (int)($total_registros ?? count($logs));
$perPage          = (int)($perPage ?? 10);
$page             = max(1, (int)($page ?? 1));
$q                = trim((string)($q ?? ''));
$total_paginas    = max(1, (int)ceil($total_registros / max(1, $perPage)));
$desde            = ($total_registros === 0) ? 0 : (($page - 1) * $perPage + 1);
$hasta            = min($total_registros, $page * $perPage);
?>

<?php if (!empty($flash_texto) && !empty($flash_nivel)): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080">
        <div class="toast text-bg-<?= htmlspecialchars($flash_nivel) ?> border-0 show" role="alert" data-bs-delay="8000">
            <div class="d-flex">
                <div class="toast-body"><?= htmlspecialchars($flash_texto) ?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    <script>
        (function() {
            const t = document.querySelector('.toast');
            if (t) new bootstrap.Toast(t).show();
        })();
    </script>
<?php endif; ?>

<!-- Título + búsqueda + resumen -->
<div class="mb-3">
    <div class="d-flex align-items-center gap-2">
        <h2 class="h5 mb-0">Bitácora del sistema</h2>
        <span class="ms-auto small text-muted">
            Mostrando <?= (int)$desde ?>–<?= (int)$hasta ?> de <?= (int)$total_registros ?> &nbsp;|&nbsp; Total: <?= (int)$total_registros ?>
        </span>
    </div>

    <form class="mt-2 d-flex align-items-center gap-2" method="get" action="<?= BASE_URL ?>/config/app.php">
        <input type="hidden" name="accion" value="bitacora.listar">
        <div class="input-group" style="max-width: 460px;">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control"
                placeholder="Buscar texto, usuario, email, módulo, acción...">
        </div>
    </form>
</div>

<!-- Tabla -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr class="table-primary">
                    <th style="width:140px;">Acción</th>
                    <th style="width:140px;">Módulo</th>
                    <!--<th style="width:180px;">Tabla / PK</th>-->
                    <th>Descripción</th>
                    <th style="width:220px;">Usuario</th>
                    <th style="width:120px;">Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Sin registros</td>
                    </tr>
                    <?php else: foreach ($logs as $r): ?>
                        <tr>
                            <td>
                                <?php
                                $accion = $r['accion'] ?? '';
                                $cls = badgeClassForAction($accion);
                                ?>
                                <span class="badge rounded-pill <?= $cls ?>">
                                    <?= htmlspecialchars($accion ?: '-') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($r['modulo'] ?? '-') ?></td>
                            <!--<td>
                                <?php /*
                                $tb = $r['tabla_bd'] ?? null;
                                $pk = $r['pk_valor'] ?? null;
                                if ($tb || $pk) {
                                    echo '<div class="small"><span class="text-muted">Tabla:</span> ' . htmlspecialchars($tb ?: '-') . '</div>';
                                    echo '<div class="small"><span class="text-muted">PK:</span> ' . htmlspecialchars($pk ?: '-') . '</div>';
                                } else {
                                    echo '-';
                                }
                                */?>
                            </td>-->
                            <td><?= htmlspecialchars($r['descripcion'] ?? '-') ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($r['usuarioNombre'] ?? '-') ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($r['usuarioEmail'] ?? '-') ?></div>
                            </td>
                            <td><?= renderFechaHora($r['creada_at'] ?? null) ?></td>
                        </tr>
                <?php endforeach;
                endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pie con paginación estilo AWS -->
    <?php if ($total_registros > 0): ?>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <!-- Items per page -->
            <form method="get" class="d-flex align-items-center gap-2 m-0">
                <input type="hidden" name="accion" value="bitacora.listar">
                <?php if ($q): ?><input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>"><?php endif; ?>
                <label class="me-1 small text-muted">Elementos por página</label>
                <select name="perPage" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($opt == $perPage ? 'selected' : '') ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <!-- Navegación -->
            <nav aria-label="Paginación">
                <ul class="pagination pagination-sm mb-0">
                    <!-- First -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= BASE_URL ?>/config/app.php?accion=bitacora.listar&page=1<?= $q ? '&q=' . urlencode($q) : '' ?>&perPage=<?= $perPage ?>">⏮</a>
                    </li>
                    <!-- Prev -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= BASE_URL ?>/config/app.php?accion=bitacora.listar&page=<?= max(1, $page - 1) ?><?= $q ? '&q=' . urlencode($q) : '' ?>&perPage=<?= $perPage ?>">◀</a>
                    </li>

                    <!-- Numeradas (rango compacto) -->
                    <?php
                    // Rango tipo AWS: 1 … (page-1,page,page+1) … N
                    $start = max(1, $page - 1);
                    $end   = min($total_paginas, $page + 1);
                    if ($start > 1) {
                        echo '<li class="page-item"><a class="page-link" href="' . BASE_URL . '/config/app.php?accion=bitacora.listar&page=1' . ($q ? '&q=' . urlencode($q) : '') . '&perPage=' . $perPage . '">1</a></li>';
                        if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                    }
                    for ($i = $start; $i <= $end; $i++) {
                        $active = ($i === $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . BASE_URL . '/config/app.php?accion=bitacora.listar&page=' . $i . ($q ? '&q=' . urlencode($q) : '') . '&perPage=' . $perPage . '">' . $i . '</a></li>';
                    }
                    if ($end < $total_paginas) {
                        if ($end < $total_paginas - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                        echo '<li class="page-item"><a class="page-link" href="' . BASE_URL . '/config/app.php?accion=bitacora.listar&page=' . $total_paginas . ($q ? '&q=' . urlencode($q) : '') . '&perPage=' . $perPage . '">' . $total_paginas . '</a></li>';
                    }
                    ?>

                    <!-- Next -->
                    <li class="page-item <?= $page >= $total_paginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= BASE_URL ?>/config/app.php?accion=bitacora.listar&page=<?= min($total_paginas, $page + 1) ?><?= $q ? '&q=' . urlencode($q) : '' ?>&perPage=<?= $perPage ?>">▶</a>
                    </li>
                    <!-- Last -->
                    <li class="page-item <?= $page >= $total_paginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= BASE_URL ?>/config/app.php?accion=bitacora.listar&page=<?= $total_paginas ?><?= $q ? '&q=' . urlencode($q) : '' ?>&perPage=<?= $perPage ?>">⏭</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>