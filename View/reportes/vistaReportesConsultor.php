<?php
// activa el menú lateral en "Reportes"
$active = 'reportes';
$title = 'Reportes del sistema';
require __DIR__ . '/../layout/headConsultor.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/View/contenido/css/styleReporte.css">
<!-- Vista principal de los reportes del sistema -->
<main class="rep-page">
    <section class="rep-panel">
        <h2 class="rep-panel-title">CATÁLOGO DE REPORTES</h2>
        <div class="rep-cards">
            <!-- CARD 1 -->
            <article class="rep-card">
                <div class="rep-card-img">
                    <img src="<?= BASE_URL ?>/View/contenido/img/reporte_directorio.png" alt="Exportación del directorio">
                </div>
                <h3 class="rep-card-title">Exportación del directorio</h3>
                <p class="rep-card-text">Este reporte te permite generar un listado completo del directorio telefónico y permite descargarlo.</p>
                <div class="rep-card-actions">
                    <a href="<?= BASE_URL ?>/config/app.php?accion=reporte.directorio" class="rep-btn ver">Ver reporte</a>
                    <a href="<?= BASE_URL ?>/config/app.php?accion=reporte.directorio.pdf" class="rep-btn pdf">PDF</a>
                    <a href="<?= BASE_URL ?>/config/app.php?accion=reporte.directorio.excel" class="rep-btn excel">Excel</a>
                </div>
            </article>
            <!-- CARD 2 -->
            <article class="rep-card">
                <div class="rep-card-img">
                    <img src="<?= BASE_URL ?>/View/contenido/img/reporte_filtro.png" alt="Exportación del directorio con filtros">
                </div>
                <h3 class="rep-card-title">Exportación del directorio con filtros</h3>
                <p class="rep-card-text">Este reporte te permite seleccionar campos como área, puesto u otros filtros antes de exportar.</p>
                <div class="rep-card-actions">
                    <a href="<?= BASE_URL ?>/config/app.php?accion=reporte.directorio.filtros" class="rep-btn ver">Ver reporte</a>
                </div>
            </article>
        </div>
    </section>
</main>