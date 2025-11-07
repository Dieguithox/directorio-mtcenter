<?php
$extensiones   = $EXTENSIONES ?? [];
$areaId        = $AREA_ID ?? null;
$areaNombre    = $AREA_NOMBRE ?? 'Todas las áreas';
$logoDataUri   = $LOGO_DATA_URI ?? '';
$usuarioNombre = $USUARIO_NOMBRE ?? 'Usuario del sistema';
$fecha         = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Extensiones Más Usadas</title>
    <style>
        body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#333;margin:25px;}
        h1{font-size:20px;margin:0 0 5px;text-align:center;}
        .sub{ text-align:center; margin-bottom:15px; }
        table{width:100%;border-collapse:collapse;}
        th{background:#005eb8;color:#fff;padding:6px 5px;font-size:10px;text-align:left;}
        td{padding:6px 5px;font-size:10px;border-bottom:1px solid #eee;}
        tr:nth-child(even){background:#f9fafb;}
        .text-center{text-align:center;}
        .text-right{text-align:right;}
        hr{border:0;border-top:3px solid #005eb8;margin:10px 0 15px;}
        .footer{margin-top:18px;text-align:center;font-size:9px;color:#888;border-top:1px solid #ddd;padding-top:6px;}
    </style>
</head>
<body>
<h1>Reporte de Extensiones Más Usadas</h1>
<div class="sub">
    Área seleccionada: <strong><?= htmlspecialchars($areaNombre) ?></strong><br>
    <strong>Generado por:</strong> <?= htmlspecialchars($usuarioNombre) ?><br>
    Fecha de creación: <strong><?= $fecha ?></strong>
</div>
<hr>

<table>
    <thead>
        <tr>
            <th style="width:35px;">#</th>
            <th style="width:70px;">Extensión</th>
            <th>Descripción / Propietario</th>
            <th style="width:140px;">Área</th>
            <th style="width:70px;" class="text-right">Consultas</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($extensiones)): ?>
            <tr>
                <td colspan="5" class="text-center" style="padding:20px 5px;">No hay datos para mostrar.</td>
            </tr>
        <?php else: ?>
            <?php $i = 1; foreach ($extensiones as $row): ?>
                <tr>
                    <td class="text-center"><?= $i++ ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['numeroExtension']) ?></td>
                    <td><?= htmlspecialchars($row['propietarioNombre']) ?></td>
                    <td><?= htmlspecialchars($row['nombreArea'] ?? '—') ?></td>
                    <td class="text-right"><?= (int)$row['totalConsultas'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div class="footer">
    Directorio Telefónico MTCenter · Reporte generado automáticamente
</div>

</body>
</html>