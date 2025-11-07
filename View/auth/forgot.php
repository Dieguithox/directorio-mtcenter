<?php
require_once __DIR__ . '/../../config/ui.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recuperar contraseña</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Mismo CSS que login -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/View/contenido/css/styleLogin.css">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-login">
    <div class="card shadow-lg rounded-4 p-4" style="max-width:420px;width:100%;">
        <h1 class="h5 mb-3">Recuperar contraseña</h1>
        <?php if ($flash_texto): ?>
            <div class="alert alert-<?= htmlspecialchars($flash_nivel ?? 'info') ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash_texto) ?>
                <button class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
            <?php endif; ?>
        <form method="post" action="<?= BASE_URL ?>/config/app.php?accion=auth.forgot.send" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">Correo registrado</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Enviar enlace</button>
        </form>
        <div class="text-center mt-3">
            <a href="<?= BASE_URL ?>/config/app.php?accion=auth.form">Volver al inicio de sesión</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Ocultar alertas tras 10s
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(el) {
                var a = bootstrap.Alert.getOrCreateInstance(el);
                a.close();
            });
        }, 10000); // 10s
    </script>
</body>

</html>