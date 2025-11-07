<?php
// activa el menú lateral en "Reportes"
$active = 'reportes';
$title = 'Extensiones más usadas';
$areas = $areas       ?? [];    // viene del controlador
$extensiones = $extensiones ?? [];
$areaId = $areaId      ?? '';
require __DIR__ . '/../layout/headEditor.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/View/contenido/css/styleDirectorio.css">
<style>
    .rep-wrapper{max-width:1180px;margin:0 auto;padding:1.5rem 0 2.5rem;}
    .rep-title{font-size:1.35rem;font-weight:700;margin-bottom:1rem;}
    .rep-card{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 12px rgba(15,23,42,.05);border:1px solid #e1e6ea;}
    .rep-card-header{background:#577895;color:#fff;padding:.6rem 1rem;display:flex;justify-content:space-between;align-items:center;}
    .rep-card-header h2{font-size:1.05rem;margin:0;font-weight:600;}
    .rep-card-body{padding:1rem 1rem 1.3rem;background:#f7f8fa;}
    .rep-filters{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.75rem;align-items:center;}
    .rep-summary-box{background:#fff;border:1px solid #d9dfe4;border-radius:10px;padding:.6rem .8rem;margin-bottom:.9rem;font-size:.9rem;}
    .table-report{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;}
    .table-report thead th{background:#f0f3f6;font-weight:600;padding:.55rem .6rem;border-bottom:1px solid #e1e6ea;font-size:.8rem;}
    .table-report tbody td{background:#fff;padding:.48rem .6rem;border-bottom:1px solid #edf0f2;font-size:.83rem;}
    .text-right{text-align:right;}
    .badge-number{display:inline-block;background:#e9eef4;border-radius:999px;padding:.1rem .55rem;font-weight:600;font-size:.75rem;}
    .btn-small{display:inline-block;background:#3b82f6;color:#fff;border-radius:8px;padding:.35rem .75rem;font-size:.78rem;text-decoration:none;}
    .btn-small.red{background:#ef4444;}
    @media(max-width:768px){
        .rep-card-header{flex-direction:column;align-items:flex-start;gap:.4rem;}
        .table-report{display:block;overflow-x:auto;}
    }
</style>

<div class="container rep-wrapper">
    <h1 class="rep-title">Reporte: Extensiones más usadas</h1>
    <div class="rep-card">
        <div class="rep-card-header">
            <h2>Consultas registradas</h2>
            <a href="<?= BASE_URL ?>/config/app.php?accion=editor.reporte.extensiones.usadas.pdf<?= $areaId ? '&areaId='.(int)$areaId : '' ?>" class="btn-small red">
                Exportar PDF
            </a>
        </div>
        <div class="rep-card-body">
            <!-- FILTROS -->
            <form class="rep-filters" method="get" action="<?= BASE_URL ?>/config/app.php">
                <input type="hidden" name="accion" value="editor.reporte.extensiones.usadas">
                <label for="areaId" class="mb-0">Área:</label>
                <select name="areaId" id="areaId" class="form-select form-select-sm" style="min-width:220px;">
                    <option value="">Todas las áreas</option>
                    <?php foreach ($areas as $a): ?>
                        <?php
                        // tomar el nombre correcto
                        $nombreArea =
                            $a['nombreArea']  ??
                            $a['nombre']      ??
                            $a['areaNombre']  ??
                            $a['descripcion'] ??
                            'Área sin nombre';
                        $idA = (int)($a['idArea'] ?? 0);
                        ?>
                        <option value="<?= $idA ?>" <?= ($areaId !== '' && (int)$areaId === $idA) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($nombreArea) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-small">Aplicar</button>
            </form>
            <!-- RESUMEN -->
            <?php
            $nombreAreaSeleccionada = 'Todas las áreas';
            if ($areaId !== '') {
                foreach ($areas as $a) {
                    $idA = (int)($a['idArea'] ?? 0);
                    if ($idA === (int)$areaId) {
                        $nombreAreaSeleccionada =
                            $a['nombreArea']  ??
                            $a['nombre']      ??
                            $a['areaNombre']  ??
                            $a['descripcion'] ??
                            'Área sin nombre';
                        break;
                    }
                }
            }
            ?>
            <div class="rep-summary-box">
                <div><strong>Área seleccionada:</strong> <?= htmlspecialchars($nombreAreaSeleccionada) ?></div>
                <div><strong>Total de extensiones en el reporte:</strong> <?= count($extensiones) ?></div>
                <div><strong>Generado:</strong> <?= date('d/m/Y H:i') ?></div>
            </div>
            <!-- TABLA -->
            <div class="table-responsive">
                <table class="table-report">
                    <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th style="width:110px;">Extensión</th>
                        <th>Propietario</th>
                        <th style="width:180px;">Área</th>
                        <th style="width:120px;" class="text-right">Núm. de consultas</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($extensiones)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No hay consultas registradas para este filtro.</td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1; foreach ($extensiones as $row): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($row['numeroExtension']) ?></td>
                                    <td><?= htmlspecialchars($row['propietarioNombre']) ?></td>
                                    <td><?= htmlspecialchars($row['nombreArea'] ?? '—') ?></td>
                                    <td class="text-end"><?= (int)$row['totalConsultas'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>