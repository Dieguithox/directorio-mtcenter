<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/flash_helpers.php';
require_once __DIR__ . '/../Model/BD.php';

require_once __DIR__ . '/../Model/Notificacion.php';
require_once __DIR__ . '/../Controller/AppControlador.php';
require_once __DIR__ . '/../Controller/AdminControlador.php';

class NotificacionControlador extends AdminControlador {

    private function asegurarAdminOEditor(): void {
        $accionActual = $_GET['accion'] ?? '';
        if ($accionActual === 'auth.form') {
            return;
        }
        $this->asegurarSesion();
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? 'consultor';
        if (!in_array($rol, ['admin','editor'], true)) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }
    }

    /* === BITACORA === */
    public function bitacoraListar(): void{
        $this->asegurarAdmin();
        $pdo     = BD::pdo();
        // Items por página con validación
        $perPage = (int)($_GET['perPage'] ?? 10);
        $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 10;
        // Página actual con validación
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;
        $q       = trim($_GET['q'] ?? '');
        // --- Totales + página actual ---
        if ($q !== '') {
            $like = "%{$q}%";
            // Total
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
            $stc->bindValue(':q1', $like);
            $stc->bindValue(':q2', $like);
            $stc->bindValue(':q3', $like);
            $stc->bindValue(':q4', $like);
            $stc->bindValue(':q5', $like);
            $stc->bindValue(':q6', $like);
            $stc->bindValue(':q7', $like);
            $stc->execute();
            $total_registros = (int)$stc->fetchColumn();
            // Página
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
            $st->bindValue(':q1',  $like);
            $st->bindValue(':q2',  $like);
            $st->bindValue(':q3',  $like);
            $st->bindValue(':q4',  $like);
            $st->bindValue(':q5',  $like);
            $st->bindValue(':q6',  $like);
            $st->bindValue(':q7',  $like);
            $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
            $st->bindValue(':off', $offset,  PDO::PARAM_INT);
            $st->execute();
            $logs = $st->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Total sin filtro
            $total_registros = (int)$pdo->query("SELECT COUNT(*) FROM bitacorasistema")->fetchColumn();
            // Página sin filtro
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
        require __DIR__ . '/../View/admin/bitacora.php';
    }

    /* ===== Helpers estáticos para lanzar notificaciones desde cualquier controlador ===== */
    public static function nuevoContacto(int $idPropietario, string $nombreCompleto): int {
        $uid = $_SESSION['usuario']['id'] ?? null;
        return Notificacion::crear(
            'nuevo_contacto',
            'Nuevo contacto',
            "Se registró el contacto {$nombreCompleto}.",
            null, null, $uid
        );
    }
    public static function modContacto(int $idPropietario, string $nombreCompleto): int {
        $uid = $_SESSION['usuario']['id'] ?? null;
        return Notificacion::crear(
            'modificacion_contacto',
            'Contacto actualizado',
            "Se actualizó el contacto {$nombreCompleto}.",
            null, null, $uid
        );
    }
    public static function elimContacto(int $idPropietario, string $nombreCompleto): int {
        $uid = $_SESSION['usuario']['id'] ?? null;
        return Notificacion::crear(
            'eliminacion_contacto',
            'Contacto eliminado',
            "Se eliminó el contacto {$nombreCompleto}.",
            null, null, $uid
        );
    }
    /* Helper genérico reutilizable (por ejemplo para usuarios/áreas) */
    public static function push(string $tipo, string $titulo, string $mensaje, ?string $entidad=null, ?int $entidadId=null): int {
        $uid = $_SESSION['usuario']['id'] ?? null;
        return Notificacion::crear($tipo,$titulo,$mensaje,$entidad,$entidadId,$uid);
    }

    /* ===== Endpoints JSON consumidos por la UI ===== */
    public function listar(): void {
        $this->asegurarAdminOEditor();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => Notificacion::ultimas(20)]);
        exit;
    }

    public function contador(): void {
        $this->asegurarAdminOEditor();
        header('Content-Type: application/json; charset=utf-8');
        $desde = $_GET['desde'] ?? null;
        echo json_encode(['count' => Notificacion::contarRecientes($desde)]);
        exit;
    }

    /* ===== Vista modal ligera ===== */
    public function modal(): void {
        $this->asegurarAdminOEditor();
        require __DIR__ . '/../View/admin/notificaciones.php';
    }
}
