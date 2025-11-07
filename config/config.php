<?php
/* CREDENCIALES DE CONEXIÓN -> XAMPP */
$DB_HOST = 'localhost';
$DB_NAME = 'directorioTelMTC';
$DB_USER = 'root';
$DB_PASS = '';
// Zona horaria
date_default_timezone_set('America/Mexico_City');
// Tiempo de vida del enlace de recuperación
if (!defined('RESET_TTL_SECONDS')) define('RESET_TTL_SECONDS', 600); // 10 min

// Ruta base del proyecto 
if (!defined('BASE_URL')) {
  define('BASE_URL', '/estadia');
}
// URL ABSOLUTA para enlaces en correos:
if (!defined('APP_URL')) define('APP_URL', 'http://localhost/estadia');
// SMTP -> Credenciales para recuperacion de cotraseña por correo
if (!defined('SMTP_HOST'))   define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT'))   define('SMTP_PORT', 587);     // 587=tls
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', 'tls'); // 'tls'
if (!defined('SMTP_USER'))   define('SMTP_USER', 'dieguithox15@gmail.com');
if (!defined('SMTP_PASS'))   define('SMTP_PASS', 'dwzk terj dzow jfpc'); // Password Gmail - codigo
if (!defined('MAIL_FROM'))   define('MAIL_FROM', 'dieguithox15@gmail.com');
if (!defined('MAIL_FROM_NOMBRE')) define('MAIL_FROM_NOMBRE', 'Directorio Telefónico');