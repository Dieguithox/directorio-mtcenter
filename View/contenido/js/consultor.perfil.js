(function () {
    const nombre = document.getElementById('nombre');
    const email = document.getElementById('email');
    const pass = document.getElementById('pass');
    const pass2 = document.getElementById('pass2');
    const btn = document.getElementById('btnGuardar');

    const errNombre = document.getElementById('err-nombre');
    const errEmail = document.getElementById('err-email');
    const errPass = document.getElementById('err-pass');
    const errPass2 = document.getElementById('err-pass2');

    const okNombre = document.getElementById('ok-nombre');
    const okEmail = document.getElementById('ok-email');
    const okPass = document.getElementById('ok-pass');
    const okPass2 = document.getElementById('ok-pass2');

    const dominioPermitido = (window.PERFIL_CFG && PERFIL_CFG.dominio) ? PERFIL_CFG.dominio : '@mtcenter.com.mx';
    const reNombre = /^[A-Za-zÁÉÍÓÚáéíóúÑñüÜ\s]+$/;

    function validarNombre() {
        const val = nombre.value.trim();
        if (val === '') {
            errNombre.textContent = 'El nombre es obligatorio.';
            errNombre.style.display = 'block';
            okNombre.style.display = 'none';
            return false;
        }
        if (!reNombre.test(val)) {
            errNombre.textContent = 'Solo letras y espacios.';
            errNombre.style.display = 'block';
            okNombre.style.display = 'none';
            return false;
        }
        errNombre.style.display = 'none';
        okNombre.style.display = 'block';
        return true;
    }

    function validarEmail() {
        const val = email.value.trim();
        if (val === '') {
            errEmail.textContent = 'El correo es obligatorio.';
            errEmail.style.display = 'block';
            okEmail.style.display = 'none';
            return false;
        }
        if (!val.toLowerCase().endsWith(dominioPermitido)) {
            errEmail.textContent = 'Debe ser un correo ' + dominioPermitido;
            errEmail.style.display = 'block';
            okEmail.style.display = 'none';
            return false;
        }
        errEmail.style.display = 'none';
        okEmail.style.display = 'block';
        return true;
    }

    function validarPass() {
        const p = pass.value;
        const p2 = pass2.value;

        // no quieren cambiarla
        if (p === '' && p2 === '') {
            errPass.style.display = 'none';
            errPass2.style.display = 'none';
            okPass.style.display = 'none';
            okPass2.style.display = 'none';
            return true;
        }

        // longitud
        if (p.length < 7) {
            errPass.textContent = 'La contraseña debe tener al menos 7 caracteres.';
            errPass.style.display = 'block';
            okPass.style.display = 'none';
            // si ya hay confirmación, la reseteamos de vista
            errPass2.style.display = 'none';
            okPass2.style.display = 'none';
            return false;
        } else {
            errPass.style.display = 'none';
            okPass.style.display = 'block';
        }

        // coincidencia
        if (p2 !== p) {
            errPass2.textContent = 'Las contraseñas no coinciden.';
            errPass2.style.display = 'block';
            okPass2.style.display = 'none';
            return false;
        } else {
            errPass2.style.display = 'none';
            okPass2.style.display = 'block';
        }

        return true;
    }

    function validarTodo() {
        const a = validarNombre();
        const b = validarEmail();
        const c = validarPass();
        btn.disabled = !(a && b && c);
        return a && b && c;
    }

    // toggles
    const togglePass = document.getElementById('togglePass');
    const togglePass2 = document.getElementById('togglePass2');

    if (togglePass) {
        togglePass.addEventListener('click', function () {
            const type = pass.type === 'password' ? 'text' : 'password';
            pass.type = type;
            this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
    }
    if (togglePass2) {
        togglePass2.addEventListener('click', function () {
            const type = pass2.type === 'password' ? 'text' : 'password';
            pass2.type = type;
            this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
    }

    nombre.addEventListener('input', validarTodo);
    email.addEventListener('input', validarTodo);
    pass.addEventListener('input', validarTodo);
    pass2.addEventListener('input', validarTodo);

    document.getElementById('perfilForm').addEventListener('submit', function (e) {
        if (!validarTodo()) {
            e.preventDefault();
        }
    });

    // correr una vez al abrir
    validarTodo();
})();
