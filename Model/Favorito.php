<?php
declare(strict_types=1);
require_once __DIR__ . '/BD.php';

class Favorito {
    private \PDO $db;

    public function __construct() {
        $this->db = BD::pdo();
    }

    public function esFavorito(int $usuarioId, int $propietarioId): bool {
        $st = $this->db->prepare("SELECT 1 FROM favoritoPropietario WHERE usuarioId = :u AND propietarioId = :p LIMIT 1");
        $st->execute([':u' => $usuarioId, ':p' => $propietarioId]);
        return (bool)$st->fetchColumn();
    }

    public function marcar(int $usuarioId, int $propietarioId): void {
        if ($this->esFavorito($usuarioId, $propietarioId)) {
            return;
        }
        $st = $this->db->prepare("INSERT INTO favoritoPropietario (usuarioId, propietarioId) VALUES (:u, :p)");
        $st->execute([':u' => $usuarioId, ':p' => $propietarioId]);
    }

    public function desmarcar(int $usuarioId, int $propietarioId): void {
        $st = $this->db->prepare("DELETE FROM favoritoPropietario WHERE usuarioId = :u AND propietarioId = :p");
        $st->execute([':u' => $usuarioId, ':p' => $propietarioId]);
    }

    public function toggle(int $usuarioId, int $propietarioId): bool {
        if ($this->esFavorito($usuarioId, $propietarioId)) {
            $this->desmarcar($usuarioId, $propietarioId);
            return false;
        }
        $this->marcar($usuarioId, $propietarioId);
        return true;
    }

    public function favoritosIds(int $usuarioId): array {
        $st = $this->db->prepare("SELECT propietarioId FROM favoritoPropietario WHERE usuarioId = :u");
        $st->execute([':u' => $usuarioId]);
        return array_map('intval', array_column($st->fetchAll(PDO::FETCH_ASSOC), 'propietarioId'));
    }

    public function listarFavoritos(int $usuarioId, ?string $q = null): array {
        $sql = "SELECT
                p.idPropietario,
                CONCAT(p.nombre,' ',p.apellidoP,' ',p.apellidoM) AS nombreCompleto,
                pu.nombre AS puesto,
                a.nombre AS area,
                COALESCE(p.email, cp1.correo, '') AS correo,
                e.numero AS extension
            FROM favoritoPropietario f
            JOIN propietario p ON p.idPropietario = f.propietarioId
            JOIN puesto pu ON pu.idPuesto = p.puestoId
            JOIN area a ON a.idArea = pu.areaId
            LEFT JOIN extension e ON e.idExtension = p.extensionId
            LEFT JOIN (
                SELECT propietarioId, MIN(correo) AS correo
                FROM correoPropietario
                GROUP BY propietarioId
            ) cp1 ON cp1.propietarioId = p.idPropietario
            WHERE f.usuarioId = :u ";

        $params = [':u' => $usuarioId];

        if ($q !== null && $q !== '') {
            $sql .= "AND(
                    p.nombre LIKE :q1
                    OR p.apellidoP LIKE :q2
                    OR p.apellidoM LIKE :q3
                    OR pu.nombre LIKE :q4
                    OR a.nombre LIKE :q5
                    OR e.numero LIKE :q6
                    OR p.email LIKE :q7 )";
            $like = '%' . $q . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
            $params[':q4'] = $like;
            $params[':q5'] = $like;
            $params[':q6'] = $like;
            $params[':q7'] = $like;
        }
        $sql .= "ORDER BY a.nombre, pu.nombre, p.apellidoP, p.apellidoM, p.nombre";
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}