    // === admin.horarios.js ===
    // ---------- Utilidades ----------
    function to12h(hhmmss) {
        const [h, m] = hhmmss.split(':');
        let H = parseInt(h, 10);
        const suf = H >= 12 ? 'p. m.' : 'a. m.';
        H = (H % 12) || 12;
        return `${H}:${m} ${suf}`;
    }

    function hhmmToMin(hhmm) {
        const [h, m] = hhmm.split(':').map(Number);
        return h * 60 + m;
    }

    function diasSeleccionados() {
        return Array.from(document.querySelectorAll('.dia:checked')).map(i => i.value);
    }

    function diasTraslapan(d1, d2) {
        if (!Array.isArray(d1) || !Array.isArray(d2)) return false;
        const s = new Set(d1);
        return d2.some(x => s.has(x));
    }

    function rangosTraslapan(he1, hs1, he2, hs2) {
        const a1 = hhmmToMin(he1.slice(0, 5));
        const b1 = hhmmToMin(hs1.slice(0, 5));
        const a2 = hhmmToMin(he2.slice(0, 5));
        const b2 = hhmmToMin(hs2.slice(0, 5));
        // Traslape si max(inicios) < min(finales)
        return Math.max(a1, a2) < Math.min(b1, b2);
    }

    function limpiarErroresForm(form) {
        form.classList.remove('was-validated');
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        const dErr = document.getElementById('diasError');
        if (dErr) dErr.style.display = 'none';
    }

    // ---------- Pintar horas en la tabla (12h) ----------
    (function hydrateTableTimes() {
        document.querySelectorAll('.pill-time').forEach(el => {
            const raw = el.getAttribute('data-time') || '00:00:00';
            el.textContent = to12h(raw);
        });
    })();

    // ---------- Días: “Horario general” ↔ checkboxes ----------
    const chkGeneral = document.getElementById('chkGeneral');
    const diasChecks = document.querySelectorAll('.dia');

    chkGeneral?.addEventListener('change', () => {
        diasChecks.forEach(c => c.checked = chkGeneral.checked);
        const dErr = document.getElementById('diasError');
        if (dErr) dErr.style.display = 'none';
    });
    diasChecks.forEach(c => c.addEventListener('change', () => {
        if (![...diasChecks].some(x => x.checked)) chkGeneral.checked = false;
    }));

    // ---------- Filtro simple en tabla (si ya existe input `name="q"`) ----------
    (function wireFilter() {
        const input = document.querySelector('input[name="q"]');
        if (!input) return;

        function apply(q) {
            const txt = (q || '').toLowerCase();
            document.querySelectorAll('table tbody tr').forEach(tr => {
                const empleado = (tr.querySelector('td:nth-child(1)')?.textContent || '').toLowerCase();
                tr.style.display = empleado.includes(txt) ? '' : 'none';
            });
        }
        apply(input.value);
        input.addEventListener('input', () => apply(input.value));
    })();

    // ---------- Abrir crear / editar / eliminar ----------
    function openCreate() {
        const f = document.getElementById('formHorario');
        f.action = `${BASE_URL}/config/app.php?accion=horarios.crear`;
        document.getElementById('modalTitle').textContent = "Nuevo horario";
        document.getElementById('idHorario').value = "";
        document.getElementById('old_he').value = "";
        document.getElementById('old_hs').value = "";
        const sub = document.getElementById('subtituloEmpleado');
        if (sub) sub.style.display = 'none';
        document.getElementById('propietarioId').value = "";
        diasChecks.forEach(c => c.checked = false);
        chkGeneral.checked = false;
        document.getElementById('horaEntrada').value = "09:00";
        document.getElementById('horaSalida').value = "18:00";
        limpiarErroresForm(f);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalHorario')).show();
    }

    function openEdit(idRep, propietarioId, diasArr, he, hs, nombreProp) {
        const f = document.getElementById('formHorario');
        f.action = `${BASE_URL}/config/app.php?accion=horarios.actualizar`;
        document.getElementById('modalTitle').textContent = "Editar horario — " + (nombreProp || '');
        document.getElementById('idHorario').value = idRep;
        document.getElementById('propietarioId').value = propietarioId;
        document.getElementById('old_he').value = he;
        document.getElementById('old_hs').value = hs;
        const sub = document.getElementById('subtituloEmpleado');
        if (sub) {
            sub.style.display = 'block';
            sub.textContent = nombreProp || '';
        }
        diasChecks.forEach(c => c.checked = (diasArr || []).includes(c.value));
        chkGeneral.checked = (diasArr || []).length === 7;
        document.getElementById('horaEntrada').value = (he || '09:00:00').slice(0, 5);
        document.getElementById('horaSalida').value = (hs || '18:00:00').slice(0, 5);
        limpiarErroresForm(f);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalHorario')).show();
    }

    function openDelete(idRep, propietarioId, he, hs, nombre) {
        document.getElementById('delId').value = idRep;
        document.getElementById('delProp').value = propietarioId;
        document.getElementById('delOldHe').value = he;
        document.getElementById('delOldHs').value = hs;
        document.getElementById('delNombre').textContent = nombre || '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDelete')).show();
    }

    // Expone para botones inline
    window.openCreate = openCreate;
    window.openEdit = openEdit;
    window.openDelete = openDelete;

    // ---------- Reapertura por PRG (si hubo errores) ----------
    (function prgReopen() {
        if (!PR_MODO) return;
        if (PR_MODO === 'crear') {
            openCreate();
        } else if (PR_MODO === 'editar') {
            const v = PR_DATA || {};
            // Espera mismo shape que ya mandas en PHP
            openEdit(
                parseInt(v.idHorario || 0, 10),
                parseInt(v.propietarioId || 0, 10),
                Array.isArray(v.dias) ? v.dias : [],
                String(v.horaEntrada || '09:00:00'),
                String(v.horaSalida || '18:00:00'),
                String(v.propNombre || 'Empleado')
            );
        }
    })();

    // ---------- Validación al enviar ----------
    (function handleSubmitValidation() {
        const f = document.getElementById('formHorario');
        if (!f) return;

        f.addEventListener('submit', (e) => {
            let ok = true;

            const empSel = document.getElementById('propietarioId');
            const heEl = document.getElementById('horaEntrada');
            const hsEl = document.getElementById('horaSalida');

            const idSelf = String(document.getElementById('idHorario')?.value || '');

            // Limpia marcas previas
            limpiarErroresForm(f);

            // 1) Empleado requerido
            if (!empSel.value) {
                ok = false;
                empSel.classList.add('is-invalid');
            }

            // 2) Días: al menos uno
            const diasSel = diasSeleccionados();
            if (diasSel.length === 0) {
                ok = false;
                const dErr = document.getElementById('diasError');
                if (dErr) dErr.style.display = 'block';
            }

            // 3) Hora lógica: entrada < salida
            const he = (heEl.value || '').trim();
            const hs = (hsEl.value || '').trim();
            if (!he || !hs || hhmmToMin(he) >= hhmmToMin(hs)) {
                ok = false;
                heEl.classList.add('is-invalid');
                hsEl.classList.add('is-invalid');
            }

            // 4) Traslape contra horarios existentes del mismo empleado (cliente, suave)
            //    — el servidor sigue siendo la autoridad.
            const empId = parseInt(empSel.value || '0', 10);
            if (empId && diasSel.length && he && hs) {
                const conflictos = HORARIOS.filter(h =>
                    String(h.id) !== idSelf && // ignora el que estás editando
                    parseInt(h.propietarioId, 10) === empId &&
                    diasTraslapan(diasSel, h.dias) &&
                    rangosTraslapan(he + ':00', hs + ':00', h.he, h.hs)
                );
                if (conflictos.length) {
                    ok = false;
                    heEl.classList.add('is-invalid');
                    hsEl.classList.add('is-invalid');
                    // También muestra el error de días para llamar la atención
                    const dErr = document.getElementById('diasError');
                    if (dErr) dErr.style.display = 'block';
                }
            }

            // 5) Corta si algo falló
            if (!ok || !f.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                f.classList.add('was-validated');
            }
        });
    })();