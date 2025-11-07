<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/flash_helpers.php';

require_once __DIR__ . '/../Model/Nota.php';
require_once __DIR__ . '/../Model/Directorio.php';
require_once __DIR__ . '/../Model/Favorito.php';
require_once __DIR__ . '/../Model/Bitacora.php';
require_once __DIR__ . '/../Model/Propietario.php';
require_once __DIR__ . '/../Model/SolicitudCambio.php';

require_once __DIR__ . '/../Controller/AppControlador.php';
require_once __DIR__ . '/../Controller/NotificacionControlador.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ConsultorControlador extends AppControlador {
    private function asegurarConsultor(): void {
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

    public function notasListar(): void {
        $this->asegurarConsultor();
        $q = trim($_GET['q'] ?? '');
        $m = new Nota();
        $notas = $m->listar($q);
        $propietarios = $m->propietariosTodos();
        // PRG
        $errores = $_SESSION['errores']       ?? [];
        $errores_origen = $_SESSION['erroresOrigen'] ?? null;
        $viejo = $_SESSION['viejo']         ?? [];
        unset($_SESSION['errores'], $_SESSION['erroresOrigen'], $_SESSION['viejo']);
        require __DIR__ . '/../View/consultor/notas.php';
    }

    public function notasCrear(): void {
        $this->asegurarConsultor();
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
        }
        $propietarioId = (int)($_POST['propietarioId'] ?? 0);
        $texto = trim($_POST['texto'] ?? '');
        $autorId = (int)($_SESSION['usuario']['id'] ?? 0);

        $errores = [];
        if($propietarioId<=0) $errores[]='Selecciona un contacto.';
        if($texto==='') $errores[]='La nota no puede estar vacía.';
        if($autorId<=0) $errores[]='Sesión inválida.';

        if($errores) {
            fallar_y_volver('notas', $errores, compact('propietarioId','texto'));
            header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
        }

        $idNuevo = (new Nota())->crear($propietarioId, $texto, $autorId);
        if($idNuevo>0) {
            (new Bitacora())->registrar('creacion','notas','nota',(string)$idNuevo,'Alta de nota',(int)$_SESSION['usuario']['id']);
            poner_flash('success','Nota creada correctamente.');
        }else{
            poner_flash('danger','No se pudo crear la nota.');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
    }

    public function notasActualizar(): void {
        $this->asegurarConsultor(); 
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
        }
        $idNota = (int)($_POST['idNota'] ?? 0);
        $texto = trim($_POST['texto'] ?? '');
        $errores = [];
        if($idNota<=0) $errores[]='ID inválido.';
        if($texto==='') $errores[]='La nota no puede estar vacía.';
        if($errores) {
            fallar_y_volver('notas', $errores, ['idNota'=>$idNota, 'texto'=>$texto]);
            header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
        }
        $ok = (new Nota())->actualizar($idNota, $texto);
        if($ok) {
            (new Bitacora())->registrar('actualizacion','notas','nota',(string)$idNota,'Actualización de nota',(int)$_SESSION['usuario']['id']);
            poner_flash('primary','Nota actualizada.');
        }else{
            poner_flash('danger','No se pudo actualizar la nota.');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
    }

    public function notasEliminar(): void {
        $this->asegurarConsultor(); 
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
        }
        $idNota = (int)($_POST['idNota'] ?? 0);
        if($idNota<=0) {
            poner_flash('danger','No se pudo eliminar (ID inválido).');
            header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
        }
        $ok = (new Nota())->eliminar($idNota);
        if($ok) {
            (new Bitacora())->registrar('eliminacion','notas','nota',(string)$idNota,'Eliminación de nota',(int)$_SESSION['usuario']['id']);
            poner_flash('danger','Nota eliminada.');
        }else{
            poner_flash('danger','No se pudo eliminar la nota.');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
    }

    /* Directorio telefónico  */
    public function directorioListar(): void {
        $this->asegurarConsultor();
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
        require_once __DIR__ . '/../Model/Directorio.php';
        require_once __DIR__ . '/../Model/Favorito.php';
        $MDir = new Directorio();
        $porArea = $MDir->listarPorArea($q);
        $usuarioId = (int)($_SESSION['usuario']['id'] ?? 0);
        $MFav = new Favorito();
        $favoritosIds = $usuarioId > 0 ? $MFav->favoritosIds($usuarioId) : [];
        $titulo = 'DIRECTORIO GENERAL';
        $telefonosLine = 'TELÉFONOS: LADA (777) 1234567, 1234567, 1234567';
        $empresaNombre = 'OPERADORA DE PRODUCTOS ELECTRÓNICOS';
        $active = 'directorio';
        require __DIR__ . '/../View/consultor/directorio.php';
    }

    public function favoritosListar(): void {
        $this->asegurarConsultor();
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
        $usuarioId = (int)($_SESSION['usuario']['id'] ?? 0);
        require_once __DIR__ . '/../Model/Favorito.php';
        $MFav = new Favorito();
        $lista = $usuarioId > 0 ? $MFav->listarFavoritos($usuarioId, $q) : [];
        $titulo = 'Contactos Favoritos';
        $active = 'favoritos';
        require __DIR__ . '/../View/consultor/favorito.php';
    }

    public function favoritoToggle(): void {
        $this->asegurarConsultor();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=consultor.directorio');
            exit;
        }
        $usuarioId = (int)($_SESSION['usuario']['id'] ?? 0);
        $propietarioId = (int)($_POST['propietarioId'] ?? 0);
        $redirectTo = (string)($_POST['redirect_to'] ?? (BASE_URL . '/config/app.php?accion=consultor.directorio'));
        if ($usuarioId <= 0 || $propietarioId <= 0) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json', true, 400);
                echo json_encode(['ok' => false, 'msg' => 'Sesión inválida']);
                return;
            }
            poner_flash('danger', 'Solicitud inválida');
            header('Location: ' . $redirectTo);
            exit;
        }
        require_once __DIR__ . '/../Model/Favorito.php';
        $MFav  = new Favorito();
        $added = $MFav->toggle($usuarioId, $propietarioId);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'ok'       => true,
                'favorito' => $added,
            ]);
            return;
        }
        header('Location: ' . $redirectTo);
        exit;
    }

        // === Datos de favoritos para el dashboard del consultor (JSON) ===
    public function favoritosJson(): void{
        $this->asegurarConsultor();
        $usuarioId = (int)($_SESSION['usuario']['id'] ?? 0);
        if ($usuarioId <= 0) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Sesión inválida']);
            return;
        }
        try {
            $pdo = BD::pdo();
            $pdo->exec("SET NAMES utf8mb4");
            // cuenta los favoritos del usuario logueado
            $stmt = $pdo->prepare("SELECT fp.idFavoritoPropietario, fp.propietarioId, p.nombre, p.apellidoP, p.apellidoM
                FROM favoritoPropietario fp
                INNER JOIN propietario p ON p.idPropietario = fp.propietarioId
                WHERE fp.usuarioId = ?
                ORDER BY fp.creado_at DESC");
            $stmt->execute([$usuarioId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'    => true,
                'total' => count($rows),
                'rows'  => $rows,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            error_log('[consultor.favoritos.json] ' . $e->getMessage());
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Error al consultar favoritos']);
        }
    }

    public function perfil(): void {
        $this->asegurarConsultor();
        // usuario logeado
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) {
            header('Location: ' . BASE_URL . '/config/app.php?accion=auth.form');
            exit;
        }
        // por si quieres recargar desde BD (más fresco)
        require_once __DIR__ . '/../Model/BD.php';
        $pdo = BD::pdo();
        $stmt = $pdo->prepare("SELECT idUsuario, nombre, email, tipoUsuario, fechaRegistro FROM usuario WHERE idUsuario = ?");
        $stmt->execute([ (int)$usuario['id'] ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            poner_flash('danger', 'No se encontró tu usuario.');
            header('Location: ' . BASE_URL . '/config/app.php?accion=home.dashboard');
            exit;
        }
        // para la vista
        $active = 'configuracion'; // si tu menú lo usa
        require __DIR__ . '/../View/consultor/usuario.php';
    }

    public function perfilGuardar(): void {
        $this->asegurarConsultor();
        $id = (int)($_SESSION['usuario']['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = trim($_POST['contrasenia'] ?? '');
        $pass2 = trim($_POST['contrasenia_confirm'] ?? '');
        $errores = [];
        if ($nombre === '') $errores[] = 'El nombre es obligatorio.';
        if ($email === '')  $errores[] = 'El correo es obligatorio.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo inválido.';
        // 💡 Verificar correo existente
        $pdo = BD::pdo();
        $stmt = $pdo->prepare("SELECT idUsuario FROM usuario WHERE email = ? AND idUsuario <> ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            $errores[] = 'El correo ya está en uso por otro usuario.';
        }
        if ($errores) {
            fallar_y_volver('perfil', $errores, compact('nombre','email'));
            header('Location: ' . BASE_URL . '/config/app.php?accion=consultor.perfil');
            exit;
        }
        // Actualizar
        if ($pass !== '') {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE usuario SET nombre=?, email=?, contrasenia=? WHERE idUsuario=?");
            $stmt->execute([$nombre, $email, $hash, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuario SET nombre=?, email=? WHERE idUsuario=?");
            $stmt->execute([$nombre, $email, $id]);
        }
        poner_flash('success', 'usr.actualizado.datos');
        header('Location: ' . BASE_URL . '/config/app.php?accion=consultor.perfil');
        exit;
    }

    /* SOLICITUD CAMBIO */
    public function solicitudCambioForm(): void {
        $this->asegurarConsultor();
        $propietarioId = isset($_GET['propietarioId']) ? (int)$_GET['propietarioId'] : 0;
        $propietario = null;
        if ($propietarioId > 0) {
            $propModel = new Propietario();
            $propietario = $propModel->buscarPorId($propietarioId);
            if (!$propietario) {
                $_SESSION['flash_error'] = 'El propietario indicado no existe.';
                header('Location: ' . BASE_URL . '/config/app.php?accion=consultor.solicitud.listar');
                exit;
            }
            $propietariosOpciones = []; // no se necesita
        } else {
            // vienes desde el menú → carga opciones
            $propModel = new Propietario();
            $propietariosOpciones = $propModel->opcionesSelect();
        }
        $errores = $_SESSION['errores'] ?? [];
        $viejo   = $_SESSION['viejo'] ?? [];
        unset($_SESSION['errores'], $_SESSION['viejo']);
        $titulo = 'Solicitud de cambio';
        require __DIR__ . '/../View/consultor/solicitudCambios.php';
    }

    public function solicitudCambioGuardar(): void {
        $this->asegurarConsultor();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=consultor.solicitud.listar');
            exit;
        }
        $usuarioId     = (int)($_SESSION['usuario']['id'] ?? 0);
        $propietarioId = (int)($_POST['propietarioId'] ?? 0);
        $campo         = trim($_POST['campo'] ?? '');
        $valorNuevo    = trim($_POST['valor_nuevo'] ?? '');
        $comentario    = trim($_POST['comentario'] ?? '');
        // validaciones básicas
        if ($propietarioId <= 0 || $campo === '' || $valorNuevo === '' || $comentario === '') {
            poner_flash('danger', 'sc.error.datos');
            header('Location: ' . BASE_URL . '/config/app.php?accion=consultor.solicitud.listar');
            exit;
        }
        // Traemos al propietario con JOIN para tener todo lo que necesitamos
        $pdo = BD::pdo();
        $sql = "SELECT 
                    p.idPropietario,
                    p.nombre,
                    p.apellidoP,
                    p.apellidoM,
                    p.email,
                    p.puestoId,
                    p.extensionId,
                    pu.nombre  AS puestoNombre,
                    e.numero   AS extensionNumero
                FROM propietario p
                LEFT JOIN puesto pu   ON pu.idPuesto = p.puestoId
                LEFT JOIN extension e ON e.idExtension = p.extensionId
                WHERE p.idPropietario = ?";
        $st = $pdo->prepare($sql);
        $st->execute([$propietarioId]);
        $prop = $st->fetch(PDO::FETCH_ASSOC);
        if (!$prop) {
            poner_flash('danger', 'sc.error.propietario');
            header('Location: ' . BASE_URL . '/config/app.php?accion=consultor.solicitud.listar');
            exit;
        }
        // Resolver valor_anterior según el campo solicitado
        $valorAnterior = null;
        switch ($campo) {
            case 'nombre':
                $valorAnterior = $prop['nombre'] ?? null;
                break;
            case 'apellidoP':
                $valorAnterior = $prop['apellidoP'] ?? null;
                break;
            case 'apellidoM':
                $valorAnterior = $prop['apellidoM'] ?? null;
                break;
            case 'email':
                $valorAnterior = $prop['email'] ?? null;
                break;
            case 'puestoId':
            case 'puesto':        // por si lo mandas así
                // si ya tenemos el nombre del puesto, lo guardamos como texto
                $valorAnterior = $prop['puestoNombre'] ?? null;
                break;
            case 'extensionId':
            case 'extension':     // por si lo mandas así
                // igual: guardamos el número de extensión, no el id
                $valorAnterior = $prop['extensionNumero'] ?? null;
                break;
            default:
                $valorAnterior = null;
        }
        //Guardar la solicitud
        $scModel = new SolicitudCambio();
        $ok = $scModel->crear([
            'campo'                 => $campo,
            'valor_anterior'        => $valorAnterior,
            'valor_nuevo'           => $valorNuevo,
            'comentario'            => $comentario,
            'propietarioId'         => $propietarioId,
            'usuarioSolicitanteId'  => $usuarioId,
        ]);
        if ($ok) {
            poner_flash('success', 'sc.creada');
        } else {
            poner_flash('danger', 'sc.error.crear');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=consultor.solicitud.listar');
        exit;
    }

    public function solicitudCambioListar(): void {
        $this->asegurarConsultor();
        $usuarioId = (int)($_SESSION['usuario']['id'] ?? 0);
        $scModel = new SolicitudCambio();
        $solicitudes = $scModel->listarPorUsuario($usuarioId);
        $propModel = new Propietario();
        $propietariosOpciones = $propModel->opcionesSelect();
        $active = 'cambios';
        require __DIR__ . '/../View/consultor/solicitudCambios.php';
    }

    public function propietarioJson(): void {
        $this->asegurarConsultor();
        $id = (int)($_GET['id'] ?? 0);
        header('Content-Type: application/json; charset=utf-8');
        if ($id <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
            return;
        }
        $pdo = BD::pdo();
        $sql = "SELECT p.idPropietario,
                    p.nombre,
                    p.apellidoP,
                    p.apellidoM,
                    p.email,
                    e.numero AS extensionNumero,
                    pu.nombre AS puestoNombre
                FROM propietario p
                LEFT JOIN extension e ON e.idExtension = p.extensionId
                LEFT JOIN puesto pu   ON pu.idPuesto = p.puestoId
                WHERE p.idPropietario = ?";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(['ok' => true, 'propietario' => $row], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'No encontrado']);
        }
    }

    public function solicitudCambioEliminar(): void {
        $this->asegurarConsultor();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=consultor.solicitud.listar');
            exit;
        }
        $usuarioId = (int)($_SESSION['usuario']['id'] ?? 0);
        $id = (int)($_POST['idSolicitudCambio'] ?? 0);
        if ($id <= 0) {
            poner_flash('danger', 'sc.error.eliminar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=consultor.solicitud.listar');
            exit;
        }
        $sc = new SolicitudCambio();
        // verificamos que sea suya y esté pendiente
        $row = $sc->buscarPorId($id);
        if (!$row || (int)$row['usuarioSolicitanteId'] !== $usuarioId || $row['estado'] !== 'pendiente') {
            poner_flash('danger', 'sc.error.permiso');
            header('Location: ' . BASE_URL . '/config/app.php?accion=consultor.solicitud.listar');
            exit;
        }
        $ok = $sc->eliminar($id);
        if ($ok) {
            poner_flash('success', 'sc.eliminada');
        } else {
            poner_flash('danger', 'sc.error.eliminar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=consultor.solicitud.listar');
        exit;
    }

    //REPORTES DEL SISTEMA
    public function reportesCatalogoConsultor() {
        $title = 'Reportes del sistema';
        require __DIR__ . '/../View/reportes/vistaReportesConsultor.php';
    }

    public function reporteDirectorio() {
        require_once __DIR__ . '/../Model/ReporteDirectorio.php';
        $model = new ReporteDirectorio();
        $directorio = $model->obtenerDirectorioCompleto();
        $title = 'Exportación del directorio';
        require __DIR__ . '/../View/reportes/directorio.php';
    }

    public function reporteDirectorioPDF() {
        require_once __DIR__ . '/../Model/ReporteDirectorio.php';
        require_once __DIR__ . '/../vendor/autoload.php';
        $model = new ReporteDirectorio();
        $directorio = $model->obtenerDirectorioCompleto();
        //Ruta del logo
        $logoPath = __DIR__ . '/../View/contenido/img/OPE.png';
        //Convierte la imagen a Base64 para evitar errores de ruta
        $logoDataUri = '';
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoDataUri = 'data:image/png;base64,' . $logoData;
        }
        //Configura opciones modernas de Dompdf
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new \Dompdf\Dompdf($options);
        //Generar el HTML
        ob_start();
        //Pasamos las variables al contexto de la vista
        $DIRECTORIO = $directorio;
        $LOGO_DATA_URI = $logoDataUri;
        require __DIR__ . '/../View/reportes/pdf_directorio.php';
        $html = ob_get_clean();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        //Pie de página (usando método moderno)
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(520, 820, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 9, [0, 0, 0]);
        //Mostrar PDF en navegador
        $dompdf->stream('directorio_mtcenter.pdf', ['Attachment' => false]);
    }

    public function reporteDirectorioExcel() {
        require_once __DIR__ . '/../Model/ReporteDirectorio.php';
        require_once __DIR__ . '/../vendor/autoload.php';
        $model = new ReporteDirectorio();
        $directorio = $model->obtenerDirectorioCompleto();
        // calcular totales
        $totalAreas = count($directorio);
        $totalContactos = 0;
        foreach ($directorio as $filas) {
            $totalContactos += count($filas);
        }
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Directorio');
        // 1. títulos
        $sheet->setCellValue('A1', 'DIRECTORIO  GENERAL');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('A2', 'TELÉFONOS: LADA (777) 3113066, 3129564, 3138242');
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('A3', 'OPERADORA DE PRODUCTOS ELECTRÓNICOS');
        $sheet->mergeCells('A3:E3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        // 2. resumen (fila 4)
        $sheet->setCellValue('A4',"Total de áreas: {$totalAreas}    |    Total de contactos: {$totalContactos}");
        $sheet->mergeCells('A4:E4');
        $sheet->getStyle('A4')->getFont()->setItalic(true);
        $sheet->getStyle('A4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        // 3. logo
        $logoPath = __DIR__ . '/../View/contenido/img/OPE.png';
        if (file_exists($logoPath)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(48);
            $drawing->setCoordinates('E1');
            $drawing->setWorksheet($sheet);
        }
        // encabezados de tabla
        $startRow = 6; // dejamos 1-4 títulos, 5 vacío, 6 encabezado
        $sheet->setCellValue('A' . $startRow, 'EXTENSIÓN');
        $sheet->setCellValue('B' . $startRow, 'NOMBRE');
        $sheet->setCellValue('C' . $startRow, 'DIRECCIÓN');
        $sheet->setCellValue('D' . $startRow, 'PUESTO');
        $sheet->setCellValue('E' . $startRow, 'CORREO');
        $sheet->getStyle("A{$startRow}:E{$startRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$startRow}:E{$startRow}")
            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $sheet->getStyle("A{$startRow}:E{$startRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle("A{$startRow}:E{$startRow}")
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        // 4. datos
        $row = $startRow + 1;
        foreach ($directorio as $area => $filas) {
            // correo del área
            $correoArea = $filas[0]['correoArea'] ?? '';
            // fila de área + correo
            $textoArea = strtoupper($area);
            if ($correoArea) {
                $textoArea .= " · Correo del área: {$correoArea}";
            }
            $sheet->setCellValue("A{$row}", $textoArea);
            $sheet->mergeCells("A{$row}:E{$row}");
            $sheet->getStyle("A{$row}:E{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFEFEFEF');
            $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:E{$row}")->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row++;
            foreach ($filas as $fila) {
                $sheet->setCellValue("A{$row}", $fila['extension'] ?? '');
                $sheet->setCellValue("B{$row}", $fila['nombre'] ?? '');
                $sheet->setCellValue("C{$row}", $area);
                $sheet->setCellValue("D{$row}", $fila['puesto'] ?? '');
                $sheet->setCellValue("E{$row}",$fila['correoPropietario'] ?? ($fila['correo'] ?? ''));
                $sheet->getStyle("A{$row}:E{$row}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $row++;
            }
        }
        // anchos de columna
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(45);
        // descargar
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="directorio_mtcenter.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    public function reporteDirectorioFiltros() {
        require_once __DIR__ . '/../Model/reporteDirectorio.php';
        $rep = new ReporteDirectorio();
        $nombre   = $_GET['nombre']   ?? '';
        $extension= $_GET['extension']?? '';
        $areaId   = isset($_GET['areaId']) && $_GET['areaId'] !== '' ? (int)$_GET['areaId'] : null;
        $directorio = $rep->obtenerDirectorioFiltrado($nombre, $extension, $areaId);
        $areas      = $rep->listarAreas();
        $active = 'reportes';
        $titulo = 'Reporte con filtros';
        require __DIR__ . '/../View/reportes/reporteFiltros.php';
    }

    public function reporteDirectorioFiltrosPDF() {
        require_once __DIR__ . '/../Model/ReporteDirectorio.php';
        require_once __DIR__ . '/../vendor/autoload.php';

        $rep = new ReporteDirectorio();
        $nombre    = $_GET['nombre']    ?? '';
        $extension = $_GET['extension'] ?? '';
        $areaId    = isset($_GET['areaId']) && $_GET['areaId'] !== '' ? (int)$_GET['areaId'] : null;

        $directorio = $rep->obtenerDirectorioFiltrado($nombre, $extension, $areaId);

        // 🔹 logo en base64
        $logoPath = __DIR__ . '/../View/contenido/img/OPE.png';
        $LOGO_DATA_URI = '';
        if (file_exists($logoPath)) {
            $LOGO_DATA_URI = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        // generar html
        ob_start();
        $DIRECTORIO = $directorio;
        $LOGO_DATA_URI_LOCAL = $LOGO_DATA_URI; // nombre distinto si tu vista usa otro
        // si tu vista espera $LOGO_DATA_URI, usa ese nombre:
        $LOGO_DATA_URI = $LOGO_DATA_URI_LOCAL;
        require __DIR__ . '/../View/reportes/pdf_directorio.php';
        $html = ob_get_clean();

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('directorio_filtrado.pdf', ['Attachment' => false]);
    }

    public function reporteDirectorioFiltrosExcel() {
        require_once __DIR__ . '/../Model/ReporteDirectorio.php';
        require_once __DIR__ . '/../vendor/autoload.php';

        $rep = new ReporteDirectorio();

        $nombre    = $_GET['nombre']    ?? '';
        $extension = $_GET['extension'] ?? '';
        $areaId    = isset($_GET['areaId']) && $_GET['areaId'] !== '' ? (int)$_GET['areaId'] : null;

        $directorio = $rep->obtenerDirectorioFiltrado($nombre, $extension, $areaId);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Directorio filtrado');

        // título
        $sheet->setCellValue('A1', 'DIRECTORIO GENERAL (FILTRADO)');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        );

        // encabezados
        $sheet->setCellValue('A3', 'EXTENSIÓN');
        $sheet->setCellValue('B3', 'NOMBRE');
        $sheet->setCellValue('C3', 'ÁREA');
        $sheet->setCellValue('D3', 'PUESTO');
        $sheet->setCellValue('E3', 'CORREO');

        $sheet->getStyle('A3:E3')->getFont()->setBold(true);
        $sheet->getStyle('A3:E3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE9ECEF');

        $row = 4;
        foreach ($directorio as $area => $filas) {
            // fila de área (estilo distinto)
            $sheet->setCellValue("A{$row}", $area);
            $sheet->mergeCells("A{$row}:E{$row}");
            $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:E{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFD0D7DD');
            $row++;

            foreach ($filas as $f) {
                $sheet->setCellValue("A{$row}", $f['extension'] ?? '');
                $sheet->setCellValue("B{$row}", $f['nombre'] ?? '');
                $sheet->setCellValue("C{$row}", $area);
                $sheet->setCellValue("D{$row}", $f['puesto'] ?? '');
                $sheet->setCellValue("E{$row}", $f['correoPropietario'] ?? '');
                $row++;
            }
        }

        foreach (['A','B','C','D','E'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="directorio_filtrado.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
    
    public function registrarConsultaExtension(): void {
        // vamos a devolver JSON siempre
        header('Content-Type: application/json; charset=utf-8');

        // aceptar también GET solo para probar rápido en el navegador
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'POST') {
            $extensionId = isset($_POST['extensionId']) ? (int)$_POST['extensionId'] : 0;
        } else {
            // para pruebas: http://localhost/estadia/config/app.php?accion=consultor.extension.copiada&extensionId=1
            $extensionId = isset($_GET['extensionId']) ? (int)$_GET['extensionId'] : 0;
        }

        if ($extensionId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'msg' => 'extensionId vacío o no válido']);
            return;
        }

        // quién lo hizo (si hay sesión)
        $usuarioId = $_SESSION['usuario']['idUsuario'] ?? null;

        require_once __DIR__ . '/../Model/ConsultaExtension.php';
        $m = new ConsultaExtension();

        try {
            $m->registrar($extensionId, $usuarioId);
            echo json_encode(['ok' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }
}