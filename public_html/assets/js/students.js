const tableBody = document.getElementById('studentsTableBody');
const alertBox = document.getElementById('alert');
const modal = document.getElementById('studentModal');
const modalTitle = document.getElementById('modalTitle');
const studentForm = document.getElementById('studentForm');
const openCreateBtn = document.getElementById('openCreateBtn');
const closeModalBtn = document.getElementById('closeModalBtn');
const cancelBtn = document.getElementById('cancelBtn');
const uploadModal = document.getElementById('uploadModal');
const openUploadBtn = document.getElementById('openUploadBtn');
const closeUploadModalBtn = document.getElementById('closeUploadModalBtn');
const cancelUploadBtn = document.getElementById('cancelUploadBtn');
const uploadStudentsForm = document.getElementById('uploadStudentsForm');
const uploadResult = document.getElementById('uploadResult');
const courseFilter = document.getElementById('courseFilter');

function removeVisibleIdHeader() {
    const firstHeader = tableBody
        ?.closest('table')
        ?.querySelector('thead th:first-child');

    if (firstHeader?.textContent.trim().toUpperCase() === 'ID') {
        firstHeader.remove();
    }
}

let editing = false;
let studentsCache = [];
let currentCourseFilter = '';

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

function openUploadModal() {
    uploadResult.className = 'info-box hidden';
    uploadResult.textContent = '';
    uploadStudentsForm.reset();
    uploadModal.classList.remove('hidden');
}

function closeUploadModal() {
    uploadModal.classList.add('hidden');
    uploadStudentsForm.reset();
    uploadResult.className = 'info-box hidden';
}

function setFormStudent(student) {
    document.getElementById('studentId').value = student.id || '';
    document.getElementById('name').value = student.name || '';
    document.getElementById('active').value = String(student.active ?? 1);
    document.getElementById('student_course').value = student.student_course || '';
    document.getElementById('student_rut').value = student.student_rut || '';

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
    if (!students.length) {
        const message = currentCourseFilter
            ? 'No hay alumnos registrados para el curso seleccionado.'
            : 'No hay alumnos registrados.';
        tableBody.innerHTML = `<tr><td colspan="7">${message}</td></tr>`;
        return;
    }

    tableBody.innerHTML = students.map(student => `
        <tr>
            <td>${escapeHtml(student.name)}</td>
            <td>${studentAttribute(student, 'student_course')}</td>
            <td>${studentAttribute(student, 'student_rut')}</td>
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

function studentAttribute(student, key) {
    return student[key] ? escapeHtml(student[key]) : 'Sin dato';
}

function normalizeCourse(course) {
    return String(course ?? '').trim();
}

function populateCourseFilter(students) {
    const selectedCourse = currentCourseFilter;
    const courses = [...new Set(students
        .map(student => normalizeCourse(student.student_course))
        .filter(Boolean))]
        .sort((first, second) => first.localeCompare(second, 'es', { numeric: true, sensitivity: 'base' }));

    courseFilter.innerHTML = '<option value="">Todos los cursos</option>';

    courses.forEach(course => {
        const option = document.createElement('option');
        option.value = course;
        option.textContent = course;
        courseFilter.appendChild(option);
    });

    const courseStillExists = courses.includes(selectedCourse);
    currentCourseFilter = courseStillExists ? selectedCourse : '';
    courseFilter.value = currentCourseFilter;
    courseFilter.disabled = courses.length === 0;
}

function filteredStudents() {
    if (!currentCourseFilter) return studentsCache;

    return studentsCache.filter(student => normalizeCourse(student.student_course) === currentCourseFilter);
}

function applyCourseFilter() {
    renderStudents(filteredStudents());
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


function renderUploadResult(data) {
    uploadResult.textContent = data.message || 'Proceso terminado.';

    if (!Array.isArray(data.errors) || data.errors.length === 0) {
        return;
    }

    const errorsList = document.createElement('ul');
    errorsList.className = 'upload-errors';

    data.errors.forEach((error) => {
        const item = document.createElement('li');
        item.textContent = error;
        errorsList.appendChild(item);
    });

    uploadResult.appendChild(errorsList);
}

async function loadStudents() {
    try {
        const data = await request('backend/users/list.php?role=alumno');
        if (!data.success) {
            showAlert(data.message || 'No se pudieron cargar los alumnos.', 'error');
            return;
        }
        studentsCache = Array.isArray(data.users) ? data.users : [];
        populateCourseFilter(studentsCache);
        applyCourseFilter();
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
    formData.append('role', 'alumno');

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
openUploadBtn.addEventListener('click', openUploadModal);
closeUploadModalBtn.addEventListener('click', closeUploadModal);
cancelUploadBtn.addEventListener('click', closeUploadModal);
courseFilter.addEventListener('change', () => {
    currentCourseFilter = courseFilter.value;
    applyCourseFilter();
});

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


uploadStudentsForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const submitButton = uploadStudentsForm.querySelector('button[type="submit"]');
    const formData = new FormData(uploadStudentsForm);
    submitButton.disabled = true;
    uploadResult.className = 'info-box';
    uploadResult.textContent = 'Procesando CSV...';

    try {
        const data = await request('backend/users/upload-students.php', formData);
        uploadResult.className = `info-box ${data.success ? 'success-text' : 'error-text'}`;
        renderUploadResult(data);
        showAlert(data.message || 'Carga finalizada.', data.success ? 'success' : 'error');

        if (data.success) {
            loadStudents();
        }
    } catch (error) {
        uploadResult.className = 'info-box error-text';
        uploadResult.textContent = 'Error al subir alumnos.';
        showAlert('Error al subir alumnos.', 'error');
    } finally {
        submitButton.disabled = false;
    }
});

removeVisibleIdHeader();

loadStudents();
