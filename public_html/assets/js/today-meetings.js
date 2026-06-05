const tableBody = document.getElementById('todayMeetingsBody');
let isLoadingMeetings = false;
const MEETINGS_REFRESH_INTERVAL_MS = 15000;
const MEETINGS_REFRESH_EVENT_KEY = 'meetings:last-change';
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

async function loadMeetings(showLoading = true) {
    if (isLoadingMeetings) return;

    isLoadingMeetings = true;

    if (showLoading) {
        tableBody.innerHTML = '<tr><td colspan="4">Cargando...</td></tr>';
    }

    try {
        const res = await fetch('backend/meetings/today-public.php', { cache: 'no-store' });
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
    } catch (error) {
        tableBody.innerHTML = '<tr><td colspan="4">No se pudieron cargar las reuniones.</td></tr>';
    } finally {
        isLoadingMeetings = false;
    }
}

window.addEventListener('storage', (event) => {
    if (event.key === MEETINGS_REFRESH_EVENT_KEY) {
        loadMeetings(false);
    }
});

setInterval(() => loadMeetings(false), MEETINGS_REFRESH_INTERVAL_MS);

loadMeetings();
