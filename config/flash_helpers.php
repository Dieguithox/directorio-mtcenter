<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/* Guarda un flash corto */
function poner_flash(string $nivel, string $codigo, array $data = []): void
{
    $_SESSION['__flash'] = ['nivel' => $nivel, 'codigo' => $codigo, 'data' => $data];
}
/* Toma y elimina el flash. */
function tomar_flash(): ?array
{
    if (empty($_SESSION['__flash'])) return null;
    $f = $_SESSION['__flash'];
    unset($_SESSION['__flash']);
    return $f;
}
/* PRG: guarda errores y datos viejos */
function fallar_y_volver(string $origen, array $errores, array $viejo = []): void
{
    $_SESSION['errores']       = $errores;
    $_SESSION['erroresOrigen'] = $origen;
    $_SESSION['viejo']         = $viejo;
}