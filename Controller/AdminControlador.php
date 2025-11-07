<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/flash_helpers.php';

require_once __DIR__ . '/../Model/Bitacora.php';
require_once __DIR__ . '/../Model/Extension.php';
require_once __DIR__ . '/../Model/Usuario.php';
require_once __DIR__ . '/../Model/Area.php';
require_once __DIR__ . '/../Model/Puesto.php';
require_once __DIR__ . '/../Model/Propietario.php';
require_once __DIR__ . '/../Model/HorarioTrabajo.php';
require_once __DIR__ . '/../Model/Nota.php';
require_once __DIR__ . '/../Model/SolicitudCambio.php';
require_once __DIR__ . '/../Model/RespaldoBD.php';

require_once __DIR__ . '/../Controller/AppControlador.php';
require_once __DIR__ . '/../Controller/NotificacionControlador.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class AdminControlador extends AppControlador {
    protected function asegurarAdmin(): void {
    $accionActual = $_GET['accion'] ?? '';
    if ($accionActual === 'auth.form') {
        return;
    }
    $this->asegurarSesion();
    if (empty($_SESSION['usuario'])) {
        poner_flash('danger', 'auth.debes_iniciar');
        header('Location: ' . BASE_URL . '/config/app.php?accion=auth.form');
        exit;
    }
    $rol = $_SESSION['usuario']['tipoUsuario'] ?? 'consultor';
    if ($rol !== 'admin' && $rol !== 'editor' && $rol !== 'consultor') {
        poner_flash('danger', 'auth.permiso_denegado');
        header('Location: ' . BASE_URL . '/config/app.php?accion=home.dashboard');
        exit;
    }
}
    /* === EXTENSIONES === */
    public function extensionesListar(): void {
        $this->asegurarAdmin();
        $extensiones = (new Extension())->todas();
        require __DIR__ . '/../View/admin/extensiones.php';
    }


    public function extensionesCrear(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=extensiones.listar');
            exit;
        }
        $numero = trim($_POST['numero'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $errores = [];
        if ($numero === '') $errores[] = 'El número es obligatorio.';
        if (!preg_match('/^[0-9]{1,3}$/', $numero)) $errores[] = 'El número debe tener de 1 a 3 dígitos.';
        $modelo = new Extension();
        if ($modelo->existeNumero($numero)) $errores[] = 'Ya existe otra extensión con ese número.';
        if ($errores) {
            fallar_y_volver('extensiones', $errores, ['numero' => $numero, 'descripcion' => $descripcion]);
            header('Location: ' . BASE_URL . '/config/app.php?accion=extensiones.listar');
            exit;
        }
        $ok = $modelo->crear($numero, $descripcion);
        if ($ok) {
            (new Bitacora())->registrar('creacion', 'extensiones', 'extension', null, 'Alta de extensión ' . $numero, (int)$_SESSION['usuario']['id']);
            poner_flash('success', 'ext.creada');
        } else {
            poner_flash('danger', 'ext.error.crear');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=extensiones.listar');
        exit;
    }

    public function extensionesActualizar(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=extensiones.listar');
            exit;
        }
        $id = (int)($_POST['idExtension'] ?? 0);
        $numero = trim($_POST['numero'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $errores = [];
        if ($id <= 0) $errores[] = 'ID inválido.';
        if ($numero === '') $errores[] = 'El número es obligatorio.';
        if (!preg_match('/^[0-9]{1,3}$/', $numero)) $errores[] = 'El número debe tener de 1 a 3 dígitos.';
        $modelo = new Extension();
        if ($modelo->existeNumero($numero, $id)) $errores[] = 'Ya existe otra extensión con ese número.';
        if ($errores) {
            fallar_y_volver('extensiones', $errores, ['idExtension' => $id, 'numero' => $numero, 'descripcion' => $descripcion]);
            header('Location: ' . BASE_URL . '/config/app.php?accion=extensiones.listar');
            exit;
        }
        $ok = $modelo->actualizar($id, $numero, $descripcion);
        if ($ok) {
            (new Bitacora())->registrar('actualizacion', 'extensiones', 'extension', (string)$id, 'Actualización de extensión ' . $numero, (int)$_SESSION['usuario']['id']);
            poner_flash('primary', 'ext.actualizada');
        } else {
            poner_flash('danger', 'ext.error.actualizar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=extensiones.listar');
        exit;
    }

    public function extensionesEliminar(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=extensiones.listar');
            exit;
        }
        $id = (int)($_POST['idExtension'] ?? 0);
        if ($id <= 0) {
            poner_flash('danger', 'ext.error.eliminar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=extensiones.listar');
            exit;
        }
        $ok = (new Extension())->borrar($id);
        if ($ok) {
            (new Bitacora())->registrar('eliminacion', 'extensiones', 'extension', (string)$id, 'Eliminación de extensión', (int)$_SESSION['usuario']['id']);
            poner_flash('danger', 'ext.eliminada');
        } else {
            poner_flash('danger', 'ext.error.eliminar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=extensiones.listar');
        exit;
    }

    /* USUARIOS */
    /* VALIDACIÓN USUARIOS */
private function validarUsuarioPost(array $post, bool $esCreacion): array {
        $errores = [];
        // Normalización segura
        $id     = isset($post['idUsuario']) ? (int)$post['idUsuario'] : 0;
        $nombre = trim(preg_replace('/\s+/', ' ', $post['nombre'] ?? ''));
        $email  = strtolower(trim($post['email'] ?? ''));
        $rol    = trim($post['rol'] ?? 'consultor');
        $pass   = $post['password'] ?? '';
        // Nombre: solo letras + espacios, 3–80
        if ($nombre === '') {
            $errores[] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 80) {
            $errores[] = 'El nombre debe tener entre 3 y 80 caracteres.';
        } elseif (!preg_match("/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{3,80}$/u", $nombre)) {
            $errores[] = 'El nombre solo puede contener letras y espacios.';
        }
        // Email: formato y dominio mtcenter.com.mx
        if ($email === '') {
            $errores[] = 'El correo es obligatorio.';
        } elseif (mb_strlen($email) > 120) {
            $errores[] = 'El correo no debe exceder 120 caracteres.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Correo inválido.';
        } elseif (!preg_match('/^[a-z0-9._%+\-]+@mtcenter\.com\.mx$/i', $email)) {
            $errores[] = 'El correo debe ser del dominio @mtcenter.com.mx.';
        } else {
            $m = new Usuario();
            if ($esCreacion && $m->existeEmail($email)) {
                $errores[] = 'El correo ya está registrado.';
            }
            if (!$esCreacion && $m->existeEmail($email, $id)) {
                $errores[] = 'Ese correo ya se usa en otro usuario.';
            }
        }
        // Rol
        if (!in_array($rol, ['consultor','editor','admin'], true)) {
            $errores[] = 'Rol inválido.';
        }
        // Password
        if ($esCreacion) {
            if ($pass === '' || strlen(trim($pass)) < 7) {
                $errores[] = 'La contraseña es obligatoria y debe tener al menos 7 caracteres.';
            }
        } else {
            if ($id <= 0) {
                $errores[] = 'ID inválido.';
            }
            if ($pass !== '') {
                if (strlen(trim($pass)) < 7) {
                    $errores[] = 'Si deseas cambiar la contraseña, usa al menos 7 caracteres.';
                }
                // Evita igual a nombre o email
                $nombreNorm = mb_strtolower($nombre);
                if (mb_strtolower($pass) === $nombreNorm || mb_strtolower($pass) === $email) {
                    $errores[] = 'La contraseña no debe coincidir con el nombre o el correo.';
                }
            }
        }
        return [$errores, [
            'idUsuario' => $id,
            'nombre'    => $nombre,
            'email'     => $email,
            'rol'       => $rol,
            'password'  => $pass,
        ]];
    }

    public function usuariosListar(): void {
        $this->asegurarAdmin();
        $q = trim($_GET['q'] ?? '');
        $modelo = new Usuario();
        $usuarios = $modelo->todos($q);
        $errores = $_SESSION['errores']       ?? [];
        $errores_origen = $_SESSION['erroresOrigen'] ?? null;
        $viejo = $_SESSION['viejo']         ?? [];
        unset($_SESSION['errores'], $_SESSION['erroresOrigen'], $_SESSION['viejo']);
        require __DIR__ . '/../View/admin/usuario.php';
    }


    public function usuariosCrear(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=usuarios.listar'); exit;
        }
        list($errores, $datos) = $this->validarUsuarioPost($_POST, true);
        if ($errores) {
            fallar_y_volver('usuarios', $errores, [
                'nombre' => $datos['nombre'],
                'email'  => $datos['email'],
                'rol'    => $datos['rol'],
            ]);
            header('Location: ' . BASE_URL . '/config/app.php?accion=usuarios.listar'); exit;
        }

        $modelo = new Usuario();
        $ok = $modelo->crear($datos['nombre'], $datos['email'], $datos['password'], $datos['rol']);
        if ($ok) {
            (new Bitacora())->registrar('creacion', 'usuarios', 'usuario', null,
                "Alta de usuario {$datos['email']}", (int)$_SESSION['usuario']['id']);
            poner_flash('success', 'usr.creado');
        } else {
            poner_flash('danger', 'usr.error.crear');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=usuarios.listar'); exit;
    }

    public function usuariosActualizar(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=usuarios.listar'); exit;
        }
        list($errores, $datos) = $this->validarUsuarioPost($_POST, false);
        if ($errores) {
            fallar_y_volver('usuarios', $errores, [
                'idUsuario' => $datos['idUsuario'],
                'nombre'    => $datos['nombre'],
                'email'     => $datos['email'],
                'rol'       => $datos['rol'],
            ]);
            header('Location: ' . BASE_URL . '/config/app.php?accion=usuarios.listar'); exit;
        }
        $modelo = new Usuario();
        $ok = $modelo->actualizar(
            (int)$datos['idUsuario'],
            $datos['nombre'],
            $datos['email'],
            $datos['rol'],
            $datos['password'] !== '' ? $datos['password'] : null
        );
        if ($ok) {
            (new Bitacora())->registrar('actualizacion', 'usuarios', 'usuario', (string)$datos['idUsuario'],
                "Actualización de usuario {$datos['email']}", (int)$_SESSION['usuario']['id']);
            poner_flash('primary', 'usr.actualizado');
        } else {
            poner_flash('danger', 'usr.error.actualizar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=usuarios.listar'); exit;
    }

    public function usuariosEliminar(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=usuarios.listar');
            exit;
        }
        $id = (int)($_POST['idUsuario'] ?? 0);
        if ($id <= 0) {
            poner_flash('danger', 'usr.error.eliminar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=usuarios.listar');
            exit;
        }
        $ok = (new Usuario())->eliminar($id);
        if ($ok) {
            (new Bitacora())->registrar('eliminacion', 'usuarios', 'usuario', (string)$id, 'Eliminación de usuario', (int)$_SESSION['usuario']['id']);
            poner_flash('danger', 'usr.eliminado');
        } else {
            poner_flash('danger', 'usr.error.eliminar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=usuarios.listar');
        exit;
    }

    /* === AREAS === */
    public function areasListar(): void {
        $this->asegurarAdmin();
        $mArea = new Area();
        $areas = $mArea->todas();
        // Mapa de correos por área para que el modal y el repeater se llenen sin recargar
        $mapCorreos = [];
        foreach ($areas as $a) {
            $mapCorreos[(int)$a['idArea']] = $mArea->correosSolo((int)$a['idArea']);
        }
        $areaId = isset($_GET['areaId']) ? (int)$_GET['areaId'] : 0;
        $correosDeArea = ($areaId > 0) ? $mArea->correosPorArea($areaId) : [];
        require __DIR__ . '/../View/admin/areas.php';
    }

    public function areasCrear(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=areas.listar');
            exit;
        }
        $nombre = trim($_POST['nombre'] ?? '');
        $email  = trim($_POST['email']  ?? '');
        $desc   = trim($_POST['descripcion'] ?? '');
        // recoge y valida correos_extra[]
        $rawExtra = $_POST['correos_extra'] ?? [];
        $correos_extra = array_values(array_filter(array_map('trim', (array)$rawExtra), fn($v) => $v !== ''));
        $errores = [];
        foreach ($correos_extra as $c) {
            if (!filter_var($c, FILTER_VALIDATE_EMAIL)) $errores[] = "Correo adicional inválido: $c";
        }
        if ($nombre === '') $errores[] = 'El nombre del área es obligatorio.';
        if (mb_strlen($nombre) > 50) $errores[] = 'El nombre no debe exceder 50 caracteres.';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo del área inválido.';
        if (mb_strlen($desc) > 255) $errores[] = 'La descripción no debe exceder 255 caracteres.';
        $mArea = new Area();
        if ($nombre !== '' && $mArea->existeNombre($nombre)) $errores[] = 'Ya existe un área con ese nombre.';
        if ($errores) {
            // PRG
            fallar_y_volver('areas', $errores, ['nombre' => $nombre, 'email' => $email, 'descripcion' => $desc, 'correos_extra' => $correos_extra]);
            header('Location: ' . BASE_URL . '/config/app.php?accion=areas.listar');
            exit;
        }
        //crear() ahora devuelve el ID
        $newId = $mArea->crear($nombre, $email, $desc);
        if ($newId > 0) {
            // Inserta correos adicionales
            foreach ($correos_extra as $c) {
                $mArea->agregarCorreo($newId, $c);
            }
            (new Bitacora())->registrar('creacion', 'areas', 'area', (string)$newId, 'Alta de área ' . $nombre, (int)$_SESSION['usuario']['id']);
            poner_flash('success', 'area.creada');
        } else {
            poner_flash('danger', 'area.error.crear');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=areas.listar');
        exit;
    }

    public function areasActualizar(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=areas.listar');
            exit;
        }
        $id = (int)($_POST['idArea'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email']  ?? '');
        $desc = trim($_POST['descripcion'] ?? '');
        //recoge y valida correos_extra[]
        $rawExtra = $_POST['correos_extra'] ?? [];
        $correos_extra = array_values(array_filter(array_map('trim', (array)$rawExtra), fn($v) => $v !== ''));
        $errores = [];
        foreach ($correos_extra as $c) {
            if (!filter_var($c, FILTER_VALIDATE_EMAIL)) $errores[] = "Correo adicional inválido: $c";
        }
        if ($id <= 0) $errores[] = 'ID inválido.';
        if ($nombre === '') $errores[] = 'El nombre del área es obligatorio.';
        if (mb_strlen($nombre) > 50) $errores[] = 'El nombre no debe exceder 50 caracteres.';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo del área inválido.';
        if (mb_strlen($desc) > 255) $errores[] = 'La descripción no debe exceder 255 caracteres.';
        $mArea = new Area();
        if ($nombre !== '' && $mArea->existeNombre($nombre, $id)) $errores[] = 'Ya existe otra área con ese nombre.';
        if ($errores) {
            fallar_y_volver('areas', $errores, ['idArea' => $id, 'nombre' => $nombre, 'email' => $email, 'descripcion' => $desc, 'correos_extra' => $correos_extra]);
            header('Location: ' . BASE_URL . '/config/app.php?accion=areas.listar');
            exit;
        }
        $ok = $mArea->actualizar($id, $nombre, $email, $desc);
        if ($ok) {
            $mArea->reemplazarCorreos($id, $correos_extra);
            (new Bitacora())->registrar('actualizacion', 'areas', 'area', (string)$id, 'Actualización de área ' . $nombre, (int)$_SESSION['usuario']['id']);
            poner_flash('primary', 'area.actualizada');
        } else {
            poner_flash('danger', 'area.error.actualizar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=areas.listar');
        exit;
    }

    public function areasEliminar(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=areas.listar');
            exit;
        }
        $id = (int)($_POST['idArea'] ?? 0);
        if ($id <= 0) {
            poner_flash('danger', 'area.error.eliminar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=areas.listar');
            exit;
        }
        $res = (new Area())->borrar($id);
        if ($res === true) {
            (new Bitacora())->registrar('eliminacion', 'areas', 'area', (string)$id, 'Eliminación de área', (int)$_SESSION['usuario']['id']);
            poner_flash('danger', 'area.eliminada');
        } elseif ($res === 'fk') {
            poner_flash('danger', 'area.error.fkpuestos');
        } else {
            poner_flash('danger', 'area.error.eliminar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=areas.listar');
        exit;
    }

    /* == Correos adicionales de área (correoArea) == */
    public function areasCorreoAgregar(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=areas.listar');
            exit;
        }
        $areaId = (int)($_POST['areaId'] ?? 0);
        $correo = trim($_POST['correo'] ?? '');
        $errores = [];
        if ($areaId <= 0) $errores[] = 'Área inválida.';
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo adicional inválido.';
        $mArea = new Area();
        if (!$mArea->existeId($areaId)) $errores[] = 'El área no existe.';
        if ($errores) {
            fallar_y_volver('areas', $errores, ['areaId' => $areaId]);
            header('Location: ' . BASE_URL . '/config/app.php?accion=areas.listar&areaId=' . $areaId);
            exit;
        }
        $ok = $mArea->agregarCorreo($areaId, $correo);
        if ($ok) {
            poner_flash('success', 'area.actualizada');
        } else {
            poner_flash('danger', 'area.error.actualizar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=areas.listar&areaId=' . $areaId);
        exit;
    }

    public function areasCorreoEliminar(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=areas.listar');
            exit;
        }
        $idCorreoArea = (int)($_POST['idCorreoArea'] ?? 0);
        $areaId = (int)($_POST['areaId'] ?? 0);
        if ($idCorreoArea <= 0 || $areaId <= 0) {
            poner_flash('danger', 'area.error.actualizar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=areas.listar');
            exit;
        }
        $ok = (new Area())->eliminarCorreo($idCorreoArea);
        if ($ok) {
            poner_flash('primary', 'area.actualizada');
        } else {
            poner_flash('danger', 'area.error.actualizar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=areas.listar&areaId=' . $areaId);
        exit;
    }

    /* === Funciones PUESTOS === */
    public function puestosListar(): void {
        $this->asegurarAdmin();
        $q = trim($_GET['q'] ?? ''); // buscador único
        $mPuesto = new Puesto();
        $mArea   = new Area();
        $puestos = $mPuesto->todos($q); // trae puestos con join de área
        $areas   = $mArea->todas(); // catálogo
        $errores        = $_SESSION['errores']       ?? [];
        $errores_origen = $_SESSION['erroresOrigen'] ?? null;
        $viejo          = $_SESSION['viejo']         ?? [];
        unset($_SESSION['errores'], $_SESSION['erroresOrigen'], $_SESSION['viejo']);
        require __DIR__ . '/../View/admin/puestos.php';
    }

    public function puestosCrear(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=puestos.listar');
            exit;
        }
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $areaId = (int)($_POST['areaId'] ?? 0);
        $errores = [];
        if ($nombre === '') $errores[] = 'El nombre del puesto es obligatorio.';
        if ($descripcion === '') $errores[] = 'La descripción es obligatoria.';
        if ($areaId <= 0) $errores[] = 'Selecciona un área válida.';
        // Validar que el área exista
        $mArea = new Area();
        if ($areaId > 0 && !$mArea->existeId($areaId)) {
            $errores[] = 'El área seleccionada no existe.';
        }
        if ($errores) {
            fallar_y_volver('puestos', $errores, ['nombre' => $nombre, 'descripcion' => $descripcion, 'areaId' => $areaId]);
            header('Location: ' . BASE_URL . '/config/app.php?accion=puestos.listar');
            exit;
        }
        $ok = (new Puesto())->crear($nombre, $descripcion, $areaId);
        if ($ok) {
            (new Bitacora())->registrar('creacion', 'puestos', 'puesto', null, 'Alta de puesto: ' . $nombre, (int)$_SESSION['usuario']['id']);
            poner_flash('success', 'puesto.creado');
        } else {
            poner_flash('danger', 'puesto.error.crear');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=puestos.listar');
        exit;
    }

    public function puestosActualizar(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=puestos.listar');
            exit;
        }
        $id = (int)($_POST['idPuesto'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $areaId = (int)($_POST['areaId'] ?? 0);
        $errores = [];
        if ($id <= 0) $errores[] = 'ID inválido.';
        if ($nombre === '') $errores[] = 'El nombre del puesto es obligatorio.';
        if ($descripcion === '') $errores[] = 'La descripción es obligatoria.';
        if ($areaId <= 0) $errores[] = 'Selecciona un área válida.';
        $mArea = new Area();
        if ($areaId > 0 && !$mArea->existeId($areaId)) {
            $errores[] = 'El área seleccionada no existe.';
        }
        if ($errores) {
            fallar_y_volver('puestos', $errores, ['idPuesto' => $id, 'nombre' => $nombre, 'descripcion' => $descripcion, 'areaId' => $areaId]);
            header('Location: ' . BASE_URL . '/config/app.php?accion=puestos.listar');
            exit;
        }
        $ok = (new Puesto())->actualizar($id, $nombre, $descripcion, $areaId);
        if ($ok) {
            (new Bitacora())->registrar('actualizacion', 'puestos', 'puesto', (string)$id, 'Actualización de puesto: ' . $nombre, (int)$_SESSION['usuario']['id']);
            poner_flash('primary', 'puesto.actualizado');
        } else {
            poner_flash('danger', 'puesto.error.actualizar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=puestos.listar');
        exit;
    }

    public function puestosEliminar(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=puestos.listar');
            exit;
        }
        $id = (int)($_POST['idPuesto'] ?? 0);
        if ($id <= 0) {
            poner_flash('danger', 'puesto.error.eliminar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=puestos.listar');
            exit;
        }
        $res = (new Puesto())->borrar($id);
        if ($res === true) {
            (new Bitacora())->registrar('eliminacion', 'puestos', 'puesto', (string)$id, 'Eliminación de puesto', (int)$_SESSION['usuario']['id']);
            poner_flash('danger', 'puesto.eliminado');
        } elseif ($res === 'fk') {
            poner_flash('danger', 'puesto.error.fkpropietarios');
        } else {
            poner_flash('danger', 'puesto.error.eliminar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=puestos.listar');
        exit;
    }

    /* === PROPIETARIOS === */
    public function propietariosListar(): void {
        $this->asegurarAdmin();
        $q = trim($_GET['q'] ?? '');
        $mProp = new Propietario();
        $mArea = new Area();
        $mPue  = new Puesto();
        $mExt  = new Extension();
        $propietarios = $mProp->listar($q);
        $areas        = $mArea->todas();
        $puestos      = $mPue->catalogo();
        $extensiones  = $mExt->todas();
        // mapa de correos adicionales para precargar en el modal de Edición
        $mapCorreosProp = [];
        foreach ($propietarios as $p) {
            $mapCorreosProp[(int)$p['idPropietario']] = $mProp->correosSolo((int)$p['idPropietario']);
        }
        unset($_SESSION['errores'], $_SESSION['erroresOrigen'], $_SESSION['viejo']);
        require __DIR__ . '/../View/admin/propietarios.php';
    }

    public function propietariosCrear(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=propietarios.listar');
            exit;
        }
        $data = [
            'nombre'      => trim($_POST['nombre'] ?? ''),
            'apellidoP'   => trim($_POST['apellidoP'] ?? ''),
            'apellidoM'   => trim($_POST['apellidoM'] ?? ''),
            'correo'      => trim($_POST['correo'] ?? ''),
            'areaId'      => ($_POST['areaId'] ?? '') === '' ? null : (int)$_POST['areaId'],
            'puestoId'    => ($_POST['puestoId'] ?? '') === '' ? null : (int)$_POST['puestoId'],
            'extensionId' => ($_POST['extensionId'] ?? '') === '' ? null : (int)$_POST['extensionId'],
        ];
        $correosExtra = array_values(array_filter(array_map('trim', (array)($_POST['correos_extra'] ?? [])), fn($v) => $v !== ''));
        // Validación
        if (empty($data['nombre']) || empty($data['apellidoP']) || empty($data['apellidoM']) || empty($data['puestoId'])) {
            poner_flash('danger', 'prop.error.crear');
            $_SESSION['errores'] = ['Completa los campos obligatorios.'];
            $_SESSION['erroresOrigen'] = 'propietarios';
            $_SESSION['viejo'] = $_POST;
            header('Location: ' . BASE_URL . '/config/app.php?accion=propietarios.listar');
            exit;
        }
        $mProp = new Propietario();
        $idNuevo = $mProp->crear($data);
        if ($idNuevo > 0) {
            foreach ($correosExtra as $c) {
                $mProp->agregarCorreo($idNuevo, $c);
            }
            // Notificación
            NotificacionControlador::nuevoContacto($idNuevo, trim($data['nombre'] . ' ' . $data['apellidoP'] . ' ' . $data['apellidoM']));
            poner_flash('success', 'prop.creado');
        } else {
            poner_flash('danger', 'prop.error.crear');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=propietarios.listar');
        exit;
    }


    public function propietariosActualizar(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=propietarios.listar');
            exit;
        }
        $id = (int)($_POST['idPropietario'] ?? 0);
        if ($id <= 0) {
            poner_flash('danger', 'prop.error.actualizar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=propietarios.listar');
            exit;
        }
        $data = [
            'nombre'      => trim($_POST['nombre'] ?? ''),
            'apellidoP'   => trim($_POST['apellidoP'] ?? ''),
            'apellidoM'   => trim($_POST['apellidoM'] ?? ''),
            'correo'      => trim($_POST['correo'] ?? ''),
            'areaId'      => ($_POST['areaId'] ?? '') === '' ? null : (int)$_POST['areaId'],
            'puestoId'    => ($_POST['puestoId'] ?? '') === '' ? null : (int)$_POST['puestoId'],
            'extensionId' => ($_POST['extensionId'] ?? '') === '' ? null : (int)$_POST['extensionId'],
        ];
        $correosExtra = array_values(array_filter(array_map('trim', (array)($_POST['correos_extra'] ?? [])), fn($v) => $v !== ''));
        $mProp = new Propietario();
        $mProp->actualizar($id, $data);
        $mProp->reemplazarCorreos($id, $correosExtra);
        // Notificación
        (new Bitacora())->registrar('actualizacion', 'propietarios', 'propietario', (string)$id, 'Actualización de propietario', (int)$_SESSION['usuario']['id']);
        NotificacionControlador::modContacto($id, trim($data['nombre'] . ' ' . $data['apellidoP'] . ' ' . $data['apellidoM']));
        poner_flash('primary', 'prop.actualizado');
        header('Location: ' . BASE_URL . '/config/app.php?accion=propietarios.listar');
        exit;
    }

    public function propietariosEliminar(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=propietarios.listar');
            exit;
        }
        $id = (int)($_POST['idPropietario'] ?? 0);
        if ($id <= 0) {
            poner_flash('danger', 'prop.error.eliminar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=propietarios.listar');
            exit;
        }
        $mProp = new Propietario();
        $rowProp = $mProp->buscarPorId($id);
        $nombre = '';
        if ($rowProp) {
            $nombre = trim(($rowProp['nombre'] ?? '') . ' ' . ($rowProp['apellidoP'] ?? '') . ' ' . ($rowProp['apellidoM'] ?? ''));
            $nombre = preg_replace('/\s+/', ' ', $nombre);
        }
        $mProp->eliminar($id);
        // Notificación
        NotificacionControlador::elimContacto($id, $nombre ? "({$nombre})" : "(ID {$id})");
        poner_flash('danger', 'prop.eliminado');
        header('Location: ' . BASE_URL . '/config/app.php?accion=propietarios.listar');
        exit;
    }

    private function validarPropietarioDatos(array $d): array {
        $err = [];
        if ($d['nombre'] === '')     $err[] = 'El nombre es obligatorio.';
        if ($d['apellidoP'] === '')  $err[] = 'El apellido paterno es obligatorio.';
        if ($d['apellidoM'] === '')  $err[] = 'El apellido materno es obligatorio.';
        if ($d['correo'] === '' || !filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) $err[] = 'Correo inválido.';
        if (empty($d['puestoId']))   $err[] = 'Seleccione un puesto.';
        // Validación coherencia área ↔ puesto
        if (!empty($d['areaId']) && !empty($d['puestoId'])) {
            $catalogo = (new Puesto())->catalogo(); // cada item debe traer idPuesto y areaId
            $coherente = false;
            foreach ($catalogo as $p) {
                if ((int)$p['idPuesto'] === (int)$d['puestoId'] && (int)$p['areaId'] === (int)$d['areaId']) {
                    $coherente = true;
                    break;
                }
            }
            if (!$coherente) $err[] = 'El puesto no pertenece al área seleccionada.';
        }
        // Extensión opcional: si viene, debe ser numérica
        if ($d['extensionId'] !== null && $d['extensionId'] !== '' && !is_numeric($d['extensionId'])) {
            $err[] = 'Extensión inválida.';
        }
        return $err;
    }

    /* === Métodos para la gestión de horarios de trabajo === */
    public function horariosListar(): void {
        $this->asegurarAdmin(); // Admin/Editor
        $q = trim($_GET['q'] ?? '');
        $modelo = new Horario();
        $horarios = $modelo->listarAgrupado($q);   
        $propietarios = $modelo->propietariosTodos();
        $errores        = $_SESSION['errores']       ?? [];
        $errores_origen = $_SESSION['erroresOrigen'] ?? null;
        $viejo          = $_SESSION['viejo']         ?? [];
        unset($_SESSION['errores'], $_SESSION['erroresOrigen'], $_SESSION['viejo']);
        require __DIR__ . '/../View/admin/horariosTrabajo.php';
    }

    public function horariosCrear(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar');
            exit;
        }
        $propietarioId = (int)($_POST['propietarioId'] ?? 0);
        $dias = $_POST['dias'] ?? [];
        $he = trim($_POST['horaEntrada'] ?? '');
        $hs = trim($_POST['horaSalida']  ?? '');
        $errores = [];
        if ($propietarioId <= 0) $errores[] = 'Empleado inválido.';
        if (empty($dias))      $errores[] = 'Selecciona al menos un día.';
        if ($he === '' || $hs === '') $errores[] = 'Horas inválidas.';
        if ($he !== '' && $hs !== '' && $he >= $hs) $errores[] = 'La hora de entrada debe ser menor a la de salida.';
        if ($errores) {
            fallar_y_volver('horarios', $errores, ['propietarioId' => $propietarioId, 'dias' => $dias, 'horaEntrada' => $he, 'horaSalida' => $hs]);
            header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar');
            exit;
        }
        $ok = (new Horario())->crearMultiple($propietarioId, $dias, $he, $hs);
        if ($ok) {
            $desc = "Creación de horario de trabajo para el empleado ID: $propietarioId";
            (new Bitacora())->registrar('creacion', 'horarios', 'horario', null, $desc, (int)$_SESSION['usuario']['id']);
            poner_flash('success', 'hor.creado');
        } else {
            poner_flash('danger', 'hor.error.crear');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar');
        exit;
    }

    public function horariosActualizar(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar');
            exit;
        }
        $idRep = (int)($_POST['idHorario'] ?? 0);
        $propietarioId = (int)($_POST['propietarioId'] ?? 0);
        $dias = $_POST['dias'] ?? [];
        $he = trim($_POST['horaEntrada'] ?? '');
        $hs = trim($_POST['horaSalida']  ?? '');
        $old_he = trim($_POST['old_he'] ?? '');
        $old_hs = trim($_POST['old_hs'] ?? '');
        $errores = [];
        if ($idRep <= 0)         $errores[] = 'ID inválido.';
        if ($propietarioId <= 0) $errores[] = 'Empleado inválido.';
        if (empty($dias))      $errores[] = 'Selecciona al menos un día.';
        if ($he === '' || $hs === '') $errores[] = 'Horas inválidas.';
        if ($he !== '' && $hs !== '' && $he >= $hs) $errores[] = 'La hora de entrada debe ser menor a la de salida.';
        if ($old_he === '' || $old_hs === '') $errores[] = 'Faltan horas originales del bloque.';
        if ($errores) {
            fallar_y_volver('horarios', $errores, [
                'idHorario' => $idRep,
                'propietarioId' => $propietarioId,
                'dias' => $dias,
                'horaEntrada' => $he,
                'horaSalida' => $hs,
                'old_he' => $old_he,
                'old_hs' => $old_hs
            ]);
            header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar');
            exit;
        }
        $ok = (new Horario())->actualizarBloque($propietarioId, $dias, $he, $hs, $old_he, $old_hs);
        if ($ok) {
            $desc = "Actualización de horario de Trabajo para el empleado ID: $propietarioId";
            (new Bitacora())->registrar('actualizacion', 'horarios', 'horario', (string)$idRep, $desc, (int)$_SESSION['usuario']['id']);
            poner_flash('primary', 'hor.actualizado');
        } else {
            poner_flash('danger', 'hor.error.actualizar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar');
        exit;
    }

    public function horariosEliminar(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar');
            exit;
        }
        $idRep = (int)($_POST['idHorario'] ?? 0);
        $propietarioId = (int)($_POST['propietarioId'] ?? 0);
        $he = trim($_POST['old_he'] ?? '');
        $hs = trim($_POST['old_hs'] ?? '');
        if ($idRep <= 0 || $propietarioId <= 0 || $he === '' || $hs === '') {
            poner_flash('danger', 'hor.error.eliminar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar');
            exit;
        }
        $ok = (new Horario())->eliminarBloque($propietarioId, $he, $hs);
        if ($ok) {
            $desc = "Eliminación de horario de trabajo para el empleado ID: $propietarioId";
            (new Bitacora())->registrar('eliminacion', 'horarios', 'horario', (string)$idRep, $desc, (int)$_SESSION['usuario']['id']);
            poner_flash('danger', 'hor.eliminado');
        } else {
            poner_flash('danger', 'hor.error.eliminar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar');
        exit;
    }

    /* === NOTAS === */
    public function notasListar(): void {
        $this->asegurarAdmin();
        $q = trim($_GET['q'] ?? '');
        $m  = new Nota();
        $notas = $m->listar($q);
        $propietarios = $m->propietariosTodos();
        // PRG
        $errores        = $_SESSION['errores']       ?? [];
        $errores_origen = $_SESSION['erroresOrigen'] ?? null;
        $viejo          = $_SESSION['viejo']         ?? [];
        unset($_SESSION['errores'], $_SESSION['erroresOrigen'], $_SESSION['viejo']);
        require __DIR__ . '/../View/admin/notas.php';
    }

    public function notasCrear(): void {
        $this->asegurarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
        }
        $propietarioId = (int)($_POST['propietarioId'] ?? 0);
        $texto         = trim($_POST['texto'] ?? '');
        $autorId       = (int)($_SESSION['usuario']['id'] ?? 0);

        $errores = [];
        if ($propietarioId<=0) $errores[]='Selecciona un contacto.';
        if ($texto==='')       $errores[]='La nota no puede estar vacía.';
        if ($autorId<=0)       $errores[]='Sesión inválida.';

        if ($errores) {
            fallar_y_volver('notas', $errores, compact('propietarioId','texto'));
            header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
        }

        $idNuevo = (new Nota())->crear($propietarioId, $texto, $autorId);
        if ($idNuevo>0) {
            (new Bitacora())->registrar('creacion','notas','nota',(string)$idNuevo,'Alta de nota',(int)$_SESSION['usuario']['id']);
            poner_flash('success','Nota creada correctamente.');
        } else {
            poner_flash('danger','No se pudo crear la nota.');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
    }

    public function notasActualizar(): void {
        $this->asegurarAdmin(); 
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
        }
        $idNota = (int)($_POST['idNota'] ?? 0);
        $texto  = trim($_POST['texto'] ?? '');
        $errores = [];
        if ($idNota<=0) $errores[]='ID inválido.';
        if ($texto==='') $errores[]='La nota no puede estar vacía.';
        if ($errores) {
            fallar_y_volver('notas', $errores, ['idNota'=>$idNota, 'texto'=>$texto]);
            header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
        }
        $ok = (new Nota())->actualizar($idNota, $texto);
        if ($ok) {
            (new Bitacora())->registrar('actualizacion','notas','nota',(string)$idNota,'Actualización de nota',(int)$_SESSION['usuario']['id']);
            poner_flash('primary','Nota actualizada.');
        } else {
            poner_flash('danger','No se pudo actualizar la nota.');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
    }

    public function notasEliminar(): void {
        $this->asegurarAdmin(); 
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
        }
        $idNota = (int)($_POST['idNota'] ?? 0);
        if ($idNota<=0) {
            poner_flash('danger','No se pudo eliminar (ID inválido).');
            header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
        }
        $ok = (new Nota())->eliminar($idNota);
        if ($ok) {
            (new Bitacora())->registrar('eliminacion','notas','nota',(string)$idNota,'Eliminación de nota',(int)$_SESSION['usuario']['id']);
            poner_flash('danger','Nota eliminada.');
        } else {
            poner_flash('danger','No se pudo eliminar la nota.');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
    }

    /* SOLICITUDES */
    public function solicitudesCambioListar(): void{
        $this->asegurarAdmin(); // mismo que usas en el resto del controlador
        // filtro opcional ?estado=pendiente|aprobado|rechazado
        $estado = isset($_GET['estado']) ? trim($_GET['estado']) : null;
        $m = new SolicitudCambio();
        $solicitudes = $m->listarTodas($estado);
        // para marcar el menú activo
        $active = 'solicitudes';
        // tu vista específica
        require __DIR__ . '/../View/admin/solicitudesAdmin.php';
    }

    public function solicitudCambioAprobar(): void{
        $this->asegurarAdmin();
        // solo POST
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=admin.solicitudes.cambio');
            exit;
        }
        $id     = (int)($_POST['idSolicitudCambio'] ?? 0);
        $motivo = trim($_POST['motivo_revision'] ?? '');
        if ($id <= 0) {
            poner_flash('danger', 'sc.error.aprobar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=admin.solicitudes.cambio');
            exit;
        }
        $modelo = new SolicitudCambio();
        $ok = $modelo->actualizarEstado($id, 'aprobado', $motivo !== '' ? $motivo : null);
        if ($ok) {
            // mensaje que irá al catálogo de ui.php
            poner_flash('success', 'sc.aprobada');
        } else {
            poner_flash('danger', 'sc.error.aprobar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=admin.solicitudes.cambio');
        exit;
    }

    public function solicitudCambioRechazar(): void{
        $this->asegurarAdmin();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=admin.solicitudes.cambio');
            exit;
        }
        $id     = (int)($_POST['idSolicitudCambio'] ?? 0);
        $motivo = trim($_POST['motivo_revision'] ?? '');
        if ($id <= 0) {
            poner_flash('danger', 'sc.error.rechazar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=admin.solicitudes.cambio');
            exit;
        }
        $m  = new SolicitudCambio();
        $ok = $m->actualizarEstado($id, 'rechazado', $motivo);
        if ($ok) {
            poner_flash('primary', 'sc.rechazada');
        } else {
            poner_flash('danger', 'sc.error.rechazar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=admin.solicitudes.cambio');
        exit;
    }

    /* ======= RESPALDOS Y RESTAURACIÓN ======= */
    public function respaldosListar(): void {
        $this->asegurarAdmin();
        $m = new RespaldoBD();
        $respaldos = $m->listar();
        $active = 'respaldos';
        require __DIR__ . '/../View/admin/respaldos.php';
    }

    public function respaldoEjecutar(): void {
        $this->asegurarAdmin();
        global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
        $nombreUsuario = trim($_POST['nombre_respaldo'] ?? '');
        // MISMA RUTA PARA TODOS
        $dir = dirname(__DIR__) . '/backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
        if ($mysqli->connect_error) {
            poner_flash('danger', 'resp.error.conexion');
            header('Location: ' . BASE_URL . '/config/app.php?accion=admin.respaldos');
            exit;
        }
        $mysqli->set_charset('utf8mb4');
        // armar dump
        $tables = [];
        $res = $mysqli->query("SHOW TABLES");
        while ($row = $res->fetch_array()) {
            $tables[] = $row[0];
        }
        $dump = "SET FOREIGN_KEY_CHECKS=0;\n";
        foreach ($tables as $table) {
            $resCreate = $mysqli->query("SHOW CREATE TABLE `$table`");
            $rowCreate = $resCreate->fetch_assoc();
            $dump .= "\nDROP TABLE IF EXISTS `$table`;\n";
            $dump .= $rowCreate['Create Table'] . ";\n\n";
            $resData = $mysqli->query("SELECT * FROM `$table`");
            if ($resData && $resData->num_rows > 0) {
                while ($fila = $resData->fetch_assoc()) {
                    $cols = array_map(fn($c) => "`$c`", array_keys($fila));
                    $vals = [];
                    foreach ($fila as $v) {
                        $vals[] = is_null($v) ? "NULL" : "'" . $mysqli->real_escape_string($v) . "'";
                    }
                    $dump .= "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
                }
            }
            $dump .= "\n";
        }
        $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
        // nombre
        if ($nombreUsuario !== '') {
            $slug = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombreUsuario);
            $nombreArchivo = $slug . '.sql';
        } else {
            $fecha = date('Ymd_His');
            $nombreArchivo = "respaldo_{$DB_NAME}_{$fecha}.sql";
        }
        $rutaArchivo = $dir . DIRECTORY_SEPARATOR . $nombreArchivo;
        file_put_contents($rutaArchivo, $dump);
        $tam = filesize($rutaArchivo);
        $uid = (int)($_SESSION['usuario']['id'] ?? 0);
        (new RespaldoBD())->registrar($nombreArchivo, $tam, $uid);
        poner_flash('success', 'resp.ok');
        header('Location: ' . BASE_URL . '/config/app.php?accion=admin.respaldos');
        exit;
    }

    public function respaldoDescargar(): void {
        $this->asegurarAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $m   = new RespaldoBD();
        $row = $m->buscarPorId($id);
        if (!$row) {
            poner_flash('danger', 'rest.error.archivo');
            header('Location: ' . BASE_URL . '/config/app.php?accion=admin.respaldos');
            exit;
        }
        // misma ruta que usar para guardar
        $dir  = dirname(__DIR__) . '/backups';
        $file = $dir . '/' . $row['archivo'];
        if (!is_file($file)) {
            poner_flash('danger', 'rest.error.archivo');
            header('Location: ' . BASE_URL . '/config/app.php?accion=admin.respaldos');
            exit;
        }
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="'. $row['archivo'] .'"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    public function respaldoRestaurar(): void {
        $this->asegurarAdmin();
        global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
        $id = (int)($_POST['idRespaldoDB'] ?? 0);
        $m  = new RespaldoBD();
        $row = $m->buscarPorId($id);
        if (!$row) {
            poner_flash('danger', 'rest.error.archivo');
            header('Location: ' . BASE_URL . '/config/app.php?accion=admin.respaldos');
            exit;
        }
        $dir  = dirname(__DIR__) . '/backups';
        $file = $dir . '/' . $row['archivo'];
        if (!is_file($file)) {
            poner_flash('danger', 'rest.error.archivo');
            header('Location: ' . BASE_URL . '/config/app.php?accion=admin.respaldos');
            exit;
        }
        $sql = file_get_contents($file);
        $mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
        if ($mysqli->connect_error) {
            poner_flash('danger', 'rest.error.conexion');
            header('Location: ' . BASE_URL . '/config/app.php?accion=admin.respaldos');
            exit;
        }
        $statements = preg_split('/;\s*\n/', $sql);
        $mysqli->query("SET FOREIGN_KEY_CHECKS=0");
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') continue;
            // si la sentencia menciona la tabla respaldoBD, la saltamos
            if (stripos($stmt, 'respaldoBD') !== false) {
                continue;
            }
            $mysqli->query($stmt);
        }
        $mysqli->query("SET FOREIGN_KEY_CHECKS=1");
        poner_flash('success', 'rest.ok');
        header('Location: ' . BASE_URL . '/config/app.php?accion=admin.respaldos');
        exit;
    }

    public function respaldoEliminar(): void {
        $this->asegurarAdmin();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=admin.respaldos');
            exit;
        }
        $id = (int)($_POST['idRespaldoDB'] ?? 0);
        if ($id <= 0) {
            poner_flash('danger', 'resp.error.id');
            header('Location: ' . BASE_URL . '/config/app.php?accion=admin.respaldos');
            exit;
        }
        $m   = new RespaldoBD();
        $row = $m->buscarPorId($id);
        if (!$row) {
            poner_flash('danger', 'resp.error.no_encontrado');
            header('Location: ' . BASE_URL . '/config/app.php?accion=admin.respaldos');
            exit;
        }
        $dir      = dirname(__DIR__) . '/backups';
        $filePath = $dir . '/' . $row['archivo'];
        if (is_file($filePath)) {
            @unlink($filePath);
        }
        $m->eliminar($id);
        poner_flash('success', 'Respaldo eliminado.');
        header('Location: ' . BASE_URL . '/config/app.php?accion=admin.respaldos');
        exit;
    }

    //*************************** REPORTES ADMIN *************************************/

    public function reportesCatalogoAdmin() {
        $title = 'Reportes del sistema';
        require __DIR__ . '/../View/reportes/vistaReportesAdmin.php';
    }

    public function reporteExtensionesUsadas(): void {
        $this->asegurarAdmin();
        require_once __DIR__ . '/../Model/ConsultaExtension.php';
        $m = new ConsultaExtension();
        $areaId = isset($_GET['areaId']) ? (int)$_GET['areaId'] : null;
        $areas = $m->obtenerAreas();
        $extensiones = $m->obtenerMasUsadas($areaId);
        require __DIR__ . '/../View/reportes/reporteExtensionesUsadasAdmin.php';
    }

    public function reporteExtensionesUsadasPDF(): void {
        // solo editor / admin
        $this->asegurarAdmin();
        require_once __DIR__ . '/../Model/ConsultaExtension.php';
        $m = new ConsultaExtension();
        //leer filtro
        $areaId = isset($_GET['areaId']) && $_GET['areaId'] !== ''
            ? (int)$_GET['areaId']
            : null;
        //traer datos
        $extensiones = $m->obtenerMasUsadas($areaId); 
        $areas = $m->obtenerAreas();
        //nombre del área seleccionada
        $areaNombre = 'Todas las áreas';
        if ($areaId) {
            foreach ($areas as $a) {
                if ((int)$a['idArea'] === $areaId) {
                    $areaNombre = $a['nombre'] ?? $a['nombreArea'] ?? $areaNombre;
                    break;
                }
            }
        }
        //datos adicionales para el PDF
        $logoPath = __DIR__ . '/../View/contenido/img/OPE.png';
        $logoDataUri = '';
        if (file_exists($logoPath)) {
            $logoDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }
        $usuarioNombre = $_SESSION['usuario']['nombre'] ?? 'Usuario del sistema';
        //renderizar vista
        ob_start();
        // nombres que la vista espera
        $EXTENSIONES   = $extensiones;
        $AREA_ID       = $areaId;
        $AREA_NOMBRE   = $areaNombre;
        $LOGO_DATA_URI = $logoDataUri;
        $USUARIO_NOMBRE = $usuarioNombre;
        include __DIR__ . '/../View/reportes/reportes_extensiones_usadas_pdf.php';
        $html = ob_get_clean();
        //dompdf
        require_once __DIR__ . '/../vendor/autoload.php';
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('reporte_extensiones_mas_usadas.pdf', ['Attachment' => false]);
        exit;
    }

    public function reporteEmpleadosPorArea() {
        // solo admin y editor
        $this->asegurarSesion();
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? 'consultor';
        if ($rol !== 'admin' && $rol !== 'editor') {
            poner_flash('danger', 'No tienes permiso para ver este reporte.');
            header('Location: ' . BASE_URL . '/config/app.php?accion=home.dashboard');
            exit;
        }
        require_once __DIR__ . '/../Model/ReporteEmpleadosArea.php';
        $model = new ReporteEmpleadosArea();
        $areas = $model->obtenerTotalesPorArea();
        $title  = 'Reporte: Empleados por área';
        $active = 'reportes';
        require __DIR__ . '/../View/reportes/reporteEmpleadosAreaAdmin.php';
    }

    public function reporteEmpleadosPorAreaPDF() {
        // solo admin y editor
        $this->asegurarSesion();
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? 'consultor';
        if ($rol !== 'admin' && $rol !== 'editor') {
            poner_flash('danger', 'No tienes permiso para generar este PDF.');
            header('Location: ' . BASE_URL . '/config/app.php?accion=home.dashboard');
            exit;
        }
        require_once __DIR__ . '/../Model/ReporteEmpleadosArea.php';
        require_once __DIR__ . '/../vendor/autoload.php';
        $model = new ReporteEmpleadosArea();
        $areas = $model->obtenerTotalesPorArea();
        // logo
        $logoPath = __DIR__ . '/../View/contenido/img/OPE.png';
        $logoDataUri = '';
        if (file_exists($logoPath)) {
            $logoDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }
        $usuarioNombre = $_SESSION['usuario']['nombre'] ?? 'Usuario del sistema';
        //pdf
        ob_start();
        $AREAS = $areas;
        $LOGO_DATA_URI = $logoDataUri;
        $USUARIO_NOMBRE = $usuarioNombre;
        //vista que ya hicimos para empleados:
        include __DIR__ . '/../View/reportes/reportes_empleados_area_pdf.php';
        $html = ob_get_clean();
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('reporte_empleados_por_area.pdf', ['Attachment' => false]);
        exit;
    }

    public function reporteExtensionesGrafica(): void {
        $this->asegurarSesion();
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? 'consultor';
        if ($rol !== 'admin' && $rol !== 'editor') {
            poner_flash('danger', 'No tienes permiso para ver este reporte.');
            header('Location: ' . BASE_URL . '/config/app.php?accion=home.dashboard');
            exit;
        }
        require_once __DIR__ . '/../Model/ConsultaExtension.php';
        $m = new ConsultaExtension();
        $areaId = isset($_GET['areaId']) && $_GET['areaId'] !== '' ? (int)$_GET['areaId'] : null;
        $areas = $m->obtenerAreas();
        $extensiones = $m->obtenerMasUsadasGrafica($areaId);
        // nombre del área seleccionada
        $areaNombre = 'Todas las áreas';
        if ($areaId) {
            foreach ($areas as $a) {
                if ((int)$a['idArea'] === $areaId) {
                    $areaNombre = $a['nombre'];
                    break;
                }
            }
        }
        // para la vista
        require __DIR__ . '/../View/reportes/reporteExtensionesGraficaAdmin.php';
    }

    public function reporteExtensionesGraficaExcel(): void {
        // 1. permisos solo admin o editor
        $this->asegurarSesion();
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? 'consultor';
        if ($rol !== 'admin' && $rol !== 'editor') {
            poner_flash('danger', 'No tienes permiso para exportar este reporte.');
            header('Location: ' . BASE_URL . '/config/app.php?accion=home.dashboard');
            exit;
        }
        // 2. modelo
        require_once __DIR__ . '/../Model/ConsultaExtension.php';
        require_once __DIR__ . '/../vendor/autoload.php';
        $m = new ConsultaExtension();
        $areaId = isset($_GET['areaId']) && $_GET['areaId'] !== '' ? (int)$_GET['areaId'] : null;
        $extensiones = $m->obtenerMasUsadasGrafica($areaId);
        // calcular total para porcentaje
        $totalGeneral = 0;
        foreach ($extensiones as $ex) {
            $totalGeneral += (int)$ex['totalConsultas'];
        }
        // 3. armar spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Extensiones más usadas');
        // título
        $sheet->setCellValue('A1', 'REPORTE: Extensiones más usadas con gráfica');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        // encabezados
        $sheet->setCellValue('A3', 'Extensión');
        $sheet->setCellValue('B3', 'Total de consultas');
        $sheet->setCellValue('C3', 'Porcentaje');
        $sheet->setCellValue('D3', 'Área filtrada');
        $row = 4;
        foreach ($extensiones as $ex) {
            $porcentaje = $totalGeneral > 0
                ? round(($ex['totalConsultas'] * 100) / $totalGeneral, 2)
                : 0;
            $sheet->setCellValue("A{$row}", $ex['extension']);
            $sheet->setCellValue("B{$row}", (int)$ex['totalConsultas']);
            // Excel entiende 0.30 como 30%, no 30
            $sheet->setCellValue("C{$row}", $porcentaje / 100);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('0.00%');
            $sheet->setCellValue("D{$row}", $areaId ? $areaId : 'Todas');
            $row++;
        }
        // autosize
        foreach (['A','B','C','D'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        // si no hay datos, igual generamos el archivo sin gráfica
        if (count($extensiones) > 0) {
            // ====== GRÁFICA ======
            $lastRow = $row - 1; // última fila con datos
            // etiquetas (nombres de extensiones)
            $dataSeriesLabels = [];
            // categorías
            $xAxisTickValues = [
                new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
                    'String',
                    "'Extensiones más usadas'!\$A\$4:\$A\${$lastRow}",
                    null,
                    ($lastRow - 3)
                )
            ];
            // valores (y): total de consultas
            $dataSeriesValues = [
                new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
                    'Number',
                    "'Extensiones más usadas'!\$B\$4:\$B\${$lastRow}",
                    null,
                    ($lastRow - 3)
                )
            ];
            $series = new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
                \PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_PIECHART,
                null,
                range(0, count($dataSeriesValues) - 1),
                $dataSeriesLabels,
                $xAxisTickValues,
                $dataSeriesValues
            );
            $plotArea = new \PhpOffice\PhpSpreadsheet\Chart\PlotArea(null, [$series]);
            $legend = new \PhpOffice\PhpSpreadsheet\Chart\Legend(\PhpOffice\PhpSpreadsheet\Chart\Legend::POSITION_RIGHT, null, false);
            $title = new \PhpOffice\PhpSpreadsheet\Chart\Title('Extensiones más usadas');
            $chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart(
                'chart1',
                $title,
                $legend,
                $plotArea
            );
            // dónde poner la gráfica
            $chart->setTopLeftPosition('F3');
            $chart->setBottomRightPosition('L20');
            $sheet->addChart($chart);
        }
        if (ob_get_length()) {
            ob_end_clean();
        }
        // usamos el writer con gráficas
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="reporte_extensiones_grafica.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    public function reporteEmpleadosAreaGrafica(): void {
        $this->asegurarSesion();
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? 'consultor';
        if ($rol !== 'admin' && $rol !== 'editor') {
            poner_flash('danger', 'No tienes permiso para ver este reporte.');
            header('Location: ' . BASE_URL . '/config/app.php?accion=home.dashboard');
            exit;
        }
        require_once __DIR__ . '/../Model/ReporteEmpleadosArea.php';
        $m = new ReporteEmpleadosArea();
        $areas = $m->obtenerTotalesPorArea();
        $title  = 'Reporte: Empleados por área con gráfica';
        $active = 'reportes';
        // vista que haremos ahorita
        require __DIR__ . '/../View/reportes/reporteEmpleadosAreaGraficaAdmin.php';
    }

    public function reporteEmpleadosAreaGraficaExcel(): void {
        $this->asegurarSesion();
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? 'consultor';
        if ($rol !== 'admin' && $rol !== 'editor') {
            poner_flash('danger', 'No tienes permiso para exportar este reporte.');
            header('Location: ' . BASE_URL . '/config/app.php?accion=home.dashboard');
            exit;
        }
        $m = new ReporteEmpleadosArea();
        $areas = $m->obtenerTotalesPorArea();
        // Armamos excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Empleados por área');
        $sheet->setCellValue('A1', 'REPORTE: Empleados por área con gráfica');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        // encabezados
        $sheet->setCellValue('A3', '#');
        $sheet->setCellValue('B3', 'Área');
        $sheet->setCellValue('C3', 'Empleados');
        $sheet->setCellValue('D3', 'Correo');
        $row = 4;
        $i   = 1;
        foreach ($areas as $a) {
            $sheet->setCellValue("A{$row}", $i++);
            $sheet->setCellValue("B{$row}", $a['nombreArea']);
            $sheet->setCellValue("C{$row}", (int)$a['totalEmpleados']);
            $sheet->setCellValue("D{$row}", $a['correoArea'] ?? '');
            $row++;
        }
        foreach (['A','B','C','D'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        // gráfica de barras en Excel
        $lastRow = $row - 1;
        if ($lastRow >= 4) {
            $xAxisTickValues = [
                new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
                    'String',
                    "'Empleados por área'!\$B\$4:\$B\${$lastRow}",
                    null,
                    ($lastRow - 3)
                )
            ];
            $dataSeriesValues = [
                new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
                    'Number',
                    "'Empleados por área'!\$C\$4:\$C\${$lastRow}",
                    null,
                    ($lastRow - 3)
                )
            ];
            $series = new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
                \PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_BARCHART,
                \PhpOffice\PhpSpreadsheet\Chart\DataSeries::GROUPING_CLUSTERED,
                range(0, count($dataSeriesValues)-1),
                [],
                $xAxisTickValues,
                $dataSeriesValues
            );
            // barras verticales
            $series->setPlotDirection(\PhpOffice\PhpSpreadsheet\Chart\DataSeries::DIRECTION_COL);
            $plotArea = new \PhpOffice\PhpSpreadsheet\Chart\PlotArea(null, [$series]);
            $legend = new \PhpOffice\PhpSpreadsheet\Chart\Legend(\PhpOffice\PhpSpreadsheet\Chart\Legend::POSITION_RIGHT, null, false);
            $title = new \PhpOffice\PhpSpreadsheet\Chart\Title('Empleados por área');
            $chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart(
                'chart_empleados_area',
                $title,
                $legend,
                $plotArea
            );
            // dónde la ponemos
            $chart->setTopLeftPosition('F3');
            $chart->setBottomRightPosition('L20');
            $sheet->addChart($chart);
        }
        // salida limpia
        if (ob_get_length()) {
            ob_end_clean();
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="reporte_empleados_area_grafica.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}