<?php
require_once __DIR__ . '/BD.php';

class Propietario
{
    /* Listado: el área viene por el JOIN con puesto.areaId */
    public function listar(string $q = ''): array {
        $pdo = BD::pdo();

        $sql = "SELECT pr.*,
                    a.nombre AS areaNombre,
                    p.nombre AS puestoNombre,
                    e.numero AS extensionNumero,
                    p.areaId  AS areaIdDelPuesto
                FROM propietario pr
        INNER JOIN puesto    p ON p.idPuesto    = pr.puestoId
        INNER JOIN area      a ON a.idArea      = p.areaId
            LEFT JOIN extension e ON e.idExtension = pr.extensionId";
        $params = [];
        if ($q !== '') {
            $sql .= " WHERE pr.nombre    LIKE :q1
                        OR pr.apellidoP LIKE :q2
                        OR pr.apellidoM LIKE :q3
                        OR pr.email     LIKE :q4
                        OR e.numero     LIKE :q5";
            $like = '%' . $q . '%';
            $params = [
                ':q1' => $like,
                ':q2' => $like,
                ':q3' => $like,
                ':q4' => $like,
                ':q5' => $like,
            ];
        }
        $sql .= " ORDER BY a.nombre, p.nombre, pr.apellidoP, pr.apellidoM, pr.nombre";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }


    /* Crear: mapeamos correo->email; área NO se guarda (se deduce por el puesto) */
    public function crear(array $data): int {
        $pdo = BD::pdo();
        $sql = "INSERT INTO propietario (nombre, email, apellidoP, apellidoM, puestoId, extensionId)
                VALUES (:nombre, :email, :apellidoP, :apellidoM, :puestoId, :extensionId)";
        $st = $pdo->prepare($sql);
        $st->execute([
            ':nombre'      => trim($data['nombre']),
            ':email'       => trim($data['correo']),
            ':apellidoP'   => trim($data['apellidoP']),
            ':apellidoM'   => trim($data['apellidoM']),
            ':puestoId'    => (int)$data['puestoId'],
            ':extensionId' => ($data['extensionId'] === '' || $data['extensionId'] === null)
                ? null : (int)$data['extensionId'],
        ]);
        return (int)$pdo->lastInsertId();
    }

    /* Actualizar */
    public function actualizar(int $id, array $data): void {
        $pdo = BD::pdo();
        $sql = "UPDATE propietario
                    SET nombre      = :nombre,
                        email       = :email,
                        apellidoP   = :apellidoP,
                        apellidoM   = :apellidoM,
                        puestoId    = :puestoId,
                        extensionId = :extensionId
                    WHERE idPropietario = :id";
        $st = $pdo->prepare($sql);
        $st->execute([
            ':id'          => $id,
            ':nombre'      => trim($data['nombre']),
            ':email'       => trim($data['correo']),          // mapeo a email
            ':apellidoP'   => trim($data['apellidoP']),
            ':apellidoM'   => trim($data['apellidoM']),
            ':puestoId'    => (int)$data['puestoId'],
            ':extensionId' => ($data['extensionId'] === '' || $data['extensionId'] === null)
                ? null : (int)$data['extensionId'],
        ]);
    }

    public function eliminar(int $id): void {
        BD::pdo()->prepare("DELETE FROM propietario WHERE idPropietario = :id")
            ->execute([':id' => $id]);
    }

    public function buscar(int $id): ?array {
        $st = BD::pdo()->prepare("SELECT * FROM propietario WHERE idPropietario = :id");
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /* ===== Correos adicionales (correoPropietario) ===== */
    public function correosPorPropietario(int $id): array {
        $st = BD::pdo()->prepare("SELECT idCorreoPropietario, correo 
                                FROM correoPropietario 
                                WHERE propietarioId = :id
                                ORDER BY idCorreoPropietario ASC");
        $st->execute([':id' => $id]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function correosSolo(int $id): array {
        $st = BD::pdo()->prepare("SELECT correo 
                                FROM correoPropietario 
                                WHERE propietarioId = :id
                                ORDER BY idCorreoPropietario ASC");
        $st->execute([':id' => $id]);
        return array_map(fn($r) => $r['correo'], $st->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public function agregarCorreo(int $propietarioId, string $correo): bool {
        $st = BD::pdo()->prepare("INSERT INTO correoPropietario (propietarioId, correo) 
                                VALUES (:pid, :c)");
        return $st->execute([':pid' => $propietarioId, ':c' => trim($correo)]);
    }

    public function eliminarCorreo(int $idCorreoPropietario): bool {
        $st = BD::pdo()->prepare("DELETE FROM correoPropietario WHERE idCorreoPropietario = :id");
        return $st->execute([':id' => $idCorreoPropietario]);
    }

    public function reemplazarCorreos(int $propietarioId, array $listaCorreos): void {
        $pdo = BD::pdo();
        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare("DELETE FROM correoPropietario WHERE propietarioId = :pid");
            $del->execute([':pid' => $propietarioId]);
            if (!empty($listaCorreos)) {
                $ins = $pdo->prepare("INSERT INTO correoPropietario (propietarioId, correo) VALUES (:pid, :c)");
                foreach ($listaCorreos as $c) {
                    $c = trim((string)$c);
                    if ($c !== '') { $ins->execute([':pid' => $propietarioId, ':c' => $c]); }
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function buscarPorId(int $id): ?array {
        $sql = "SELECT idPropietario, nombre, apellidoP, apellidoM, email
                FROM propietario
                WHERE idPropietario = :id";
        $st = BD::pdo()->prepare($sql);
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /* Horario */
    public function opcionesSelect(?string $q = ''): array {
        $sql = "SELECT idPropietario, CONCAT(nombre, ' ', apellidoP, ' ', apellidoM) 
                AS nombreCompleto, email FROM propietario";
        $params = [];
        if ($q !== null && $q !== '') {
            $like = '%' . $q . '%';
            $sql .= " WHERE nombre LIKE ? OR apellidoP LIKE ? OR apellidoM LIKE ? OR email LIKE ?";
            array_push($params, $like, $like, $like, $like);
        }
        $sql .= " ORDER BY nombre, apellidoP";
        $st = BD::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}