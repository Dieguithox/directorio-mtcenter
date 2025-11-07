// === admin.extensiones.js ===
// Filtro en tabla (buscador)
(function tableFilter() {
    const input = document.getElementById('buscador');
    function apply(q) {
        const txt = (q || '').toLowerCase();
        document.querySelectorAll('#tablaExt tbody tr').forEach(tr => {
            const num = (tr.children[0]?.textContent || '').toLowerCase();
            const desc = (tr.children[1]?.textContent || '').toLowerCase();
            tr.style.display = (num.includes(txt) || desc.includes(txt)) ? '' : 'none';
        });
    }
    if (input) {
        apply(input.value);
        input.addEventListener('input', () => apply(input.value));
    }
})();

// Validación Bootstrap
document.querySelectorAll('.needs-validation').forEach(f => {
    f.addEventListener('submit', e => {
        if (!f.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        f.classList.add('was-validated');
    });
});

// Utilidades UI del modal
function limpiarEstadoModal() {
    // Quitar alertas duplicadas previas
    const cont = document.querySelector('#modalExtension .modal-body');
    cont?.querySelectorAll('.alert-dup').forEach(a => a.remove());
    // Limpiar validación visual
    const form = document.getElementById('formExtension');
    form?.classList.remove('was-validated');
    // Limpiar customValidity del input numero
    const numeroInput = document.getElementById('numero');
    if (numeroInput) {
        numeroInput.setCustomValidity('');
    }
}

// Abrir modal en modo crear
let EXT_ORIGINAL = null; // número original cuando editas (solo cliente)
function openCreate() {
    const form = document.getElementById('formExtension');
    form.action = `${BASE_URL}/config/app.php?accion=extensiones.crear`;
    document.getElementById('modalTitle').textContent = 'Nueva extensión';
    document.getElementById('idExtension').value = '';
    document.getElementById('numero').value = '';
    document.getElementById('descripcion').value = '';
    EXT_ORIGINAL = null;
    limpiarEstadoModal();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalExtension')).show();
}

// Abrir modal en modo editar
function openEdit(id, numero, descripcion) {
    const form = document.getElementById('formExtension');
    form.action = `${BASE_URL}/config/app.php?accion=extensiones.actualizar`;
    document.getElementById('modalTitle').textContent = 'Editar extensión';
    document.getElementById('idExtension').value = id;
    document.getElementById('numero').value = numero || '';
    document.getElementById('descripcion').value = descripcion || '';
    EXT_ORIGINAL = (numero || '').trim();
    limpiarEstadoModal();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalExtension')).show();
}

// Confirmar eliminar
function openConfirmEliminar(id, numero) {
    document.getElementById('idEliminar').value = id;
    document.getElementById('etiquetaNumero').textContent = numero ?? '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmEliminar')).show();
}

// Exponer a los botones inline de la tabla
window.openCreate = openCreate;
window.openEdit = openEdit;
window.openConfirmEliminar = openConfirmEliminar;

// Reapertura automática PRG
(function prgReopen() {
    if (!window.PR_REABRIR) return;
    const v = window.PR_DATOS || {};
    if (PR_REABRIR === 'crear') {
        openCreate();
        if (v) {
            if (typeof v.numero === 'string') document.getElementById('numero').value = v.numero;
            if (typeof v.descripcion === 'string') document.getElementById('descripcion').value = v.descripcion;
        }
    } else if (PR_REABRIR === 'editar') {
        openEdit(
            v.idExtension || '',
            v.numero || '',
            v.descripcion || ''
        );
    }
})();

// Validación de duplicados (cliente)
// Mantiene el modal ABIERTO tanto en crear como en editar
(function duplicateCheck() {
    const form = document.getElementById('formExtension');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        const id = (document.getElementById('idExtension')?.value || '').trim();
        const numeroInput = document.getElementById('numero');
        const numero = (numeroInput?.value || '').trim();

        // Limpia errores previos del input
        if (numeroInput) numeroInput.setCustomValidity('');

        // Construye set de existentes desde la tabla visible
        const existentes = new Set(
            Array.from(document.querySelectorAll('#tablaExt tbody tr'))
                .map(tr => (tr.children[0]?.textContent || '').trim())
                .filter(v => v !== '')
        );

        const esEdicion = id !== '';

        // Caso CREAR: si ya existe, bloquear y mantener modal
        if (!esEdicion && numero !== '' && existentes.has(numero)) {
            e.preventDefault(); e.stopPropagation();
            mostrarErrorDuplicado('Ya existe otra extensión con ese número.');
            if (numeroInput) { numeroInput.reportValidity(); }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalExtension')).show();
            return;
        }

        // Caso EDITAR: si cambió el número y el nuevo ya existe, bloquear y mantener modal
        if (esEdicion) {
            const noCambio = (EXT_ORIGINAL !== null) && (numero === EXT_ORIGINAL);
            if (!noCambio && numero !== '' && existentes.has(numero)) {
                e.preventDefault(); e.stopPropagation();
                mostrarErrorDuplicado('Ya existe otra extensión con ese número.');
                if (numeroInput) { numeroInput.reportValidity(); }
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalExtension')).show();
                return;
            }
        }
        // Si pasa, el backend procede normal (PRG, flash, etc.)
    });

    function mostrarErrorDuplicado(msg) {
        const cont = document.querySelector('#modalExtension .modal-body');
        if (!cont) return;
        let alert = cont.querySelector('.alert-dup');
        if (!alert) {
            alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show alert-dup';
            alert.setAttribute('role', 'alert');
            alert.innerHTML = `
        <div>${msg}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        `;
            cont.prepend(alert);
        } else {
            alert.querySelector('div').textContent = msg;
            alert.classList.add('show');
        }
    }
})();
