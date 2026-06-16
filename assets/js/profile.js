const profileForm = document.getElementById('profileForm');
const alertBox = document.getElementById('alert');

function showAlert(message, type = 'success') {
    alertBox.textContent = message;
    alertBox.className = `alert ${type}`;
    alertBox.classList.remove('hidden');
}

profileForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(profileForm);

    try {
        const response = await fetch('backend/users/profile-update.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const result = await response.json();

        if (!result.success) {
            showAlert(result.message || 'No se pudo actualizar el perfil.', 'error');
            return;
        }

        showAlert(result.message || 'Perfil actualizado correctamente.');
        profileForm.reset();
    } catch (error) {
        showAlert('Error de red. Intenta nuevamente.', 'error');
    }
});
