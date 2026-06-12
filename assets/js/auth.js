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
