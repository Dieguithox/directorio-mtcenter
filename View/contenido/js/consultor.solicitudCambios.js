(function () {
    const selProp = document.getElementById('propietarioSelect');
    const card = document.getElementById('prop-card');
    const avatar = document.getElementById('prop-avatar');
    const nombreEl = document.getElementById('prop-nombre');
    const puestoEl = document.getElementById('prop-puesto');
    const emailEl = document.getElementById('prop-email');
    const extEl = document.getElementById('prop-ext');
    const campoSel = document.getElementById('campoSelect');
    const valorInp = document.getElementById('valor_nuevo');
    const ayuda = document.getElementById('ayuda-campo');
    const errCampo = document.getElementById('error-campo');
    const valAntInp = document.getElementById('valor_anterior');
    const form = document.getElementById('formSolicitudCambio');

    // 1. traer datos del propietario
    if (selProp) {
        selProp.addEventListener('change', function () {
            const id = this.value;
            if (!id) {
                card.classList.add('d-none');
                return;
            }
            // OJO: ajusta la URL base si tu proyecto la necesita
            fetch(BASE_URL + '/config/app.php?accion=consultor.propietario.json&id=' + id)
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) {
                        card.classList.add('d-none');
                        return;
                    }
                    const p = data.propietario;
                    card.classList.remove('d-none');

                    const nombreCompleto =
                        (p.nombre || '') + ' ' + (p.apellidoP || '') + ' ' + (p.apellidoM || '');

                    nombreEl.textContent = nombreCompleto.trim() || 'Propietario';
                    avatar.textContent = nombreCompleto.trim()
                        ? nombreCompleto.trim().charAt(0).toUpperCase()
                        : 'P';

                    // si no mandas puesto desde PHP, deja el texto fijo
                    puestoEl.textContent = p.puestoNombre || 'Contacto del directorio';
                    emailEl.textContent = p.email ? p.email : 'sin correo';

                    // aquí el fix: usamos extensionNumero si lo manda el PHP
                    extEl.textContent = 'Ext: ' + (p.extensionNumero ? p.extensionNumero : '—');

                    // valor anterior lo dejamos limpio por ahora
                    valAntInp.value = '';
                })
                .catch(() => {
                    card.classList.add('d-none');
                });
        });
    }

    // 2. validaciones según campo
    if (campoSel) {
        campoSel.addEventListener('change', function () {
            const campo = this.value;
            valorInp.value = '';
            valorInp.classList.remove('is-invalid');
            valorInp.removeAttribute('pattern');
            valorInp.removeAttribute('maxlength');
            valorInp.setAttribute('required', 'required');

            switch (campo) {
                case 'nombre':
                case 'apellidoP':
                case 'apellidoM':
                case 'puestoId':
                    ayuda.textContent = 'Solo letras y espacios. Ej. "María López"';
                    valorInp.setAttribute('pattern', '^[A-Za-zÁÉÍÓÚÑáéíóúñ ]+$');
                    errCampo.textContent = 'Solo letras y espacios.';
                    break;
                case 'extensionId':
                    ayuda.textContent = 'Solo números, máximo 3 dígitos. Ej. "225"';
                    valorInp.setAttribute('pattern', '^\\d{1,3}$');
                    valorInp.setAttribute('maxlength', '3');
                    errCampo.textContent = 'Coloca solo números (1 a 3 dígitos).';
                    break;
                case 'email':
                    ayuda.textContent = 'Debe ser un correo empresarial @mtcenter.com.mx';
                    valorInp.setAttribute('pattern', '^[A-Za-z0-9._%+-]+@mtcenter\\.com\\.mx$');
                    errCampo.textContent = 'El correo debe terminar en @mtcenter.com.mx';
                    break;
                default:
                    ayuda.textContent = 'Escribe el nuevo valor.';
                    errCampo.textContent = 'Este campo es obligatorio.';
            }
        });
    }

    // 3. validación del form
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
})();