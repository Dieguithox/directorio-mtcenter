<?php
// /View/admin/dashboardAdmin.php
$title  = 'Dashboard';
$active = 'home';

// Layout base
require __DIR__ . '/../layout/headAdmin.php';

// Asegura config y BD (por si no vienen desde el layout)
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../Model/BD.php';

// Consulta de datos
try {
    $pdo = BD::pdo();
    $pdo->exec("SET NAMES utf8mb4");

    // Totales
    $total_contactos = (int)$pdo->query("SELECT COUNT(*) FROM propietario")->fetchColumn();
    $usuarios        = (int)$pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
    $extensiones     = (int)$pdo->query("SELECT COUNT(*) FROM extension")->fetchColumn();

    // Distribución por departamentos (áreas)
    // propietario -> puesto -> area
    $sqlDeptos = "
        SELECT a.nombre AS name, COUNT(p.idPropietario) AS value
        FROM area a
        LEFT JOIN puesto pu   ON pu.areaId = a.idArea
        LEFT JOIN propietario p ON p.puestoId = pu.idPuesto
        GROUP BY a.idArea, a.nombre
        ORDER BY value DESC, a.nombre ASC
    ";
    $departamentos = $pdo->query($sqlDeptos)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $departamentos = array_map(fn($r) => ['name' => $r['name'], 'value' => (int)$r['value']], $departamentos);

    // Actividad reciente (bitácora)
    $stmt = $pdo->prepare("
        SELECT descripcion AS text, creada_at
        FROM bitacorasistema
        ORDER BY creada_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $activity = array_map(function ($r) {
        $tsMs = strtotime($r['creada_at'] ?? 'now') * 1000;
        return ['text' => ($r['text'] ?? 'Actividad'), 'ts' => $tsMs];
    }, $rows);

    $summary = [
        'total_contactos' => $total_contactos,
        'usuarios'        => $usuarios,
        'extensiones'     => $extensiones,
        'departamentos'   => $departamentos,
    ];
} catch (Throwable $e) {
    error_log("[DashboardAdmin] " . $e->getMessage());
    $summary  = ['total_contactos' => 0, 'usuarios' => 0, 'extensiones' => 0, 'departamentos' => []];
    $activity = [];
}
?>

<!-- Font Awesome -->
<script src="https://kit.fontawesome.com/35c8a07d23.js" crossorigin="anonymous"></script>

<div class="adm-wrapper" id="admWrapper">
    <!-- KPIs -->
    <section class="adm-kpis">
        <article class="adm-kpi is-blue" id="kpi-total-contactos">
            <div class="adm-kpi-icon"><i class="fa-solid fa-address-book" style="color:#3542a7;"></i></div>
            <div class="adm-kpi-body">
                <div class="adm-kpi-title">Total de contactos</div>
                <div class="adm-kpi-value">0</div>
            </div>
        </article>
        <article class="adm-kpi is-green" id="kpi-usuarios">
            <div class="adm-kpi-icon"><i class="fa-solid fa-user-plus" style="color:#4cc277;"></i></div>
            <div class="adm-kpi-body">
                <div class="adm-kpi-title">Usuarios registrados</div>
                <div class="adm-kpi-value">0</div>
            </div>
        </article>
        <article class="adm-kpi is-amber" id="kpi-extensiones">
            <div class="adm-kpi-icon"><i class="fa-solid fa-phone" style="color:#ffce0a;"></i></div>
            <div class="adm-kpi-body">
                <div class="adm-kpi-title">Extensiones</div>
                <div class="adm-kpi-value">0</div>
            </div>
        </article>
        <article class="adm-kpi is-rose" id="kpi-deptos">
            <div class="adm-kpi-icon"><i class="fa-solid fa-briefcase" style="color:#ff576a;"></i></div>
            <div class="adm-kpi-body">
                <div class="adm-kpi-title">Áreas</div>
                <div class="adm-kpi-value">0</div>
            </div>
        </article>
    </section>

    <!-- GRID -->
    <section class="adm-grid">
        <!-- Gráfica -->
        <article class="adm-card">
            <div class="adm-card-header">
                <h3>Distribución por áreas</h3>
                <div class="adm-legend" id="chart-legend"></div>
            </div>
            <div class="adm-card-body chart-fixed">
                <canvas id="deptosChart"></canvas>
            </div>
        </article>

        <!-- Actividad reciente -->
        <article class="adm-card is-activity">
            <div class="adm-card-header">
                <h3>Actividad reciente</h3>
                <div class="adm-actions">
                    <button class="adm-chip" id="btn-clear-activity"><i class="fa-solid fa-trash"></i> Limpiar</button>
                    <button class="adm-chip" id="btn-reload-activity"><i class="fa-solid fa-rotate"></i> Recargar</button>
                </div>
            </div>

            <!--  Cambio: altura fija + scroll interno -->
            <div class="adm-card-body adm-activity-scroll">
                <ul class="adm-activity" id="activityList" aria-live="polite">
                    <!-- items por JS -->
                </ul>
            </div>
        </article>
    </section>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<!-- Inyecta datos desde PHP (igual que antes) -->
<script>
    window.SUMMARY  = <?= json_encode($summary,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.ACTIVITY = <?= json_encode($activity, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<!-- Tu JS externo con la misma lógica de antes -->
<script src="<?= BASE_URL ?>/View/contenido/js/dashboard.admin.js?v=<?= time() ?>"></script>