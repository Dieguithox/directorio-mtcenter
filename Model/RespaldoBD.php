<?php
require_once __DIR__ . '/BD.php';

class RespaldoBD {
    public function listar(): array {
        $pdo = BD::pdo();
        $st = $pdo->query("SELECT * FROM respaldoBD ORDER BY creado_at DESC");
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function registrar(string $archivo, int $tamano, ?int $usuarioId = null): bool {
        $pdo = BD::pdo();
        $sql = "INSERT INTO respaldoBD (archivo, tamano_bytes, realizadoPor) VALUES (?,?,?)";
        $st  = $pdo->prepare($sql);
        return $st->execute([$archivo, $tamano, $usuarioId]);
    }

    public function buscarPorId(int $id): ?array {
        $pdo = BD::pdo();
        $st = $pdo->prepare("SELECT * FROM respaldoBD WHERE idRespaldoDB = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function eliminar(int $id): bool {
        $pdo = BD::pdo();
        $st = $pdo->prepare("DELETE FROM respaldoBD WHERE idRespaldoDB = ?");
        return $st->execute([$id]);
    }
}