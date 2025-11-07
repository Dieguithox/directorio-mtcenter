// ==== admin.notas.js ====
(() => {
    // ---------- Helpers ----------
    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

    const BASE = (window.BASE_URL || '').replace(/\/+$/, '');

    function setText(el, txt) {
        if (!el) return;
        el.textContent = String(txt ?? '');
    }

    function cleanFormStates(form) {
        if (!form) return;
        form.classList.remove('was-validated');
        $$('.is-invalid', form).forEach(e => e.classList.remove('is-invalid'));
    }

    // Mantén esta función igual: colapsa espacios y deja bonito AL ENVIAR
    function sanitizeNoteText(s) {
        if (typeof s !== 'string') return '';
        s = s.replace(/\r\n/g, '\n');          // CRLF -> LF
        s = s.replace(/\n{3,}/g, '\n\n');      // máx 2 saltos seguidos
        s = s.split('\n').map(line =>
            line.replace(/[ \t]+/g, ' ')         // colapsa espacios/tabs por línea
                .replace(/^ +| +$/g, '')         // trim por línea
        ).join('\n');
        return s.trim();
    }

    function lenWithin(txt, min, max) {
        const n = (txt || '').length;
        return n >= min && n <= max;
    }

    // ---------- Elementos base ----------
    const form = $('#formNota');
    const selProp = $('#propietarioId');
    const ta = $('#texto');
    const cnt = $('#cnt');
    const modalNotaEl = $('#modalNota');
    const btnGuardar = $('#btnGuardar');

    // Feedbacks
    const fbProp = $('#fb-prop');
    const fbTexto = $('#fb-texto');

    // ---------- Contador, sanitización y validación en vivo ----------
    function updateCounter() {
        if (!ta || !cnt) return;
        setText(cnt, (ta.value || '').length);
    }

    // AJUSTE: mientras escribes, NO colapses espacios, solo normaliza CRLF y limita saltos extra
    function applyLiveSanitize() {
        if (!ta) return;
        const pos = ta.selectionStart;
        const before = ta.value;
        const after = before
            .replace(/\r\n/g, '\n')   // CRLF -> LF
            .replace(/\n{3,}/g, '\n\n'); // máx 2 saltos seguidos
        if (after !== before) {
            ta.value = after;
            if (typeof pos === 'number') ta.setSelectionRange(Math.min(after.length, pos), Math.min(after.length, pos));
        }
    }

    ta?.addEventListener('input', () => {
        applyLiveSanitize();        // ya no colapsa espacios en vivo
        updateCounter();
        const v = ta.value;
        if (!lenWithin(v, 5, 500)) ta.classList.add('is-invalid');
        else ta.classList.remove('is-invalid');
    });

    selProp?.addEventListener('change', () => {
        if (selProp.value) selProp.classList.remove('is-invalid');
    });

    // ---------- Validación al enviar ----------
    form?.addEventListener('submit', (e) => {
        let ok = true;
        cleanFormStates(form);

        // propietario obligatorio
        if (!selProp?.value) {
            ok = false;
            selProp?.classList.add('is-invalid');
            if (fbProp) fbProp.textContent = 'Selecciona un contacto.';
        }

        // Nota: sanitiza COMPLETO aquí (sí colapsa espacios)
        if (ta) {
            ta.value = sanitizeNoteText(ta.value);
            const valido = lenWithin(ta.value, 5, 500);
            if (!valido) {
                ok = false;
                ta.classList.add('is-invalid');
                if (fbTexto) fbTexto.textContent = 'La nota debe tener entre 5 y 500 caracteres.';
            }
            updateCounter();
        }

        if (!ok || !form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            form.classList.add('was-validated');
        }
    });

    // ---------- Accesos públicos para la vista ----------
    function openCreate() {
        if (!form) return;
        form.action = `${BASE}/config/app.php?accion=notas.crear`;
        const ttl = $('#modalTitle');
        if (ttl) ttl.textContent = 'Nueva nota';

        $('#idNota')?.setAttribute('value', '');
        if (selProp) {
            selProp.value = '';
            selProp.classList.remove('is-invalid');
        }
        if (ta) {
            ta.value = '';
            ta.classList.remove('is-invalid');
        }
        updateCounter();
        cleanFormStates(form);
        bootstrap.Modal.getOrCreateInstance(modalNotaEl).show();
    }

    function openEdit(idNota, propietarioId, texto) {
        if (!form) return;
        form.action = `${BASE}/config/app.php?accion=notas.actualizar`;
        const ttl = $('#modalTitle');
        if (ttl) ttl.textContent = 'Editar nota';

        $('#idNota')?.setAttribute('value', String(idNota ?? ''));
        if (selProp) {
            selProp.value = String(propietarioId ?? '');
            selProp.classList.remove('is-invalid');
        }
        if (ta) {
            // Al abrir edición, puedes mostrar el texto tal cual (sin colapsar espacios)
            ta.value = String(texto ?? '');
            ta.classList.remove('is-invalid');
        }
        updateCounter();
        cleanFormStates(form);
        bootstrap.Modal.getOrCreateInstance(modalNotaEl).show();
    }

    function openDelete(id, nombre) {
        $('#delId')?.setAttribute('value', String(id ?? ''));
        setText($('#delNombre'), nombre ?? '');
        bootstrap.Modal.getOrCreateInstance($('#modalDelete')).show();
    }

    // Exponer a window
    window.openCreate = openCreate;
    window.openEdit = openEdit;
    window.openDelete = openDelete;

    // ---------- UX extra ----------
    ta?.addEventListener('keydown', (e) => {
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            btnGuardar?.click();
        }
    });

    updateCounter();

    // ---------- Reapertura por PRG ----------
    (function prgReopen() {
        const modo = window.PR_MODO; // 'crear' | 'editar' | null
        const v = window.PR_DATA || {};
        if (!modo) return;

        if (modo === 'crear') {
            openCreate();
            if (selProp) selProp.value = String(v.propietarioId ?? '');
            if (ta) {
                ta.value = String(v.texto ?? ''); // sin colapsar espacios al repoblar
                updateCounter();
            }
        } else if (modo === 'editar') {
            openEdit(
                parseInt(v.idNota || 0, 10),
                parseInt(v.propietarioId || 0, 10),
                String(v.texto || '')
            );
        }
    })();

    // ---------- Toast ----------
    const toastEl = document.querySelector('.toast');
    if (toastEl) bootstrap.Toast.getOrCreateInstance(toastEl).show();
})();