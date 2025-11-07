<?php
require_once __DIR__ . '/../auth/auth_guard.php';
$rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
$nombre = $_SESSION['usuario']['nombre'] ?? 'Consultor';
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= $title ?? 'Directorio Telefónico' ?></title>
    <!-- Bootstrap 5.3 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Fuente un poco más formal para la marca -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/View/contenido/css/styleConsultor.css">
</head>
<body class="d-flex flex-column min-vh-100">
<!-- SIDEBAR -->
<aside id="sidebar" class="sidebar" aria-label="Menú lateral">
    <div class="brand">
        <img src="<?= BASE_URL ?>/View/contenido/img/mtc-logo.png" class="brand-logo" alt="Logo" />
        <span class="brand-title">Directorio MTCenter</span>
        <button id="toggleSidebar" class="toggle" title="Colapsar/expandir">
        <i class="bi bi-caret-left-fill"></i>
        </button>
    </div>
    <!-- Principal -->
    <div class="nav-group">
        <div class="nav-group-title">Principal</div>
        <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= ($active ?? '')==='home'?'active':'' ?>"
            href="<?= BASE_URL ?>/config/app.php?accion=home.dashboard">
            <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($active ?? '')==='directorio'?'active':'' ?>"
            href="<?= BASE_URL ?>/config/app.php?accion=consultor.directorio">
            <i class="bi bi-telephone"></i><span>Directorio</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($active ?? '')==='favoritos'?'active':'' ?>"
            href="<?= BASE_URL ?>/config/app.php?accion=consultor.favoritos">
            <i class="bi bi-star"></i><span>Favoritos</span>
            </a>
        </li>
        </ul>
    </div>
    <!-- Gestiones para el tipo de usuario -->
    <div class="nav-group">
        <div class="nav-group-title">Gestión</div>
        <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= ($active ?? '')==='notas'?'active':'' ?>"
            href="<?= BASE_URL ?>/config/app.php?accion=notas.listar">
            <i class="bi bi-journal-text"></i><span>Notas</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($active ?? '')==='cambios'?'active':'' ?>"
            href="<?= BASE_URL ?>/config/app.php?accion=consultor.solicitud.listar">
            <i class="bi bi-pencil-square"></i><span>Cambios</span>
            </a>
        </li>
        </ul>
    </div>
    <!-- Sistema -->
    <div class="nav-group">
        <div class="nav-group-title">Sistema</div>
        <ul class="nav flex-column">
        <!-- Reportes del sistema -->
        <li class="nav-item">
            <a class="nav-link <?= ($active ?? '')==='reportes'?'active': '' ?>"
            href="<?= BASE_URL ?>/config/app.php?accion=consultor.reportes">
            <i class="bi bi-graph-up"></i><span>Reportes</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($active ?? '')==='config'?'active':'' ?>"
            href="<?= BASE_URL ?>/config/app.php?accion=consultor.perfil">
            <i class="bi bi-gear"></i><span>Configuración</span>
            </a>
        </li>
        </ul>
    </div>
</aside>
<!-- TOPBAR -->
<header class="topbar">
    <div class="topbar-right ms-auto d-flex align-items-center gap-2">
        <!-- Variante: buscador compacto pill -->
        <form id="topSearchForm" action="#" class="search-compact d-none d-md-flex">
            <input type="search" name="q" id="topSearchInput" class="search-input" placeholder="Buscar…" aria-label="Buscar">
            <button class="search-btn" aria-label="Buscar"><i class="bi bi-search"></i></button>
        </form>
        <span class="fw-semibold d-none d-sm-inline text-white">Hola, <?= htmlspecialchars($nombre) ?></span>
        <!--<button class="btn btn-light position-relative" aria-label="Notificaciones" title="Notificaciones">
            <i class="bi bi-bell text-primary"></i>
            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
        </button>-->
        <form method="post" action="<?= BASE_URL ?>/config/app.php?accion=auth.logout">
            <button type="submit" class="btn btn-ghost">Cerrar Sesión</button>
        </form>
    </div>
</header>

<!-- SCRIPTS (lógica de colapso y clase en body para mover el contenido -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebar   = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    const bodyEl    = document.body;
    function applyInitialState(){
        const saved = localStorage.getItem('sidebarCollapsed');
        const collapsed = saved === null ? (window.innerWidth < 992) : (saved === 'true');
        sidebar.classList.toggle('collapsed', collapsed);
        bodyEl.classList.toggle('sidebar-collapsed', collapsed);
    }
    applyInitialState();
    toggleBtn?.addEventListener('click', () => {
        const collapsed = !sidebar.classList.contains('collapsed');
        sidebar.classList.toggle('collapsed', collapsed);
        bodyEl.classList.toggle('sidebar-collapsed', collapsed);
        localStorage.setItem('sidebarCollapsed', collapsed);
    });
    window.addEventListener('resize', () => {
        if (localStorage.getItem('sidebarCollapsed') === null) {
        const collapsed = window.innerWidth < 992;
        sidebar.classList.toggle('collapsed', collapsed);
        bodyEl.classList.toggle('sidebar-collapsed', collapsed);
        }
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('topSearchForm');
    const input = document.getElementById('topSearchInput');

    if (!form || !input) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault(); // no ir a PHP
        const text = input.value.trim().toLowerCase();
        filtrarContenido(text);
    });

    // opcional: filtrar mientras escribe
    input.addEventListener('input', function () {
        const text = this.value.trim().toLowerCase();
        filtrarContenido(text);
    });

    function filtrarContenido(texto) {
        // 1) Tablas
        const filas = document.querySelectorAll('main table tbody tr');
        if (filas.length) {
            filas.forEach(tr => {
                const contenido = tr.innerText.toLowerCase();
                if (texto === '' || contenido.includes(texto)) {
                    tr.classList.remove('d-none');
                } else {
                    tr.classList.add('d-none');
                }
            });
        }

        // 2) Cards (por si tienes tarjetas de notas/favoritos)
        const cards = document.querySelectorAll('main .card, main .nota-item, main .fav-card');
        if (cards.length) {
            cards.forEach(card => {
                const contenido = card.innerText.toLowerCase();
                if (texto === '' || contenido.includes(texto)) {
                    card.classList.remove('d-none');
                } else {
                    card.classList.add('d-none');
                }
            });
        }
    }
});
</script>

<!-- CONTENIDO DE LA VISTA -->
<main class="main">
    <div class="container-fluid">
        <div class="mb-3"><span class="badge rounded-pill badge-role">Consultor · Vista principal</span></div>
        <!-- CONTENIDO -->