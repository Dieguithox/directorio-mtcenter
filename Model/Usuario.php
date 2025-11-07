<?php
require_once __DIR__ . '/BD.php';

class Usuario {
    /* Buscar por Email */
    public function buscarPorEmail(string $email): ?array{
        $email = strtolower(trim($email));
        $sql = "SELECT idUsuario, nombre, email, contrasenia, tipoUsuario
                FROM usuario
                WHERE email = ?
                LIMIT 1";
        $st = BD::pdo()->prepare($sql);
        $st->execute([$email]);
        $fila = $st->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    /* Descripción:
    Inserta un nuevo registro en la tabla `usuario`.
    Se encarga de normalizar el correo, generar el hash
    de la contraseña y ejecutar la consulta preparada. */
    /* Registrar Usuario en la base de datos */
    public function registrarUsuario(
        // Entradas nombre, email, constraseña normal, tipo de usuario default = consultor
        string $nombre, string $email, string $contraseniaPlano,string $tipoUsuario = 'consultor'
        // Salida con normalización de correo y contraseña con hash
    ): bool {
        // Normalizamos correo a minúsculas y elimina espacios. 
        $email = strtolower(trim($email));
        // Cifrado de contraseña, generando un hash de PHP bcrypt
        $hash = password_hash($contraseniaPlano, PASSWORD_DEFAULT);
        // Preparamos consulta SQL e inserta los datos en la tabla usuario evitando inyecciones SQL
        $sql = "INSERT INTO usuario (nombre, email, contrasenia, tipoUsuario) VALUES (?,?,?,?)";
        // Consulta preparada
        $st = BD::pdo()->prepare($sql);
        return $st->execute([$nombre, $email, $hash, $tipoUsuario]);
    }

    /* Actualizar contraseña */
    public function actualizarPassword(int $idUsuario, string $hash): bool {
    $sql = "UPDATE usuario SET contrasenia = ? WHERE idUsuario = ?";
    $st  = BD::pdo()->prepare($sql);
    return $st->execute([$hash, $idUsuario]);
    }

    /* MÉTODOS CRUD PARA LA GESTIÓN DE USUARIOS */
    /* Listado de usuarios con filtros por nombre o email */
    public function todos(string $busqueda = ''): array {
        $pdo = BD::pdo();
        if ($busqueda !== '') {
            $busqueda = '%' . strtolower(trim($busqueda)) . '%';
            $sql = "SELECT idUsuario, nombre, email, tipoUsuario, fechaRegistro FROM usuario
                    WHERE LOWER(nombre) LIKE ? OR LOWER(email) LIKE ? ORDER BY fechaRegistro DESC";
            $st = $pdo->prepare($sql);
            $st->execute([$busqueda, $busqueda]);
        } else {
            $sql = "SELECT idUsuario, nombre, email, tipoUsuario, fechaRegistro FROM usuario
                    ORDER BY fechaRegistro DESC";
            $st = $pdo->query($sql);
        }
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function contar(string $q = ''): int {
        $pdo = BD::pdo();
        if ($q !== '') {
            $q = "%".trim($q)."%";
            $st = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE nombre LIKE ? OR email LIKE ?");
            $st->execute([$q,$q]);
            return (int)$st->fetchColumn();
        }
        return (int)$pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
    }

    public function buscarPorId(int $id): ?array {
        $st = BD::pdo()->prepare("SELECT idUsuario, nombre, email, tipoUsuario FROM usuario WHERE idUsuario = ? LIMIT 1");
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function existeEmail(string $email, ?int $exceptoId = null): bool {
        $email = strtolower(trim($email));
        if ($exceptoId) {
            $st = BD::pdo()->prepare("SELECT 1 FROM usuario WHERE email = ? AND idUsuario <> ? LIMIT 1");
            $st->execute([$email,$exceptoId]);
        } else {
            $st = BD::pdo()->prepare("SELECT 1 FROM usuario WHERE email = ? LIMIT 1");
            $st->execute([$email]);
        }
        return (bool)$st->fetchColumn();
    }

    /* Crear variante “general” */
    public function crear(string $nombre, string $email, string $passwordPlano, string $rol): bool {
        return $this->registrarUsuario($nombre, $email, $passwordPlano, $rol);
    }

    /* Actualiza datos y opcionalmente contraseña */
    public function actualizar(int $id, string $nombre, string $email, string $rol, ?string $passwordPlano = null): bool {
        $email = strtolower(trim($email));
        if ($passwordPlano && $passwordPlano !== '') {
            $hash = password_hash($passwordPlano, PASSWORD_DEFAULT);
            $sql = "UPDATE usuario
                    SET nombre = ?, email = ?, tipoUsuario = ?, contrasenia = ?
                    WHERE idUsuario = ?";
            $st  = BD::pdo()->prepare($sql);
            return $st->execute([$nombre, $email, $rol, $hash, $id]);
        } else {
            $sql = "UPDATE usuario
                    SET nombre = ?, email = ?, tipoUsuario = ?
                    WHERE idUsuario = ?";
            $st  = BD::pdo()->prepare($sql);
            return $st->execute([$nombre, $email, $rol, $id]);
        }
    }

    public function eliminar(int $id): bool {
        $st = BD::pdo()->prepare("DELETE FROM usuario WHERE idUsuario = ?");
        return $st->execute([$id]);
    }
}