<?php
require_once __DIR__ . '/BD.php';

class Extension
{
    /* Lista todas las extensiones (más recientes primero). */
    public function todas(): array
    {
        $st = BD::pdo()->query(
            "SELECT idExtension, numero, descripcion, creada_at FROM extension ORDER BY idExtension DESC");
        return $st->fetchAll() ?: [];
    }
    /* Crea una extensión. */
    public function crear(string $numero, string $descripcion): bool
    {
        $sql = "INSERT INTO extension (numero, descripcion) VALUES (?, ?)";
        $st  = BD::pdo()->prepare($sql);
        return $st->execute([$numero, $descripcion]);
    }
    /* Actualiza una extensión por ID. */
    public function actualizar(int $id, string $numero, string $descripcion): bool
    {
        $sql = "UPDATE extension SET numero = ?, descripcion = ? WHERE idExtension = ?";
        $st  = BD::pdo()->prepare($sql);
        return $st->execute([$numero, $descripcion, $id]);
    }
    /* Elimina una extensión por ID. */
    public function borrar(int $id): bool
    {
        $st = BD::pdo()->prepare("DELETE FROM extension WHERE idExtension = ?");
        return $st->execute([$id]);
    }
    /* Busca una extensión por ID. */
    public function buscarPorId(int $id): ?array
    {
        $st = BD::pdo()->prepare(
            "SELECT idExtension, numero, descripcion, creada_at FROM extension WHERE idExtension = ? LIMIT 1");
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }
    /* Verifica si ya existe el número. */
    public function existeNumero(string $numero, ?int $exceptoId = null): bool
    {
        if ($exceptoId) {
            $sql = "SELECT 1 FROM extension WHERE numero = ? AND idExtension <> ? LIMIT 1";
            $st  = BD::pdo()->prepare($sql);
            $st->execute([$numero, $exceptoId]);
        } else {
            $sql = "SELECT 1 FROM extension WHERE numero = ? LIMIT 1";
            $st  = BD::pdo()->prepare($sql);
            $st->execute([$numero]);
        }
        return (bool)$st->fetchColumn();
    }

    public function catalogo(): array {
        return BD::pdo()->query("SELECT idExtension, numero FROM extension ORDER BY numero")->fetchAll(PDO::FETCH_ASSOC);
    }
}