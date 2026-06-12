function showAlert(message, type = 'success') {
    const alert = document.getElementById('alert');
    if (!alert) return;

    alert.textContent = message;
    alert.className = `alert ${type}`;
}

async function submitForm(url, form) {
    const formData = new FormData(form);
    const response = await fetch(url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    });

    return response.json();
}

function initializePasswordToggles() {
    const passwordToggleButtons = document.querySelectorAll('[data-password-toggle]');

    passwordToggleButtons.forEach((button) => {
        button.addEventListener('mousedown', (event) => {
            event.preventDefault();
        });

        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;

            const shouldShowPassword = input.type === 'password';
            input.type = shouldShowPassword ? 'text' : 'password';
            button.textContent = shouldShowPassword ? 'Ocultar' : 'Mostrar';
            button.setAttribute('aria-label', `${shouldShowPassword ? 'Ocultar' : 'Mostrar'} contraseña`);
            button.setAttribute('aria-pressed', String(shouldShowPassword));
        });
    });
}

initializePasswordToggles();

const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        try {
            const data = await submitForm('backend/auth/login.php', loginForm);
            showAlert(data.message, data.success ? 'success' : 'error');

            if (data.success) {
                window.location.href = data.redirect || 'dashboard.php';
            }
        } catch (error) {
            showAlert('Error de conexión con el servidor.', 'error');
        }
    });
}

const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        try {
            const data = await submitForm('backend/auth/register.php', registerForm);
            showAlert(data.message, data.success ? 'success' : 'error');

            if (data.success) {
                registerForm.reset();
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 900);
            }
        } catch (error) {
            showAlert('Error de conexión con el servidor.', 'error');
        }
    });
}
