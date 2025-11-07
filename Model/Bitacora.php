<?php
require_once __DIR__ . '/BD.php';

class Bitacora
{
    public function registrar(
        string $accion,
        ?string $modulo,
        ?string $tabla,
        ?string $pk_valor,
        ?string $descripcion,
        ?int $usuarioId
    ): void {
        // Toma nombre/email desde la sesión si existen (opcional pero útil)
        $usuarioNombre = $_SESSION['usuario']['nombre'] ?? null;
        $usuarioEmail  = $_SESSION['usuario']['email']  ?? null;

        // Usa SIEMPRE el mismo nombre que en tus SELECT: bitacorasistema
        $sql = "INSERT INTO bitacorasistema
                    (accion, modulo, tabla_bd, pk_valor, descripcion,
                    usuarioId, usuarioNombre, usuarioEmail)
                VALUES (?,?,?,?,?,?,?,?)";
        $st = BD::pdo()->prepare($sql);
        $st->execute([
            $accion,
            $modulo,
            $tabla,
            $pk_valor,
            $descripcion,
            $usuarioId,
            $usuarioNombre,
            $usuarioEmail
        ]);
    }

    public function contar(string $q = ''): int
    {
        $pdo = BD::pdo();
        if ($q !== '') {
            $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM bitacorasistema
            WHERE descripcion   LIKE :q OR usuarioNombre LIKE :q OR usuarioEmail LIKE :q
                OR modulo        LIKE :q OR accion        LIKE :q OR tabla_bd     LIKE :q
                OR pk_valor      LIKE :q
        ");
            $stmt->execute([':q' => "%$q%"]);
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) FROM bitacorasistema");
        }
        return (int)$stmt->fetchColumn();
    }

    public function paginar(int $offset, int $limit, string $q = ''): array
    {
        $pdo = BD::pdo();
        if ($q !== '') {
            $stmt = $pdo->prepare("
            SELECT idBitacora, accion, modulo, tabla_bd, pk_valor, descripcion,
                    usuarioNombre, usuarioEmail, creada_at, usuarioId
            FROM bitacorasistema
            WHERE descripcion   LIKE :q OR usuarioNombre LIKE :q OR usuarioEmail LIKE :q
                OR modulo        LIKE :q OR accion        LIKE :q OR tabla_bd     LIKE :q
                OR pk_valor      LIKE :q
            ORDER BY creada_at DESC, idBitacora DESC
            LIMIT :lim OFFSET :off
        ");
            $stmt->bindValue(':q', "%$q%");
        } else {
            $stmt = $pdo->prepare("
            SELECT idBitacora, accion, modulo, tabla_bd, pk_valor, descripcion,
                    usuarioNombre, usuarioEmail, creada_at, usuarioId
            FROM bitacorasistema
            ORDER BY creada_at DESC, idBitacora DESC
            LIMIT :lim OFFSET :off
        ");
        }
        $stmt->bindValue(':lim', (int)$limit,  PDO::PARAM_INT);
        $stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}