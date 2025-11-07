<?php
declare(strict_types=1);
require_once __DIR__ . '/BD.php';

class Directorio {
    private \PDO $db;

    public function __construct() {
        $this->db = BD::pdo();
    }

    public function listarPorArea(?string $q = null): array {
        // 👇 AQUI agregamos e.idExtension AS idExtension
        $baseSql = "
            SELECT 
                a.nombre AS area,
                a.email  AS correo_area,
                e.idExtension AS idExtension,
                e.numero AS extension,
                p.idPropietario,
                CONCAT(p.nombre, ' ', p.apellidoP, ' ', p.apellidoM) AS propietario,
                pu.nombre AS puesto,
                COALESCE(p.email, cp1.correo, '') AS correo
            FROM propietario p
            JOIN puesto pu 
                ON pu.idPuesto = p.puestoId
            JOIN area a 
                ON a.idArea = pu.areaId
            LEFT JOIN extension e 
                ON e.idExtension = p.extensionId
            LEFT JOIN (
                SELECT propietarioId, MIN(correo) AS correo
                FROM correoPropietario
                GROUP BY propietarioId
            ) cp1 ON cp1.propietarioId = p.idPropietario
        ";

        $where  = [];
        $params = [];

        if ($q !== null && $q !== '') {
            $where[] = "(
                p.nombre LIKE :q1
                OR p.apellidoP LIKE :q2
                OR p.apellidoM LIKE :q3
                OR pu.nombre LIKE :q4
                OR a.nombre LIKE :q5
                OR e.numero LIKE :q6
                OR p.email LIKE :q7
                OR cp1.correo LIKE :q8
            )";
            $like = '%' . $q . '%';
            $params = [
                ':q1' => $like,
                ':q2' => $like,
                ':q3' => $like,
                ':q4' => $like,
                ':q5' => $like,
                ':q6' => $like,
                ':q7' => $like,
                ':q8' => $like,
            ];
        }

        $orderBy = "
            ORDER BY 
                a.nombre,
                CASE WHEN e.numero REGEXP '^[0-9]+$' THEN CAST(e.numero AS UNSIGNED) ELSE NULL END,
                e.numero,
                p.apellidoP,
                p.apellidoM,
                p.nombre
        ";

        $sql = $baseSql
            . (empty($where) ? "" : " WHERE " . implode(" AND ", $where))
            . " " . $orderBy;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Reagrupar por área como ya lo hacías
        $out = [];
        foreach ($rows as $r) {
            $area = $r['area'];
            if (!isset($out[$area])) {
                $out[$area] = [
                    'correo_area' => $r['correo_area'] ?? '',
                    'contactos'   => [],
                ];
            }
            $out[$area]['contactos'][] = [
                'id'         => (int)$r['idPropietario'],
                'idExtension'=> $r['idExtension'] ? (int)$r['idExtension'] : 0, // 👈 AQUI lo mandamos a la vista
                'extension'  => $r['extension'],
                'nombre'     => $r['propietario'],
                'puesto'     => $r['puesto'],
                'correo'     => $r['correo'],
            ];
        }

        return $out;
    }
}