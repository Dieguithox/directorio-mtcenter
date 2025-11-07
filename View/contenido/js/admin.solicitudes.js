// /View/contenido/js/admin.solicitudes.js
(function () {
    const tabla = document.getElementById('tablaSolicitudes');
    if (!tabla) return;

    const modalDetalle = document.getElementById('modalDetalle');
    const modalAprobar = document.getElementById('modalAprobar');
    const modalRechazar = document.getElementById('modalRechazar');

    const bsDetalle = modalDetalle ? new bootstrap.Modal(modalDetalle) : null;
    const bsAprobar = modalAprobar ? new bootstrap.Modal(modalAprobar) : null;
    const bsRechazar = modalRechazar ? new bootstrap.Modal(modalRechazar) : null;

    tabla.addEventListener('click', function (e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        const tr = btn.closest('tr');
        if (!tr) return;

        if (btn.classList.contains('btn-ver')) {
            abrirDetalle(tr);
        } else if (btn.classList.contains('btn-aprobar')) {
            abrirAprobar(tr);
        } else if (btn.classList.contains('btn-rechazar')) {
            abrirRechazar(tr);
        }
    });

    function abrirDetalle(tr) {
        if (!bsDetalle) return;
        const prop = tr.dataset.propietario || '-';
        const campo = tr.dataset.campo || '-';
        const va = tr.dataset.valorAnterior && tr.dataset.valorAnterior !== '' ? tr.dataset.valorAnterior : '-';
        const vn = tr.dataset.valorNuevo && tr.dataset.valorNuevo !== '' ? tr.dataset.valorNuevo : '-';
        const com = tr.dataset.comentario || '-';
        const mot = tr.dataset.motivo || '-';

        document.getElementById('detPropietario').textContent = prop;
        document.getElementById('detCampo').textContent = 'Campo: ' + campo;
        document.getElementById('detValorAnterior').textContent = va;
        document.getElementById('detValorNuevo').textContent = vn;
        document.getElementById('detComentario').textContent = com;
        document.getElementById('detAvatar').textContent = prop ? prop.charAt(0).toUpperCase() : 'P';

        if (mot && mot !== '-') {
            document.getElementById('detMotivo').textContent = mot;
            document.getElementById('detMotivoWrap').style.display = '';
        } else {
            document.getElementById('detMotivo').textContent = '-';
            document.getElementById('detMotivoWrap').style.display = 'none';
        }

        bsDetalle.show();
    }

    function abrirAprobar(tr) {
        if (!bsAprobar) return;
        document.getElementById('apId').value = tr.dataset.id;
        document.getElementById('apCampo').textContent = tr.dataset.campo || '-';
        document.getElementById('apValorAnterior').textContent = tr.dataset.valorAnterior || '-';
        document.getElementById('apValorNuevo').textContent = tr.dataset.valorNuevo || '-';
        const ta = document.getElementById('apMotivo');
        ta.value = '';
        ta.classList.remove('is-invalid');
        bsAprobar.show();
    }

    function abrirRechazar(tr) {
        if (!bsRechazar) return;
        document.getElementById('reId').value = tr.dataset.id;
        document.getElementById('reCampo').textContent = tr.dataset.campo || '-';
        document.getElementById('reValorAnterior').textContent = tr.dataset.valorAnterior || '-';
        document.getElementById('reValorNuevo').textContent = tr.dataset.valorNuevo || '-';
        const ta = document.getElementById('reMotivo');
        ta.value = '';
        ta.classList.remove('is-invalid');
        bsRechazar.show();
    }

    // Validaciones bootstrap-like
    const formAprobar = document.getElementById('formAprobar');
    const formRechazar = document.getElementById('formRechazar');

    if (formAprobar) {
        formAprobar.addEventListener('submit', function (e) {
            const ta = document.getElementById('apMotivo');
            if (!ta.value.trim()) {
                e.preventDefault();
                ta.classList.add('is-invalid');
                ta.focus();
                mostrarToastError('Debes escribir el motivo de aprobación.');
            } else {
                ta.classList.remove('is-invalid');
            }
        });
    }

    if (formRechazar) {
        formRechazar.addEventListener('submit', function (e) {
            const ta = document.getElementById('reMotivo');
            if (!ta.value.trim()) {
                e.preventDefault();
                ta.classList.add('is-invalid');
                ta.focus();
                mostrarToastError('Debes escribir el motivo del rechazo.');
            } else {
                ta.classList.remove('is-invalid');
            }
        });
    }

    // pequeño helper para mostrar toast de error con Bootstrap, usando el mismo estilo que ya tienes
    function mostrarToastError(msg) {
        // si ya tienes el sistema de $flash servidor, esto es opcional.
        // aquí lo hacemos cliente por si la validación es puro JS.
        const cont = document.querySelector('.toast-container') || crearContenedorToast();
        const div = document.createElement('div');
        div.className = 'toast text-bg-danger border-0';
        div.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${msg}</div>
                <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        cont.appendChild(div);
        const t = new bootstrap.Toast(div);
        t.show();
    }

    function crearContenedorToast() {
        const c = document.createElement('div');
        c.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        c.style.zIndex = 1080;
        document.body.appendChild(c);
        return c;
    }

})();