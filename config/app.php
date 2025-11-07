<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../Controller/AppControlador.php';
require_once __DIR__ . '/../Controller/AdminControlador.php';
require_once __DIR__ . '/../Controller/ConsultorControlador.php';
require_once __DIR__ . '/../Controller/NotificacionControlador.php';
require_once __DIR__ . '/../Controller/ConsultorControlador.php';
require_once __DIR__ . '/../Controller/EditorControlador.php';
require_once __DIR__ . '/../Controller/Dashboard.php';


/* Instancias principales del sistema */
$C = new AppControlador();
$Co = new ConsultorControlador();
$A = new AdminControlador();
$N = new NotificacionControlador();
$E = new EditorControlador(); 
$D = new Dashboard();

/* Función auxiliar de redirección rápida */
function irA(string $ruta): void {
    header('Location: ' . $ruta);
    exit;
}

/* Determina la acción solicitada */
$accion = $_GET['accion'] ?? 'auth.form';
switch ($accion) {

    // AUTENTICACIÓN DE USUARIOS
    case 'auth.form': {
            require __DIR__ . '/../View/auth/login.php';
            break;
        }
    case 'auth.login': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                irA(BASE_URL . '/config/app.php?accion=auth.form');
            }
            $C->iniciarSesion();
            break;
        }
    case 'auth.register': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                irA(BASE_URL . '/config/app.php?accion=auth.form');
            }
            $C->registrarUsuario();
            break;
        }
    case 'auth.logout': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                irA(BASE_URL . '/config/app.php?accion=auth.form');
            }
            $C->cerrarSesion();
            break;
        }

        // DASHBOARD PRINCIPAL
    case 'home.dashboard': {
            $C->dashboard();
            break;
        }
    case 'dashboard.datos': {
        require_once __DIR__ . '/../Controller/Dashboard.php';
        (new Dashboard())->datos(); // el método que entrega el JSON
        break;
    }

        // RECUPERACIÓN DE CONTRASEÑA
    case 'auth.forgot.form': {
            require __DIR__ . '/../View/auth/forgot.php';
            break;
        }
    case 'auth.forgot.send': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                irA(BASE_URL . '/config/app.php?accion=auth.forgot.form');
            }
            $C->enviarEnlaceRecuperacion();
            break;
        }
    case 'auth.reset.form': {
            $C->mostrarFormularioReset();
            break;
        }
    case 'auth.reset.apply': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                irA(BASE_URL . '/config/app.php?accion=auth.reset.form');
            }
            $C->aplicarNuevaContrasenia();
            break;
        }

        // EXTENSIONES
    case 'extensiones.listar': {
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
            if ($rol === 'editor') { $E->extensionesListar(); }
        else { $A->extensionesListar(); }
    break;
    }
    case 'extensiones.crear': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                irA(BASE_URL . '/config/app.php?accion=extensiones.listar');
            }
            $A->extensionesCrear();
            break;
        }
    case 'extensiones.actualizar': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                irA(BASE_URL . '/config/app.php?accion=extensiones.listar');
            }
            $A->extensionesActualizar();
            break;
        }
    case 'extensiones.eliminar': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                irA(BASE_URL . '/config/app.php?accion=extensiones.listar');
            }
            $A->extensionesEliminar();
            break;
        }

        // USUARIOS
    case 'usuarios.listar': {
            $A->usuariosListar();
            break;
        }
    case 'usuarios.crear': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                irA(BASE_URL . '/config/app.php?accion=usuarios.listar');
            }
            $A->usuariosCrear();
            break;
        }
    case 'usuarios.actualizar': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                irA(BASE_URL . '/config/app.php?accion=usuarios.listar');
            }
            $A->usuariosActualizar();
            break;
        }
    case 'usuarios.eliminar': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                irA(BASE_URL . '/config/app.php?accion=usuarios.listar');
            }
            $A->usuariosEliminar();
            break;
        }

        //AREAS
    case 'areas.listar': {
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
            if ($rol === 'editor') { $E->areasListar(); }
        else { $A->areasListar(); }
        break;
    }
    case 'areas.crear': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        irA(BASE_URL . '/config/app.php?accion=areas.listar');
    }
        $A->areasCrear();
        break;
    }
    case 'areas.actualizar': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        irA(BASE_URL . '/config/app.php?accion=areas.listar');
    }
        $A->areasActualizar();
        break;
    }
    case 'areas.eliminar': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            irA(BASE_URL . '/config/app.php?accion=areas.listar');
        }
        $A->areasEliminar();
        break;
    }
    case 'areas.correo.agregar': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
            irA(BASE_URL.'/config/app.php?accion=areas.listar');
        }
        $A->areasCorreoAgregar(); 
        break;
    }
    case 'areas.correo.eliminar': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            irA(BASE_URL.'/config/app.php?accion=areas.listar');
        }
        $A->areasCorreoEliminar(); 
        break;
    }

        // PUESTOS
    case 'puestos.listar': {
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
        if ($rol === 'editor') { 
            $E->puestosListar(); 
        } else { 
            $A->puestosListar(); 
        }
        break;
    }
    case 'puestos.crear': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            irA(BASE_URL . '/config/app.php?accion=puestos.listar');
        }
        $A->puestosCrear();
        break;
    }
    case 'puestos.actualizar': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            irA(BASE_URL . '/config/app.php?accion=puestos.listar');
        }
        $A->puestosActualizar();
        break;
    }
    case 'puestos.eliminar': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            irA(BASE_URL . '/config/app.php?accion=puestos.listar');
        }
        $A->puestosEliminar();
        break;
    }

        // BITÁCORA
    case 'bitacora.listar': {
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
            if ($rol === 'editor') { $E->bitacoraListar(); }
        else { $N->bitacoraListar(); }
    break;
    }

        // PROPIETARIOS
    case 'propietarios.listar': {
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
        if ($rol === 'editor') {
            $E->propietariosListar();
        } else {
            $A->propietariosListar();
        }
        break;
    }

    case 'propietarios.crear': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            irA(BASE_URL . '/config/app.php?accion=propietarios.listar');
        }
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
        if ($rol === 'editor') {
            $E->propietariosCrear();
        } else {
            $A->propietariosCrear();
        }
        break;
    }

    case 'propietarios.actualizar': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            irA(BASE_URL . '/config/app.php?accion=propietarios.listar');
        }
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
        if ($rol === 'editor') {
            $E->propietariosActualizar();
        } else {
            $A->propietariosActualizar();
        }
        break;
    }

    case 'propietarios.eliminar': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            irA(BASE_URL . '/config/app.php?accion=propietarios.listar');
        }
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
        if ($rol === 'editor') {
            $E->propietariosEliminar();
        } else {
            $A->propietariosEliminar();
        }
        break;
    }

        // NOTIFICACIÓN
    case 'notificacion.listar': {
        $N->listar();
        break;
    }
    case 'notificacion.contador': {
        $N->contador();
        break;
    }
    case 'notif.modal': {
        $N->modal();
        break;
    }

        // HORARIOS
    case 'horarios.listar': {
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
            if ($rol === 'editor') { $E->horariosListar(); }
        else { $A->horariosListar(); }
    break;
    }
    case 'horarios.crear': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            irA(BASE_URL . '/config/app.php?accion=horarios.listar');
        }
        $A->horariosCrear();
        break;
    }
    case 'horarios.actualizar': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            irA(BASE_URL . '/config/app.php?accion=horarios.listar');
        }
        $A->horariosActualizar();
        break;
    }
    case 'horarios.eliminar': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            irA(BASE_URL . '/config/app.php?accion=horarios.listar');
        }
        $A->horariosEliminar();
        break;
    }

        // NOTAS
    case 'notas.listar': {
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
            if ($rol === 'editor') { $E->notasListar(); } 
        elseif ($rol === 'admin') { $A->notasListar(); }
        else { $Co->notasListar(); }
    break;
    }

    case 'notas.crear': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); break; }
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
        if ($rol === 'admin' || $rol === 'editor' || $rol === 'consultor') {
            $A->notasCrear();
        } else {
            $Co->notasCrear();
        }
        break;
    }
    case 'notas.actualizar': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); break; }
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
        if ($rol === 'admin' || $rol === 'editor') {
            $A->notasActualizar();
        } else {
            $Co->notasActualizar();
        }
        break;
    }
    case 'notas.eliminar': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/config/app.php?accion=notas.listar'); break; }
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
        if ($rol === 'admin' || $rol === 'editor') {
            $A->notasEliminar();
        } else {
            $Co->notasEliminar();
        }
        break;
    }
    //Mostrar directorio telefonico
    case 'consultor.directorio': {
        $Co->directorioListar();
        break;
    }
    //Mostrar favoritos
    case 'consultor.favoritos': { 
        $Co->favoritosListar(); 
        break; 
    }
    case 'consultor.favorito.toggle': {
        $Co->favoritoToggle();
        break;
    }
    // JSON para dashboard consultor
    case 'consultor.favoritos.json': {
        $Co->favoritosJson();
        break;
    }

    // PERFILES
    case 'consultor.perfil': {
        $Co->perfil();
        break;
    }
    case 'consultor.perfil.guardar': {
        $Co->perfilGuardar();
        break;
    }

    //SOLICITUD CAMBIO
    case 'consultor.solicitud.listar': {
        $Co->solicitudCambioListar();
        break;
    }
    case 'consultor.solicitud.nueva': {
        $Co->solicitudCambioForm();
        break;
    }
    case 'consultor.solicitud.guardar': {
        $Co->solicitudCambioGuardar();
        break;
    }
    case 'consultor.propietario.json': {
        $Co->propietarioJson();
        break;
    }
    case 'consultor.solicitud.eliminar': {
        $Co->solicitudCambioEliminar();
        break;
    }
    // ADMIN
    case 'admin.solicitudes.cambio': {
        $A->solicitudesCambioListar();
        break;
    }
    case 'admin.solicitud.aprobar': {
        $A->solicitudCambioAprobar();
        break;
    }
    case 'admin.solicitud.rechazar': {
        $A->solicitudCambioRechazar();
        break;
    }

    // EDITOR
    case 'editor.solicitudes.cambio': {
        $E->solicitudesCambioListar();
        break;
    }
    case 'editor.solicitud.aprobar': {
        $E->solicitudCambioAprobar();
        break;
    }
    case 'editor.solicitud.rechazar': {
        $E->solicitudCambioRechazar();
        break;
    }

    //********REPORTES*********//

    //CONSULTOR
    case 'consultor.reportes': {
        $Co->reportesCatalogoConsultor();
        break;
    }
    case 'reporte.directorio': {
        $Co->reporteDirectorio();
        break;
    }
    case 'reporte.directorio.pdf': {
        $Co->reporteDirectorioPDF();
        break;
    }
    case 'reporte.directorio.excel': {
        $Co->reporteDirectorioExcel();
        break;
    }
    case 'reporte.directorio.filtros': {
        $Co->reporteDirectorioFiltros();
        break;
    }
    case 'reporte.directorio.filtros.pdf': {
        $Co->reporteDirectorioFiltrosPDF();
        break;
    }
    case 'reporte.directorio.filtros.excel': {
        $Co->reporteDirectorioFiltrosExcel();
        break;
    }
    case 'consultor.extension.copiada': {
        $Co->registrarConsultaExtension();
        break;
    }
    /* REPORTES EDITOR */
    case 'editor.reportes': {
        $E->reportesCatalogoEditor();
        break;
    }
    case 'reporte.directorio.editor': {
        $E->reporteDirectorioEditor();
        break;
    }
    case 'reporte.directorio.filtros.editor': {
        $E->reporteDirectorioFiltrosEditor();
        break;
    }
    case 'reporte.directorio.filtros.pdf.editor': {
        $E->reporteDirectorioFiltrosPDFEditor();
        break;
    }
    case 'reporte.directorio.filtros.excel.editor': {
        $E->reporteDirectorioFiltrosExcelEditor();
        break;
    }
    // --- REPORTES DE EXTENSIONES ---
    case 'editor.reporte.extensiones.usadas': {
        $E->reporteExtensionesUsadas();
        break;
    }
    case 'editor.reporte.extensiones.usadas.pdf': {
        $E->reporteExtensionesUsadasPDF();
        break;
    }
    case 'editor.reporte.empleados.area': {
        $E->reporteEmpleadosPorArea();
        break;
    }

    case 'editor.reporte.empleados.area.pdf': {
        $E->reporteEmpleadosPorAreaPDF();
        break;
    }
    // REPORTES EDITOR
    case 'editor.reporte.extensiones.grafica': {
        $E->reporteExtensionesGrafica();
        break;
    }
    case 'editor.reporte.extensiones.grafica.excel': {
        $E->reporteExtensionesGraficaExcel();
        break;
    }
    case 'editor.reporte.empleados.area.grafica': {
        $E->reporteEmpleadosAreaGrafica();
        break;
    }
    case 'editor.reporte.empleados.area.grafica.excel': {
        $E->reporteEmpleadosAreaGraficaExcel();
        break;
    }

    //************ADMIN****************//
    case 'admin.reportes': {
        $A->reportesCatalogoAdmin();
        break;
    }
    case 'admin.reporte.extensiones.usadas': {
        $A->reporteExtensionesUsadas();
        break;
    }
    case 'admin.reporte.extensiones.usadas.pdf': {
        $A->reporteExtensionesUsadasPDF();
        break;
    }
    case 'admin.reporte.empleados.area': {
        $A->reporteEmpleadosPorArea();
        break;
    }
    case 'admin.reporte.empleados.area.pdf': {
        $A->reporteEmpleadosPorAreaPDF();
        break;
    }
    case 'admin.reporte.extensiones.grafica': {
        $A->reporteExtensionesGrafica();
        break;
    }
    case 'admin.reporte.extensiones.grafica.excel': {
        $A->reporteExtensionesGraficaExcel();
        break;
    }
    case 'admin.reporte.empleados.area.grafica': {
        $A->reporteEmpleadosAreaGrafica();
        break;
    }
    case 'admin.reporte.empleados.area.grafica.excel': {
        $A->reporteEmpleadosAreaGraficaExcel();
        break;
    }

    /* RESPALDO Y RESTAURACIÓN DE BD */
    case 'admin.respaldos': {
        $A->respaldosListar();
        break;
    }
    case 'admin.respaldo.ejecutar': {   // solo crea y registra
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            irA(BASE_URL . '/config/app.php?accion=admin.respaldos');
        }
        $A->respaldoEjecutar();
        break;
    }
    case 'admin.respaldo.descargar': {  // descarga un respaldo ya creado
        $A->respaldoDescargar();
        break;
    }
    case 'admin.respaldo.restaurar': {  // aplica el .sql
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            irA(BASE_URL . '/config/app.php?accion=admin.respaldos');
        }
        $A->respaldoRestaurar();
        break;
    }
    case 'admin.respaldo.eliminar':
    (new AdminControlador())->respaldoEliminar();
    break;

    //  ACCIÓN DESCONOCIDA
    default: {
        irA(BASE_URL . '/config/app.php?accion=auth.form');
        break;
    }
}