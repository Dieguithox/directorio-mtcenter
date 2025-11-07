<?php 
// activa la línea del menú lateral "Reportes"
$active = 'reportes';
$title  = 'Reportes del sistema';
require __DIR__ . '/../layout/headAdmin.php';
?>
<style>
.rep-page {padding: 1.5rem 2rem 2rem;}
.rep-panel {
    background: #f5f5f5;
    border-radius: 1.25rem;
    padding: 1.8rem;
}
.rep-panel-title {
    font-size: 1.5rem; font-weight: 700;
    margin-bottom: 1.6rem; color: #1d1d1d;
    text-align: left;
}
.rep-cards {
    display: grid;
    grid-template-columns: repeat(2, minmax(340px, 1fr)); /* 2 columnas porque son 4 cards */
    gap: 1.5rem;
    justify-content: center;
}
.rep-card {
    background: #fff;
    border: 1px solid #e2e2e2;
    border-radius: 1rem;
    padding: 1.2rem 1rem 1.4rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.rep-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.rep-card-img {
    width: 110px;
    height: 110px;
    margin-bottom: .6rem;
}
.rep-card-img img {
    width: 100%; height: 100%; object-fit: contain;
}
.rep-card-title {
    font-size: 1.05rem; font-weight: 600;
    color: #202020; margin-bottom: .4rem;
}
.rep-card-text {
    font-size: .87rem; color: #4a4a4a;
    flex: 1; margin-bottom: .6rem;
}
.rep-card-actions {
    display: flex; gap: .5rem;
    flex-wrap: wrap; justify-content: center;
}
.rep-btn {
    border-radius: .6rem; padding: .48rem .9rem;
    font-size: .8rem; font-weight: 500;
    text-decoration: none; color: #fff;
}
.rep-btn.ver { background: #3fa9d8; }
.rep-btn.pdf { background: #ff5b5b; }
.rep-btn.excel { background: #18b66b; }

@media (max-width: 1200px) {
    .rep-cards {grid-template-columns: 1fr;}
}
</style>

<main class="rep-page">
    <section class="rep-panel">
        <h2 class="rep-panel-title">CATÁLOGO DE REPORTES</h2>

        <div class="rep-cards">
            <!-- CARD 1: Extensiones más usadas -->
            <article class="rep-card">
                <div class="rep-card-img">
                    <img src="<?= BASE_URL ?>/View/contenido/img/reporte_frecuente.png" alt="Extensiones más usadas">
                </div>
                <h3 class="rep-card-title">Extensiones más usadas</h3>
                <p class="rep-card-text">
                    Muestra las extensiones con mayor número de consultas, con opción de filtrar por área.
                </p>
                <div class="rep-card-actions">
                    <a href="<?= BASE_URL ?>/config/app.php?accion=admin.reporte.extensiones.usadas" class="rep-btn ver">Ver reporte</a>
                    <a href="<?= BASE_URL ?>/config/app.php?accion=admin.reporte.extensiones.usadas.pdf" class="rep-btn pdf">PDF</a>
                </div>
            </article>

            <!-- CARD 2: Empleados por área -->
            <article class="rep-card">
                <div class="rep-card-img">
                    <img src="<?= BASE_URL ?>/View/contenido/img/reporte_empleadosArea.png" alt="Empleados por área">
                </div>
                <h3 class="rep-card-title">Empleados por área</h3>
                <p class="rep-card-text">
                    Genera el número de colaboradores registrados por cada área del directorio.
                </p>
                <div class="rep-card-actions">
                    <a href="<?= BASE_URL ?>/config/app.php?accion=admin.reporte.empleados.area" class="rep-btn ver">Ver reporte</a>
                    <a href="<?= BASE_URL ?>/config/app.php?accion=admin.reporte.empleados.area.pdf" class="rep-btn pdf">PDF</a>
                </div>
            </article>

            <!-- CARD 3: Extensiones más usadas con gráfica -->
            <article class="rep-card">
                <div class="rep-card-img">
                    <img src="<?= BASE_URL ?>/View/contenido/img/circular.png" alt="Extensiones más usadas con gráfica">
                </div>
                <h3 class="rep-card-title">Extensiones más usadas con gráfica</h3>
                <p class="rep-card-text">
                    Visualización en gráfica circular del porcentaje de consultas por extensión.
                </p>
                <div class="rep-card-actions">
                    <a href="<?= BASE_URL ?>/config/app.php?accion=admin.reporte.extensiones.grafica" class="rep-btn ver">Ver reporte</a>
                    <a href="<?= BASE_URL ?>/config/app.php?accion=admin.reporte.extensiones.grafica.excel" class="rep-btn excel">Excel</a>
                </div>
            </article>

            <!-- CARD 4: Empleados por área con gráfica -->
            <article class="rep-card">
                <div class="rep-card-img">
                    <img src="<?= BASE_URL ?>/View/contenido/img/barras.png" alt="Empleados por área con gráfica">
                </div>
                <h3 class="rep-card-title">Empleados por área con gráfica</h3>
                <p class="rep-card-text">
                    Muestra en barras la distribución de personal por área para comparar departamentos.
                </p>
                <div class="rep-card-actions">
                    <a href="<?= BASE_URL ?>/config/app.php?accion=admin.reporte.empleados.area.grafica" class="rep-btn ver">Ver reporte</a>
                    <a href="<?= BASE_URL ?>/config/app.php?accion=admin.reporte.empleados.area.grafica.excel" class="rep-btn excel">Excel</a>
                </div>
            </article>
        </div>
    </section>
</main>