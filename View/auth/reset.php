<?php
require_once __DIR__ . '/../../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$erroresLista = $_SESSION['errores'] ?? [];
unset($_SESSION['errores']);
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Restablecer contraseña</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/View/contenido/css/styleLogin.css">
    <style>
        .pw-rule.ok {
            color: #198754;
        }

        /* verde */
        .pw-rule.bad {
            color: #dc3545;
        }
    </style>
</head>

<body class="d-flex align-items-center justify-content-center vh-100 bg-login">
    <div class="card shadow-lg rounded-4 p-4" style="max-width:420px;width:100%;">
        <h1 class="h5 mb-3">Elige una nueva contraseña</h1>
        <?php if (!empty($erroresLista)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php foreach ($erroresLista as $e): ?>
                    <div><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
                <button class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= BASE_URL ?>/config/app.php?accion=auth.reset.apply" autocomplete="off" id="formReset">
            <input type="hidden" name="uid" value="<?= htmlspecialchars($reset_uid) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($reset_token) ?>">
            <!-- Nueva contraseña -->
            <div class="mb-3">
                <label class="form-label">Nueva contraseña</label>
                <div class="input-group">
                    <input
                        type="password"
                        class="form-control"
                        name="contrasenia"
                        id="pw1"
                        minlength="7"
                        required
                        autocomplete="new-password">
                    <button type="button" class="btn btn-outline-secondary" id="togglePw1">Mostrar</button>
                </div>
            </div>
            <!-- Confirmar contraseña -->
            <div class="mb-1">
                <label class="form-label">Confirmar contraseña</label>
                <div class="input-group">
                    <input
                        type="password"
                        class="form-control"
                        name="contrasenia2"
                        id="pw2"
                        minlength="7"
                        required
                        autocomplete="new-password">
                    <button type="button" class="btn btn-outline-secondary" id="togglePw2">Mostrar</button>
                </div>
            </div>
            <!-- Reglas/estado -->
            <ul class="small mb-3 ps-3">
                <li id="rule-length" class="pw-rule text-danger">Mínimo 7 caracteres</li>
                <li id="rule-match" class="pw-rule text-danger">Ambas contraseñas deben coincidir</li>
            </ul>
            <button class="btn btn-success w-100" id="btnSubmit">Actualizar contraseña</button>
        </form>
        <div class="text-center mt-3">
            <a href="<?= BASE_URL ?>/config/app.php?accion=auth.form">Volver al inicio de sesión</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Ocultar alertas
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(el) {
                var a = bootstrap.Alert.getOrCreateInstance(el);
                a.close();
            });
        }, 10000);
        // Mostrar/ocultar contraseña
        const pw1 = document.getElementById('pw1');
        const pw2 = document.getElementById('pw2');
        document.getElementById('togglePw1').addEventListener('click', () => pw1.type = pw1.type === 'password' ? 'text' : 'password');
        document.getElementById('togglePw2').addEventListener('click', () => pw2.type = pw2.type === 'password' ? 'text' : 'password');
        // Validación visual en vivo
        const ruleLength = document.getElementById('rule-length');
        const ruleMatch = document.getElementById('rule-match');
        const btnSubmit = document.getElementById('btnSubmit');
        const MIN = 7;
        function refreshRules() {
            const okLen = pw1.value.length >= MIN;
            const okMatch = pw1.value !== '' && pw1.value === pw2.value;
            ruleLength.className = 'pw-rule ' + (okLen ? 'ok' : 'bad');
            ruleMatch.className = 'pw-rule ' + (okMatch ? 'ok' : 'bad');
            btnSubmit.disabled = !(okLen && okMatch);
        }
        pw1.addEventListener('input', refreshRules);
        pw2.addEventListener('input', refreshRules);
        refreshRules();
    </script>
</body>
</html>