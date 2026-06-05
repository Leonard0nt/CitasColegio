const tableBody = document.getElementById('usersTableBody');
const alertBox = document.getElementById('alert');
const modal = document.getElementById('userModal');
const modalTitle = document.getElementById('modalTitle');
const userForm = document.getElementById('userForm');
const openCreateBtn = document.getElementById('openCreateBtn');
const closeModalBtn = document.getElementById('closeModalBtn');
const cancelBtn = document.getElementById('cancelBtn');
const uploadModal = document.getElementById('uploadModal');
const openUploadBtn = document.getElementById('openUploadBtn');
const closeUploadModalBtn = document.getElementById('closeUploadModalBtn');
const cancelUploadBtn = document.getElementById('cancelUploadBtn');
const uploadTeachersForm = document.getElementById('uploadTeachersForm');
const uploadResult = document.getElementById('uploadResult');

let editing = false;
let usersCache = [];

function showAlert(message, type = 'success') {
    alertBox.textContent = message;
    alertBox.className = `alert ${type}`;

    setTimeout(() => {
        alertBox.className = 'alert hidden';
    }, 3500);
}

function openModal() {
    modal.classList.remove('hidden');
}

function closeModal() {
    modal.classList.add('hidden');
    userForm.reset();
    document.getElementById('userId').value = '';
    editing = false;
}

function openUploadModal() {
    uploadResult.className = 'info-box hidden';
    uploadResult.textContent = '';
    uploadTeachersForm.reset();
    uploadModal.classList.remove('hidden');
}

function closeUploadModal() {
    uploadModal.classList.add('hidden');
    uploadTeachersForm.reset();
    uploadResult.className = 'info-box hidden';
}

function setFormUser(user) {
    document.getElementById('userId').value = user.id || '';
    document.getElementById('name').value = user.name || '';
    document.getElementById('email').value = user.email || '';
    document.getElementById('active').value = String(user.active ?? 1);
    document.getElementById('password').value = '';
    document.getElementById('teacher_cost_center').value = user.teacher_cost_center || '';
    document.getElementById('teacher_rut').value = user.teacher_rut || '';
    document.getElementById('teacher_phone').value = user.teacher_phone || '';
}

async function request(url, formData = null) {
    const options = {
        method: formData ? 'POST' : 'GET',
        credentials: 'same-origin'
    };

    if (formData) options.body = formData;

    const response = await fetch(url, options);
    return response.json();
}

function renderUsers(users) {
    usersCache = users;

    if (!users.length) {
        tableBody.innerHTML = '<tr><td colspan="7">No hay profesores registrados.</td></tr>';
        return;
    }

    tableBody.innerHTML = users.map(user => `
        <tr>
            <td>${escapeHtml(user.name)}</td>
            <td>${escapeHtml(user.email)}</td>
            <td><span class="badge ${Number(user.active) === 1 ? 'active' : 'inactive'}">${Number(user.active) === 1 ? 'Activo' : 'Inactivo'}</span></td>
            <td>${teacherAttribute(user, 'teacher_rut')}</td>
            <td>${teacherAttribute(user, 'teacher_cost_center')}</td>
            <td>${teacherAttribute(user, 'teacher_phone')}</td>
            <td>
                <div class="actions">
                    <button class="btn btn-small" data-action="edit" data-id="${user.id}">Editar</button>
                    <button class="btn btn-danger btn-small" data-action="delete" data-id="${user.id}">Eliminar</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function teacherAttribute(user, key) {
    return user[key] ? escapeHtml(user[key]) : 'Sin dato';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

async function loadUsers() {
    try {
        const data = await request('backend/users/list.php?role=profesor');
        if (!data.success) {
            showAlert(data.message || 'No se pudieron cargar los profesores.', 'error');
            return;
        }
        renderUsers(data.users);
    } catch (error) {
        tableBody.innerHTML = '<tr><td colspan="7">Error cargando profesores.</td></tr>';
    }
}

function editUserById(id) {
    const user = usersCache.find(item => Number(item.id) === Number(id));
    if (!user) return;

    editing = true;
    modalTitle.textContent = 'Editar profesor';
    setFormUser(user);
    openModal();
}

async function deleteUser(id) {
    if (!confirm('¿Seguro que deseas eliminar este profesor?')) return;

    const formData = new FormData();
    formData.append('id', id);

    try {
        const data = await request('backend/users/delete.php', formData);
        showAlert(data.message, data.success ? 'success' : 'error');
        if (data.success) loadUsers();
    } catch (error) {
        showAlert('Error al eliminar profesor.', 'error');
    }
}

openCreateBtn.addEventListener('click', () => {
    editing = false;
    userForm.reset();
    document.getElementById('userId').value = '';
    modalTitle.textContent = 'Nuevo profesor';
    document.getElementById('active').value = '1';
    openModal();
});

closeModalBtn.addEventListener('click', closeModal);
cancelBtn.addEventListener('click', closeModal);
openUploadBtn.addEventListener('click', openUploadModal);
closeUploadModalBtn.addEventListener('click', closeUploadModal);
cancelUploadBtn.addEventListener('click', closeUploadModal);

modal.addEventListener('click', (event) => {
    if (event.target === modal) closeModal();
});

uploadModal.addEventListener('click', (event) => {
    if (event.target === uploadModal) closeUploadModal();
});

tableBody.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action]');
    if (!button) return;

    const id = button.dataset.id;
    const action = button.dataset.action;

    if (action === 'edit') editUserById(id);
    if (action === 'delete') deleteUser(id);
});

userForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(userForm);
    formData.set('role', 'profesor');
    const url = editing ? 'backend/users/update.php' : 'backend/users/create.php';

    try {
        const data = await request(url, formData);
        showAlert(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            closeModal();
            loadUsers();
        }
    } catch (error) {
        showAlert('Error al guardar profesor.', 'error');
    }
});

uploadTeachersForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const submitButton = uploadTeachersForm.querySelector('button[type="submit"]');
    const formData = new FormData(uploadTeachersForm);
    submitButton.disabled = true;
    uploadResult.className = 'info-box';
    uploadResult.textContent = 'Procesando CSV...';

    try {
        const data = await request('backend/users/upload-teachers.php', formData);
        uploadResult.className = `info-box ${data.success ? 'success-text' : 'error-text'}`;
        uploadResult.textContent = data.message || 'Proceso terminado.';
        showAlert(data.message || 'Carga finalizada.', data.success ? 'success' : 'error');

        if (data.success) {
            loadUsers();
        }
    } catch (error) {
        uploadResult.className = 'info-box error-text';
        uploadResult.textContent = 'Error al subir profesores.';
        showAlert('Error al subir profesores.', 'error');
    } finally {
        submitButton.disabled = false;
    }
});

loadUsers();
