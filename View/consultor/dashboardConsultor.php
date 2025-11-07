<?php
$title  = 'Dashboard Consultor';
$active = 'home';
require __DIR__ . '/../layout/headConsultor.php';

$usuario   = $_SESSION['usuario'] ?? [];
// intenta varias claves
$usuarioId = (int)($usuario['idUsuario'] ?? $usuario['id'] ?? $usuario['user_id'] ?? 0);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../Model/BD.php';

$kpiContactos = 0;
$kpiFavoritos = 0;
$kpiBusquedas = 0;
$favoritos = [];
$misSolicitudes = [];
$kpiSolicitudes = 0;

// 1) Contactos totales
try {
    $pdo = BD::pdo();
    $pdo->exec("SET NAMES utf8mb4");
    $kpiContactos = (int)$pdo->query("SELECT COUNT(*) FROM propietario")->fetchColumn();
} catch (Throwable $e) {
    error_log('[dashboard consultor] contactos: ' . $e->getMessage());
}

// 2) Favoritos del usuario
try {
    if (!isset($pdo)) {
        $pdo = BD::pdo();
        $pdo->exec("SET NAMES utf8mb4");
    }
    $sqlFav = "SELECT 
            fp.idFavoritoPropietario,
            p.idPropietario,
            CONCAT(p.nombre,' ',p.apellidoP,' ',p.apellidoM) AS nombreCompleto,
            a.nombre AS area,
            p.email AS correo,
            e.numero AS extension
        FROM favoritoPropietario fp
        INNER JOIN propietario p      ON p.idPropietario = fp.propietarioId
        LEFT JOIN puesto pu           ON pu.idPuesto = p.puestoId
        LEFT JOIN area a              ON a.idArea = pu.areaId
        LEFT JOIN extension e         ON e.idExtension = p.extensionId
        WHERE fp.usuarioId = :uid
        ORDER BY fp.creado_at DESC
        LIMIT 30";
    $st = $pdo->prepare($sqlFav);
    $st->execute([':uid' => $usuarioId]);
    $favoritos    = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $kpiFavoritos = count($favoritos);
} catch (Throwable $e) {
    error_log('[dashboard consultor] favoritos: ' . $e->getMessage());
}

// 3) Búsquedas de hoy (si la tabla no existe, no queremos que rompa)
try {
    if (!isset($pdo)) {
        $pdo = BD::pdo();
        $pdo->exec("SET NAMES utf8mb4");
    }
    $st2 = $pdo->prepare("SELECT COUNT(*) 
        FROM consultaExtension 
        WHERE DATE(creada_at) = CURDATE()
        AND (usuarioId = :uid OR :uid = 0)");
    $st2->execute([':uid' => $usuarioId]);
    $kpiBusquedas = (int)$st2->fetchColumn();
} catch (Throwable $e) {
    error_log('[dashboard consultor] consultas: ' . $e->getMessage());
    $kpiBusquedas = 0;
}

// 4)MIS SOLICITUDES DE CAMBIO
try {
    if (!isset($pdo)) {
        $pdo = BD::pdo();
        $pdo->exec("SET NAMES utf8mb4");
    }
    $stmt = $pdo->prepare("SELECT 
            sc.idSolicitudCambio,
            sc.campo,
            sc.valor_nuevo,
            sc.estado,
            sc.creada_at,
            sc.comentario,
            p.nombre    AS propNombre,
            p.apellidoP AS propAp,
            p.apellidoM AS propAm
        FROM solicitudCambio sc
        INNER JOIN propietario p ON p.idPropietario = sc.propietarioId
        WHERE sc.usuarioSolicitanteId = :uid
        ORDER BY sc.creada_at DESC
        LIMIT 50");
    $stmt->execute([':uid' => $usuarioId]);
    $misSolicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $kpiSolicitudes = count($misSolicitudes);
} catch (Throwable $e) {
    error_log('[dashboard consultor] solicitudes cambio: ' . $e->getMessage());
    $misSolicitudes = [];
    $kpiSolicitudes = 0;
}
?>
<script src="https://kit.fontawesome.com/35c8a07d23.js" crossorigin="anonymous"></script>

<div class="row g-3">
    <!-- KPI 1 -->
    <div class="col-12 col-md-4">
        <div class="card kpi h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="fs-2 fw-bold"><?= $kpiContactos ?></div>
                    <div class="small text-muted">Contactos Totales</div>
                </div>
                <i class="bi bi-people fs-2 text-success"></i>
            </div>
            <div class="kpi-border" style="height:5px;background: #34d399;"></div>
        </div>
    </div>
    <!-- KPI 2 -->
    <div class="col-12 col-md-4">
        <div class="card kpi h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="fs-2 fw-bold"><?= $kpiFavoritos ?></div>
                    <div class="small text-muted">Favoritos</div>
                </div>
                <i class="bi bi-stars fs-2 text-warning"></i>
            </div>
            <div class="kpi-border" style="height:5px;background: #fbbf24;"></div>
        </div>
    </div>
    <!-- KPI 3 -->
    <div class="col-12 col-md-4">
        <div class="card kpi h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="fs-2 fw-bold"><?= $kpiBusquedas ?></div>
                    <div class="small text-muted">Búsquedas hoy</div>
                </div>
                <i class="bi bi-search-heart fs-2 text-info"></i>
            </div>
            <div class="kpi-border" style="height:5px;background: #93c5fd;"></div>
        </div>
    </div>
</div>

<div class="col-12 mt-3">
    <section class="mtc-grid">
        <!-- COLUMNA PRINCIPAL: solo solicitudes -->
        <section class="mtc-card">
            <h3 style="display:flex;align-items:center;gap:.5rem;">
                <i class="fa-solid fa-arrows-rotate" style="color: #15eaeaff;"></i>
                Mis solicitudes de cambio
                <?php if ($kpiSolicitudes > 0): ?>
                    <span class="badge bg-primary"><?= $kpiSolicitudes ?></span>
                <?php endif; ?>
            </h3>

            <div class="mtc-list-wrap">
                <div class="mtc-list">
                    <?php if (empty($misSolicitudes)): ?>
                        <p class="text-muted small mt-2 mb-0">
                            No has enviado solicitudes de cambio todavía.
                        </p>
                    <?php else: ?>
                        <?php
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
                        foreach ($misSolicitudes as $s):
                            $prop = trim(($s['propNombre'] ?? '') . ' ' . ($s['propAp'] ?? '') . ' ' . ($s['propAm'] ?? ''));
                            $campoRaw = $s['campo'] ?? '';
                            $campoLbl = $mapaCampos[$campoRaw] ?? ucfirst($campoRaw);
                            $valor    = $s['valor_nuevo'] ?? '';
                            $estado   = $s['estado'] ?? 'pendiente';
                            $fecha    = !empty($s['creada_at']) ? date('d/m/Y H:i', strtotime($s['creada_at'])) : '';
                            $badgeClass = match ($estado) {
                                'aprobado'  => 'bg-success',
                                'rechazado' => 'bg-danger',
                                default     => 'bg-warning text-dark'
                            };
                        ?>
                            <article class="mtc-contact" style="border-left:4px solid #8ce8ffff">
                                <div class="mtc-left">
                                    <div class="mtc-avatar" style="background: #c4f5fc;">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <div class="mtc-meta">
                                        <div class="mtc-name">
                                            <?= $prop !== '' ? htmlspecialchars($prop) : 'Propietario #'.$s['idSolicitudCambio'] ?>
                                        </div>
                                        <div class="mtc-sub">
                                            Campo: <strong><?= htmlspecialchars($campoLbl) ?></strong>
                                        </div>
                                        <?php if ($valor !== ''): ?>
                                            <div class="mtc-sub text-truncate" style="max-width:320px;">
                                                Valor solicitado: <?= htmlspecialchars($valor) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="mtc-sub">
                                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($estado) ?></span>
                                            <?php if ($fecha): ?>
                                                <span class="text-muted small ms-1">
                                                    <i class="bi bi-clock"></i> <?= $fecha ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($s['comentario'])): ?>
                                            <div class="mtc-sub small text-muted">
                                                “<?= htmlspecialchars($s['comentario']) ?>”
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- COLUMNA DERECHA: tus favoritos intactos -->
        <aside class="mtc-aside">
            <h4><i class="fa-solid fa-star fa-beat" style="color:#FFD43B;"></i>Mis favoritos</h4>
            <div class="mtc-favlist">
                <?php if (empty($favoritos)): ?>
                    <p class="text-muted small mb-0">Aún no tienes favoritos.</p>
                <?php else: ?>
                    <?php foreach ($favoritos as $fav): ?>
                        <div class="mtc-fav mtc-fav--hover">
                            <div class="mtc-fav-left">
                                <div class="mtc-avatar" style="width:44px;height:44px">
                                    <i class="bi bi-person"></i>
                                </div>
                                <div>
                                    <div class="mtc-name" style="font-weight:700">
                                        <?= htmlspecialchars($fav['nombreCompleto']) ?>
                                    </div>
                                    <div class="mtc-sub">
                                        <?= htmlspecialchars($fav['area'] ?? 'Sin área') ?>
                                    </div>
                                    <div class="mtc-sub">
                                        <strong>
                                            <?= $fav['extension'] ? 'Ext. ' . htmlspecialchars($fav['extension']) : 'Sin extensión' ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                            <div class="mtc-fav-actions">
                                <?php if (!empty($fav['correo'])): ?>
                                    <a class="mtc-chip mtc-chip--ghost" href="mailto:<?= htmlspecialchars($fav['correo']) ?>" title="Correo">
                                        <i class="bi bi-envelope"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($fav['extension'])): ?>
                                    <button class="mtc-chip mtc-chip--success" data-ext="<?= htmlspecialchars($fav['extension']) ?>" title="Copiar ext">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                <?php endif; ?>
                                <form method="post" action="<?= BASE_URL ?>/config/app.php?accion=consultor.favorito.toggle" style="display:inline">
                                    <input type="hidden" name="propietarioId" value="<?= (int)$fav['idPropietario'] ?>">
                                    <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>/config/app.php?accion=home.dashboard">
                                    <button type="submit" class="mtc-chip mtc-chip--warn" title="Quitar de favoritos">
                                        <i class="bi bi-star-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>
    </section>
</div>

<script>
    // copiar extensión desde favoritos
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-ext]');
        if (!b) return;
        const ext = b.getAttribute('data-ext');
        navigator.clipboard.writeText(ext).then(() => {
            const prev = b.innerHTML;
            b.innerHTML = '<i class="bi bi-check2"></i>';
            setTimeout(() => b.innerHTML = prev, 1000);
        });
    });
</script>