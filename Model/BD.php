<?php
class BD
{
    public static function pdo(): PDO
    {
        require __DIR__ . '/../config/config.php';
        static $pdo = null;
        if ($pdo !== null) {
            return $pdo;
        }
        // DSN = cadena de conexión de PDO para MySQL
        $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // errores como excepciones
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch por defecto como array asociativo
            PDO::ATTR_EMULATE_PREPARES   => false,                  // usar prepares nativos de MySQL
        ];
        // Crear conexión
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $opciones);
        // Alinear la zona horaria de la sesión MySQL con la de PHP
        $pdo->exec("SET time_zone = '" . date('P') . "'");
        return $pdo;
    }
}
