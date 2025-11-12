<?php
require_once __DIR__ . '/../Controller/AppControlador.php';

require_once __DIR__ . '/../Model/Area.php';
require_once __DIR__ . '/../Model/Puesto.php';
require_once __DIR__ . '/../Model/Propietario.php';
require_once __DIR__ . '/../Model/Extension.php';
require_once __DIR__ . '/../Model/HorarioTrabajo.php';
require_once __DIR__ . '/../Model/Bitacora.php';
require_once __DIR__ . '/../Model/Nota.php';
require_once __DIR__ . '/../Model/SolicitudCambio.php';
require_once __DIR__ . '/../Model/ReporteEmpleadosArea.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;


class EditorControlador extends AppControlador {
    private function asegurarEditor(): void {
        $accionActual = $_GET['accion'] ?? '';
        if ($accionActual === 'auth.form') {
            return;
        }
        $this->asegurarSesion();
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
        if ($rol !== 'editor' && $rol !== 'admin') {
            poner_flash('danger', 'auth.permiso_denegado');
            header('Location: ' . BASE_URL . '/config/app.php?accion=home.dashboard');
            exit;
        }
    }

    public function extensionesListar(): void {
        $this->asegurarEditor();
        $extensiones = (new Extension())->todas();
        //PRG
        $errores        = $_SESSION['errores']       ?? [];
        $errores_origen = $_SESSION['erroresOrigen'] ?? null;
        $viejo          = $_SESSION['viejo']         ?? [];
        unset($_SESSION['errores'], $_SESSION['erroresOrigen'], $_SESSION['viejo']);
        require __DIR__ . '/../View/editor/extensiones.php';
    }

    public function areasListar(): void {
        $this->asegurarEditor();
        $M = new Area();
        $areas = $M->todas();
        $mapCorreos = [];
        foreach ($areas as $a) {
            $mapCorreos[(int)$a['idArea']] = $M->correosSolo((int)$a['idArea']);
        }
        require __DIR__ . '/../View/editor/areas.php';
    }


    public function puestosListar(): void {
        $this->asegurarEditor();
        $q = trim($_GET['q'] ?? '');
        // puestos filtrados
        $puestos = (new Puesto())->todos($q);
        $areas = (new Area())->todas();
        // PRG (por si vienes de un error de crear/editar)
        $errores        = $_SESSION['errores']       ?? [];
        $errores_origen = $_SESSION['erroresOrigen'] ?? null;
        $viejo          = $_SESSION['viejo']         ?? [];
        unset($_SESSION['errores'], $_SESSION['erroresOrigen'], $_SESSION['viejo']);
        require __DIR__ . '/../View/editor/puestos.php';
    }

    /* PROPIETARIOS */
    public function propietariosListar(): void {
        $this->asegurarEditor();
        $q = trim($_GET['q'] ?? '');
        $MProp = new Propietario();
        $MArea = new Area();
        $MPuesto = new Puesto();
        $MExt = new Extension();
        $propietarios = $MProp->listar($q);
        $areas       = $MArea->todas();
        $extensiones = $MExt->todas();
        $puestos     = $MPuesto->todos('');
        $mapCorreosProp = [];
        foreach ($propietarios as $p) {
            $pid = (int)$p['idPropietario'];
            $mapCorreosProp[$pid] = $MProp->correosSolo($pid);
        }
        // PRG por si vienes de un error
        $errores        = $_SESSION['errores']       ?? [];
        $errores_origen = $_SESSION['erroresOrigen'] ?? null;
        $viejo          = $_SESSION['viejo']         ?? [];
        unset($_SESSION['errores'], $_SESSION['erroresOrigen'], $_SESSION['viejo']);
        $active = 'propietario';
        $title  = 'Propietarios';
        require __DIR__ . '/../View/editor/propietarios.php';
    }

    public function propietariosCrear(): void {
        $this->asegurarEditor();
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
        // Validación mínima (igual que admin)
        if (empty($data['nombre']) || empty($data['apellidoP']) || empty($data['apellidoM']) || empty($data['puestoId'])) {
            poner_flash('danger', 'prop.error.crear');
            $_SESSION['errores'] = ['Completa los campos obligatorios.'];
            $_SESSION['erroresOrigen'] = 'propietarios';
            $_SESSION['viejo'] = $_POST;
            header('Location: ' . BASE_URL . '/config/app.php?accion=propietarios.listar');
            exit;
        }
        $MProp = new Propietario();
        $idNuevo = $MProp->crear($data);
        if ($idNuevo > 0) {
            foreach ($correosExtra as $c) {
                $MProp->agregarCorreo($idNuevo, $c);
            }
            NotificacionControlador::nuevoContacto($idNuevo, trim($data['nombre'] . ' ' . $data['apellidoP'] . ' ' . $data['apellidoM']));
            poner_flash('success', 'prop.creado');
        } else {
            poner_flash('danger', 'prop.error.crear');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=propietarios.listar');
        exit;
    }


    public function propietariosActualizar(): void {
        $this->asegurarEditor();
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
        $MProp = new Propietario();
        $MProp->actualizar($id, $data);
        $MProp->reemplazarCorreos($id, $correosExtra);
        NotificacionControlador::modContacto($id, trim($data['nombre'] . ' ' . $data['apellidoP'] . ' ' . $data['apellidoM']));
        poner_flash('primary', 'prop.actualizado');
        header('Location: ' . BASE_URL . '/config/app.php?accion=propietarios.listar');
        exit;
    }


    public function propietariosEliminar(): void {
        $this->asegurarEditor();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=propietarios.listar');
            exit;
        }
        $id = (int)($_POST['idPropietario'] ?? 0);
        if ($id <= 0) {
            poner_flash('danger', 'prop.error.eliminar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=propietarios.listar');
            exit;
        }
        $MProp = new Propietario();
        $rowProp = $MProp->buscarPorId($id);
        $nombre = '';
        if ($rowProp) {
            $nombre = trim(($rowProp['nombre'] ?? '') . ' ' . ($rowProp['apellidoP'] ?? '') . ' ' . ($rowProp['apellidoM'] ?? ''));
            $nombre = preg_replace('/\s+/', ' ', $nombre);
        }
        $MProp->eliminar($id);
        NotificacionControlador::elimContacto($id, $nombre ? "({$nombre})" : "(ID {$id})");
        poner_flash('danger', 'prop.eliminado');
        header('Location: ' . BASE_URL . '/config/app.php?accion=propietarios.listar');
        exit;
    }

    public function bitacoraListar(): void {
        $this->asegurarEditor();
        $pdo     = BD::pdo();
        $perPage = (int)($_GET['perPage'] ?? 10);
        $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 10;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;
        $q       = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $like = "%{$q}%";
            $stc = $pdo->prepare("
            SELECT COUNT(*)
            FROM bitacorasistema
            WHERE descripcion   LIKE :q1
                OR usuarioNombre LIKE :q2
                OR usuarioEmail  LIKE :q3
                OR modulo        LIKE :q4
                OR accion        LIKE :q5
                OR tabla_bd      LIKE :q6
                OR pk_valor      LIKE :q7
        ");
            foreach ([':q1', ':q2', ':q3', ':q4', ':q5', ':q6', ':q7'] as $k) $stc->bindValue($k, $like);
            $stc->execute();
            $total_registros = (int)$stc->fetchColumn();

            $st = $pdo->prepare("
            SELECT idBitacora, accion, modulo, tabla_bd, pk_valor, descripcion,
                    usuarioNombre, usuarioEmail, creada_at, usuarioId
            FROM bitacorasistema
            WHERE descripcion   LIKE :q1
                OR usuarioNombre LIKE :q2
                OR usuarioEmail  LIKE :q3
                OR modulo        LIKE :q4
                OR accion        LIKE :q5
                OR tabla_bd      LIKE :q6
                OR pk_valor      LIKE :q7
            ORDER BY creada_at DESC, idBitacora DESC
            LIMIT :lim OFFSET :off
        ");
            foreach ([':q1', ':q2', ':q3', ':q4', ':q5', ':q6', ':q7'] as $k) $st->bindValue($k, $like);
            $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
            $st->bindValue(':off', $offset,  PDO::PARAM_INT);
            $st->execute();
            $logs = $st->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $total_registros = (int)$pdo->query("SELECT COUNT(*) FROM bitacorasistema")->fetchColumn();
            $st = $pdo->prepare("
            SELECT idBitacora, accion, modulo, tabla_bd, pk_valor, descripcion,
                    usuarioNombre, usuarioEmail, creada_at, usuarioId
            FROM bitacorasistema
            ORDER BY creada_at DESC, idBitacora DESC
            LIMIT :lim OFFSET :off
        ");
            $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
            $st->bindValue(':off', $offset,  PDO::PARAM_INT);
            $st->execute();
            $logs = $st->fetchAll(PDO::FETCH_ASSOC);
        }
        require __DIR__ . '/../View/editor/bitacora.php';
    }

    /* === NOTAS === */
    public function notasListar(): void {
        $this->asegurarEditor();
        $q = trim($_GET['q'] ?? '');
        $m  = new Nota();
        $notas = $m->listar($q);
        $propietarios = $m->propietariosTodos();
        // PRG
        $errores        = $_SESSION['errores']       ?? [];
        $errores_origen = $_SESSION['erroresOrigen'] ?? null;
        $viejo          = $_SESSION['viejo']         ?? [];
        unset($_SESSION['errores'], $_SESSION['erroresOrigen'], $_SESSION['viejo']);
        // Cargar vista editor si existe, si no, usar admin
        $vistaEditor = __DIR__ . '/../View/editor/notas.php';
        $vistaAdmin  = __DIR__ . '/../View/admin/notas.php';
        if (is_file($vistaEditor)) {
            require $vistaEditor;
        } else {
            require $vistaAdmin;
        }
    }

    public function notasCrear(): void {
        $this->asegurarEditor();
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
            poner_flash('success','nota.creada');
        } else {
            poner_flash('danger','nota.error.crear');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
    }

    public function notasActualizar(): void {
        $this->asegurarEditor();
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
        if ($ok) { poner_flash('primary','nota.actualizada'); }
        else     { poner_flash('danger','nota.error.actualizar'); }
        header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
    }

    public function notasEliminar(): void {
        $this->asegurarEditor();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
        }
        $idNota = (int)($_POST['idNota'] ?? 0);
        if ($idNota<=0) {
            poner_flash('danger','nota.error.eliminar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
        }
        $ok = (new Nota())->eliminar($idNota);
        if ($ok) { poner_flash('danger','nota.eliminada'); }
        else     { poner_flash('danger','nota.error.eliminar'); }
        header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); exit;
    }


    /* === HORARIOS === */
    public function horariosListar(): void {
        $this->asegurarEditor();
        $q = trim($_GET['q'] ?? '');
        $modelo = new Horario();
        $horarios = $modelo->listarAgrupado($q);
        $propietarios = $modelo->propietariosTodos();
        // PRG
        $errores        = $_SESSION['errores']       ?? [];
        $errores_origen = $_SESSION['erroresOrigen'] ?? null;
        $viejo          = $_SESSION['viejo']         ?? [];
        unset($_SESSION['errores'], $_SESSION['erroresOrigen'], $_SESSION['viejo']);
        $vistaEditor = __DIR__ . '/../View/editor/horariosTrabajo.php';
        $vistaAdmin  = __DIR__ . '/../View/admin/horariosTrabajo.php';
        if (is_file($vistaEditor)) { require $vistaEditor; } else { require $vistaAdmin; }
    }

    public function horariosCrear(): void {
        $this->asegurarEditor();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar'); exit;
        }
        $propietarioId = (int)($_POST['propietarioId'] ?? 0);
        $dias = $_POST['dias'] ?? [];
        $he = trim($_POST['horaEntrada'] ?? '');
        $hs = trim($_POST['horaSalida']  ?? '');
        $errores = [];
        if ($propietarioId <= 0)                $errores[] = 'Empleado inválido.';
        if (empty($dias))                        $errores[] = 'Selecciona al menos un día.';
        if ($he === '' || $hs === '')            $errores[] = 'Horas inválidas.';
        if ($he !== '' && $hs !== '' && $he >= $hs) $errores[] = 'La hora de entrada debe ser menor a la de salida.';
        if ($errores) {
            fallar_y_volver('horarios', $errores, ['propietarioId'=>$propietarioId,'dias'=>$dias,'horaEntrada'=>$he,'horaSalida'=>$hs]);
            header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar'); exit;
        }
        $ok = (new Horario())->crearMultiple($propietarioId, $dias, $he, $hs);
        if ($ok) { poner_flash('success', 'hor.creado'); }
        else     { poner_flash('danger', 'hor.error.crear'); }
        header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar'); exit;
    }

    public function horariosActualizar(): void {
        $this->asegurarEditor();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar'); exit;
        }
        $idRep = (int)($_POST['idHorario'] ?? 0);
        $propietarioId = (int)($_POST['propietarioId'] ?? 0);
        $dias = $_POST['dias'] ?? [];
        $he = trim($_POST['horaEntrada'] ?? '');
        $hs = trim($_POST['horaSalida']  ?? '');
        $old_he = trim($_POST['old_he'] ?? '');
        $old_hs = trim($_POST['old_hs'] ?? '');
        $errores = [];
        if ($idRep <= 0)                         $errores[] = 'ID inválido.';
        if ($propietarioId <= 0)                 $errores[] = 'Empleado inválido.';
        if (empty($dias))                        $errores[] = 'Selecciona al menos un día.';
        if ($he === '' || $hs === '')            $errores[] = 'Horas inválidas.';
        if ($he !== '' && $hs !== '' && $he >= $hs) $errores[] = 'La hora de entrada debe ser menor a la de salida.';
        if ($old_he === '' || $old_hs === '')    $errores[] = 'Faltan horas originales del bloque.';
        if ($errores) {
            fallar_y_volver('horarios', $errores, [
                'idHorario'=>$idRep,'propietarioId'=>$propietarioId,
                'dias'=>$dias,'horaEntrada'=>$he,'horaSalida'=>$hs,
                'old_he'=>$old_he,'old_hs'=>$old_hs
            ]);
            header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar'); exit;
        }
        $ok = (new Horario())->actualizarBloque($propietarioId, $dias, $he, $hs, $old_he, $old_hs);
        if ($ok) { poner_flash('primary', 'hor.actualizado'); }
        else     { poner_flash('danger',  'hor.error.actualizar'); }
        header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar'); exit;
    }

    public function horariosEliminar(): void {
        $this->asegurarEditor();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar'); exit;
        }
        $idRep = (int)($_POST['idHorario'] ?? 0);
        $propietarioId = (int)($_POST['propietarioId'] ?? 0);
        $he = trim($_POST['old_he'] ?? '');
        $hs = trim($_POST['old_hs'] ?? '');
        if ($idRep <= 0 || $propietarioId <= 0 || $he === '' || $hs === '') {
            poner_flash('danger', 'hor.error.eliminar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar'); exit;
        }
        $ok = (new Horario())->eliminarBloque($propietarioId, $he, $hs);
        if ($ok) { poner_flash('danger', 'hor.eliminado'); }
        else     { poner_flash('danger', 'hor.error.eliminar'); }
        header('Location: ' . BASE_URL . '/config/app.php?accion=horarios.listar'); exit;
    }

    public function solicitudesCambioListar(): void {
        $this->asegurarEditor();
        require_once __DIR__ . '/../Model/SolicitudCambio.php';
        // filtro opcional ?estado=pendiente|aprobado|rechazado
        $estado = isset($_GET['estado']) ? trim($_GET['estado']) : null;
        $m = new SolicitudCambio();
        $solicitudes = $m->listarTodas($estado);
        // para marcar en el menú
        $active = 'solicitudes';
        // tu vista para editor
        require __DIR__ . '/../View/editor/solicitudesEditor.php';
    }

    public function solicitudCambioAprobar(): void {
        $this->asegurarEditor();
        // solo POST
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=editor.solicitudes.cambio');
            exit;
        }
        $id     = (int)($_POST['idSolicitudCambio'] ?? 0);
        $motivo = trim($_POST['motivo_revision'] ?? '');
        if ($id <= 0) {
            // clave ya cargada en ui.php
            poner_flash('danger', 'sc.error.aprobar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=editor.solicitudes.cambio');
            exit;
        }
        require_once __DIR__ . '/../Model/SolicitudCambio.php';
        $m  = new SolicitudCambio();
        $ok = $m->actualizarEstado($id, 'aprobado', $motivo !== '' ? $motivo : null);
        if ($ok) {
            poner_flash('success', 'sc.aprobada');
        } else {
            poner_flash('danger', 'sc.error.aprobar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=editor.solicitudes.cambio');
        exit;
    }

    public function solicitudCambioRechazar(): void {
        $this->asegurarEditor();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . BASE_URL . '/config/app.php?accion=editor.solicitudes.cambio');
            exit;
        }
        $id     = (int)($_POST['idSolicitudCambio'] ?? 0);
        $motivo = trim($_POST['motivo_revision'] ?? '');
        if ($id <= 0) {
            poner_flash('danger', 'sc.error.rechazar');
            header('Location: ' . BASE_URL . '/config/app.php?accion=editor.solicitudes.cambio');
            exit;
        }
        if ($motivo === '') {
            // mensaje específico cuando no mandan motivo
            poner_flash('danger', 'sc.error.motivo');
            header('Location: ' . BASE_URL . '/config/app.php?accion=editor.solicitudes.cambio');
            exit;
        }
        require_once __DIR__ . '/../Model/SolicitudCambio.php';
        $m  = new SolicitudCambio();
        $ok = $m->actualizarEstado($id, 'rechazado', $motivo);
        if ($ok) {
            poner_flash('primary', 'sc.rechazada');
        } else {
            poner_flash('danger', 'sc.error.rechazar');
        }
        header('Location: ' . BASE_URL . '/config/app.php?accion=editor.solicitudes.cambio');
        exit;
    }

    /**************** REPORTES EDITOR **************/
    
    public function reportesCatalogoEditor(): void {
        $title = 'Reportes del sistema';
        require __DIR__ . '/../View/reportes/vistaReportesEditor.php';
    }

    public function reporteDirectorioEditor() {
        require_once __DIR__ . '/../Model/ReporteDirectorio.php';
        $model = new ReporteDirectorio();
        $directorio = $model->obtenerDirectorioCompleto();
        $title = 'Exportación del directorio';
        require __DIR__ . '/../View/reportes/directorioEditor.php';
    }

    public function reporteDirectorioFiltrosEditor() {
        require_once __DIR__ . '/../Model/reporteDirectorio.php';
        $rep = new ReporteDirectorio();
        $nombre   = $_GET['nombre']   ?? '';
        $extension= $_GET['extension']?? '';
        $areaId   = isset($_GET['areaId']) && $_GET['areaId'] !== '' ? (int)$_GET['areaId'] : null;
        $directorio = $rep->obtenerDirectorioFiltrado($nombre, $extension, $areaId);
        $areas      = $rep->listarAreas();
        $active = 'reportes';
        $titulo = 'Reporte con filtros';
        require __DIR__ . '/../View/reportes/reporteFiltrosEditor.php';
    }

    public function reporteDirectorioFiltrosPDFEditor() {
        require_once __DIR__ . '/../Model/ReporteDirectorio.php';
        require_once __DIR__ . '/../vendor/autoload.php';
        $rep = new ReporteDirectorio();
        $nombre    = $_GET['nombre']    ?? '';
        $extension = $_GET['extension'] ?? '';
        $areaId    = isset($_GET['areaId']) && $_GET['areaId'] !== '' ? (int)$_GET['areaId'] : null;
        $directorio = $rep->obtenerDirectorioFiltrado($nombre, $extension, $areaId);
        //logo 
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

    public function reporteDirectorioFiltrosExcelEditor() {
        require_once __DIR__ . '/../Model/ReporteDirectorio.php';
        require_once __DIR__ . '/../vendor/autoload.php';
        $rep = new ReporteDirectorio();
        $nombre = $_GET['nombre']    ?? '';
        $extension = $_GET['extension'] ?? '';
        $areaId = isset($_GET['areaId']) && $_GET['areaId'] !== '' ? (int)$_GET['areaId'] : null;
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

    public function reporteExtensionesUsadas(): void {
        $this->asegurarEditor();
        require_once __DIR__ . '/../Model/ConsultaExtension.php';
        $m = new ConsultaExtension();
        $areaId = isset($_GET['areaId']) ? (int)$_GET['areaId'] : null;
        $areas = $m->obtenerAreas();
        $extensiones = $m->obtenerMasUsadas($areaId);
        require __DIR__ . '/../View/reportes/reporteExtensionesUsadas.php';
    }

    public function reporteExtensionesUsadasPDF(): void {
        // solo editor / admin
        $this->asegurarEditor();
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
        require __DIR__ . '/../View/reportes/reporte_empleados_area.php';
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
        require __DIR__ . '/../View/reportes/reporte_extensiones_grafica.php';
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
        require __DIR__ . '/../View/reportes/reporteEmpleadosAreaGrafica.php';
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