<?php
require_once __DIR__ . '/../../config/ui.php';
$abrirRegistro = ($errores_origen === 'register') || (($_GET['t'] ?? null) === 'register');
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Acceso - Directorio Telefónico</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/View/contenido/css/styleLogin.css">
  <script src="https://kit.fontawesome.com/35c8a07d23.js" crossorigin="anonymous"></script>
</head>

<body class="d-flex align-items-center justify-content-center vh-100 bg-login">
  <div class="card shadow-lg rounded-4 p-4" style="max-width:420px;width:100%;">
    <div class="text-center mb-4">
      <img src="<?= BASE_URL ?>/View/contenido/img/libro-de-contactos.png" alt="Directorio" width="64" class="mb-2">
      <h1 class="h4 fw-bold">Directorio Telefónico</h1>
    </div>

    <!-- NAV PESTAÑAS -->
    <ul class="nav nav-pills nav-justified mb-3">
      <li class="nav-item">
        <button class="nav-link <?= $abrirRegistro ? '' : 'active' ?>" data-bs-toggle="pill" data-bs-target="#pane-login">Iniciar Sesión</button>
      </li>
      <li class="nav-item">
        <button class="nav-link <?= $abrirRegistro ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#pane-register">Registrarse</button>
      </li>
    </ul>

    <div class="tab-content">
      <!-- ================= LOGIN ================= -->
      <div class="tab-pane fade <?= $abrirRegistro ? '' : 'show active' ?>" id="pane-login">

        <!-- Mensaje flash (solo cuando la pestaña activa es Login) -->
        <?php if ($flash_texto && !$abrirRegistro): ?>
          <div class="alert alert-<?= htmlspecialchars($flash_nivel ?? 'info') ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash_texto) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <!-- Errores de validación (login) -->
        <?php if ($errores && $errores_origen === 'login'): ?>
          <div class="alert alert-danger alert-dismissible fade show">
            <?php foreach ($errores as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <!-- Formulario Login -->
        <form class="needs-validation" novalidate method="post" action="<?= BASE_URL ?>/config/app.php?accion=auth.login" autocomplete="off">
          <div class="form-floating mb-3">
            <input type="email" name="email" id="emailLogin" class="form-control"
              value="<?= htmlspecialchars($viejo['email'] ?? '') ?>"
              placeholder="usuario@mtcenter.com.mx"
              pattern="^[A-Za-z0-9._%+-]+@mtcenter\.com\.mx$"
              required autocomplete="username">
            <label for="emailLogin">Correo corporativo</label>
            <div class="invalid-feedback">Usa tu correo corporativo @mtcenter.com.mx</div>
          </div>

          <div class="form-floating mb-3">
            <input type="password" name="contrasenia" id="passLogin" class="form-control"
              placeholder="Contraseña" minlength="7" required autocomplete="current-password">
            <label for="passLogin">Contraseña</label>
            <div class="invalid-feedback">Contraseña obligatoria (mín. 7 caracteres)</div>
          </div>

          <button type="submit" class="btn btn-primary w-100">Entrar</button>
          <div class="text-center mt-3">
            <a href="<?= BASE_URL ?>/config/app.php?accion=auth.forgot.form">¿Olvidaste tu contraseña?</a>
          </div>
        </form>
      </div>

      <!-- === REGISTRO ==== -->
      <div class="tab-pane fade <?= $abrirRegistro ? 'show active' : '' ?>" id="pane-register">
        <!-- Mensaje flash y alertas bootstrap htmlspecialchars evita inyección XSS al imprimir texto desde sesión. -->
        <?php if ($flash_texto && $abrirRegistro): ?>
          <div class="alert alert-<?= htmlspecialchars($flash_nivel ?? 'info') ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash_texto) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <!-- Errores de validación -->
        <?php if ($errores && $errores_origen === 'register'): ?>
          <div class="alert alert-danger alert-dismissible fade show">
            <?php foreach ($errores as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <!-- Formulario Registro nombre, email, contraseña y botón -->
        <form class="needs-validation" novalidate method="post" action="<?= BASE_URL ?>/config/app.php?accion=auth.register" autocomplete="off">
          <div class="form-floating mb-3">
            <input type="text" name="nombre" id="nombre" class="form-control"
              value="<?= htmlspecialchars($viejo['nombre'] ?? '') ?>"
              placeholder="Nombre completo" required>
            <label for="nombre">Nombre completo</label>
            <div class="invalid-feedback">Ingresa tu nombre</div>
          </div>
          <!-- Correo empresarial -->
          <div class="form-floating mb-3">
            <input type="email" name="email" id="emailReg" class="form-control"
              value="<?= htmlspecialchars($viejo['email'] ?? '') ?>"
              placeholder="usuario@mtcenter.com.mx"
              pattern="^[A-Za-z0-9._%+-]+@mtcenter\.com\.mx$"
              required autocomplete="email">
            <label for="emailReg">Correo corporativo</label>
            <div class="invalid-feedback">Debe ser @mtcenter.com.mx</div>
          </div>
          <!-- Contraseña -->
          <div class="form-floating mb-3">
            <input type="password" name="contrasenia" id="passReg" class="form-control"
              placeholder="Contraseña" minlength="7" required autocomplete="new-password">
            <label for="passReg">Contraseña</label>
            <div class="invalid-feedback">Mínimo 7 caracteres</div>
          </div>
          <!-- Botón Crear -->
          <button type="submit" class="btn btn-success w-100">Crear cuenta</button>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (() => {
      // Validación cliente Bootstrap
      document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', e => {
          if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
          }
          form.classList.add('was-validated');
        });
      });
      // Auto-cierre de alertas
      setTimeout(() => document.querySelectorAll('.alert').forEach(a =>
        bootstrap.Alert.getOrCreateInstance(a).close()
      ), 10000);
    })();
  </script>
</body>
</html>