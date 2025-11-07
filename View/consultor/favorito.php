<?php
require __DIR__ . '/../layout/headConsultor.php';
$uid  = (int)($_SESSION['usuario']['id'] ?? 0);
$rol  = $_SESSION['usuario']['tipoUsuario'] ?? 'consultor';
?>
<!-- Estilos -->
<link rel="stylesheet" href="<?= BASE_URL ?>/View/contenido/css/styleDirectorio.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<main class="fav-main">
  <header class="header-favoritos">
    <h1 class="header-favoritos-title">
      <?= htmlspecialchars($titulo ?? 'Contactos Favoritos') ?>
    </h1>
    <form class="search-bar" method="get" action="">
      <input type="hidden" name="accion" value="consultor.favoritos">
      <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
        <path d="M21 21l-6-6m2-5a7 7 0 10-14 0 7 7 0 0014 0z" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <input type="search" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Buscar en favoritos...">
    </form>
  </header>

  <?php if (empty($lista)): ?>
    <div class="empty-box">
      Aún no tienes contactos favoritos. Marca la estrella desde el directorio.
    </div>
  <?php else: ?>
    <section class="contact-grid">
      <?php foreach ($lista as $it):
        $inicial = mb_substr($it['nombreCompleto'], 0, 1);
        $correo = $it['correo']     ?? '';
        $ext = $it['extension']  ?? '';
      ?>
      <article class="contact-card" data-prop="<?= (int)$it['idPropietario'] ?>">
        <!-- quitar de favoritos -->
          <form method="post"
                action="<?= BASE_URL ?>/config/app.php?accion=consultor.favorito.toggle"
                class="form-unfav">
            <input type="hidden" name="propietarioId" value="<?= (int)$it['idPropietario'] ?>">
            <input type="hidden" name="redirect_to"   value="<?= BASE_URL ?>/config/app.php?accion=consultor.favoritos">
            <button type="submit" class="favorite-btn" title="Quitar de favoritos">★</button>
          </form>
          <div class="avatar-section">
            <div class="avatar-initials"><?= htmlspecialchars($inicial) ?></div>
          </div>
          <h4 class="contact-name"><?= htmlspecialchars($it['nombreCompleto']) ?></h4>
          <p class="contact-area"><?= htmlspecialchars($it['area'] ?? '—') ?></p>
          <p class="contact-role"><?= htmlspecialchars($it['puesto'] ?? '') ?></p>
          <!-- extensión -->
          <div class="ext-row">
            <?php if ($ext !== ''): ?>
              <span class="badge-soft">Ext: <?= htmlspecialchars($ext) ?></span>
              <button type="button" class="btn-circle" onclick="copyTxt('<?= htmlspecialchars($ext) ?>')" title="Copiar extensión">
                <i class="bi bi-clipboard"></i>
              </button>
            <?php else: ?>
              <span class="badge-soft">Sin extensión</span>
            <?php endif; ?>
          </div>
          <!-- correo electronico -->
          <?php if (!empty($correo)): ?>
            <p class="contact-email">
              <a href="mailto:<?= htmlspecialchars($correo) ?>" class="email-link">
                <?= htmlspecialchars($correo) ?>
              </a>
            </p>
          <?php else: ?>
            <p class="contact-email text-muted">Sin correo</p>
          <?php endif; ?>
          <!-- correo -->
          <div class="info-row">
            <?php if ($correo !== ''): ?>
              <button type="button" class="btn-circle" onclick="copyTxt('<?= htmlspecialchars($correo) ?>')" title="Copiar correo">
                <i class="bi bi-clipboard-check"></i>
              </button>
            <?php else: ?>
              <span class="badge-soft">correo</span>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</main>
<!-- contenedor de toasts -->
<div class="toast-bottom" id="toastBox"></div>

<script>
function showToast(msg, isError = false) {
  const box = document.getElementById('toastBox');
  const div = document.createElement('div');
  div.className = 'toast-item' + (isError ? ' error' : '');
  div.textContent = msg;
  box.appendChild(div);
  setTimeout(() => div.remove(), 3200);
}

function copyTxt(texto){
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(texto).then(() => {
      showToast('Copiado: ' + texto);
    }).catch(() => showToast('No se pudo copiar', true));
  } else {
    const ta = document.createElement('textarea');
    ta.value = texto;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    showToast('Copiado: ' + texto);
  }
}

// quitar de favoritos
document.querySelectorAll('.form-unfav').forEach(form => {
  form.addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(form);
    fetch(form.action, { method: 'POST', body: fd })
      .then(r => r.ok ? r.text() : Promise.reject())
      .then(() => {
        const card = form.closest('.contact-card');
        card?.remove();
        showToast('Contacto eliminado de favoritos');
        if (!document.querySelector('.contact-card')) {
          const main = document.querySelector('.fav-main');
          const empty = document.createElement('div');
          empty.className = 'empty-box';
          empty.textContent = 'Aún no tienes contactos favoritos. Marca la estrella desde el directorio.';
          main.appendChild(empty);
        }
      })
      .catch(() => showToast('No se pudo actualizar el favorito', true));
  });
});
</script>