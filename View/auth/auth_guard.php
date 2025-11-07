<?php
// Guardia simple para vistas protegidas
require_once __DIR__ . '/../../config/flash_helpers.php'; // helpers puros
require_once __DIR__ . '/../../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
// Si el usuario no está autenticado → login
if (empty($_SESSION['usuario'])) {
  poner_flash('danger', 'auth.debes_iniciar');
  header('Location: ' . BASE_URL . '/config/app.php?accion=auth.form');
  exit;
}
// Anti-caché (evita regresar con "atrás" tras logout)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');