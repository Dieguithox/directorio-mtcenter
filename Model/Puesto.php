<?php
require_once __DIR__ . '/BD.php';

class Puesto {
    /* Mostrar puestos */
    public function todos(string $q = ''): array {
        $sql = "SELECT p.idPuesto, p.nombre, p.descripcion, p.creada_at, p.areaId, a.nombre AS areaNombre FROM puesto p INNER JOIN area a ON a.idArea = p.areaId";
        $params = [];
        if ($q !== '') {
            $sql .= " WHERE p.nombre LIKE ? OR a.nombre LIKE ?";
            $like = '%' . $q . '%';
            $params = [$like, $like];   // ← 2 placeholders, 2 valores
        }
        $sql .= " ORDER BY p.idPuesto DESC";

        $st = BD::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /*  Crear puesto */
    public function crear(string $nombre, string $descripcion, int $areaId): bool {
        $st = BD::pdo()->prepare("INSERT INTO puesto (nombre, descripcion, areaId) VALUES (?,?,?)");
        return $st->execute([$nombre, $descripcion, $areaId]);
    }

    /* Actualizar puesto */
    public function actualizar(int $id, string $nombre, string $descripcion, int $areaId): bool {
        $st = BD::pdo()->prepare("UPDATE puesto SET nombre = ?, descripcion = ?, areaId = ? WHERE idPuesto = ?");
        return $st->execute([$nombre, $descripcion, $areaId, $id]);
    }

    /* Borrar puesto */
    public function borrar(int $id): bool|string {
    try {
        $st = BD::pdo()->prepare("DELETE FROM puesto WHERE idPuesto = ?");
        $st->execute([$id]);
        return true;
    } catch (PDOException $e) {
        $info = $e->errorInfo;
        if (($info[0] ?? '') === '23000' && (int)($info[1] ?? 0) === 1451) {
            return 'fk';
        }
        return false;
    }
    }

    /* Buscar puesto por Id */
    public function buscarPorId(int $id): ?array {
        $st = BD::pdo()->prepare(
            "SELECT p.idPuesto, p.nombre, p.descripcion, p.areaId, a.nombre AS areaNombre FROM puesto p 
            JOIN area a ON a.idArea = p.areaId WHERE p.idPuesto = ? LIMIT 1");
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /* Nombre existente */
    public function existeNombreEnArea(string $nombre, int $areaId, ?int $exceptoId = null): bool {
        if ($exceptoId) {
            $sql = "SELECT 1 FROM puesto WHERE nombre = ? AND areaId = ? AND idPuesto <> ? LIMIT 1";
            $st  = BD::pdo()->prepare($sql);
            $st->execute([$nombre, $areaId, $exceptoId]);
        } else {
            $sql = "SELECT 1 FROM puesto WHERE nombre = ? AND areaId = ? LIMIT 1";
            $st  = BD::pdo()->prepare($sql);
            $st->execute([$nombre, $areaId]);
        }
        return (bool)$st->fetchColumn();
    }

    public function catalogo(): array {
        $sql = "SELECT p.idPuesto, p.nombre, p.areaId, a.nombre AS areaNombre
                    FROM puesto p
                    INNER JOIN area a ON a.idArea = p.areaId
                    ORDER BY a.nombre, p.nombre";
        return BD::pdo()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    public function porArea(int $areaId): array {
        $st = BD::pdo()->prepare("SELECT idPuesto, nombre FROM puesto WHERE areaId = :a ORDER BY nombre");
        $st->execute([':a' => $areaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}