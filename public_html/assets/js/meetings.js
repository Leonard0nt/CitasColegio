const tableBody = document.getElementById('meetingsTableBody');
const alertBox = document.getElementById('alert');
const modal = document.getElementById('meetingModal');
const form = document.getElementById('meetingForm');
const openBtn = document.getElementById('openCreateMeetingBtn');
const closeBtn = document.getElementById('closeMeetingModalBtn');
const cancelBtn = document.getElementById('cancelMeetingBtn');
const teacherSelect = document.getElementById('teacher_id');
const studentSelect = document.getElementById('student_id');
const guardianTypeSelect = document.getElementById('guardian_type');
const dateInput = document.getElementById('meeting_date');
const timeInput = document.getElementById('meeting_time');
const statusSelect = document.getElementById('status');
const notesInput = document.getElementById('notes');

let options = { teachers: [], students: [] };

function showAlert(message, type = 'success') {
    alertBox.textContent = message;
    alertBox.className = `alert ${type}`;
    setTimeout(() => alertBox.classList.add('hidden'), 3500);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function statusLabel(status) {
    return status === 'atendido' ? 'Atendido' : 'Por atender';
}

function guardianLabel(type) {
    return type === 'suplente' ? 'Suplente' : 'Titular';
}

function formatTime(time) {
    return String(time || '').slice(0, 5);
}

async function loadOptions() {
    const res = await fetch('backend/meetings/options.php');
    const data = await res.json();

    if (!data.success) {
        showAlert(data.message || 'Error al cargar opciones.', 'error');
        return;
    }

    options = data;

    if (teacherSelect) {
        teacherSelect.innerHTML = data.teachers.length
            ? data.teachers.map(t => `<option value="${t.id}">${escapeHtml(t.name)} (${escapeHtml(t.email)})</option>`).join('')
            : '<option value="">No hay profesores activos</option>';
    }

    studentSelect.innerHTML = data.students.length
        ? data.students.map(s => `<option value="${s.id}">${escapeHtml(s.name)} (${escapeHtml(s.email)})</option>`).join('')
        : '<option value="">No hay alumnos con apoderado</option>';

    updateGuardianAvailability();
}

function getSelectedStudent() {
    return options.students.find(s => String(s.id) === String(studentSelect.value));
}

function updateGuardianAvailability() {
    const student = getSelectedStudent();
    if (!student) return;

    const titular = guardianTypeSelect.querySelector('option[value="titular"]');
    const suplente = guardianTypeSelect.querySelector('option[value="suplente"]');

    titular.textContent = student.guardian_name
        ? `Apoderado titular (${student.guardian_name})`
        : 'Apoderado titular no registrado';

    titular.disabled = !student.guardian_name;

    suplente.textContent = student.backup_guardian_name
        ? `Apoderado suplente (${student.backup_guardian_name})`
        : 'Apoderado suplente no registrado';

    suplente.disabled = !student.backup_guardian_name;

    if (guardianTypeSelect.selectedOptions[0]?.disabled) {
        guardianTypeSelect.value = student.guardian_name ? 'titular' : 'suplente';
    }
}

async function loadMeetings() {
    tableBody.innerHTML = '<tr><td colspan="8">Cargando reuniones...</td></tr>';

    const res = await fetch('backend/meetings/list.php');
    const data = await res.json();

    if (!data.success) {
        tableBody.innerHTML = '<tr><td colspan="8">Error al cargar reuniones.</td></tr>';
        return;
    }

    if (!data.meetings.length) {
        tableBody.innerHTML = '<tr><td colspan="8">No hay reuniones agendadas.</td></tr>';
        return;
    }

    tableBody.innerHTML = data.meetings.map(m => `
        <tr>
            <td>${m.id}</td>
            <td>${escapeHtml(m.teacher_name)}</td>
            <td>${escapeHtml(m.student_name)}</td>
            <td>
                <div class="guardian-summary">
                    <strong>${escapeHtml(m.guardian_name)}</strong>
                    <small>${guardianLabel(m.guardian_type)}${m.guardian_phone ? ' · ' + escapeHtml(m.guardian_phone) : ''}</small>
                </div>
            </td>
            <td>${escapeHtml(m.meeting_date)}</td>
            <td>${escapeHtml(formatTime(m.meeting_time))}</td>
            <td><span class="badge ${m.status}">${statusLabel(m.status)}</span></td>
            <td class="actions-cell">
                <button class="btn btn-small" onclick="toggleStatus(${m.id}, '${m.status === 'atendido' ? 'por_atender' : 'atendido'}')">
                    ${m.status === 'atendido' ? 'Por atender' : 'Atendido'}
                </button>
                <button class="btn btn-danger btn-small" onclick="deleteMeeting(${m.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

function openModal() {
    form.reset();
    const today = new Date().toISOString().slice(0, 10);
    dateInput.value = today;
    timeInput.value = '08:00';
    statusSelect.value = 'por_atender';
    modal.classList.remove('hidden');
    updateGuardianAvailability();
}

function closeModal() {
    modal.classList.add('hidden');
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(form);

    const res = await fetch('backend/meetings/create.php', {
        method: 'POST',
        body: formData,
    });

    const data = await res.json();
    showAlert(data.message || 'Acción realizada.', data.success ? 'success' : 'error');

    if (data.success) {
        closeModal();
        await loadMeetings();
    }
});

async function toggleStatus(id, status) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('status', status);

    const res = await fetch('backend/meetings/update-status.php', {
        method: 'POST',
        body: formData,
    });

    const data = await res.json();
    showAlert(data.message || 'Estado actualizado.', data.success ? 'success' : 'error');
    if (data.success) loadMeetings();
}

async function deleteMeeting(id) {
    if (!confirm('¿Eliminar esta reunión?')) return;

    const formData = new FormData();
    formData.append('id', id);

    const res = await fetch('backend/meetings/delete.php', {
        method: 'POST',
        body: formData,
    });

    const data = await res.json();
    showAlert(data.message || 'Reunión eliminada.', data.success ? 'success' : 'error');
    if (data.success) loadMeetings();
}

openBtn.addEventListener('click', openModal);
closeBtn.addEventListener('click', closeModal);
cancelBtn.addEventListener('click', closeModal);
studentSelect.addEventListener('change', updateGuardianAvailability);

loadOptions().then(loadMeetings);
