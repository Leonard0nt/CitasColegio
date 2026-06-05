const tableBody = document.getElementById('todayMeetingsBody');
function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function formatTime(time) {
    return String(time || '').slice(0, 5);
}

async function loadMeetings() {
    tableBody.innerHTML = '<tr><td colspan="4">Cargando...</td></tr>';
    const res = await fetch('backend/meetings/today-public.php');
    const data = await res.json();

    if (!data.success) {
        tableBody.innerHTML = '<tr><td colspan="4">No se pudieron cargar las reuniones.</td></tr>';
        return;
    }

    if (!data.meetings.length) {
        tableBody.innerHTML = '<tr><td colspan="4">No hay reuniones para hoy.</td></tr>';
        return;
    }

    tableBody.innerHTML = data.meetings.map(m => `
        <tr>
            <td>${escapeHtml(m.teacher_name)}</td>
            <td>${escapeHtml(m.student_name)}</td>
            <td>${escapeHtml(m.guardian_name)}</td>
            <td>${escapeHtml(formatTime(m.meeting_time))}</td>
        </tr>
    `).join('');
}

loadMeetings();
