<?php require_once __DIR__ . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Directorio Telefónico</title>
  <!-- Bootstrap 5.3 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- CSS personalizado -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/View/contenido/css/styleIndex.css">
  <!-- Font Awesome -->
  <script src="https://kit.fontawesome.com/35c8a07d23.js" crossorigin="anonymous"></script>
</head>
<body class="bg-white text-body">
  <!-- HEADER / NAVBAR -->
  <header class="hero-bg text-white position-relative">
    <nav class="navbar navbar-expand-lg navbar-dark navbar-glass fixed-top">
      <div class="container-fluid px-lg-5 px-3">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php">
          <img src="<?= BASE_URL ?>/View/contenido/img/libro-de-contactos.png" alt="MTCenter" width="34" height="34" class="brand-mark">
          <span class="fw-semibold">Directorio Telefónico MTCenter</span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Mostrar menú">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div id="navMain" class="collapse navbar-collapse justify-content-end text-center text-lg-start">
          <ul class="navbar-nav align-items-lg-center gap-lg-2">
            <li class="nav-item">
              <a class="nav-link" href="#top"><i class="fa-solid fa-house me-1"></i>Inicio</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#acerca"><i class="fa-solid fa-circle-info me-1"></i>Acerca de</a>
            </li>
            <li class="nav-item">
              <a href="<?= BASE_URL ?>/View/auth/login.php" class="btn btn-login-outline ms-lg-2">
                <i class="fa-solid fa-right-to-bracket me-1"></i> Iniciar sesión
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    <!-- Sección del HERO -->
    <section id="top" class="container hero-section">
      <div class="row align-items-center g-4">
        <!-- Izquierda -->
        <div class="col-lg-6 pt-5 pt-md-4">
          <h1 class="display-5 fw-bold lh-1 text-shadow">
            Directorio Telefónico <br class="d-none d-md-block">MTCenter
          </h1>
          <p class="lead mb-4 text-blue-75">Conecta rápido y fácil con tu equipo</p>
        </div>
        <!-- Derecha -->
        <div class="col-lg-6 text-center">
          <img src="<?= BASE_URL ?>/View/contenido/img/directorio3.png" alt="Ilustración" class="img-fluid rounded-4 shadow-lg">
        </div>
      </div>
    </section>
  </header>
  <!-- SECCIÓN ACERCA DE -->
  <section id="acerca" class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="display-5 fw-bold text-primary mb-3">Acerca de</h2>
            </div>
        </div>
        <div class="row g-4">
            <!-- Tarjeta Informativa -->
            <div class="col-md-4">
                <div class="card feature-card h-100 rounded-4 shadow-soft border-0 hover-lift">
                    <div class="card-body p-4 text-center">
                        <div class="icon-badge mb-4 bg-primary-soft rounded-3 p-3 d-inline-block">
                            <i class="fa-solid fa-address-book fa-2x" style="color: #123A8A;"></i>
                        </div>
                        <h4 class="text-primary fw-bold mb-3">¿Por qué este sistema?</h4>
                        <p class="text-secondary">
                            Desarrollado específicamente para <strong>MTCenter</strong>, este sistema web centraliza y moderniza 
                            la gestión del directorio telefónico, eliminando las limitaciones de los métodos manuales 
                            con hojas de cálculo. Optimizamos procesos internos y mejoramos la comunicación 
                            para que toda la organización cuente con <strong>información confiable al instante</strong>.
                        </p>
                    </div>
                </div>
            </div>
            <!-- Tarjeta Favoritos -->
            <div class="col-md-4">
                <div class="card feature-card h-100 rounded-4 shadow-soft border-0 hover-lift">
                    <div class="card-body p-4 text-center">
                        <div class="icon-badge mb-4 bg-success-soft rounded-3 p-3 d-inline-block">
                            <i class="fa-regular fa-star fa-2x" style="color: #14B8A6;"></i>
                        </div>
                        <h4 class="text-success fw-bold mb-3">Ventajas</h4>
                        <ul class="list-unstyled text-start small text-muted">
                            <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Sin pérdida de tiempo</li>
                            <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Reduce el riesgo de perdida de datos</li>
                            <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Consulta inmediata</li>
                            <li><i class="fa-solid fa-check text-success me-2"></i>Información actualizada</li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Tarjeta Funcionalidades -->
            <div class="col-md-4">
                <div class="card feature-card h-100 rounded-4 shadow-soft border-0 hover-lift">
                    <div class="card-body p-4 text-center">
                        <div class="icon-badge mb-4 bg-info-soft rounded-3 p-3 d-inline-block">
                            <i class="fa-solid fa-chart-line fa-2x" style="color: #1C79CF;"></i>
                        </div>
                        <h4 class="text-info fw-bold mb-3">Funcionalidades principales</h4>
                        <ul class="list-unstyled text-start small text-muted">
                          <li class="mb-2"><i class="fa-solid fa-search text-info me-2"></i>Consulta de contactos de manera rápida y eficiente</li>
                          <li class="mb-2"><i class="fa-solid fa-file-pen text-info me-2"></i>Actualización de información existente de forma segura</li>
                          <li class="mb-2"><i class="fa-solid fa-chart-bar text-info me-2"></i>Estadísticas de uso en tiempo real</li>
                          <li class="mb-2"><i class="fa-solid fa-file-export text-info me-2"></i>Exportación a múltiples formatos</li>
                          <li class="mb-2"><i class="fa-solid fa-globe text-info me-2"></i>Acceso desde cualquier dispositivo con conexión a internet</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </section>
  <!-- footer -->
  <?php require __DIR__ .'/View/layout/footer.php'; ?>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Scripts NAVBAR -->
  <script>
    //Navbar shrink
    const nav = document.querySelector('.navbar-glass');
    const shrink = () => { (window.scrollY > 10) ? nav.classList.add('navbar-shrink') : nav.classList.remove('navbar-shrink'); };
    document.addEventListener('scroll', shrink); shrink();
    //Cerrar menú móvil al hacer clic en un enlace
    const navLinks = document.querySelectorAll('.navbar-collapse .nav-link');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        const collapse = bootstrap.Collapse.getInstance(navbarCollapse);
        if (collapse) collapse.hide();
      });
    });
  </script>
</body>
</html>