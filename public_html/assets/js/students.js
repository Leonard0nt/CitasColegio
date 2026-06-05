const tableBody = document.getElementById('studentsTableBody');
const alertBox = document.getElementById('alert');
const modal = document.getElementById('studentModal');
const modalTitle = document.getElementById('modalTitle');
const studentForm = document.getElementById('studentForm');
const openCreateBtn = document.getElementById('openCreateBtn');
const closeModalBtn = document.getElementById('closeModalBtn');
const cancelBtn = document.getElementById('cancelBtn');

let editing = false;
let studentsCache = [];

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
    studentForm.reset();
    document.getElementById('studentId').value = '';
    editing = false;
}

function setFormStudent(student) {
    document.getElementById('studentId').value = student.id || '';
    document.getElementById('name').value = student.name || '';
    document.getElementById('email').value = student.email || '';
    document.getElementById('active').value = String(student.active ?? 1);
    document.getElementById('password').value = '';

    document.getElementById('guardian_name').value = student.guardian_name || '';
    document.getElementById('guardian_rut').value = student.guardian_rut || '';
    document.getElementById('guardian_phone').value = student.guardian_phone || '';
    document.getElementById('guardian_email').value = student.guardian_email || '';
    document.getElementById('guardian_relationship').value = student.guardian_relationship || '';

    document.getElementById('backup_guardian_name').value = student.backup_guardian_name || '';
    document.getElementById('backup_guardian_rut').value = student.backup_guardian_rut || '';
    document.getElementById('backup_guardian_phone').value = student.backup_guardian_phone || '';
    document.getElementById('backup_guardian_email').value = student.backup_guardian_email || '';
    document.getElementById('backup_guardian_relationship').value = student.backup_guardian_relationship || '';
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

function renderStudents(students) {
    studentsCache = students;

    if (!students.length) {
        tableBody.innerHTML = '<tr><td colspan="7">No hay alumnos registrados.</td></tr>';
        return;
    }

    tableBody.innerHTML = students.map(student => `
        <tr>
            <td>${student.id}</td>
            <td>${escapeHtml(student.name)}</td>
            <td>${escapeHtml(student.email)}</td>
            <td><span class="badge ${Number(student.active) === 1 ? 'active' : 'inactive'}">${Number(student.active) === 1 ? 'Activo' : 'Inactivo'}</span></td>
            <td>${guardianSummary(student, false)}</td>
            <td>${guardianSummary(student, true)}</td>
            <td>
                <div class="actions">
                    <button class="btn btn-small" data-action="edit" data-id="${student.id}">Editar</button>
                    <button class="btn btn-danger btn-small" data-action="delete" data-id="${student.id}">Eliminar</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function guardianSummary(student, backup = false) {
    const name = backup ? student.backup_guardian_name : student.guardian_name;
    const phone = backup ? student.backup_guardian_phone : student.guardian_phone;
    const email = backup ? student.backup_guardian_email : student.guardian_email;
    const rut = backup ? student.backup_guardian_rut : student.guardian_rut;

    if (!name) return backup ? 'Sin suplente' : 'Sin apoderado';

    return `
        <div class="guardian-summary">
            <strong>${escapeHtml(name)}</strong>
            ${rut ? `<small>${escapeHtml(rut)}</small>` : ''}
            ${phone ? `<small>${escapeHtml(phone)}</small>` : ''}
            ${email ? `<small>${escapeHtml(email)}</small>` : ''}
        </div>
    `;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

async function loadStudents() {
    try {
        const data = await request('backend/users/list.php?role=alumno');
        if (!data.success) {
            showAlert(data.message || 'No se pudieron cargar los alumnos.', 'error');
            return;
        }
        renderStudents(data.users);
    } catch (error) {
        tableBody.innerHTML = '<tr><td colspan="7">Error cargando alumnos.</td></tr>';
    }
}

function editStudentById(id) {
    const student = studentsCache.find(item => Number(item.id) === Number(id));
    if (!student) return;

    editing = true;
    modalTitle.textContent = 'Editar alumno';
    setFormStudent(student);
    openModal();
}

async function deleteStudent(id) {
    if (!confirm('¿Seguro que deseas eliminar este alumno?')) return;

    const formData = new FormData();
    formData.append('id', id);

    try {
        const data = await request('backend/users/delete.php', formData);
        showAlert(data.message, data.success ? 'success' : 'error');
        if (data.success) loadStudents();
    } catch (error) {
        showAlert('Error al eliminar alumno.', 'error');
    }
}

openCreateBtn.addEventListener('click', () => {
    editing = false;
    studentForm.reset();
    document.getElementById('studentId').value = '';
    modalTitle.textContent = 'Nuevo alumno';
    document.getElementById('active').value = '1';
    openModal();
});

closeModalBtn.addEventListener('click', closeModal);
cancelBtn.addEventListener('click', closeModal);

modal.addEventListener('click', (event) => {
    if (event.target === modal) closeModal();
});

tableBody.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action]');
    if (!button) return;

    const id = button.dataset.id;
    const action = button.dataset.action;

    if (action === 'edit') editStudentById(id);
    if (action === 'delete') deleteStudent(id);
});

studentForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(studentForm);
    formData.set('role', 'alumno');
    const url = editing ? 'backend/users/update.php' : 'backend/users/create.php';

    try {
        const data = await request(url, formData);
        showAlert(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            closeModal();
            loadStudents();
        }
    } catch (error) {
        showAlert('Error al guardar alumno.', 'error');
    }
});

loadStudents();
