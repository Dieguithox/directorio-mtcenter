// /View/js/val-puestos.js
(function () {
    // Espera al DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else { init(); }

    function init() {
        const form = document.getElementById('formPuesto');
        const modal = document.getElementById('modalPuesto');
        if (!form || !modal) return;

        const idPuesto = document.getElementById('idPuesto');
        const nombre = document.getElementById('nombre');
        const areaId = document.getElementById('areaId');
        const desc = document.getElementById('descripcion');
        const submit = document.getElementById('submitBtn');

        // ======== Reglas ========
        const RE_NOMBRE = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{3,80}$/; // letras+espacios, 3–80

        // ======== Normalizadores ========
        const collapseSpacesNoTrim = s => s.replace(/ {2,}/g, ' '); // no recorta extremos
        const fullNormalize = s => s.replace(/\s+/g, ' ').trim();

        // ======== Helpers de feedback ========
        function setInvalid(el, msg) {
            if (!el) return false;
            const fb = el.parentElement && el.parentElement.querySelector('.invalid-feedback');
            if (fb) fb.textContent = msg;
            el.setCustomValidity(msg || 'Inválido');
            return false;
        }
        function clearInvalid(el) {
            if (!el) return true;
            el.setCustomValidity('');
            return true;
        }

        // ======== Validaciones de campo ========
        function validaNombre() {
            if (!nombre) return true;
            const v = nombre.value;
            if (!v) return setInvalid(nombre, 'El nombre es obligatorio.');
            if (!RE_NOMBRE.test(v)) return setInvalid(nombre, 'Solo letras y espacios (3–80).');
            return clearInvalid(nombre);
        }
        function validaArea() {
            if (!areaId) return true;
            const v = String(areaId.value || '').trim();
            if (!v) return setInvalid(areaId, 'Selecciona un área.');
            return clearInvalid(areaId);
        }
        function validaDescripcion() {
            if (!desc) return true;
            const v = desc.value || '';
            if (!v) return setInvalid(desc, 'La descripción es obligatoria.');
            if (v.length > 100) return setInvalid(desc, 'Máximo 100 caracteres.');
            return clearInvalid(desc);
        }

        // ======== UX al teclear (espacios permitidos) ========
        nombre?.addEventListener('input', () => {
            const cur = nombre.selectionStart;
            const before = nombre.value;
            const after = collapseSpacesNoTrim(before);
            if (after !== before) {
                nombre.value = after;
                try { nombre.setSelectionRange(cur, cur); } catch { }
            }
            validaNombre();
        });
        nombre?.addEventListener('blur', () => {
            nombre.value = fullNormalize(nombre.value);
            validaNombre();
        });

        desc?.addEventListener('input', () => {
            const before = desc.value;
            const after = collapseSpacesNoTrim(before);
            if (after !== before) desc.value = after;
            validaDescripcion();
        });
        desc?.addEventListener('blur', () => {
            desc.value = fullNormalize(desc.value);
            validaDescripcion();
        });

        areaId?.addEventListener('change', validaArea);

        // ======== Submit (solo JS) + anti doble envío ========
        let enviando = false;
        form.addEventListener('submit', (e) => {
            // Normalizaciones finales
            if (nombre) nombre.value = fullNormalize(nombre.value);
            if (desc) desc.value = fullNormalize(desc.value);

            const ok = (validaNombre() & validaArea() & validaDescripcion());
            if (!ok) {
                e.preventDefault(); e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }
            if (enviando) {
                e.preventDefault(); e.stopPropagation();
                return;
            }
            enviando = true;
            if (submit) {
                submit.disabled = true;
                submit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Guardando...';
            }
        });

        // ======== Funciones globales para botones (sin PHP en JS) ========
        // Importante: NO usamos BASE_URL aquí. Tomamos el action actual y reemplazamos la parte final.
        function toActualizarAction(url) { return url.replace('puestos.crear', 'puestos.actualizar'); }
        function toCrearAction(url) { return url.replace('puestos.actualizar', 'puestos.crear'); }

        window.openCreate = function () {
            form.action = toCrearAction(form.action);
            document.getElementById('modalTitle').textContent = "Nuevo puesto";
            if (idPuesto) idPuesto.value = "";
            if (nombre) nombre.value = "";
            if (desc) desc.value = "";
            if (areaId) areaId.value = "";
            clearInvalid(nombre); clearInvalid(desc); clearInvalid(areaId);
            form.classList.remove('was-validated');
        };

        window.openEdit = function (id, n, d, aId) {
            form.action = toActualizarAction(form.action);
            document.getElementById('modalTitle').textContent = "Editar puesto";
            if (idPuesto) idPuesto.value = id;
            if (nombre) nombre.value = n;
            if (desc) desc.value = d;
            if (areaId) areaId.value = aId;
            clearInvalid(nombre); clearInvalid(desc); clearInvalid(areaId);
            form.classList.remove('was-validated');
            bootstrap.Modal.getOrCreateInstance(modal).show();
        };

        window.openConfirmEliminar = function (id, n) {
            const idEliminar = document.getElementById('idEliminar');
            const etiqueta = document.getElementById('etiquetaNombre');
            if (idEliminar) idEliminar.value = id;
            if (etiqueta) etiqueta.textContent = n ?? '';
            const m = document.getElementById('modalConfirmEliminar');
            if (m) bootstrap.Modal.getOrCreateInstance(m).show();
        };

        // ======== Reapertura automática por PRG (sin PHP dentro de JS) ========
        // Si el servidor imprimió un .alert-danger dentro del modal, lo detectamos y abrimos.
        const hayErrores = modal.querySelector('.alert.alert-danger');
        if (hayErrores) {
            // Decide crear/editar según si idPuesto tiene valor
            const editando = idPuesto && String(idPuesto.value || '').trim() !== '';
            form.action = editando ? toActualizarAction(form.action) : toCrearAction(form.action);
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    }
})();