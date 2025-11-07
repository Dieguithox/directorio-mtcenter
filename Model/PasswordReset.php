<?php
require_once __DIR__ . '/BD.php';

class PasswordReset
{
    /*  */
    public function crear(int $usuarioId, string $tokenHash, string $expiraAt): bool {
        $sql = "INSERT INTO recuperarPassword (usuarioId, token_hash, expira_at) VALUES (?,?,?)";
        $st  = BD::pdo()->prepare($sql);
        return $st->execute([$usuarioId, $tokenHash, $expiraAt]);
    }

    /* Devuelve filas vigentes */
    public function tokensVigentesPorUsuario(int $usuarioId): array {
        $sql = "SELECT idRecuperarP, token_hash FROM recuperarPassword WHERE usuarioId = ? AND used = 0 AND expira_at > NOW() ORDER BY idRecuperarP DESC";
        $st = BD::pdo()->prepare($sql);
        $st->execute([$usuarioId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function marcarUsado(int $idRecuperarP): void {
        $st = BD::pdo()->prepare("UPDATE recuperarPassword SET used = 1 WHERE idRecuperarP = ?");
        $st->execute([$idRecuperarP]);
    }

    public function invalidarOtros(int $usuarioId, int $exceptoId): void {
        $st = BD::pdo()->prepare("UPDATE recuperarPassword SET used = 1 WHERE usuarioId = ? AND idRecuperarP <> ?");
        $st->execute([$usuarioId, $exceptoId]);
    }
}
