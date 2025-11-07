// ==== admin.usuarios.js ====
(() => {
    // ---------- Helpers ----------
    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    const BASE = (window.BASE_URL || '').replace(/\/+$/, '');

    function setInvalid(el, fbId, msg) {
        if (!el) return false;
        const fb = fbId ? document.getElementById(fbId) : null;
        if (fb) {
            if (!fb.dataset.default) fb.dataset.default = fb.textContent;
            fb.textContent = msg || fb.dataset.default || '';
        }
        el.setCustomValidity(msg || 'Inválido');
        return false;
    }
    function clearInvalid(el, fbId) {
        if (!el) return;
        const fb = fbId ? document.getElementById(fbId) : null;
        if (fb && fb.dataset.default) fb.textContent = fb.dataset.default;
        el.setCustomValidity('');
    }

    function collapseSpacesNoTrim(str) { return String(str ?? '').replace(/ {2,}/g, ' '); }
    function fullNormalize(str) { return String(str ?? '').replace(/\s+/g, ' ').trim(); }
    function normalizeEmail(str) { return String(str ?? '').trim().toLowerCase(); }

    // ---------- Refs ----------
    const form = $('#formUsuario');
    const modalEl = $('#modalUsuario');
    const modalTitle = $('#modalTitle');
    const inputId = $('#idUsuario');
    const inputNombre = $('#nombre');
    const inputEmail = $('#email');
    const inputRol = $('#rol');
    const inputPassword = $('#password');
    const hintEdit = $('#hintEdit');
    const feedbackPass = $('#feedbackPassword');
    const submitBtn = $('#submitBtn');

    // ---------- Reglas ----------
    const EMAIL_RE = /^[a-z0-9._%+\-]+@mtcenter\.com\.mx$/i;
    const NOMBRE_RE = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{3,80}$/;
    const ROLES = new Set(['consultor', 'editor', 'admin']);

    // ---------- Validaciones ----------
    const creando = () => !inputId?.value;

    function validaNombre() {
        const v = inputNombre?.value || '';
        if (!v) return setInvalid(inputNombre, 'fb-nombre', 'El nombre es obligatorio.');
        if (!NOMBRE_RE.test(v)) return setInvalid(inputNombre, 'fb-nombre', 'Solo letras y espacios (3–80).');
        clearInvalid(inputNombre, 'fb-nombre');
        return true;
    }

    function validaEmail() {
        if (!inputEmail) return false;
        inputEmail.value = normalizeEmail(inputEmail.value);
        const v = inputEmail.value;
        if (!v) return setInvalid(inputEmail, 'fb-email', 'Correo obligatorio.');
        if (v.length > 120) return setInvalid(inputEmail, 'fb-email', 'Máx. 120 caracteres.');
        if (!EMAIL_RE.test(v)) {
            return setInvalid(inputEmail, 'fb-email', 'Debe ser @mtcenter.com.mx (ej. usuario@mtcenter.com.mx).');
        }
        clearInvalid(inputEmail, 'fb-email');
        return true;
    }

    function validaPassword() {
        if (!inputPassword) return false;
        const v = inputPassword.value;
        if (creando()) {
            inputPassword.required = true;
            if (!v) return setInvalid(inputPassword, 'feedbackPassword', 'Contraseña obligatoria.');
            if (v.trim().length < 7) return setInvalid(inputPassword, 'feedbackPassword', 'Mínimo 7 caracteres.');
        } else {
            inputPassword.required = false;
            if (v) {
                if (v.trim().length < 7) return setInvalid(inputPassword, 'feedbackPassword', 'Mínimo 7 caracteres.');
                const nombreNorm = fullNormalize(inputNombre?.value || '').toLowerCase();
                if (v.toLowerCase() === nombreNorm || v.toLowerCase() === (inputEmail?.value || '').toLowerCase()) {
                    return setInvalid(inputPassword, 'feedbackPassword', 'No debe coincidir con nombre o correo.');
                }
            }
        }
        clearInvalid(inputPassword, 'feedbackPassword');
        return true;
    }

    function validaRol() {
        const v = inputRol?.value || '';
        if (!v) return setInvalid(inputRol, 'fb-rol', 'Selecciona un rol.');
        if (!ROLES.has(v)) return setInvalid(inputRol, 'fb-rol', 'Rol inválido.');
        clearInvalid(inputRol, 'fb-rol');
        return true;
    }

    // ---------- Eventos de entrada ----------
    inputNombre?.addEventListener('input', () => {
        const cur = inputNombre.selectionStart;
        const before = inputNombre.value;
        const after = collapseSpacesNoTrim(before);
        if (after !== before) {
            inputNombre.value = after;
            try { inputNombre.setSelectionRange(cur, cur); } catch { }
        }
        validaNombre();
    });
    inputNombre?.addEventListener('blur', () => {
        inputNombre.value = fullNormalize(inputNombre.value);
        validaNombre();
    });

    ['input', 'blur'].forEach(ev => {
        inputEmail?.addEventListener(ev, validaEmail);
        inputPassword?.addEventListener(ev, validaPassword);
    });
    inputRol?.addEventListener('change', validaRol);

    // Evitar pegar espacios en contraseña
    inputPassword?.addEventListener('paste', e => {
        const txt = (e.clipboardData || window.clipboardData)?.getData('text') || '';
        if (/\s/.test(txt)) e.preventDefault();
    });

    // ---------- Submit ----------
    let enviando = false;
    form?.addEventListener('submit', (e) => {
        // Normalizaciones finales
        if (inputNombre) inputNombre.value = fullNormalize(inputNombre.value);
        if (inputEmail) inputEmail.value = normalizeEmail(inputEmail.value);

        const ok =
            validaNombre() &
            validaEmail() &
            validaPassword() &
            validaRol();

        if (!ok || !form.checkValidity()) {
            e.preventDefault(); e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        if (enviando) {
            e.preventDefault(); e.stopPropagation();
            return;
        }
        enviando = true;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Guardando...';
        }
    });

    // ---------- Abrir modales ----------
    function openCreate() {
        if (!form) return;
        form.action = `${BASE}/config/app.php?accion=usuarios.crear`;
        if (modalTitle) modalTitle.textContent = 'Nuevo usuario';
        if (inputId) inputId.value = '';
        if (inputNombre) { inputNombre.value = ''; clearInvalid(inputNombre, 'fb-nombre'); }
        if (inputEmail) { inputEmail.value = ''; clearInvalid(inputEmail, 'fb-email'); }
        if (inputRol) { inputRol.value = ''; clearInvalid(inputRol, 'fb-rol'); }
        if (inputPassword) {
            inputPassword.value = '';
            inputPassword.required = true;
            clearInvalid(inputPassword, 'feedbackPassword');
        }
        if (feedbackPass) feedbackPass.textContent = 'Contraseña obligatoria (mínimo 7 caracteres).';
        if (hintEdit) hintEdit.style.display = 'none';

        form.classList.remove('was-validated');
        $$('.is-invalid', form).forEach(el => el.classList.remove('is-invalid'));
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        setTimeout(() => inputNombre?.focus(), 50);
    }

    function openEdit(id, nombre, email, rol) {
        if (!form) return;
        form.action = `${BASE}/config/app.php?accion=usuarios.actualizar`;
        if (modalTitle) modalTitle.textContent = 'Editar usuario';

        if (inputId) inputId.value = String(id ?? '');
        if (inputNombre) { inputNombre.value = String(nombre ?? ''); clearInvalid(inputNombre, 'fb-nombre'); }
        if (inputEmail) { inputEmail.value = String(email ?? ''); clearInvalid(inputEmail, 'fb-email'); }
        if (inputRol) { inputRol.value = String(rol ?? ''); clearInvalid(inputRol, 'fb-rol'); }
        if (inputPassword) {
            inputPassword.value = '';
            inputPassword.required = false;
            clearInvalid(inputPassword, 'feedbackPassword');
        }

        if (feedbackPass) feedbackPass.textContent = 'Al menos 7 caracteres (si deseas cambiarla).';
        if (hintEdit) hintEdit.style.display = 'block';

        form.classList.remove('was-validated');
        $$('.is-invalid', form).forEach(el => el.classList.remove('is-invalid'));
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        setTimeout(() => inputNombre?.focus(), 50);
    }

    function openConfirmEliminar(id, nombre) {
        const idEliminar = $('#idEliminar');
        const etiqueta = $('#etiquetaNombre');
        if (idEliminar) idEliminar.value = String(id ?? '');
        if (etiqueta) etiqueta.textContent = String(nombre ?? '');
        bootstrap.Modal.getOrCreateInstance($('#modalConfirmEliminar')).show();
    }
    // Exponer globales para onclick inline
    window.openCreate = openCreate;
    window.openEdit = openEdit;
    window.openConfirmEliminar = openConfirmEliminar;
    // ---------- Reapertura PRG ----------
    (function prgReopen() {
        const modo = window.PR_MODO; // 'crear' | 'editar' | null
        const v = window.PR_DATA || {};
        if (!modo) return;
        if (modo === 'crear') {
            openCreate();
            if (inputNombre) inputNombre.value = String(v.nombre ?? '');
            if (inputEmail) inputEmail.value = String(v.email ?? '');
            if (inputRol) inputRol.value = String(v.rol ?? '');
        } else if (modo === 'editar') {
            openEdit(
                parseInt(v.idUsuario || 0, 10),
                String(v.nombre || ''),
                String(v.email || ''),
                String(v.rol || '')
            );
        }
    })();
    // ---------- Toast si existe ----------
    const toastEl = document.querySelector('.toast');
    if (toastEl) bootstrap.Toast.getOrCreateInstance(toastEl).show();
})();