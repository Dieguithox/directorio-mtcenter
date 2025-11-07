<?php
$active = 'reportes';
$title = 'Extensiones más usadas gráfica';
// vienen del controlador
$areas = $areas ?? [];
$extensiones = $extensiones ?? [];
$areaId = $areaId ?? null;
$areaNombre = $areaNombre ?? 'Todas las áreas';
// header del editor
require __DIR__ . '/../layout/headAdmin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/View/contenido/css/styleReporte.css">
<main class="rep-page">
    <section class="rep-panel">
        <h2 class="rep-panel-title">Extensiones más usadas con gráfica</h2>
        <!-- Filtros -->
        <form method="get" action="<?= BASE_URL ?>/config/app.php" class="mb-4 d-flex gap-2 align-items-center flex-wrap">
            <input type="hidden" name="accion" value="admin.reporte.extensiones.grafica">
            <label for="areaId" class="mb-0">Área:</label>
            <select name="areaId" id="areaId" class="form-select" style="max-width: 240px;">
                <option value="">Todas las áreas</option>
                <?php foreach ($areas as $a): ?>
                    <option value="<?= (int)$a['idArea'] ?>" <?= ($areaId == (int)$a['idArea']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit">Aplicar</button>
            <a class="btn btn-success"
                href="<?= BASE_URL ?>/config/app.php?accion=admin.reporte.extensiones.grafica.excel<?= $areaId ? '&areaId='.(int)$areaId : '' ?>">
                Exportar Excel
            </a>
        </form>
        <div class="row g-3">
            <!-- TABLA -->
            <div class="col-12 col-lg-6">
                <div class="table-responsive bg-white p-2 rounded shadow-sm">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Extensión</th>
                                <th>Consultas</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalGeneral = 0;
                            foreach ($extensiones as $ex) {
                                $totalGeneral += (int)$ex['totalConsultas'];
                            }
                            ?>
                            <?php if (empty($extensiones)): ?>
                                <tr><td colspan="4">No hay datos para este filtro.</td></tr>
                            <?php else: ?>
                                <?php $i=1; foreach ($extensiones as $ex): ?>
                                    <?php
                                    $pct = $totalGeneral > 0
                                        ? round(($ex['totalConsultas'] * 100) / $totalGeneral, 2)
                                        : 0;
                                    ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($ex['extension']) ?></td>
                                        <td><?= (int)$ex['totalConsultas'] ?></td>
                                        <td><?= $pct ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- GRÁFICA -->
            <div class="col-12 col-lg-6">
                <div class="bg-white p-3 rounded shadow-sm">
                    <canvas id="extChart" style="max-height:320px;"></canvas>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const dataPHP = <?= json_encode($extensiones, JSON_UNESCAPED_UNICODE) ?>;
const total = dataPHP.reduce((acc, it) => acc + parseInt(it.totalConsultas), 0);
const labels = dataPHP.map(it => it.extension);
const values = dataPHP.map(it => it.totalConsultas);
const colors = labels.map((_, i) => `hsl(${(i*40)%360}, 75%, 55%)`);

const ctx = document.getElementById('extChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: labels,
        datasets: [{data: values, backgroundColor: colors}]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Extensiones más usadas - <?= htmlspecialchars($areaNombre) ?>'
            },
            legend: {
                position: 'right'
            },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        const v = ctx.parsed;
                        const pct = total > 0 ? ((v*100)/total).toFixed(1) : 0;
                        return ctx.label + ': ' + v + ' (' + pct + '%)';
                    }
                }
            }
        }
    }
});
</script>