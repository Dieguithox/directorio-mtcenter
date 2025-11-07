<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/flash_helpers.php';
require_once __DIR__ . '/../Model/BD.php';

require_once __DIR__ . '/../Model/Usuario.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Model/Bitacora.php';
require_once __DIR__ . '/../Model/PasswordReset.php';

/* Librerias para el correo electronico */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class AppControlador {
    private const SQLSTATE_UNIQUE_VIOLATION = '23000'; // correo unico
    private const TIEMPO_INACTIVIDAD_SEGUNDOS = 7200; // Despues de 2 hora de inactividad se cierra la sesion
    /* Inicia sesion si no existe y aplica expiracion por inactividad. */
    
    protected function asegurarSesion(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // si estoy en el login NO hagas cierre por inactividad ni redirecciones
    $accionActual = $_GET['accion'] ?? '';
    if ($accionActual === 'auth.form') {
        return;
    }

    $this->cerrarSesionInactiva();
}


    /* Cierra sesion automaticamente si hubo inactividad mayor al umbral. */
    protected function cerrarSesionInactiva(): void {
        $ahora = time();
        if (empty($_SESSION['usuario'])) {
            $_SESSION['__ultimo_movimiento'] = $ahora;
            return;
        }
        if (!isset($_SESSION['__ultimo_movimiento'])) {
            $_SESSION['__ultimo_movimiento'] = $ahora;
            return;
        }
        $segundosInactivos = $ahora - (int)$_SESSION['__ultimo_movimiento'];
        if ($segundosInactivos > self::TIEMPO_INACTIVIDAD_SEGUNDOS) {
            try {
                $bitacora = new Bitacora();
                $uid = $_SESSION['usuario']['id'] ?? null;
                $bitacora->registrar(
                    'cierre_sesion', 'auth', null, null,
                    'Cierre de sesion por inactividad', $uid ? (int)$uid : null
                );
            } catch (\Throwable $e) {}
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', $ahora - 3600, $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']);
            }
            session_destroy();
            session_start();
            session_regenerate_id(true);
            poner_flash('danger', 'auth.inactividad');
            header('Location: ' . BASE_URL . '/config/app.php?accion=auth.form');
            exit;
        }
        $_SESSION['__ultimo_movimiento'] = $ahora;
    }

    /* guarda errores y datos viejos en sesion y vuelve al formulario. */
    private function failBack(string $origen, array $errores, array $old = []): void {
        $this->asegurarSesion();
        fallar_y_volver($origen, $errores, $old);
        header('Location: ' . BASE_URL . '/config/app.php?accion=auth.form');
        exit;
    }

    /* Muestra la vista de dashboard segun el rol guardado en sesion. */
    public function dashboard(): void {
        $this->asegurarSesion();
        if (empty($_SESSION['usuario'])) {
            header('Location: ' . BASE_URL . '/config/app.php?accion=auth.form');
            exit;
        }
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? 'consultor';
        switch ($rol) {
            case 'admin':
                require __DIR__ . '/../View/admin/dashboardAdmin.php';
                break;
            case 'editor':
                require __DIR__ . '/../View/editor/dashboardEditor.php';
                break;
            default:
                require __DIR__ . '/../View/consultor/dashboardConsultor.php';
                break;
        }
    }

    /* Acciones de autenticacion*/
    public function iniciarSesion(): void {
        $this->asegurarSesion();
        $email       = trim($_POST['email'] ?? '');
        $contrasenia = $_POST['contrasenia'] ?? '';
        if ($email === '' || $contrasenia === '') {
            $this->failBack('login', ['Correo y contraseña son obligatorios.'], ['email' => $email]);
        }
        $usuarioModel = new Usuario();
        $bitacora     = new Bitacora();
        $u = $usuarioModel->buscarPorEmail($email);
        if ($u && password_verify($contrasenia, $u['contrasenia'])) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = [
                'id'          => $u['idUsuario'],
                'nombre'      => $u['nombre'],
                'email'       => $u['email'],
                'tipoUsuario' => $u['tipoUsuario'],
            ];
            $_SESSION['__ultimo_movimiento'] = time();
            // Bitácora
            $bitacora->registrar(
                'inicio_sesion_ok', 'auth', null, null,
                'Inicio de sesion correcto', (int)$u['idUsuario']
            );
            //poner_flash('success', 'auth.login.ok');
            header('Location: ' . BASE_URL . '/config/app.php?accion=home.dashboard');
            exit;
        }
        // Bitácora: intento fallido
        $bitacora->registrar(
            'inicio_sesion_fallido', 'auth', null, null,
            'Intento de login fallido para ' . $email, null
        );
        // PRG con errores de validación
        $this->failBack('login', ['Credenciales inválidas.'], ['email' => $email]);
    }

    /* Registrar un nuevo usuario en el sistema.
Descripción: 
Este método valida las entradas del formulario de registro,
asegura la sesión activa, y comunica la vista con el modelo
para registrar un nuevo usuario en la base de datos.
    Entradas:
 $_POST['nombre']: string  → Nombre completo del usuario.
 *  - $_POST['email']:  string  → Correo corporativo (@mtcenter.com.mx).
 *  - $_POST['contrasenia']: string → Contraseña en texto plano.
 *
 * Salidas:
 *  - Redirección con mensaje de éxito o error.
 *  - Registro en bitácora del sistema. */
    public function registrarUsuario(): void {
        $this->asegurarSesion(); // Función de inactividad en cada acción
        // Entradas
        $nombre = trim($_POST['nombre'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? '')); 
        $contrasenia = $_POST['contrasenia'] ?? '';
        $old = ['nombre' => $nombre, 'email' => $email];
        // Validaciones de 
        if ($nombre === '' || $email === '' || $contrasenia === '') {
            $this->failBack('register', ['Todos los campos son obligatorios.'], $old);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->failBack('register', ['Correo inválido.'], $old);
        }
        if (!preg_match('/@mtcenter\.com\.mx$/i', $email)) {
            $this->failBack('register', ['Usa tu correo corporativo (@mtcenter.com.mx).'], $old);
        }
        if (strlen($contrasenia) < 7) {
            $this->failBack('register', ['La contraseña debe tener al menos 7 caracteres.'], $old);
        }
        // Interacción con Modelo y Bitácora
        $usuarioModel = new Usuario();
        $bitacora     = new Bitacora();
        try {
            $ok = $usuarioModel->registrarUsuario($nombre, $email, $contrasenia, 'consultor');
            if ($ok) {
                $idNuevo = (int)BD::pdo()->lastInsertId();
                // Alta en Bitácora, redirección y mensajes 
                $bitacora->registrar('registro', 'auth', 'usuario', (string)$idNuevo,'Registro de nuevo usuario: ' . $email, $idNuevo);
                poner_flash('success', 'auth.registro.ok');
                header('Location: ' . BASE_URL . '/config/app.php?accion=auth.form&t=register');
                exit;
            }
            // Interacción fallida
            $this->failBack('register', ['No se pudo registrar. Intenta de nuevo.'], $old);
        } catch (PDOException $e) {
            // SQLstate 23000 para evitar correos duplicados, también en BD
            $msg = ($e->getCode() === self::SQLSTATE_UNIQUE_VIOLATION)
                ? 'Ese correo ya está en uso.'
                : 'Error al registrar. Intenta más tarde.';
            $this->failBack('register', [$msg], $old);
        }
    }

    /* Logout */
    public function cerrarSesion(): void {
        $this->asegurarSesion();
        $bitacora = new Bitacora();
        $uid = $_SESSION['usuario']['id'] ?? null;
        $bitacora->registrar(
            'cierre_sesion', 'auth', null, null,
            'Cierre de sesion manual', $uid ? (int)$uid : null
        );
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']);
        }
        session_destroy();
        session_start();
        session_regenerate_id(true);
        header('Location: ' . BASE_URL . '/config/app.php?accion=auth.form');
        exit;
    }

    /* Recuperacion de contraseña */
    public function enviarEnlaceRecuperacion(): void {
        $this->asegurarSesion();
        $email = trim($_POST['email'] ?? '');
        poner_flash('primary', 'auth.reset.enlace');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . BASE_URL . '/config/app.php?accion=auth.forgot.form');
            exit;
        }
        $usuarioModel  = new Usuario();
        $passwordReset = new PasswordReset();
        $bitacora      = new Bitacora();
        $u = $usuarioModel->buscarPorEmail($email);
        if (!$u) {
            header('Location: ' . BASE_URL . '/config/app.php?accion=auth.forgot.form');
            exit;
        }
        $token     = bin2hex(random_bytes(32));
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        $expiraAt  = date('Y-m-d H:i:s', time() + RESET_TTL_SECONDS);
        $passwordReset->crear((int)$u['idUsuario'], $tokenHash, $expiraAt);
        // Bitacora: inicio de recuperacion
        $bitacora->registrar(
            'actualizacion', 'auth', 'recuperarPassword', null,
            'Solicitud de recuperacion enviada a ' . $email, (int)$u['idUsuario']
        );
        $resetUrl = APP_URL . '/config/app.php?accion=auth.reset.form'
                . '&uid=' . urlencode((string)$u['idUsuario'])
                . '&token=' . urlencode($token);
        $html   = $this->renderEmail('reset_password', ['nombre' => $u['nombre'], 'resetUrl' => $resetUrl]);
        $asunto = 'Recupera tu contraseña';
        $this->enviarCorreoHTML($email, $asunto, $html);
        header('Location: ' . BASE_URL . '/config/app.php?accion=auth.forgot.form');
        exit;
    }

    /* Muestra el formulario de nueva contraseña si el token es valido. */
    public function mostrarFormularioReset(): void {
        $this->asegurarSesion();
        $uid   = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
        $token = $_GET['token'] ?? '';
        if ($uid <= 0 || $token === '') {
            $_SESSION['errores'] = ['Enlace invalido.'];
            $_SESSION['erroresOrigen'] = 'reset';
            header('Location: ' . BASE_URL . '/config/app.php?accion=auth.form');
            exit;
        }
        $passwordReset = new PasswordReset();
        $tokens = $passwordReset->tokensVigentesPorUsuario($uid);
        $resetId = null;
        foreach ($tokens as $row) {
            if (password_verify($token, $row['token_hash'])) {
                $resetId = (int)$row['idRecuperarP'];
                break;
            }
        }
        if (!$resetId) {
            $_SESSION['errores'] = ['El enlace de recuperación es inválido o ha caducado.'];
            $_SESSION['erroresOrigen'] = 'reset';
            header('Location: ' . BASE_URL . '/config/app.php?accion=auth.form');
            exit;
        }
        $reset_uid   = $uid;
        $reset_token = $token;
        require __DIR__ . '/../View/auth/reset.php';
    }

    /* Aplica la nueva contraseña si el token es valido (POST). */
    public function aplicarNuevaContrasenia(): void {
        $this->asegurarSesion();
        $uid   = isset($_POST['uid']) ? (int)$_POST['uid'] : 0;
        $token = $_POST['token'] ?? '';
        $p1    = $_POST['contrasenia']  ?? '';
        $p2    = $_POST['contrasenia2'] ?? '';
        if ($uid <= 0 || $token === '') {
            $_SESSION['errores'] = ['Solicitud inválida.'];
            $_SESSION['erroresOrigen'] = 'reset';
            header('Location: ' . BASE_URL . '/config/app.php?accion=auth.form');
            exit;
        }
        if ($p1 === '' || strlen($p1) < 7 || $p1 !== $p2) {
            $_SESSION['errores'] = ['La contraseña es inválida o no coincide (mínimo 7 caracteres).'];
            $_SESSION['erroresOrigen'] = 'reset';
            header('Location: ' . BASE_URL . '/config/app.php?accion=auth.reset.form&uid='
                . urlencode((string)$uid) . '&token=' . urlencode($token));
            exit;
        }
        $usuarioModel  = new Usuario();
        $passwordReset = new PasswordReset();
        $bitacora      = new Bitacora();
        // Verificar token
        $tokens = $passwordReset->tokensVigentesPorUsuario($uid);
        $resetId = null;
        foreach ($tokens as $row) {
            if (password_verify($token, $row['token_hash'])) {
                $resetId = (int)$row['idRecuperarP'];
                break;
            }
        }
        if (!$resetId) {
            $_SESSION['errores'] = ['El enlace de recuperación es inválido o ha caducado.'];
            $_SESSION['erroresOrigen'] = 'reset';
            header('Location: ' . BASE_URL . '/config/app.php?accion=auth.form');
            exit;
        }
        // Transaccion: actualizar pass + marcar tokens
        $pdo = BD::pdo();
        $pdo->beginTransaction();
        try {
            $hash = password_hash($p1, PASSWORD_DEFAULT);
            $usuarioModel->actualizarPassword($uid, $hash);
            $passwordReset->marcarUsado($resetId);
            $passwordReset->invalidarOtros($uid, $resetId);
            $pdo->commit();
            // Bitacora: actualizacion de contraseña
            $bitacora->registrar(
                'actualizacion', 'auth', 'usuario', (string)$uid,
                'password actualizada via recuperacion', $uid
            );
            poner_flash('success', 'auth.reset.ok');
            header('Location: ' . BASE_URL . '/config/app.php?accion=auth.form');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['errores'] = ['No pudimos actualizar la contraseña. Intenta de nuevo.'];
            $_SESSION['erroresOrigen'] = 'reset';
            header('Location: ' . BASE_URL . '/config/app.php?accion=auth.form');
            exit;
        }
    }

    /* Email (SMTP) y plantillas  Envia un correo HTML con PHPMailer + SMTP */
    private function enviarCorreoHTML(string $para, string $asunto, string $html): bool {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE; // 'tls'
            $mail->Port       = SMTP_PORT;
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NOMBRE);
            $mail->addAddress($para);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $asunto;
            $mail->Body    = $html;
            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            return false;
        }
    }

    /* Renderiza una plantilla de email y devuelve el HTML. */
    private function renderEmail(string $nombrePlantilla, array $vars = []): string
    {
        $ruta = __DIR__ . '/../View/emails/' . $nombrePlantilla . '.php';
        if (!is_file($ruta)) {
            return '';
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        include $ruta;
        return (string) ob_get_clean();
    }
}