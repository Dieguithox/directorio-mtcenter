<?php
$active = 'usuarios';
$titulo = 'Gestión de usuarios';
require_once __DIR__ . '/../../config/ui.php';
require __DIR__ . '/../layout/headAdmin.php';

/* Reapertura de modal según PRG */
$reabrirCrear = !empty($errores) && $errores_origen === 'usuarios' && empty($viejo['idUsuario']);
$reabrirEditar= !empty($errores) && $errores_origen === 'usuarios' && !empty($viejo['idUsuario']);
$q = trim($_GET['q'] ?? '');
?>

<?php if (!empty($flash_texto) && !empty($flash_nivel)): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080">
        <div class="toast text-bg-<?= htmlspecialchars($flash_nivel) ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body"><?= htmlspecialchars($flash_texto) ?></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
        </div>
    </div>
    <script> (function(){ const el=document.querySelector('.toast'); if(el) new bootstrap.Toast(el).show(); })(); </script>
    <?php endif; ?>
    <div class="mb-3">
    <h2 class="h5 mb-2">Usuarios del sistema</h2>
    <div class="d-flex align-items-center gap-2">
        <form class="d-flex flex-grow-1" method="get" action="<?= BASE_URL ?>/config/app.php">
        <input type="hidden" name="accion" value="usuarios.listar">
        <div class="input-group" style="max-width:420px">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input type="search" name="q" class="form-control" placeholder="Buscar por nombre o correo"
                value="<?= htmlspecialchars($q) ?>">
        </div>
        </form>
        <div class="ms-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario" onclick="openCreate()">
                <i class="bi bi-person-plus me-1"></i> Agregar usuario
            </button>
        </div>
    </div>
    </div>
    
    <div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tablaUsuarios">
            <thead class="table-light fw-semibold text-center">
                <tr class="table-light">
                    <th class="text-start">Nombre</th>
                    <th class="text-center" style="width:33%">Correo corporativo</th>
                    <th style="width:15%">Rol</th>
                    <th style="width:19%">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Sin registros</td></tr>
                <?php else: foreach ($usuarios as $u): ?>
                <tr data-id="<?= (int)$u['idUsuario'] ?>">
                    <!-- Nombre -->
                    <td class="text-start fw-semibold">
                        <?php
                            //Lógica para badge y avatar
                            $rol = $u['tipoUsuario'];
                            $badge = ['admin'=>'danger','editor'=>'warning','consultor'=>'secondary'][$rol] ?? 'secondary';
                            //Obtenemos la inicial
                            $inicial = strtoupper(substr($u['nombre'], 0, 1));
                        ?>
                        <span class="avatar-iniciales avatar-<?= $badge ?> me-2"><?= htmlspecialchars($inicial) ?></span>
                        <?= htmlspecialchars($u['nombre']) ?>
                    </td>
                    <!-- Correo -->
                    <td class="text-center"><?= htmlspecialchars($u['email']) ?></td>
                    <!-- Rol -->
                    <td class="text-center">
                        <span class="badge text-bg-<?= $badge ?>"><?= ucfirst($rol) ?></span>
                    </td>
                    <!-- Acción -->
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary"
                                onclick='openEdit(<?= (int)$u["idUsuario"] ?>, <?= json_encode($u["nombre"]) ?>, <?= json_encode($u["email"]) ?>, <?= json_encode($u["tipoUsuario"]) ?>)'>
                                <i class="bi bi-pencil-square me-1"></i> Editar
                            </button>
                            <button class="btn btn-outline-danger"
                                onclick='openConfirmEliminar(<?= (int)$u["idUsuario"] ?>, <?= json_encode($u["nombre"]) ?>)'>
                                <i class="bi bi-trash me-1"></i> Eliminar
                            </button>
                            </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    </div>
    <div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content needs-validation" novalidate method="post" id="formUsuario"
        action="<?= BASE_URL ?>/config/app.php?accion=usuarios.crear">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Nuevo usuario</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <?php if (!empty($errores) && $errores_origen === 'usuarios'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php foreach ($errores as $m): ?><div><?= htmlspecialchars($m) ?></div><?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <!-- Id usuario -->
            <input type="hidden" name="idUsuario" id="idUsuario"
                value="<?= isset($viejo['idUsuario']) ? (int)$viejo['idUsuario'] : '' ?>">

            <!-- Nombre (solo letras y espacios, 3–80) -->
            <div class="mb-2">
            <label class="form-label" for="nombre">Nombre Completo</label>
            <input
                type="text"
                class="form-control"
                name="nombre"
                id="nombre"
                required
                minlength="3"
                maxlength="80"
                spellcheck="false"
                autocomplete="name"
                pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{3,80}$"
                title="Solo letras y espacios, de 3 a 80 caracteres"
                value="<?= isset($viejo['nombre']) ? htmlspecialchars($viejo['nombre']) : '' ?>"
            >
            <div class="invalid-feedback" id="fb-nombre">
                Nombre obligatorio (3–80 caracteres, solo letras y espacios).
            </div>
            </div>

            <!-- Correo (solo @mtcenter.com.mx) -->
            <div class="mb-2">
            <label class="form-label" for="email">Correo</label>
            <input
                type="email"
                class="form-control"
                name="email"
                id="email"
                required
                maxlength="120"
                inputmode="email"
                spellcheck="false"
                autocomplete="email"
                pattern="^[A-Za-z0-9._%+\-]+@mtcenter\.com\.mx$"
                title="Usa un correo @mtcenter.com.mx (ej. usuario@mtcenter.com.mx)"
                value="<?= isset($viejo['email']) ? htmlspecialchars($viejo['email']) : '' ?>"
            >
            <div class="invalid-feedback" id="fb-email">
                Correo inválido. Debe ser @mtcenter.com.mx (ej. usuario@mtcenter.com.mx).
            </div>
            </div>

            <!-- Contraseña -->
            <div class="mb-2" id="wrapPassword">
            <label class="form-label" for="password">Contraseña</label>
            <input
                type="password"
                class="form-control"
                name="password"
                id="password"
                minlength="7"
                maxlength="128"
                autocomplete="new-password"
                placeholder="Mínimo 7 caracteres"
            >
            <div class="invalid-feedback" id="feedbackPassword">Al menos 7 caracteres.</div>
            <div class="form-text" id="hintEdit" style="display:none">Déjalo vacío para no cambiarla.</div>
            </div>

            <!-- Rol -->
            <div class="mb-2">
            <label class="form-label" for="rol">Rol</label>
            <select name="rol" id="rol" class="form-select" required>
                <option value="">Seleccione un rol</option>
                <option value="consultor" <?= isset($viejo['rol']) && $viejo['rol']==='consultor' ? 'selected' : '' ?>>Consultor</option>
                <option value="editor"    <?= isset($viejo['rol']) && $viejo['rol']==='editor'    ? 'selected' : '' ?>>Editor</option>
                <option value="admin"     <?= isset($viejo['rol']) && $viejo['rol']==='admin'     ? 'selected' : '' ?>>Administrador</option>
            </select>
            <div class="invalid-feedback" id="fb-rol">Rol obligatorio.</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" data-bs-dismiss="modal" type="button">Cancelar</button>
            <button class="btn btn-primary" type="submit" id="submitBtn">Guardar</button>
        </div>
        </form>
    </div>
    </div>
    <!-- Eliminación de un usuario -->
    <div class="modal fade" id="modalConfirmEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= BASE_URL ?>/config/app.php?accion=usuarios.eliminar" id="formEliminar">
        <div class="modal-header">
            <h5 class="modal-title">Confirmar eliminación</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="idUsuario" id="idEliminar">
            <p class="mb-0">¿Seguro que deseas eliminar a <span class="fw-semibold" id="etiquetaNombre"></span>?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger">Sí, eliminar</button>
        </div>
        </form>
    </div>
    </div>
<script>window.BASE_URL = "<?= BASE_URL ?>";</script>
<script src="<?= BASE_URL ?>/View/contenido/js/admin.usuarios.js"></script>