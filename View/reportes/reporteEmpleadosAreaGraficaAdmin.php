<?php
$active = 'reportes';
$title = 'Empleados por área';
require __DIR__ . '/../layout/headAdmin.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/View/contenido/css/styleReporte.css">
<main class="rep-page">
    <section class="rep-panel">
        <h2 class="rep-panel-title">Empleados por área con gráfica</h2>
        <div class="rep-actions" style="margin-bottom:1rem;">
            <a href="<?= BASE_URL ?>/config/app.php?accion=admin.reporte.empleados.area.grafica.excel" class="rep-btn excel">Exportar a Excel</a>
        </div>
        <div class="rep-grid" style="display:grid; grid-template-columns: 1.1fr .9fr; gap:1.5rem;">
            <!-- tabla -->
            <div>
                <table class="table table-sm" style="width:100%; background:#fff; border-radius:10px; overflow:hidden;">
                    <thead style="background:#f4f6f8;">
                        <tr>
                            <th>#</th>
                            <th>Área</th>
                            <th>Correo del área</th>
                            <th style="text-align:right;">Empleados</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($areas)): ?>
                        <tr><td colspan="4">No hay datos.</td></tr>
                    <?php else: ?>
                        <?php $i=1; foreach ($areas as $a): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($a['nombreArea']) ?></td>
                                <td><?= htmlspecialchars($a['correoArea'] ?? '-') ?></td>
                                <td style="text-align:right;"><?= (int)$a['totalEmpleados'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- gráfica -->
            <div style="background:#fff; border-radius:10px; padding:1rem;">
                <canvas id="chartAreas" width="400" height="300"></canvas>
            </div>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const labels = <?= json_encode(array_column($areas, 'nombreArea'), JSON_UNESCAPED_UNICODE) ?>;
const dataVals = <?= json_encode(array_map('intval', array_column($areas, 'totalEmpleados'))) ?>;

const ctx = document.getElementById('chartAreas').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Empleados',
            data: dataVals,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: {
                display: true,
                text: 'Distribución de empleados por área'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});
</script>