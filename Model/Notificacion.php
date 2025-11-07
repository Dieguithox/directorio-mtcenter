<?php
require_once __DIR__ . '/BD.php';

class Notificacion
{
    public static function crear(
        string $tipo,            // 'nuevo_contacto'|'eliminacion_contacto'|'modificacion_contacto'|...
        string $titulo,
        string $mensaje,
        ?string $entidad = null, // 'propietario'|'extension'|'usuario'|'area'|'puesto'|NULL
        ?int $entidadId = null,
        ?int $creadoPor = null
    ): int {
        $sql = "INSERT INTO notificacion (tipo, titulo, mensaje, creadoPor)
                VALUES (?,?,?,?)";
        $st  = BD::pdo()->prepare($sql);
        $st->execute([$tipo, $titulo, $mensaje, $creadoPor]);
        return (int)BD::pdo()->lastInsertId();
    }

    public static function ultimas(int $limite = 20): array {
    $sql = "SELECT n.idNotificacion, n.tipo, n.titulo, n.mensaje, n.creadoPor, n.creada_at,
                    u.nombre AS creadorNombre, u.email AS creadorEmail
            FROM notificacion n
            LEFT JOIN usuario u ON u.idUsuario = n.creadoPor
            ORDER BY n.creada_at DESC
            LIMIT ?";
    $st = BD::pdo()->prepare($sql);
    $st->bindValue(1, $limite, \PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(\PDO::FETCH_ASSOC);
}

    public static function contarRecientes(?string $desdeISO = null): int {
        if ($desdeISO) {
            $st = BD::pdo()->prepare("SELECT COUNT(*) FROM notificacion WHERE creada_at >= ?");
            $st->execute([$desdeISO]);
            return (int)$st->fetchColumn();
        }
        $st = BD::pdo()->query("SELECT COUNT(*) FROM notificacion WHERE creada_at >= (NOW() - INTERVAL 1 DAY)");
        return (int)$st->fetchColumn();
    }

    
}