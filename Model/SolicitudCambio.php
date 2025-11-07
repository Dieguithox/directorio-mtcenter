<?php
require_once __DIR__ . '/BD.php';

class SolicitudCambio {
    public function crear(array $data): bool{
        $pdo = BD::pdo();
        $sql = "INSERT INTO solicitudCambio
                (campo, valor_anterior, valor_nuevo, comentario, estado, motivo_revision, propietarioId, usuarioSolicitanteId)
                VALUES (:campo, :valor_anterior, :valor_nuevo, :comentario, 'pendiente', NULL, :propietarioId, :usuarioSolicitanteId)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':campo'               => $data['campo'],
            ':valor_anterior'      => $data['valor_anterior'],
            ':valor_nuevo'         => $data['valor_nuevo'],
            ':comentario'          => $data['comentario'],
            ':propietarioId'       => $data['propietarioId'],
            ':usuarioSolicitanteId'=> $data['usuarioSolicitanteId'],
        ]);
    }

    public function listarPorUsuario(int $usuarioId): array {
        $pdo = BD::pdo();
        $sql = "SELECT sc.*, p.nombre AS propietarioNombre, p.apellidoP AS propietarioApellidoP, p.apellidoM AS propietarioApellidoM
                FROM solicitudCambio sc
                INNER JOIN propietario p ON p.idPropietario = sc.propietarioId
                WHERE sc.usuarioSolicitanteId = ?
                ORDER BY sc.creada_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTodas(?string $estado = null): array {
        $pdo = BD::pdo();
        $params = [];
        $where  = '';
        if ($estado !== null && $estado !== '') {
            $where = "WHERE sc.estado = ?";
            $params[] = $estado;
        }
        $sql = "SELECT sc.*,
                        p.nombre      AS propietarioNombre,
                        p.apellidoP   AS propietarioApellidoP,
                        p.apellidoM   AS propietarioApellidoM,
                        p.email       AS propietarioEmail,
                        u.nombre      AS solicitanteNombre,
                        u.email       AS solicitanteEmail
                FROM solicitudCambio sc
                INNER JOIN propietario p ON p.idPropietario = sc.propietarioId
                INNER JOIN usuario u     ON u.idUsuario     = sc.usuarioSolicitanteId
                $where
                ORDER BY sc.creada_at DESC";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtener(int $id): ?array {
        $pdo = BD::pdo();
        $st = $pdo->prepare("SELECT * FROM solicitudCambio WHERE idSolicitudCambio = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function actualizarEstado(int $id, string $nuevoEstado, ?string $motivo = null): bool {
        $pdo = BD::pdo();
        $sql = "UPDATE solicitudCambio
                SET estado = :estado,
                    motivo_revision = :motivo
                WHERE idSolicitudCambio = :id";
        $st = $pdo->prepare($sql);
        return $st->execute([
            ':estado' => $nuevoEstado,
            ':motivo' => $motivo,
            ':id'     => $id,
        ]);
    }

    public function buscarPorId(int $id): ?array {
        $pdo = BD::pdo();
        $st = $pdo->prepare("SELECT * FROM solicitudCambio WHERE idSolicitudCambio = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function eliminar(int $id): bool{
        $pdo = BD::pdo();
        $st = $pdo->prepare("DELETE FROM solicitudCambio WHERE idSolicitudCambio = ? AND estado = 'pendiente'");
        return $st->execute([$id]);
    }
}