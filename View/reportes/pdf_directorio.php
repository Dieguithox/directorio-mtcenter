<?php
$directorio = $DIRECTORIO ?? [];
$logoDataUri = $LOGO_DATA_URI ?? '';
// totales para el resumen
$totalAreas = count($directorio);
$totalContactos = 0;
foreach ($directorio as $filas) {
    $totalContactos += count($filas);
}
$usuarioNombre = $_SESSION['usuario']['nombre'] ?? 'Usuario del sistema';
$fecha = date('d/m/Y H:i');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Directorio Telefónico MTCenter</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11.5px; margin:0; padding:0; color:#222; }
        .page { width:95%; max-width:780px; margin:0 auto 10px; }
        .header { border-bottom:2px solid #d0d7dd; margin-bottom:8px; padding:6px 0 4px; }
        .header-table { width:100%; }
        .logo-cell { width:90px; vertical-align:top; }
        .logo { width:70px; height:auto; }
        .title-cell { text-align:right; line-height:1.2; }
        .title-cell h1 { margin:0; font-size:15px; font-weight:bold; }
        .title-cell p { margin:2px 0; font-size:10px; }
        .summary { background:#f4f6f8; border:1px solid #dde3e7; border-radius:5px; padding:5px 8px; margin-bottom:10px; font-size:13px; }
        .summary-title { font-weight:bold; margin-bottom:3px; }
        .area-block { border:1px solid #d6d6d6; border-radius:6px; overflow:hidden; margin-bottom:10px; page-break-inside:avoid; }
        .area-header { background:#577895; color:#fff; padding:5px 9px; display:table; width:100%; }
        .area-title { display:table-cell; font-weight:bold; font-size:12px; }
        .area-title small { font-weight:normal; font-size:9.4px; }
        .area-mail { display:table-cell; text-align:right; font-size:9.5px; }
        table.area-table { width:100%; border-collapse:collapse; background:#fff; }
        table.area-table th, table.area-table td { border-bottom:1px solid #e5e5e5; padding:4px 6px; }
        table.area-table th { background:#f4f6f8; font-weight:bold; font-size:10px; text-align:left; }
        table.area-table td { font-size:9.8px; }
        .col-ext { width:55px; }
        .col-puesto { width:110px; }
        .col-correo { width:165px; word-wrap:break-word; }
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
                        <img src="<?= $logoDataUri ?>" alt="Logo MTCenter" class="logo">
                    <?php endif; ?>
                </td>
                <td class="title-cell">
                    <strong><h1>DIRECTORIO GENERAL</h1></strong>
                    <p>TELÉFONOS: LADA(777) 1234567, 1234567, 1234567</p>
                    <p>OPERADORA DE PRODUCTOS ELÉCTRONICOS</p>
                    <p>Generado por: <strong><?= htmlspecialchars($usuarioNombre) ?></p></strong>
                </td>
            </tr>
        </table>
    </div>
    <!-- RESUMEN -->
    <div class="summary">
        <div class="summary-title">Resumen del directorio</div>
        <div>
            Total de áreas: <strong><?= $totalAreas ?></strong> |
            Total de contactos: <strong><?= $totalContactos ?></strong> |
            Fecha de generación: <strong><?= $fecha ?></strong>
        </div>
    </div>
    <!-- ÁREAS -->
    <?php foreach ($directorio as $area => $filas): ?>
        <?php
            $correoArea = $filas[0]['correoArea'] ?? '';
            $totalArea  = count($filas);
        ?>
        <div class="area-block">
            <div class="area-header">
                <div class="area-title">
                    <?= htmlspecialchars($area) ?>
                    <small> · <?= $totalArea ?> contacto<?= $totalArea !== 1 ? 's' : '' ?></small>
                </div>
                <?php if ($correoArea): ?>
                    <div class="area-mail">
                        Correo del área: <strong><?= htmlspecialchars($correoArea) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
            <table class="area-table">
                <thead>
                <tr>
                    <th class="col-ext">Extensión</th>
                    <th>Nombre</th>
                    <th class="col-puesto">Puesto</th>
                    <th class="col-correo">Correo electrónico</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($filas as $fila): ?>
                    <tr>
                        <td><?= htmlspecialchars($fila['extension'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($fila['nombre'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($fila['puesto'] ?? '-') ?></td>
                        <td>
                            <?php
                            $correo = $fila['correoPropietario'] ?? '';
                            echo $correo ? htmlspecialchars($correo) : '-';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>