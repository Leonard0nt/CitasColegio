const tableBody = document.getElementById('usersTableBody');
const alertBox = document.getElementById('alert');
const modal = document.getElementById('userModal');
const modalTitle = document.getElementById('modalTitle');
const userForm = document.getElementById('userForm');
const openCreateBtn = document.getElementById('openCreateBtn');
const closeModalBtn = document.getElementById('closeModalBtn');
const cancelBtn = document.getElementById('cancelBtn');
const roleSelect = document.getElementById('role');
const guardianFields = document.getElementById('guardianFields');
const teacherFields = document.getElementById('teacherFields');
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
    toggleGuardianFields();
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
    document.getElementById('role').value = user.role || 'alumno';
    document.getElementById('active').value = String(user.active ?? 1);
    document.getElementById('password').value = '';
    document.getElementById('teacher_cost_center').value = user.teacher_cost_center || '';
    document.getElementById('teacher_rut').value = user.teacher_rut || '';
    document.getElementById('teacher_phone').value = user.teacher_phone || '';

    document.getElementById('guardian_name').value = user.guardian_name || '';
    document.getElementById('guardian_rut').value = user.guardian_rut || '';
    document.getElementById('guardian_phone').value = user.guardian_phone || '';
    document.getElementById('guardian_email').value = user.guardian_email || '';
    document.getElementById('guardian_relationship').value = user.guardian_relationship || '';

    document.getElementById('backup_guardian_name').value = user.backup_guardian_name || '';
    document.getElementById('backup_guardian_rut').value = user.backup_guardian_rut || '';
    document.getElementById('backup_guardian_phone').value = user.backup_guardian_phone || '';
    document.getElementById('backup_guardian_email').value = user.backup_guardian_email || '';
    document.getElementById('backup_guardian_relationship').value = user.backup_guardian_relationship || '';

    toggleGuardianFields();
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
        tableBody.innerHTML = '<tr><td colspan="12">No hay usuarios registrados.</td></tr>';
        return;
    }

    tableBody.innerHTML = users.map(user => `
        <tr>
            <td>${user.id}</td>
            <td>${escapeHtml(user.name)}</td>
            <td>${escapeHtml(user.email)}</td>
            <td><span class="badge ${escapeHtml(user.role)}">${roleLabel(user.role)}</span></td>
            <td><span class="badge ${Number(user.active) === 1 ? 'active' : 'inactive'}">${Number(user.active) === 1 ? 'Activo' : 'Inactivo'}</span></td>
            <td>${teacherAttribute(user, 'teacher_rut')}</td>
            <td>${teacherAttribute(user, 'teacher_cost_center')}</td>
            <td>${teacherAttribute(user, 'teacher_phone')}</td>
            <td>${guardianSummary(user, false)}</td>
            <td>${guardianSummary(user, true)}</td>
            <td>${escapeHtml(user.created_at)}</td>
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
    if (user.role !== 'profesor') return 'No aplica';

    return user[key] ? escapeHtml(user[key]) : 'Sin dato';
}

function guardianSummary(user, backup = false) {
    if (user.role !== 'alumno') return 'No aplica';

    const name = backup ? user.backup_guardian_name : user.guardian_name;
    const phone = backup ? user.backup_guardian_phone : user.guardian_phone;
    const email = backup ? user.backup_guardian_email : user.guardian_email;

    if (!name) return backup ? 'Sin suplente' : 'Sin apoderado';

    return `
        <div class="guardian-summary">
            <strong>${escapeHtml(name)}</strong>
            ${phone ? `<small>${escapeHtml(phone)}</small>` : ''}
            ${email ? `<small>${escapeHtml(email)}</small>` : ''}
        </div>
    `;
}

function roleLabel(role) {
    if (role === 'admin') return 'Admin';
    if (role === 'profesor') return 'Profesor';
    if (role === 'alumno') return 'Alumno';
    return escapeHtml(role || '');
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function toggleGuardianFields() {
    if (!roleSelect || !guardianFields || !teacherFields) return;

    const requiredInputs = [document.getElementById('guardian_name')];

    if (roleSelect.value === 'alumno') {
        guardianFields.classList.remove('hidden');
        teacherFields.classList.add('hidden');
        requiredInputs.forEach(input => input && input.setAttribute('required', 'required'));
    } else if (roleSelect.value === 'profesor') {
        guardianFields.classList.add('hidden');
        teacherFields.classList.remove('hidden');
        requiredInputs.forEach(input => input && input.removeAttribute('required'));
    } else {
        guardianFields.classList.add('hidden');
        teacherFields.classList.add('hidden');
        requiredInputs.forEach(input => input && input.removeAttribute('required'));
    }
}

async function loadUsers() {
    try {
        const data = await request('backend/users/list.php');
        if (!data.success) {
            showAlert(data.message || 'No se pudieron cargar los usuarios.', 'error');
            return;
        }
        renderUsers(data.users);
    } catch (error) {
        tableBody.innerHTML = '<tr><td colspan="12">Error cargando usuarios.</td></tr>';
    }
}

function editUserById(id) {
    const user = usersCache.find(item => Number(item.id) === Number(id));
    if (!user) return;

    editing = true;
    modalTitle.textContent = 'Editar usuario';
    setFormUser(user);
    openModal();
}

async function deleteUser(id) {
    if (!confirm('¿Seguro que deseas eliminar este usuario?')) return;

    const formData = new FormData();
    formData.append('id', id);

    try {
        const data = await request('backend/users/delete.php', formData);
        showAlert(data.message, data.success ? 'success' : 'error');
        if (data.success) loadUsers();
    } catch (error) {
        showAlert('Error al eliminar usuario.', 'error');
    }
}

openCreateBtn.addEventListener('click', () => {
    editing = false;
    userForm.reset();
    document.getElementById('userId').value = '';
    modalTitle.textContent = 'Nuevo usuario';
    document.getElementById('role').value = 'alumno';
    document.getElementById('active').value = '1';
    toggleGuardianFields();
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

if (roleSelect) {
    roleSelect.addEventListener('change', toggleGuardianFields);
}

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
    const url = editing ? 'backend/users/update.php' : 'backend/users/create.php';

    try {
        const data = await request(url, formData);
        showAlert(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            closeModal();
            loadUsers();
        }
    } catch (error) {
        showAlert('Error al guardar usuario.', 'error');
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
        const errors = Array.isArray(data.errors) && data.errors.length
            ? `\n${data.errors.join('\n')}`
            : '';

        uploadResult.className = `info-box ${data.success ? 'success-text' : 'error-text'}`;
        uploadResult.textContent = `${data.message || 'Proceso terminado.'}${errors}`;
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

toggleGuardianFields();
loadUsers();
