<?php
// variables que manda el controlador
$areas         = $AREAS ?? [];
$logoDataUri   = $LOGO_DATA_URI ?? '';
$usuarioNombre = $USUARIO_NOMBRE ?? 'Usuario del sistema';
$fecha         = date('d/m/Y H:i');

// totales
$totalAreas     = count($areas);
$totalEmpleados = 0;
foreach ($areas as $a) {
    $totalEmpleados += (int)($a['totalEmpleados'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte - Empleados por área</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color:#222; margin:0; padding:0; }
        .page { width:95%; max-width:780px; margin:0 auto; }
        .header { border-bottom:2px solid #d0d7dd; margin-bottom:10px; padding:6px 0 4px; }
        .header-table { width:100%; }
        .logo-cell { width:90px; vertical-align:top; }
        .logo { width:70px; height:auto; }
        .title-cell { text-align:right; line-height:1.2; }
        .title-cell h1 { margin:0; font-size:15px; font-weight:bold; }
        .title-cell p { margin:2px 0; font-size:10px; }

        .summary { background:#f4f6f8; border:1px solid #dde3e7; border-radius:5px; padding:5px 8px; margin-bottom:10px; font-size:11.5px; }
        .summary-title { font-weight:bold; margin-bottom:3px; }

        table { width:100%; border-collapse:collapse; }
        th, td { padding:5px 6px; border-bottom:1px solid #e6e6e6; }
        th { background:#577895; color:#fff; font-size:10px; text-align:left; }
        td { font-size:10.5px; }
        .text-right { text-align:right; }
    </style>
</head>
<body>
<div class="page">
    <!-- ENCABEZADO -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <?php if ($logoDataUri): ?>
                        <img src="<?= $logoDataUri ?>" alt="Logo" class="logo">
                    <?php endif; ?>
                </td>
                <td class="title-cell">
                    <h1>EMPLEADOS POR ÁREA</h1>
                    <p>Operadora de Productos Electrónicos</p>
                    <p>Generado por: <strong><?= htmlspecialchars($usuarioNombre) ?></strong></p>
                </td>
            </tr>
        </table>
    </div>

    <!-- RESUMEN -->
    <div class="summary">
        <div class="summary-title">Resumen del reporte</div>
        <div>
            Total de áreas: <strong><?= $totalAreas ?></strong> |
            Total de empleados: <strong><?= $totalEmpleados ?></strong> |
            Fecha: <strong><?= $fecha ?></strong>
        </div>
    </div>

    <!-- TABLA -->
    <table>
        <thead>
        <tr>
            <th style="width:40px;">#</th>
            <th>Área</th>
            <th style="width:200px;">Correo del área</th>
            <th style="width:120px;" class="text-right">Empleados</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($areas)): ?>
            <tr>
                <td colspan="4">No hay datos para mostrar.</td>
            </tr>
        <?php else: ?>
            <?php $i = 1; foreach ($areas as $a): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($a['nombreArea'] ?? '') ?></td>
                    <td><?= htmlspecialchars($a['correoArea'] ?? '-') ?></td>
                    <td class="text-right"><?= (int)($a['totalEmpleados'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>