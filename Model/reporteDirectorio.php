<?php
require_once __DIR__ . '/BD.php';
class ReporteDirectorio {
    private $pdo;

    public function __construct() {
        $this->pdo = BD::pdo();
    }

    public function obtenerDirectorioCompleto() {
        $sql = "SELECT
                    a.nombre AS area,
                    a.email  AS correoArea,
                    e.numero AS extension,
                    CONCAT(p.nombre, ' ', p.apellidoP, ' ', p.apellidoM) AS nombre,
                    pu.nombre AS puesto,
                    COALESCE(p.email, '') AS correoPropietario
                FROM propietario p
                INNER JOIN puesto pu ON p.puestoId = pu.idPuesto
                INNER JOIN area a ON pu.areaId = a.idArea
                LEFT JOIN extension e ON p.extensionId = e.idExtension
                ORDER BY a.nombre ASC, p.nombre ASC, p.apellidoP ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $agrupado = [];
        foreach ($rows as $row) {
            $area = $row['area'] ?: 'Sin área';
            if (!isset($agrupado[$area])) {
                $agrupado[$area] = [];
            }
            $agrupado[$area][] = $row;
        }
        return $agrupado;
    }

    public function obtenerDirectorioFiltrado(string $nombre = '', string $extension = '', ?int $areaId = null) {
        $sql = "
            SELECT
                a.nombre AS area,
                COALESCE(ca.correoArea, a.email) AS correoArea,
                e.numero AS extension,
                CONCAT(p.nombre, ' ', p.apellidoP, ' ', p.apellidoM) AS nombre,
                pu.nombre AS puesto,
                COALESCE(cp.correos, p.email) AS correoPropietario
            FROM propietario p
            INNER JOIN puesto pu ON p.puestoId = pu.idPuesto
            INNER JOIN area  a   ON pu.areaId = a.idArea
            LEFT JOIN extension e ON p.extensionId = e.idExtension
            /* correos del propietario agrupados */
            LEFT JOIN (
                SELECT propietarioId,
                    GROUP_CONCAT(correo ORDER BY correo SEPARATOR ', ') AS correos
                FROM correoPropietario
                GROUP BY propietarioId
            ) cp ON cp.propietarioId = p.idPropietario
            /* correos del área agrupados */
            LEFT JOIN (
                SELECT areaId,
                    GROUP_CONCAT(correo ORDER BY correo SEPARATOR ', ') AS correoArea
                FROM correoArea
                GROUP BY areaId
            ) ca ON ca.areaId = a.idArea
        ";

        $conds = [];

        // 🚩 aquí estaba el problema: mismo nombre 3 veces
        if ($nombre !== '') {
            $conds[] = "(p.nombre LIKE :nom1 OR p.apellidoP LIKE :nom2 OR p.apellidoM LIKE :nom3)";
        }
        if ($extension !== '') {
            $conds[] = "e.numero LIKE :ext";
        }
        if (!is_null($areaId) && $areaId > 0) {
            $conds[] = "a.idArea = :areaId";
        }

        if ($conds) {
            $sql .= " WHERE " . implode(' AND ', $conds);
        }

        $sql .= "
            GROUP BY
                p.idPropietario,
                a.nombre,
                a.email,
                ca.correoArea,
                e.numero,
                pu.nombre,
                cp.correos,
                p.email
            ORDER BY a.nombre, p.nombre, p.apellidoP
        ";

        $stmt = $this->pdo->prepare($sql);

        // ✅ bind SOLO de lo que esté en el SQL y con nombres únicos
        if ($nombre !== '') {
            $like = "%{$nombre}%";
            $stmt->bindValue(':nom1', $like, PDO::PARAM_STR);
            $stmt->bindValue(':nom2', $like, PDO::PARAM_STR);
            $stmt->bindValue(':nom3', $like, PDO::PARAM_STR);
        }
        if ($extension !== '') {
            $stmt->bindValue(':ext', "%{$extension}%", PDO::PARAM_STR);
        }
        if (!is_null($areaId) && $areaId > 0) {
            $stmt->bindValue(':areaId', $areaId, PDO::PARAM_INT);
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // lo devolvemos agrupado por área (como espera tu vista)
        $agrupado = [];
        foreach ($rows as $row) {
            $areaNombre = $row['area'];
            if (!isset($agrupado[$areaNombre])) {
                $agrupado[$areaNombre] = [];
            }
            $agrupado[$areaNombre][] = $row;
        }

        return $agrupado;
    }

    
    public function listarAreas() {
        $stmt = $this->pdo->query("SELECT idArea, nombre FROM area ORDER BY nombre");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}