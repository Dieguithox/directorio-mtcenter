<?php
require_once __DIR__ . '/BD.php';

class ConsultaExtension {
    /** @var PDO */
    private $db;

    public function __construct() {
        $this->db = BD::pdo();
    }

    /** 1) Registrar que alguien copió/consultó una extensión se usa en: accion=consultor.extension.copiada */
    public function registrar(int $extensionId, ?int $usuarioId = null): bool {
        $sql = "INSERT INTO consultaExtension (extensionId, usuarioId) VALUES (:ext, :usr)";
        $st = $this->db->prepare($sql);
        return $st->execute([
            ':ext' => $extensionId,
            ':usr' => $usuarioId,
        ]);
    }

    /** 2) Listar áreas para los filtros de los reportes */
    public function obtenerAreas(): array {
        $sql = "SELECT idArea, nombre FROM area ORDER BY nombre";
        $st = $this->db->query($sql);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** 3) Extensiones más usadas (reporte sin gráfica) */
    public function obtenerMasUsadas(?int $areaId = null): array {
        $sql = "SELECT e.idExtension, e.numero AS numeroExtension,
                COALESCE(CONCAT(p.nombre, ' ', p.apellidoP, ' ', p.apellidoM), 'Sin propietario') AS propietarioNombre, a.nombre AS nombreArea,
                COUNT(ce.idConsultaExtension) AS totalConsultas
            FROM consultaExtension ce
            INNER JOIN extension e ON e.idExtension = ce.extensionId
            LEFT JOIN propietario p
                ON p.extensionId = e.idExtension
            LEFT JOIN puesto pu
                ON pu.idPuesto = p.puestoId
            LEFT JOIN area a
                ON a.idArea = pu.areaId";
        $params = [];
        if ($areaId) {
            $sql .= " WHERE a.idArea = :areaId ";
            $params[':areaId'] = $areaId;
        }
        $sql .= "
            GROUP BY
                e.idExtension,
                p.idPropietario,
                a.idArea
            ORDER BY
                totalConsultas DESC,
                e.numero ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** 4) Extensiones más usadas para la GRÁFICA */
    public function obtenerMasUsadasGrafica(?int $areaId = null): array {
        $sql = "
            SELECT 
                e.idExtension,
                e.numero AS extension,
                COUNT(ce.idConsultaExtension) AS totalConsultas
            FROM consultaExtension ce
            INNER JOIN extension e ON e.idExtension = ce.extensionId
            LEFT JOIN propietario p ON p.extensionId = e.idExtension
            LEFT JOIN puesto pu ON pu.idPuesto = p.puestoId
            LEFT JOIN area a ON a.idArea = pu.areaId
            WHERE 1=1";
        $params = [];

        if (!empty($areaId)) {
            $sql .= " AND a.idArea = :areaId ";
            $params[':areaId'] = $areaId;
        }

        $sql .= "
            GROUP BY e.idExtension, e.numero
            ORDER BY totalConsultas DESC
        ";

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}