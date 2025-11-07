<?php
$active = 'reportes';
$titulo = 'Reportes del sistema';
require __DIR__ . '/../layout/headConsultor.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/View/contenido/css/styleReporte.css">
<main class="dirrep-page">
    <header class="dirrep-header">
        <h1>DIRECTORIO GENERAL</h1>
        <p>TELÉFONOS: LADA(777) 1234567, 1234567, 1234567</p>
        <p>OPERADORA DE PRODUCTOS ELÉCTRONICOS</p>
    </header>
    <section class="dirrep-card">
        <?php foreach ($directorio as $area => $filas): ?>
            <?php
            $correoArea = $filas[0]['correoArea'] ?? '';
            ?>
            <article class="dirrep-area">
                <header class="dirrep-area-head">
                    <h2><?= htmlspecialchars($area) ?></h2>
                    <?php if ($correoArea): ?>
                        <span class="dirrep-area-mail">Correo del área: <strong><?= htmlspecialchars($correoArea) ?></strong></span>
                    <?php endif; ?>
                </header>
                <div class="dirrep-tablewrap">
                    <table class="dirrep-table">
                        <thead>
                            <tr>
                                <th class="text-center">Extensión</th>
                                <th class="text-center">Nombre</th>
                                <th class="text-center">Puesto</th>
                                <th class="text-center">Correo Electrónico</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filas as $fila): ?>
                                <tr>
                                    <td class="text-center"><?= htmlspecialchars($fila['extension'] ?? '-') ?></td>
                                    <td class="text-center"><?= htmlspecialchars($fila['nombre'] ?? '-') ?></td>
                                    <td class="text-center"><?= htmlspecialchars($fila['puesto'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($fila['correoPropietario'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($fila['correoPropietario']) ?>">
                                            <?= htmlspecialchars($fila['correoPropietario']) ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        <?php endforeach; ?>
        <div class="dirrep-actions">
            <a href="<?= BASE_URL ?>/config/app.php?accion=reporte.directorio.pdf" class="dirrep-btn pdf">Exportar a PDF</a>
            <a href="<?= BASE_URL ?>/config/app.php?accion=reporte.directorio.excel" class="rep-btn excel">Excel</a>
        </div>
    </section>
</main>