    // === admin.areas.js ===
    // ---------- Utilidades ----------
    const RE_AREA = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9'&()., -]{3,60}$/; // Ajusta el charset si quieres
    const RE_CORP = /^[A-Za-z0-9._%+-]+@mtcenter\.com\.mx$/i;

    function normTxt(s) {
        return (s || '').replace(/\s+/g, ' ').trim();
    }

    // Lee el ID que está cargado en el modal (si estás editando)
    function currentEditingId() {
        const hidden = document.getElementById('idArea');
        return hidden ? String(hidden.value || '') : '';
    }

    // Obtiene los nombres existentes de la tabla, con su id (para evitar falsos positivos al editar)
    function tableAreaNames() {
        const list = [];
        document.querySelectorAll('#tablaAreas tbody tr').forEach(tr => {
            const idAttr = tr.getAttribute('data-id');
            const id = idAttr ? String(idAttr) : '';
            const nombre = normTxt(tr.children[0]?.textContent);
            if (id && nombre) list.push({
                id,
                nombreLower: nombre.toLowerCase()
            });
        });
        return list;
    }

    // Chequeo de duplicidad (por nombre, case-insensitive), ignorando el propio id si estás editando
    function isDuplicateAreaName(nombre, selfId) {
        const target = normTxt(nombre).toLowerCase();
        if (!target) return false;
        return tableAreaNames().some(row => row.nombreLower === target && row.id !== selfId);
    }

    // ---------- Filtro en tabla ----------
    (function tableFilter() {
        const input = document.getElementById('buscador');

        function apply(q) {
            const txt = (q || '').toLowerCase();
            document.querySelectorAll('#tablaAreas tbody tr').forEach(tr => {
                const nombre = (tr.children[0]?.textContent || '').toLowerCase();
                const desc = (tr.children[2]?.textContent || '').toLowerCase();
                tr.style.display = (nombre.includes(txt) || desc.includes(txt)) ? '' : 'none';
            });
        }
        if (input) {
            apply(input.value);
            input.addEventListener('input', () => apply(input.value));
        }
    })();

    // ---------- Validación Bootstrap base (sin tooltips nativos) ----------
    document.querySelectorAll('.needs-validation').forEach(f => {
        f.addEventListener('submit', e => {
            if (!f.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            f.classList.add('was-validated');
        });
    });

    // ---------- Repeater Correos adicionales ----------
    const wrapCorreos = document.getElementById('correosExtraWrap');
    const tplCorreo = document.getElementById('tplCorreoExtra');
    const btnAddCorreoExtra = document.getElementById('btnAddCorreoExtra');

    function addCorreoExtra(value = '') {
        const node = tplCorreo.content.firstElementChild.cloneNode(true);
        const input = node.querySelector('input[name="correos_extra[]"]');
        input.value = value || '';
        input.addEventListener('input', () => input.classList.remove('is-invalid'));
        node.querySelector('.btnRemoveCorreoExtra').addEventListener('click', () => node.remove());
        wrapCorreos.appendChild(node);
    }
    btnAddCorreoExtra?.addEventListener('click', () => addCorreoExtra());

    // ---------- Abrir modal: Crear / Editar / Eliminar ----------
    function openCreate() {
        const form = document.getElementById('formArea');
        form.action = `${BASE_URL}/config/app.php?accion=areas.crear`;
        document.getElementById('modalTitle').textContent = "Nueva área";
        document.getElementById('idArea').value = "";
        // Limpia campos (si no vienes de PRG)
        document.getElementById('nombre').value = "";
        document.getElementById('email').value = "";
        document.getElementById('descripcion').value = "";
        wrapCorreos.innerHTML = "";
        addCorreoExtra(""); // deja al menos un campo vacío
        // Limpia marcas
        form.classList.remove('was-validated');
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalArea')).show();
    }

    function openEdit(id, nombre, email, descripcion) {
        const form = document.getElementById('formArea');
        form.action = `${BASE_URL}/config/app.php?accion=areas.actualizar`;
        document.getElementById('modalTitle').textContent = "Editar área";
        document.getElementById('idArea').value = id || '';
        document.getElementById('nombre').value = nombre || '';
        document.getElementById('email').value = email || '';
        document.getElementById('descripcion').value = descripcion || '';
        // Correos extra desde mapa
        wrapCorreos.innerHTML = "";
        (AREAS_CORREOS[id] || []).forEach(c => addCorreoExtra(c));
        if (!wrapCorreos.children.length) addCorreoExtra("");
        // Limpia marcas
        form.classList.remove('was-validated');
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalArea')).show();
    }

    function openConfirmEliminar(id, nombre) {
        document.getElementById('idEliminar').value = id;
        document.getElementById('etiquetaNombre').textContent = nombre ?? '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmEliminar')).show();
    }

    // Expone para los botones inline
    window.openCreate = openCreate;
    window.openEdit = openEdit;
    window.openConfirmEliminar = openConfirmEliminar;

    // ---------- Reapertura PRG ----------
    (function prgReopen() {
        if (!window.PR_REABRIR) return;
        const v = window.PR_DATOS || {};
        if (PR_REABRIR === 'crear') {
            openCreate();
            // Si vinieron correos via PRG, los restituimos:
            if (Array.isArray(v.correos_extra) && v.correos_extra.length) {
                wrapCorreos.innerHTML = '';
                v.correos_extra.forEach(c => addCorreoExtra(String(c || '').trim()));
            }
        } else if (PR_REABRIR === 'editar') {
            openEdit(v.idArea || '', v.nombre || '', v.email || '', v.descripcion || '');
            if (Array.isArray(v.correos_extra) && v.correos_extra.length) {
                wrapCorreos.innerHTML = '';
                v.correos_extra.forEach(c => addCorreoExtra(String(c || '').trim()));
            }
        }
    })();

    // ---------- Sanitizadores en tiempo real ----------
    (function realtimeSanitize() {
        const nombreEl = document.getElementById('nombre');
        const emailEl = document.getElementById('email');
        const descEl = document.getElementById('descripcion');

        // Nombre: colapsa espacios múltiples; recorta extremos visualmente
        nombreEl?.addEventListener('input', () => {
            const cur = nombreEl.value;
            const next = cur.replace(/ {2,}/g, ' ');
            if (next !== cur) {
                const p = nombreEl.selectionStart;
                nombreEl.value = next;
                nombreEl.setSelectionRange(p, p);
            }
            nombreEl.classList.remove('is-invalid');
        });

        // Email principal: solo quita marca de error al teclear
        emailEl?.addEventListener('input', () => emailEl.classList.remove('is-invalid'));

        // Descripción: colapsa espacios múltiples (opcional)
        descEl?.addEventListener('input', () => {
            const cur = descEl.value;
            const next = cur.replace(/ {2,}/g, ' ');
            if (next !== cur) {
                const p = descEl.selectionStart;
                descEl.value = next;
                descEl.setSelectionRange(p, p);
            }
        });
    })();

    // ---------- Validación al enviar (suave, sin tooltips nativos) ----------
    (function onSubmitValidate() {
        const form = document.getElementById('formArea');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            let ok = true;

            const idSelf = currentEditingId();
            const nombreEl = document.getElementById('nombre');
            const emailEl = document.getElementById('email');

            const nombre = normTxt(nombreEl?.value);
            const email = normTxt(emailEl?.value);

            // Limpia marcas previas
            [nombreEl, emailEl].forEach(el => el && el.classList.remove('is-invalid'));
            document.querySelectorAll('#correosExtraWrap input[name="correos_extra[]"]').forEach(i => i.classList.remove('is-invalid'));

            // 1) Nombre requerido + formato + duplicado
            if (!nombre || !RE_AREA.test(nombre)) {
                ok = false;
                nombreEl?.classList.add('is-invalid');
            } else if (isDuplicateAreaName(nombre, idSelf)) {
                ok = false;
                nombreEl?.classList.add('is-invalid');
            }

            // 2) Correo principal (opcional, pero si viene debe ser corporativo)
            const emails = [];
            if (email) {
                if (!RE_CORP.test(email)) {
                    ok = false;
                    emailEl?.classList.add('is-invalid');
                } else {
                    emails.push(email.toLowerCase());
                }
            }

            // 3) Correos extra (opcionales) + sin duplicados
            document.querySelectorAll('#correosExtraWrap input[name="correos_extra[]"]').forEach(inp => {
                const v = normTxt(inp.value).toLowerCase();
                if (!v) return; // vacío permitido
                if (!RE_CORP.test(v)) {
                    ok = false;
                    inp.classList.add('is-invalid');
                } else {
                    emails.push(v);
                }
            });

            // Duplicados entre principal y extra
            const set = new Set(emails);
            if (set.size !== emails.length) {
                ok = false;
                // marca duplicados
                const counts = {};
                emails.forEach(x => counts[x] = (counts[x] || 0) + 1);
                // principal
                if (email && counts[email.toLowerCase()] > 1) emailEl?.classList.add('is-invalid');
                // extras
                document.querySelectorAll('#correosExtraWrap input[name="correos_extra[]"]').forEach(inp => {
                    const v = normTxt(inp.value).toLowerCase();
                    if (v && counts[v] > 1) inp.classList.add('is-invalid');
                });
            }

            // 4) Si falla algo, no enviamos
            if (!ok || !form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');
            }
        });
    })();