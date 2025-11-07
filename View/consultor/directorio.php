<?php
require __DIR__ . '/../layout/headConsultor.php';

$uid  = (int)($_SESSION['usuario']['id'] ?? 0);
$rol  = $_SESSION['usuario']['tipoUsuario'] ?? 'consultor';
$esAdmin = ($rol === 'admin');
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/View/contenido/css/styleDirectorio.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

<div class="container container-narrow py-4">
    <div class="header-wrap">
        <h1 class="h3 header-title"><?= htmlspecialchars($titulo) ?></h1>
    </div>

    <div class="chips">
        <span class="chip"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($telefonosLine) ?></span>
        <span class="chip"><i class="bi bi-building me-1"></i><?= htmlspecialchars($empresaNombre) ?></span>
    </div>

    <div class="filter-card">
        <div class="filter-title">Buscar en el directorio</div>
        <form class="row gy-2 gx-2 justify-content-center" method="get" action="">
            <input type="hidden" name="accion" value="consultor.directorio">
            <div class="col-12 col-md-8">
                <label class="form-label mb-1">Nombre, puesto, extensión, correo o área</label>
                <div class="input-group">
                    <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Escribe para filtrar...">
                    <button class="btn btn-primary" type="submit" title="Buscar">
                        <i class="bi bi-search"></i>
                    </button>
                    <a class="btn btn-outline-secondary" title="Reiniciar" href="<?= BASE_URL ?>/config/app.php?accion=consultor.directorio">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <?php if (empty($porArea)): ?>
        <div class="alert alert-light border text-center">No se encontraron resultados.</div>
    <?php else: ?>
        <?php foreach ($porArea as $areaNombre => $data): ?>
            <div class="card card-area mb-4">
                <div class="card-header d-flex justify-content-between align-items-center px-3 py-2">
                    <div class="h5 mb-0 fw-bold"><?= htmlspecialchars($areaNombre) ?></div>
                    <?php if (!empty($data['correo_area'])): ?>
                        <small>Correo del área: <strong><?= htmlspecialchars($data['correo_area']) ?></strong></small>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                            <tr>
                                <th style="width:46px;"></th>
                                <th style="width:110px;">Extensión</th>
                                <th style="width:30%;">Nombre</th>
                                <th>Puesto</th>
                                <th style="width:30%;">Correo corporativo</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($data['contactos'] as $c): ?>
                                <?php
                                // propietario para favoritos
                                $id = (int)($c['id'] ?? 0);
                                $esFav = in_array($id, $favoritosIds ?? [], true);
                                $redirect = BASE_URL . '/config/app.php?accion=consultor.directorio'
                                    . (isset($_GET['q']) && $_GET['q'] !== '' ? '&q=' . urlencode($_GET['q']) : '');

                                // ===== extensión e id de extensión =====
                                $idExt = 0;
                                if (!empty($c['idExtension'])) {
                                    $idExt = (int)$c['idExtension'];
                                } elseif (!empty($c['extensionId'])) {
                                    $idExt = (int)$c['extensionId'];
                                }

                                if (!empty($c['extension'])) {
                                    $ext = htmlspecialchars($c['extension']);
                                } elseif (!empty($c['numero'])) {
                                    $ext = htmlspecialchars($c['numero']);
                                } else {
                                    $ext = '';
                                }
                                ?>
                                <tr>
                                    <!-- favorito -->
                                    <td class="text-center">
                                        <form method="post"
                                              action="<?= BASE_URL ?>/config/app.php?accion=consultor.favorito.toggle"
                                              class="form-favorito">
                                            <input type="hidden" name="propietarioId" value="<?= $id ?>">
                                            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirect) ?>">
                                            <button class="btn-fav <?= $esFav ? 'active' : '' ?>"
                                                    title="<?= $esFav ? 'Quitar de favoritos':'Marcar como favorito' ?>">
                                                <span class="icon-star">
                                                    <?php if ($esFav): ?>
                                                        <i class="fa-solid fa-star"></i>
                                                    <?php else: ?>
                                                        <i class="fa-regular fa-star"></i>
                                                    <?php endif; ?>
                                                </span>
                                            </button>
                                        </form>
                                    </td>

                                    <!-- extensión -->
                                    <td>
                                        <?php if (!empty($ext)): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="ext-badge"><?= $ext ?></span>
                                                <button
                                                    class="btn-copy"
                                                    data-ext="<?= $ext ?>"
                                                    data-id="<?= $idExt ?>"
                                                    title="Copiar extensión">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- nombre -->
                                    <td class="fw-medium"><?= htmlspecialchars($c['nombre']) ?></td>
                                    <td><?= htmlspecialchars($c['puesto']) ?></td>

                                    <!-- correo -->
                                    <td>
                                        <?php if (!empty($c['correo'])): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <a href="mailto:<?= htmlspecialchars($c['correo']) ?>"><?= htmlspecialchars($c['correo']) ?></a>
                                                <button type="button"
                                                        class="btn-copy btn-copy-mail"
                                                        onclick="copyMail('<?= htmlspecialchars($c['correo']) ?>')"
                                                        title="Copiar correo">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Sin correo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    /* ===== Favoritos ===== */
    const forms = document.querySelectorAll('.form-favorito');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const button   = form.querySelector('.btn-fav');
            const iconSpan = form.querySelector('.icon-star');
            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(res => {
                if (!res.ok) throw new Error(res.msg || 'Error');
                const activo = !!res.favorito;
                button.classList.toggle('active', activo);
                button.title = activo ? 'Quitar de favoritos' : 'Marcar como favorito';
                iconSpan.innerHTML = activo
                    ? '<i class="fa-solid fa-star"></i>'
                    : '<i class="fa-regular fa-star"></i>';
            })
            .catch(err => {
                console.error('Error al actualizar favorito:', err);
                alert('No se pudo actualizar el favorito. Inténtalo de nuevo.');
            });
        });
    });
});

/* ===== Copiar extensión + registrar ===== */
document.addEventListener('click', async function(e) {
    const btn = e.target.closest('.btn-copy');
    if (!btn) return;

    const ext   = btn.dataset.ext;
    const idExt = btn.dataset.id;

    console.log('copiando extensión:', ext, 'id:', idExt);

    // copiar
    if (ext && navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(ext);
    } else if (ext) {
        const ta = document.createElement('textarea');
        ta.value = ext;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    }

    // efecto
    btn.classList.add('copied');
    setTimeout(() => btn.classList.remove('copied'), 600);

    // registrar
    if (idExt && idExt !== '0') {
        fetch('<?= BASE_URL ?>/config/app.php?accion=consultor.extension.copiada', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'extensionId=' + encodeURIComponent(idExt)
        })
        .then(r => r.json())
        .then(data => {
            console.log('respuesta del servidor:', data);
        })
        .catch(err => console.warn('error fetch', err));
    } else {
        console.warn('No se envió al servidor porque data-id venía vacío o 0');
    }
});

/* ===== Copiar correo (no guarda en BD) ===== */
function copyMail(texto) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(texto);
    } else {
        const ta = document.createElement('textarea');
        ta.value = texto;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    }
}
</script>