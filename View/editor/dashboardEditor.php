<?php
$title  = 'Dashboard · Consultor';
$active = 'home';
require __DIR__ . '/../layout/headEditor.php';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../Model/BD.php';

try {
    $pdo = BD::pdo();
    $pdo->exec("SET NAMES utf8mb4");

    // =========================
    // KPIs base
    // =========================
    $usuarios    = (int)$pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
    $contactos   = (int)$pdo->query("SELECT COUNT(*) FROM propietario")->fetchColumn();
    $extensiones = (int)$pdo->query("SELECT COUNT(*) FROM extension")->fetchColumn();

    // =========================
    // Solicitudes de cambio (usando TU tabla y columnas)
    // =========================
    $solicitudes = 0;
    $queue       = [];

    try {
        // total para el KPI
        $solicitudes = (int)$pdo->query("SELECT COUNT(*) FROM solicitudCambio")->fetchColumn();

        // últimas 6 solicitudes
        $stmtSol = $pdo->prepare("
            SELECT
                sc.idSolicitudCambio,
                sc.campo,
                sc.valor_nuevo,
                sc.estado,
                sc.creada_at,
                p.nombre    AS propNombre,
                p.apellidoP AS propAp,
                p.apellidoM AS propAm,
                u.nombre    AS solicitanteNombre
            FROM solicitudCambio sc
            LEFT JOIN propietario p ON p.idPropietario = sc.propietarioId
            LEFT JOIN usuario u     ON u.idUsuario     = sc.usuarioSolicitanteId
            ORDER BY sc.creada_at DESC
            LIMIT 6
        ");
        $stmtSol->execute();
        $rowsSol = $stmtSol->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rowsSol as $r) {
            $nombreProp = trim(
                ($r['propNombre'] ?? '') . ' ' .
                ($r['propAp'] ?? '') . ' ' .
                ($r['propAm'] ?? '')
            );
            if ($nombreProp === '') {
                $nombreProp = 'Propietario #' . (int)($r['idSolicitudCambio'] ?? 0);
            }

            $queue[] = [
                'id'       => (int)$r['idSolicitudCambio'],
                'prop'     => $nombreProp,
                'campo'    => $r['campo'] ?? '',
                'nuevo'    => $r['valor_nuevo'] ?? '',
                'estado'   => $r['estado'] ?? 'pendiente',
                'solicita' => $r['solicitanteNombre'] ?? 'Usuario',
                'ts'       => $r['creada_at'] ?? ''
            ];
        }
    } catch (Throwable $eInner) {
        // si falla porque la tabla todavía no está, no rompemos el dashboard
        // error_log('[DashboardEditor][solicitudCambio] ' . $eInner->getMessage());
        $solicitudes = 0;
        $queue = [];
    }

    // =========================
    // Empleados por área (lo tuyo)
    // =========================
    $sqlAreas = "
        SELECT a.nombre AS name, COUNT(p.idPropietario) AS value
        FROM area a
        LEFT JOIN puesto pu      ON pu.areaId = a.idArea
        LEFT JOIN propietario p  ON p.puestoId = pu.idPuesto
        GROUP BY a.idArea, a.nombre
        ORDER BY value DESC, a.nombre ASC
    ";
    $areas = $pdo->query($sqlAreas)->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $palette = ['#fba1f2ff', '#ecfd8aff', '#d9b4fdff', '#a2ffa0ff', '#57beff', '#ffc380', '#9e41fb', '#16d4ae'];
    $i = 0;
    $estados = [];
    foreach ($areas as $r) {
        $estados[] = [
            'name'  => $r['name'],
            'value' => (int)$r['value'],
            'color' => $palette[$i % count($palette)]
        ];
        $i++;
    }

    // =========================
    // Actividad reciente
    // =========================
    $stmt = $pdo->prepare("
        SELECT descripcion AS text, creada_at
        FROM bitacorasistema
        ORDER BY creada_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $activity = array_map(function ($r) {
        return [
            'text' => ($r['text'] ?? 'Actividad'),
            'ts'   => strtotime($r['creada_at'] ?? 'now') * 1000
        ];
    }, $rows);

    // =========================
    // Resumen para el JS
    // =========================
    $editorSummary = [
        'publicados'  => $usuarios,
        'revision'    => $contactos,
        'borradores'  => $extensiones,
        'programados' => $solicitudes, // ← KPI de solicitudes
        'estados'     => $estados,
    ];
} catch (Throwable $e) {
    error_log("[DashboardEditor] " . $e->getMessage());
    $editorSummary = [
        'publicados'  => 0,
        'revision'    => 0,
        'borradores'  => 0,
        'programados' => 0,
        'estados'     => []
    ];
    $activity = [];
    $queue = [];
}
?>
<!-- LinkFont Awesome -->
<script src="https://kit.fontawesome.com/35c8a07d23.js" crossorigin="anonymous"></script>

<div class="ed-wrapper">
    <section class="ed-kpis">
        <article class="ed-kpi is-blue" id="kpi-publicados">
            <div class="ed-kpi__icon"><i class="fa-solid fa-user-plus" style="color: #57beff;"></i></div>
            <div>
                <div class="ed-kpi__title">Usuarios resgistrados</div>
                <div class="ed-kpi__value">0</div>
            </div>
        </article>
        <article class="ed-kpi is-amber" id="kpi-revision">
            <div class="ed-kpi__icon"><i class="fa-solid fa-users" style="color: #ffc380;"></i></div>
            <div>
                <div class="ed-kpi__title">Contactos totales</div>
                <div class="ed-kpi__value">0</div>
            </div>
        </article>
        <article class="ed-kpi is-violet" id="kpi-borradores">
            <div class="ed-kpi__icon"><i class="fa-solid fa-phone" style="color: #9e41fb;"></i></div>
            <div>
                <div class="ed-kpi__title">Extensiones</div>
                <div class="ed-kpi__value">0</div>
            </div>
        </article>
        <article class="ed-kpi is-green" id="kpi-programados">
            <div class="ed-kpi__icon"><i class="fa-solid fa-pen-to-square" style="color: #16d4ae;"></i></div>
            <div>
                <div class="ed-kpi__title">Solicitudes de cambio</div>
                <div class="ed-kpi__value">0</div>
            </div>
        </article>
    </section>

    <section class="ed-grid">
        <article class="ed-card">
            <div class="ed-card__header">
                <h3>Empleados por área</h3>
                <div class="ed-legend" id="chart-legend-estado"></div>
            </div>
            <div class="ed-card__body chart-fixed">
                <canvas id="estadoChart"></canvas>
            </div>
            <div class="ed-card__footer">
                <div class="ed-hint"><i class="bi bi-lightbulb"></i>La siguiente gráfica muestra los empleados por área.</div>
            </div>
        </article>

        <article class="ed-card">
            <div class="ed-card__header">
                <h3>Actividad reciente</h3>
                <div class="ed-actions">
                    <button class="ed-chip" id="btn-clear-activity"><i class="bi bi-trash"></i> Limpiar</button>
                    <button class="ed-chip" id="btn-reload-activity"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                </div>
            </div>
            <div class="ed-card__body ed-activity-scroll">
                <ul class="ed-activity" id="activityList" aria-live="polite"></ul>
            </div>
            <div class="ed-card__footer ed-muted">
                <i class="bi bi-info-circle"></i>
                Se muestran los últimos eventos en el sistema...
            </div>
        </article>
    </section>

    <section class="ed-grid ed-grid--secondary">
        <article class="ed-card">
            <div class="ed-card__header">
                <h3>Solicitud de cambios</h3>
                <div class="ed-actions">
                    <a class="ed-chip" href="<?= BASE_URL ?>/config/app.php?accion=editor.solicitudes.cambio">
                        <i class="bi bi-ui-checks"></i> Abrir solicitudes
                    </a>
                </div>
            </div>
            <div class="ed-card__body ed-scroll">
                <ul class="ed-queue" id="queueList"></ul>
            </div>
        </article>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    window.BASE_URL        = "<?= BASE_URL ?>";
    window.EDITOR_SUMMARY  = <?= json_encode($editorSummary,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.EDITOR_ACTIVITY = <?= json_encode($activity,       JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.EDITOR_QUEUE    = <?= json_encode($queue,          JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= BASE_URL ?>/View/contenido/js/dashboard.editor.js?v=<?= time() ?>"></script>