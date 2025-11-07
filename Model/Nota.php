<?php
require_once __DIR__ . '/BD.php';

class Nota {
    public function listar(string $q = ''): array {
        $pdo = BD::pdo();
        $sql = "SELECT 
                    n.idNota,
                    n.texto,
                    n.creada_at,
                    n.propietarioId   AS idPropietario,
                    p.nombre          AS propNombre,
                    p.email           AS propEmail,
                    n.usuarioId       AS autorId,
                    u.nombre          AS autorNombre,
                    u.email           AS autorEmail
                FROM nota n
                JOIN propietario p ON p.idPropietario = n.propietarioId
                LEFT JOIN usuario u ON u.idUsuario = n.usuarioId";
        $params = [];
        if ($q !== '') {
            $sql .= " WHERE p.nombre LIKE :q OR p.email LIKE :q2 OR n.texto LIKE :q3";
            $params = [':q' => "%$q%", ':q2' => "%$q%", ':q3' => "%$q%"];
        }
        $sql .= " ORDER BY n.creada_at DESC, n.idNota DESC";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function propietariosTodos(): array {
        $pdo = BD::pdo();
        $st = $pdo->query("SELECT idPropietario, nombre, email FROM propietario ORDER BY nombre ASC");
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(int $propietarioId, string $texto, int $autorId): int {
        $pdo = BD::pdo();
        $st  = $pdo->prepare("INSERT INTO nota (texto, propietarioId, usuarioId) VALUES (:t, :pid, :uid)");
        $ok  = $st->execute([':t'=>$texto, ':pid'=>$propietarioId, ':uid'=>$autorId]);
        return $ok ? (int)$pdo->lastInsertId() : 0;
    }

    public function actualizar(int $idNota, string $texto): bool {
        $pdo = BD::pdo();
        $st  = $pdo->prepare("UPDATE nota SET texto=:t WHERE idNota=:id");
        return $st->execute([':t'=>$texto, ':id'=>$idNota]);
    }

    public function eliminar(int $idNota): bool {
        $pdo = BD::pdo();
        $st  = $pdo->prepare("DELETE FROM nota WHERE idNota=:id");
        return $st->execute([':id'=>$idNota]);
    }
}