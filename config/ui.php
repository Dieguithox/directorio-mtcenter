<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/flash_helpers.php';

/* Catálogo de mensajes */
$MENSAJES = [
    // Auth
    'auth.inactividad'        => 'Tu sesión se cerró por inactividad. Vuelve a iniciar sesión.',
    'auth.permiso_denegado'   => 'No tienes permisos para esta sección.',
    'auth.login.ok'           => 'Bienvenido.',
    'auth.login.fail'         => 'Credenciales inválidas.',
    'auth.registro.ok'        => 'Cuenta creada correctamente. Ahora inicia sesión.',
    'auth.reset.enlace'       => 'Si el correo existe, te enviamos un enlace de recuperación.',
    'auth.reset.ok'           => 'Tu contraseña se actualizó correctamente. Inicia sesión.',
    'auth.debes_iniciar'      => 'Debes iniciar sesión para continuar.',

    // Extensiones
    'ext.creada'              => 'Extensión creada correctamente.',
    'ext.actualizada'         => 'Extensión actualizada.',
    'ext.eliminada'           => 'Extensión eliminada.',
    'ext.error.crear'         => 'No se pudo crear la extensión.',
    'ext.error.actualizar'    => 'No se pudo actualizar la extensión.',
    'ext.error.eliminar'      => 'No se pudo eliminar la extensión.',

    // Usuarios
    'usr.creado'              => 'Usuario creado correctamente.',
    'usr.actualizado'         => 'Usuario actualizado.',
    'usr.eliminado'           => 'Usuario eliminado.',
    'usr.error.crear'         => 'No se pudo crear el usuario.',
    'usr.error.actualizar'    => 'No se pudo actualizar el usuario.',
    'usr.error.eliminar'      => 'No se pudo eliminar el usuario.',
    'usr.actualizado.datos'   => 'Datos actualizados correctamente',
    
    // Puestos
    'puesto.creado'           => 'Puesto creado correctamente.',
    'puesto.actualizado'      => 'Puesto actualizado',
    'puesto.eliminado'        => 'Puesto eliminado.',
    'puesto.error.crear'      => 'No se pudo crear el puesto.',
    'puesto.error.actualizar' => 'No se pudo actualizar el puesto.',
    'puesto.error.eliminar'   => 'No se pudo eliminar el puesto.',
    'puesto.error.fkpropietarios' => 'No puedes borrar este puesto porque tiene propietarios asignados.',

    // Areas
    'area.creada'           => 'Área creada correctamente.',
    'area.actualizada'      => 'Área actualizada.',
    'area.eliminada'        => 'Área eliminada.',
    'area.error.crear'      => 'No se pudo crear el área.',
    'area.error.actualizar' => 'No se pudo actualizar el área.',
    'area.error.eliminar'   => 'No pudo eliminar el área.',
    'area.error.fkpuestos' => 'No puedes borrar esta área porque tiene puestos asignados.',

    // Propietarios
    'prop.creado'           => 'Propietario creado correctamente.',
    'prop.actualizado'      => 'Propietario actualizado.',
    'prop.eliminado'        => 'Propietario eliminado.',
    'prop.error.crear'      => 'No se pudo crear el propietario.',
    'prop.error.actualizar' => 'No se pudo actualizar el propietario.',
    'prop.error.eliminar'   => 'No se pudo eliminar el propietario.',

    // Horarios de trabajo
    'hor.creado'           => 'Horario de trabajo creado correctamente.',
    'hor.actualizado'      => 'Horario de trabajo actualizado.',
    'hor.eliminado'        => 'Horario de trabajo eliminado.',
    'hor.error.crear'      => 'No se pudo crear el horario de trabajo.',
    'hor.error.actualizar' => 'No se pudo actualizar el horario de trabajo.',
    'hor.error.eliminar'   => 'No se pudo eliminar el horario de trabajo.',

    // Notas por contacto
    'nota.creada'             => 'Nota creada correctamente.',
    'nota.actualizada'        => 'Nota actualizada.',
    'nota.eliminada'          => 'Nota eliminada.',
    'nota.error.crear'        => 'No se pudo crear la nota.',
    'nota.error.actualizar'   => 'No se pudo actualizar la nota.',
    'nota.error.eliminar'     => 'No se pudo eliminar la nota.',

    // Solicitudes de cambio
    'sc.creada'            => 'Solicitud registrada. Queda en revisión.',
    'sc.error.crear'       => 'No se pudo registrar la solicitud.',
    'sc.error.datos'       => 'Completa todos los campos de la solicitud.',
    'sc.error.propietario' => 'El propietario indicado no existe.',
    'sc.eliminada'      => 'Solicitud cancelada.',
    'sc.error.eliminar' => 'No se pudo cancelar la solicitud.',
    'sc.error.permiso'  => 'No puedes cancelar esta solicitud.',
    'sc.aprobada'        => 'Solicitud aprobada correctamente.',
    'sc.rechazada'       => 'Solicitud rechazada correctamente.',
    'sc.error.aprobar'   => 'No se pudo aprobar la solicitud.',
    'sc.error.rechazar'  => 'No se pudo rechazar la solicitud.',

    // Respaldos BD
    'resp.ok'            => 'Respaldo creado correctamente.',
    'resp.error'         => 'No se pudo crear el respaldo.',
    'rest.ok'            => 'Base de datos restaurada correctamente.',
    'rest.error'         => 'No se pudo restaurar la base de datos.',
    'rest.error.archivo' => 'El archivo de respaldo no existe o no es válido.',
    'resp.eliminado' => 'Respaldo eliminado correctamente.',
    'resp.error.eliminar' => 'No se pudo eliminar el respaldo.',

];

/* Desempacar flash (usando tus helpers actuales) */
$__flash      = tomar_flash(); // puede ser null
$flash_nivel  = $__flash['nivel']  ?? null;
$flash_codigo = $__flash['codigo'] ?? null;

/* Resuelve el texto; si no está en catálogo, usa el código como fallback */
if ($flash_codigo) {
    $flash_texto = $MENSAJES[$flash_codigo] ?? $flash_codigo;
} else {
    $flash_texto = null;
}

/* PRG: errores y viejo */
$errores        = $_SESSION['errores']       ?? [];
$errores_origen = $_SESSION['erroresOrigen'] ?? null;
$viejo          = $_SESSION['viejo']         ?? [];

/* Limpia PRG para que no se repita */
unset($_SESSION['errores'], $_SESSION['erroresOrigen'], $_SESSION['viejo']);