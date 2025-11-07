<?php
require_once __DIR__ . '/BD.php';

class Area
{
    public function todas(): array {
        $sql = "SELECT idArea, nombre, email, descripcion, creada_at FROM area ORDER BY nombre";
        $st  = BD::pdo()->query($sql);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function existeId(int $id): bool {
        $st = BD::pdo()->prepare("SELECT 1 FROM area WHERE idArea = ? LIMIT 1");
        $st->execute([$id]);
        return (bool)$st->fetchColumn();
    }

    public function existeNombre(string $nombre, ?int $ignorarId = null): bool {
        if ($ignorarId) {
            $st = BD::pdo()->prepare("SELECT 1 FROM area WHERE nombre = ? AND idArea <> ? LIMIT 1");
            $st->execute([$nombre, $ignorarId]);
        } else {
            $st = BD::pdo()->prepare("SELECT 1 FROM area WHERE nombre = ? LIMIT 1");
            $st->execute([$nombre]);
        }
        return (bool)$st->fetchColumn();
    }

    public function crear(string $nombre, ?string $email, ?string $descripcion): int {
    $st = BD::pdo()->prepare(
        "INSERT INTO area (nombre, email, descripcion) VALUES (:n, :e, :d)"
    );
    $ok = $st->execute([
        ':n' => $nombre,
        ':e' => ($email === '' ? null : $email),
        ':d' => ($descripcion === '' ? null : $descripcion),
    ]);
    return $ok ? (int)BD::pdo()->lastInsertId() : 0;
    }

    public function actualizar(int $id, string $nombre, ?string $email, ?string $descripcion): bool {
        $st = BD::pdo()->prepare(
            "UPDATE area
                SET nombre = :n, email = :e, descripcion = :d
                WHERE idArea = :id"
        );
        return $st->execute([
            ':id' => $id,
            ':n'  => $nombre,
            ':e'  => ($email === '' ? null : $email),
            ':d'  => ($descripcion === '' ? null : $descripcion),
        ]);
    }

    public function borrar(int $id): bool|string {
    try {
        $st = BD::pdo()->prepare("DELETE FROM area WHERE idArea = ?");
        $st->execute([$id]);
        return true;
    } catch (PDOException $e) {
        $info = $e->errorInfo; // [sqlstate, driver_code, driver_msg]
        if (($info[0] ?? '') === '23000' && (int)($info[1] ?? 0) === 1451) {
            return 'fk'; // dependencia referencial
        }
        return false; // otro error
    }
    }

    /* ================== correoArea (N correos por área) ================== */
    public function correosPorArea(int $areaId): array {
        $st = BD::pdo()->prepare(
            "SELECT idCorreoArea, areaId, correo, creado_at
                FROM correoArea
                WHERE areaId = ?
                ORDER BY creado_at DESC"
        );
        $st->execute([$areaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function agregarCorreo(int $areaId, string $correo): bool {
        $st = BD::pdo()->prepare(
            "INSERT INTO correoArea (areaId, correo) VALUES (?, ?)"
        );
        return $st->execute([$areaId, trim($correo)]);
    }

    public function eliminarCorreo(int $idCorreoArea): bool {
        $st = BD::pdo()->prepare("DELETE FROM correoArea WHERE idCorreoArea = ?");
        return $st->execute([$idCorreoArea]);
    }

    public function correosSolo(int $areaId): array {
    $st = BD::pdo()->prepare("SELECT correo FROM correoArea WHERE areaId = ? ORDER BY idCorreoArea ASC");
    $st->execute([$areaId]);
    return array_map(fn($r) => $r['correo'], $st->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public function eliminarCorreosDeArea(int $areaId): bool {
        $st = BD::pdo()->prepare("DELETE FROM correoArea WHERE areaId = ?");
        return $st->execute([$areaId]);
    }

    public function reemplazarCorreos(int $areaId, array $listaCorreos): bool {
        $pdo = BD::pdo();
        $pdo->beginTransaction();
        try {
            $this->eliminarCorreosDeArea($areaId);
            if (!empty($listaCorreos)) {
                $ins = $pdo->prepare("INSERT INTO correoArea (areaId, correo) VALUES (?, ?)");
                foreach ($listaCorreos as $c) {
                    $ins->execute([$areaId, trim($c)]);
                }
            }
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }
}