<?php
require_once __DIR__ . '/BD.php';

class ReporteEmpleadosArea {
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = BD::pdo();
    }

    /**
     * Regresa un arreglo con: idArea, nombreArea, correoArea, totalEmpleados
     */
    public function obtenerTotalesPorArea(): array
    {
        $sql = "
            SELECT 
                a.idArea,
                a.nombre AS nombreArea,
                a.email  AS correoArea,
                COUNT(p.idPropietario) AS totalEmpleados
            FROM area a
            LEFT JOIN puesto pu     ON pu.areaId = a.idArea
            LEFT JOIN propietario p ON p.puestoId = pu.idPuesto
            GROUP BY a.idArea, a.nombre, a.email
            ORDER BY a.nombre ASC
        ";
        $st = $this->pdo->query($sql);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}