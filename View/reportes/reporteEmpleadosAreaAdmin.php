<?php
require __DIR__ . '/../layout/headAdmin.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/View/contenido/css/styleReporte.css">

<main class="rep-page">
    <section class="rep-panel">
        <h2 class="rep-panel-title">Reporte: Empleados por área</h2>
        <div class="mb-3">
            <a href="<?= BASE_URL ?>/config/app.php?accion=admin.reporte.empleados.area.pdf" class="rep-btn pdf">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Exportar PDF
            </a>
        </div>

        <div class="table-responsive rep-table">
            <table class="table table-striped align-middle">
                <thead>
                <tr>
                    <th style="width:70px;">#</th>
                    <th>Área</th>
                    <th style="width:230px;">Correo del área</th>
                    <th style="width:150px;" class="text-end">Total de empleados</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($areas)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No hay áreas registradas.</td>
                    </tr>
                <?php else: ?>
                    <?php $i=1; $totalGlobal = 0; ?>
                    <?php foreach ($areas as $area): ?>
                        <?php $totalGlobal += (int)$area['totalEmpleados']; ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($area['nombreArea']) ?></td>
                            <td><?= htmlspecialchars($area['correoArea'] ?? '-') ?></td>
                            <td class="text-end">
                                <span class="badge bg-primary bg-opacity-75">
                                    <?= (int)$area['totalEmpleados'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total general:</strong></td>
                        <td class="text-end"><strong><?= $totalGlobal ?></strong></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>