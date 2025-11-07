<?php
// activa el menú lateral
$active = 'reportes';
$titulo = 'Directorio con filtros';
require __DIR__ . '/../layout/headEditor.php';
?>
<style>
/* ===== CONTENEDOR GENERAL ===== */
.rep-page {
    background: #ffffff;
    min-height: calc(100vh - 60px);
    display: flex;
    justify-content: center;
    padding: 2rem 1.5rem;
}
.rep-panel {
    background: #f2f4f8;
    border-radius: 1.5rem;
    border: 1px solid #e1e1e1;
    padding: 1.8rem 1.5rem 2.2rem;
    width: 100%;
}
.rep-panel--wide {
    max-width: 1180px;
    margin: 0 auto;
}
.rep-panel-title {
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: .4rem;
    color: #222;
}
.rep-panel-subtext {
    font-size: .9rem;
    color: #555;
    margin-bottom: 1.2rem;
}
/* ===== FILTRO ===== */
.rep-filter {
    background: #fff;
    border: 1px solid #e4e4e4;
    border-radius: 1rem;
    padding: 1rem 1.2rem;
    margin-bottom: 1rem;
}
.rep-filter-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(120px, 1fr));
    gap: 1rem;
}
.rep-filter-item label {
    display: block;
    font-size: .8rem;
    font-weight: 600;
    margin-bottom: .3rem;
}
.rep-filter-item input,
.rep-filter-item select {
    width: 100%;
    border: 1px solid #dcdfe4;
    border-radius: .7rem;
    padding: .4rem .6rem;
    font-size: .9rem;
    background: #fff;
}
.rep-filter-actions {
    display: flex;
    align-items: flex-end;
}
/* ===== BOTONES ===== */
.rep-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .3rem;
    padding: .45rem 1.1rem;
    border-radius: .9rem;
    font-size: .85rem;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: .15s ease-in-out;
}
.rep-btn.ver {
    background: #e7f4ff;
    color: #0c63b0;
}
.rep-btn.pdf {
    background: #ff5e5e;
    color: #fff;
}
.rep-btn.excel {
    background: #1abc9c;
    color: #fff;
}
.rep-btn:hover {
    filter: brightness(.95);
}
.rep-exportbar {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: 1.2rem;
}
.rep-exportbar span {
    font-size: .85rem;
    color: #444;
}
/* ===== RESULTADOS ===== */
.rep-empty {
    background: #fff;
    border-radius: .8rem;
    padding: 1rem;
    border: 1px dashed #ccc;
    text-align: center;
    color: #666;
}
.rep-results .dirrep-area {
    background: #fff;
    border: 1px solid #e1e1e1;
    border-radius: 1rem;
    overflow: hidden;
    margin-bottom: 1rem;
}
/* ===== CABECERA DE ÁREA ===== */
.dirrep-area-head {
    background: #577895;
    color: #fff;
    padding: .6rem 1.1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.dirrep-area-head h3 {
    margin: 0;
    font-size: 1rem;
}
.dirrep-area-mail {
    font-size: .75rem;
    opacity: .9;
    text-align: right;
}
/* ===== TABLA ===== */
.dirrep-tablewrap {
    width: 100%;
    display: flex;
    justify-content: center;
    overflow-x: auto;
    background: #fff;
}
.dirrep-table {
    width: 95%;
    max-width: 1050px;
    border-collapse: collapse;
    margin: 0 auto;
}
.dirrep-table th,
.dirrep-table td {
    padding: .55rem .8rem;
    border-bottom: 1px solid #eef1f4;
    font-size: .85rem;
    vertical-align: middle;
}
.dirrep-table th {
    background: #f4f6f8;
    text-align: left;
    font-weight: 600;
}
.dirrep-table td a {
    color: #0c63b0;
    text-decoration: none;
    word-break: break-word;
}
.dirrep-table td a:hover {
    text-decoration: underline;
}
/* ===== RESPONSIVE ===== */
@media (max-width: 920px) {
    .rep-filter-grid {
        grid-template-columns: 1fr 1fr;
    }
    .dirrep-table {
        min-width: 540px; /* para que no se rompa */
    }
}
@media (max-width: 520px) {
    .rep-filter-grid {
        grid-template-columns: 1fr;
    }
    .rep-exportbar {
        flex-wrap: wrap;
    }
    .dirrep-table {
        min-width: 500px;
    }
}
</style>
<main class="rep-page">
    <section class="rep-panel rep-panel--wide">
        <h2 class="rep-panel-title">Directorio con filtros</h2>
        <p class="rep-panel-subtext">Busca por nombre, extensión o área y después exporta exactamente esos resultados.</p>
        <!-- FORMULARIO -->
        <form class="rep-filter" method="get" action="<?= BASE_URL ?>/config/app.php">
            <input type="hidden" name="accion" value="reporte.directorio.filtros.editor">
            <div class="rep-filter-grid">
                <div class="rep-filter-item">
                    <label for="f-nombre">Nombre</label>
                    <input type="text" id="f-nombre" name="nombre" value="<?= htmlspecialchars($_GET['nombre'] ?? '') ?>" placeholder="Ej. Ana, Pérez...">
                </div>
                <div class="rep-filter-item">
                    <label for="f-ext">Extensión</label>
                    <input type="text" id="f-ext" name="extension" value="<?= htmlspecialchars($_GET['extension'] ?? '') ?>" placeholder="Ej. 105">
                </div>
                <div class="rep-filter-item">
                    <label for="f-area">Área</label>
                    <select id="f-area" name="areaId">
                        <option value="">-- Todas --</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= $a['idArea'] ?>"
                                <?= (($_GET['areaId'] ?? '') == $a['idArea']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="rep-filter-item rep-filter-actions">
                    <button type="submit" class="rep-btn ver w-100">Filtrar</button>
                </div>
            </div>
        </form>

        <!-- BOTONES DE EXPORTAR -->
        <div class="rep-exportbar">
            <span>Exportar resultados:</span>
            <a class="rep-btn pdf"
                href="<?= BASE_URL ?>/config/app.php?accion=reporte.directorio.filtros.pdf&nombre=<?= urlencode($_GET['nombre'] ?? '') ?>&extension=<?= urlencode($_GET['extension'] ?? '') ?>&areaId=<?= urlencode($_GET['areaId'] ?? '') ?>">
                PDF
            </a>
            <a class="rep-btn excel"
                href="<?= BASE_URL ?>/config/app.php?accion=reporte.directorio.filtros.excel&nombre=<?= urlencode($_GET['nombre'] ?? '') ?>&extension=<?= urlencode($_GET['extension'] ?? '') ?>&areaId=<?= urlencode($_GET['areaId'] ?? '') ?>">
                Excel
            </a>
        </div>

        <!-- RESULTADOS -->
        <?php if (empty($directorio)): ?>
            <p class="rep-empty">No se encontraron registros con esos filtros.</p>
        <?php else: ?>
            <div class="rep-results">
                <?php foreach ($directorio as $area => $filas): ?>
                    <article class="dirrep-area">
                        <header class="dirrep-area-head">
                            <h3><?= htmlspecialchars($area) ?></h3>
                            <?php if (!empty($filas[0]['correoArea'])): ?>
                                <span class="dirrep-area-mail">Correo del área: <?= htmlspecialchars($filas[0]['correoArea']) ?></span>
                            <?php endif; ?>
                        </header>
                        <div class="dirrep-tablewrap">
                            <table class="dirrep-table">
                                <thead>
                                <tr>
                                    <th>Extensión</th>
                                    <th>Nombre</th>
                                    <th>Puesto</th>
                                    <th>Correo</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($filas as $fila): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($fila['extension'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($fila['nombre'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($fila['puesto'] ?? '-') ?></td>
                                        <td>
                                            <?php if (!empty($fila['correoPropietario'])): ?>
                                                <?php
                                                    $correos = explode(',', $fila['correoPropietario']);
                                                ?>
                                                <?php foreach ($correos as $c): ?>
                                                    <?php $c = trim($c); ?>
                                                    <a href="mailto:<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></a><br>
                                                <?php endforeach; ?>
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
            </div>
        <?php endif; ?>
    </section>
</main>