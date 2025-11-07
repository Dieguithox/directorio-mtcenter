<?php
require_once __DIR__ . '/../auth/auth_guard.php';
$rol = $_SESSION['usuario']['tipoUsuario'] ?? '';
$nombre = $_SESSION['usuario']['nombre'] ?? 'Administrador';
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
    <!-- Fuente -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/View/contenido/css/styleAdmin.css">
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/35c8a07d23.js" crossorigin="anonymous"></script>
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
        <!-- Acesso Principal -->
        <div class="nav-group">
            <div class="nav-group-title">Principal</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= ($active ?? '') === 'home' ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>/config/app.php?accion=home.dashboard">
                        <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>
        <!-- Gestiones para el tipo de usuario -->
        <div class="nav-group">
            <div class="nav-group-title">Gestión</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= ($active ?? '') === 'extension' ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>/config/app.php?accion=extensiones.listar">
                        <i class="fa-solid fa-phone"></i><span>Extensiones</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($active ?? '') === 'propietario' ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>/config/app.php?accion=propietarios.listar">
                        <i class="fa-solid fa-user"></i><span>Propietarios</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($active ?? '') === 'puestos' ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>/config/app.php?accion=puestos.listar">
                        <i class="fa-solid fa-briefcase"></i><span>Puestos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($active ?? '') === 'areas' ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>/config/app.php?accion=areas.listar">
                        <i class="fa-solid fa-street-view"></i><span>Áreas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($active ?? '') === 'horario' ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>/config/app.php?accion=horarios.listar">
                        <i class=" fa-solid fa-business-time"></i><span>Horario de Trabajo</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($active ?? '') === 'notas' ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>/config/app.php?accion=notas.listar">
                        <i class="bi bi-journal-text"></i><span>Notas</span>
                    </a>
                </li>
            </ul>
        </div>
        <!-- Sistema -->
        <div class="nav-group">
            <div class="nav-group-title">Sistema</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= ($active ?? '') === 'cambios' ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>/config/app.php?accion=admin.solicitudes.cambio">
                        <i class="fa-solid fa-arrows-rotate"></i><span>Solicitud de cambio</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($active ?? '')==='reportes'?'active':'' ?>"
                    href="<?= BASE_URL ?>/config/app.php?accion=admin.reportes">
                    <i class="bi bi-graph-up"></i><span>Reportes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($active ?? '') === 'bitacora' ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>/config/app.php?accion=bitacora.listar">
                        <i class="fa-solid fa-book"></i><span>Bitácora del sistema</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($active ?? '') === 'bd' ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>/config/app.php?accion=admin.respaldos">
                        <i class="fa-solid fa-database"></i><span>Respaldo y restauración</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php if (($rol ?? 'consultor') === 'admin'): ?>
            <div class="nav-group">
                <div class="nav-group-title">Administración</div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= ($active ?? '') === 'usuarios' ? 'active' : '' ?>"
                            href="<?= BASE_URL ?>/config/app.php?accion=usuarios.listar">
                            <i class="bi bi-person-gear"></i><span>Gestión de usuarios</span>
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
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
            <button id="btnBell" class="btn btn-light position-relative" aria-label="Notificaciones" title="Notificaciones">
                <i class="bi bi-bell text-primary"></i>
                <span id="bellDot" class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="display:none;"></span>
            </button>
            <form method="post" action="<?= BASE_URL ?>/config/app.php?accion=auth.logout">
                <button type="submit" class="btn btn-ghost">Cerrar Sesión</button>
            </form>
        </div>
    </header>
    <!-- CONTENIDO DE LA VISTA -->
    <main class="main">
        <div class="container-fluid">
            <div class="mb-3"><span class="badge rounded-pill badge-role">Administrador · Vista principal</span></div>
            <!-- CONTENIDO -->

    <!-- Drawer de notificaciones -->
    <div id="notifDrawer" class="notif-drawer" aria-hidden="true">
        <div class="notif-drawer-header">
            <h6 class="m-0">Notificaciones</h6>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCloseNotif">X</button>
        </div>
        <div id="notifList" class="notif-drawer-body">
            <div class="text-center text-muted py-4">Cargando…</div>
        </div>
    </div>
<!-- SCRIPTS colapso + notificaciones -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ===== Sidebar / colapso =====
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    const bodyEl = document.body;

    function applyInitialState() {
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

    // ===== Notificaciones =====
    (function() {
        const bellBtn  = document.getElementById('btnBell');
        const bellDot  = document.getElementById('bellDot');
        const drawer   = document.getElementById('notifDrawer');
        const listEl   = document.getElementById('notifList');
        const btnClose = document.getElementById('btnCloseNotif');

        const TIPO_LABELS = {
            'nuevo_contacto': 'Nuevo contacto',
            'modificacion_contacto': 'Contacto actualizado',
            'eliminacion_contacto': 'Contacto eliminado'
        };
        const TIPO_STYLES = {
            'nuevo_contacto': { card: 'is-green', badge: 'badge-soft--green', avatar: 'is-green' },
            'modificacion_contacto': { card: 'is-amber', badge: 'badge-soft--amber', avatar: 'is-amber' },
            'eliminacion_contacto': { card: 'is-rose', badge: 'badge-soft--rose', avatar: 'is-rose' }
        };

        function esc(s) {
            return String(s ?? '')
                .replaceAll('&', '&amp;').replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;').replaceAll('"', '&quot;')
                .replaceAll("'", "&#039;");
        }

        // Extrae el nombre del contacto desde el mensaje
        function extraerNombreContacto(m) {
            if (!m) return '';
            const sinId = m.replace(/#\d+\s*/g, '');
            const par = sinId.match(/\(([^)]+)\)/);
            if (par && par[1]) return par[1].trim();
            const post = sinId.match(/contacto\s+([^.]+)\.?/i);
            if (post && post[1]) return post[1].trim();
            return sinId.replace(/Se\s+(registró|actualizó|eliminó)\s+el\s+contacto\s*/i, '').trim();
        }

        function inicialDeNombre(nombre) {
            const n = (nombre || '').trim();
            return n ? n.charAt(0).toUpperCase() : '?';
        }

        function renderItems(items) {
            if (!items.length) {
                return '<div class="text-center text-muted py-4">Sin notificaciones</div>';
            }
            return items.map(n => {
                const tipo    = TIPO_LABELS[n.tipo] || esc(n.tipo || '');
                const fecha   = esc(n.creada_at || '');
                const titulo  = esc(n.titulo || tipo);
                const nombreC = extraerNombreContacto(n.mensaje || '');
                const inicial = esc(inicialDeNombre(nombreC));
                const creador = esc(n.creadorNombre || 'Usuario');
                const sty     = TIPO_STYLES[n.tipo] || {};
                return `
                    <div class="notif-card ${sty.card || ''}">
                        <div class="notif-avatar ${sty.avatar || ''}">${inicial}</div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <p class="notif-title mb-1">${titulo}</p>
                                <span class="notif-meta ms-2">${fecha}</span>
                            </div>
                            <div class="notif-meta">${esc(nombreC || 'Contacto')}</div>
                            <div class="notif-badges">
                                <span class="badge-soft ${sty.badge || ''}">${tipo}</span>
                                <span class="notif-meta">Realizado por ${creador}</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        async function refreshCount() {
            try {
                const res = await fetch('<?= BASE_URL ?>/config/app.php?accion=notificacion.contador', {
                    credentials: 'same-origin'
                });
                const data = await res.json();
                const has  = (data?.count ?? 0) > 0;
                if (bellDot) bellDot.style.display = has ? '' : 'none';
            } catch (e) {
                // opcional: console.warn(e);
            }
        }

        function openDrawer() {
            drawer.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
        }

        function closeDrawer() {
            drawer.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
        }

        async function loadAndOpen() {
            listEl.innerHTML = '<div class="text-center text-muted py-4">Cargando…</div>';
            try {
                const res = await fetch('<?= BASE_URL ?>/config/app.php?accion=notificacion.listar', {
                    credentials: 'same-origin'
                });
                const data  = await res.json();
                const items = Array.isArray(data?.items) ? data.items : [];
                listEl.innerHTML = renderItems(items);
            } catch (e) {
                listEl.innerHTML = '<div class="alert alert-danger m-2">No se pudieron cargar las notificaciones.</div>';
            }
            openDrawer();
        }

        bellBtn?.addEventListener('click', loadAndOpen);
        btnClose?.addEventListener('click', closeDrawer);

        // Cerrar al click fuera
        document.addEventListener('click', (e) => {
            if (!drawer.classList.contains('is-open')) return;
            const isInside = drawer.contains(e.target) || (e.target === bellBtn);
            if (!isInside) closeDrawer();
        });

        // Primera carga + polling del puntito
        refreshCount();
        setInterval(refreshCount, 30000);
    })();
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