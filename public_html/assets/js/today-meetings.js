const tableBody = document.getElementById('todayMeetingsBody');
const alertBox = document.getElementById('alert');

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

function formatTime(time) {
    return String(time || '').slice(0, 5);
}

async function loadMeetings() {
    tableBody.innerHTML = '<tr><td colspan="6">Cargando...</td></tr>';
    const res = await fetch('backend/meetings/today-public.php');
    const data = await res.json();

    if (!data.success) {
        tableBody.innerHTML = '<tr><td colspan="6">No se pudieron cargar las reuniones.</td></tr>';
        return;
    }

    if (!data.meetings.length) {
        tableBody.innerHTML = '<tr><td colspan="6">No hay reuniones para hoy.</td></tr>';
        return;
    }

    tableBody.innerHTML = data.meetings.map(m => `
        <tr>
            <td>${escapeHtml(m.teacher_name)}</td>
            <td>${escapeHtml(m.student_name)}</td>
            <td>${escapeHtml(m.guardian_name)}</td>
            <td>${escapeHtml(formatTime(m.meeting_time))}</td>
            <td><span class="badge ${m.status}">${statusLabel(m.status)}</span></td>
            <td>
                ${m.status === 'atendido'
                    ? '<span class="muted">Confirmada</span>'
                    : `<button class="btn btn-small" onclick="confirmAttendance(${m.id})">Confirmar asistencia</button>`}
            </td>
        </tr>
    `).join('');
}

async function confirmAttendance(id) {
    const pin = prompt('Ingrese el PIN de 4 números para confirmar asistencia:');
    if (pin === null) return;

    const formData = new FormData();
    formData.append('id', id);
    formData.append('pin', pin.trim());

    const res = await fetch('backend/meetings/confirm-attendance.php', {
        method: 'POST',
        body: formData,
    });

    const data = await res.json();
    showAlert(data.message || 'Acción ejecutada.', data.success ? 'success' : 'error');
    if (data.success) loadMeetings();
}

loadMeetings();