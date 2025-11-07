    // === admin.propietarios.js ===
    // --- Buscador en tabla
    document.getElementById('buscador')?.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#tablaPropietarios tbody tr').forEach(tr => {
            const nombre = (tr.children[0]?.textContent || '').toLowerCase();
            const ext = (tr.children[4]?.textContent || '').toLowerCase();
            tr.style.display = (nombre.includes(q) || ext.includes(q)) ? '' : 'none';
        });
    });

    // --- Validación Bootstrap base (sin globos del navegador)
    document.querySelectorAll('.needs-validation').forEach(f => {
        f.addEventListener('submit', e => {
            if (!f.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            f.classList.add('was-validated');
        });
    });

    // --- Área → Puestos dependientes
    const selArea = document.getElementById('areaId');
    const selPuesto = document.getElementById('puestoId');

    function renderPuestos(areaId, seleccionado = '') {
        selPuesto.innerHTML = '<option value="">Selecciona</option>';
        let items = CATALOGO_PUESTOS;
        if (areaId) items = items.filter(p => String(p.areaId) === String(areaId));
        items.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.idPuesto;
            opt.textContent = p.nombre;
            if (String(seleccionado) === String(p.idPuesto)) opt.selected = true;
            selPuesto.appendChild(opt);
        });
    }
    selArea?.addEventListener('change', () => renderPuestos(selArea.value, selPuesto.value));

    // --- Repeater de correos adicionales
    const wrapCorreos = document.getElementById('wrapCorreos');
    const tplCorreo = document.getElementById('tplCorreo');
    const btnAddCorreo = document.getElementById('btnAddCorreo');

    function addCorreo(value = '') {
        const node = tplCorreo.content.cloneNode(true);
        const input = node.querySelector('input[name="correos_extra[]"]');
        input.value = value || '';
        input.addEventListener('input', () => input.classList.remove('is-invalid'));
        node.querySelector('.btn-remove').addEventListener('click', function() {
            this.closest('.correo-item')?.remove();
        });
        wrapCorreos.appendChild(node);
    }

    function renderCorreosDesde(lista = []) {
        wrapCorreos.innerHTML = '';
        (Array.isArray(lista) && lista.length ? lista : ['']).forEach(c => addCorreo(c));
    }

    // --- Modos Crear / Editar
    function openCreate() {
        const form = document.getElementById('formPropietario');
        form.action = `${BASE_URL}/config/app.php?accion=propietarios.crear`;
        document.getElementById('modalTitle').textContent = "Nuevo propietario";
        document.getElementById('idPropietario').value = "";
        form.reset();
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.classList.remove('was-validated');
        renderPuestos('', '');
        renderCorreosDesde([]);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPropietario')).show();
    }

    function openEdit(id, nombre, apellidoP, apellidoM, correo, puestoId, areaId, extensionId) {
        const form = document.getElementById('formPropietario');
        form.action = `${BASE_URL}/config/app.php?accion=propietarios.actualizar`;
        document.getElementById('modalTitle').textContent = "Editar propietario";
        document.getElementById('idPropietario').value = id || '';
        document.getElementById('nombre').value = nombre || '';
        document.getElementById('apellidoP').value = apellidoP || '';
        document.getElementById('apellidoM').value = apellidoM || '';
        document.getElementById('correo').value = correo || '';
        document.getElementById('areaId').value = areaId || '';
        renderPuestos(areaId || '', puestoId || '');
        document.getElementById('extensionId').value = extensionId || '';
        renderCorreosDesde(PROPS_CORREOS[id] || []);
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.classList.remove('was-validated');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPropietario')).show();
    }

    function openConfirmEliminar(id, nombreEtiqueta) {
        document.getElementById('idEliminar').value = id;
        document.getElementById('etiquetaNombre').textContent = nombreEtiqueta ?? '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmEliminar')).show();
    }

    // Expone funciones globales (para los botones inline en PHP)
    window.openCreate = openCreate;
    window.openEdit = openEdit;
    window.openConfirmEliminar = openConfirmEliminar;

    // --- PRG (reapertura del modal si hubo errores)
    if (window.PR_REABRIR && window.PR_DATOS) {
        const v = window.PR_DATOS;
        if (PR_REABRIR === 'crear') {
            openCreate();
        } else if (PR_REABRIR === 'editar') {
            openEdit(v.idPropietario || '', v.nombre || '', v.apellidoP || '',
                v.apellidoM || '', v.correo || '', v.puestoId || '',
                v.areaId || '', v.extensionId || '');
        }
        renderCorreosDesde(Array.isArray(v.correos_extra) ? v.correos_extra : []);
    }

    // === VALIDACIONES ===
    const RE_NOMBRE = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' ]{3,50}$/; // letras + espacios
    const RE_APE = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ'-]{3,50}$/; // letras + ' + -
    const RE_CORP = /^[A-Za-z0-9._%+-]+@mtcenter\.com\.mx$/i;

    // Sanitizador en tiempo real
    (function () {
    const soloNombre = /[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ' ]/g;  // deja letras, espacio y '
    const nombre = document.getElementById('nombre');

    // No usamos trim() aquí. Colapsamos espacios múltiples y
    // quitamos solo espacios al inicio; permitimos el espacio final.
    function normalizaNombreEnEntrada(s) {
        return s
        .replace(soloNombre, '') // quita lo no permitido
        .replace(/ {2,}/g, ' ')  // colapsa múltiples espacios
        .replace(/^\s+/, '');    // sin espacios al inicio
        // Nota: NO hacemos .trim() para no borrar el espacio final
    }

    if (nombre) nombre.addEventListener('input', () => {
        const v = normalizaNombreEnEntrada(nombre.value);
        if (v !== nombre.value) {
        const p = nombre.selectionStart;
        nombre.value = v;
        nombre.setSelectionRange(p, p);
        }
        nombre.classList.remove('is-invalid');
    });
    })();

    // Validación manual al enviar
    (function() {
        const form = document.getElementById('formPropietario');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            let ok = true;
            const nombreEl = document.getElementById('nombre');
            const apPEl = document.getElementById('apellidoP');
            const apMEl = document.getElementById('apellidoM');
            const mailEl = document.getElementById('correo');

            const nombreVal = (nombreEl?.value || '').trim();
            const apPVal = (apPEl?.value || '').trim();
            const apMVal = (apMEl?.value || '').trim();
            const mailVal = (mailEl?.value || '').trim().toLowerCase();

            [nombreEl, apPEl, apMEl, mailEl].forEach(el => el && el.classList.remove('is-invalid'));
            document.querySelectorAll('input[name="correos_extra[]"]').forEach(i => i.classList.remove('is-invalid'));

            if (!RE_NOMBRE.test(nombreVal)) {
                ok = false;
                nombreEl?.classList.add('is-invalid');
            }
            if (!RE_APE.test(apPVal)) {
                ok = false;
                apPEl?.classList.add('is-invalid');
            }
            if (!RE_APE.test(apMVal)) {
                ok = false;
                apMEl?.classList.add('is-invalid');
            }

            const emails = [];
            if (!RE_CORP.test(mailVal)) {
                ok = false;
                mailEl?.classList.add('is-invalid');
            } else emails.push(mailVal);

            document.querySelectorAll('input[name="correos_extra[]"]').forEach(inp => {
                const v = (inp.value || '').trim().toLowerCase();
                if (!v) return;
                if (!RE_CORP.test(v)) {
                    ok = false;
                    inp.classList.add('is-invalid');
                } else emails.push(v);
            });

            const set = new Set(emails);
            if (set.size !== emails.length) {
                ok = false;
                const counts = {};
                emails.forEach(x => counts[x] = (counts[x] || 0) + 1);
                [mailEl, ...document.querySelectorAll('input[name="correos_extra[]"]')].forEach(i => {
                    if (!i) return;
                    const v = (i.value || '').trim().toLowerCase();
                    if (v && counts[v] > 1) i.classList.add('is-invalid');
                });
            }

            if (!ok || !form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');
            }
        });
    })();

    // Botón agregar correo
    btnAddCorreo?.addEventListener('click', () => addCorreo(''));