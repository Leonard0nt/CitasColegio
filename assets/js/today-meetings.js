const tableBody = document.getElementById('todayMeetingsBody');
let isLoadingMeetings = false;
const MEETINGS_REFRESH_INTERVAL_MS = 15000;
const teacherContactModal = document.getElementById('teacherContactModal');
const closeTeacherContactModalBtn = document.getElementById('closeTeacherContactModalBtn');
const closeTeacherContactModalActionBtn = document.getElementById('closeTeacherContactModalActionBtn');

const teacherContactName = document.getElementById('teacherContactName');
const teacherContactEmail = document.getElementById('teacherContactEmail');
const teacherContactPhone = document.getElementById('teacherContactPhone');
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

function emptyContactLabel(value) {
    return String(value || '').trim() || 'No registrado';
}

function contactHref(type, value) {
    const cleanedValue = String(value || '').trim();

    if (!cleanedValue) return '#';

    if (type === 'email') {
        return `mailto:${cleanedValue}`;
    }

    return `tel:${cleanedValue.replace(/[^+\d]/g, '')}`;
}

function openTeacherContactModal({ name, email, phone }) {
    teacherContactName.textContent = emptyContactLabel(name);

    teacherContactEmail.textContent = emptyContactLabel(email);
    teacherContactEmail.href = contactHref('email', email);

    teacherContactPhone.textContent = emptyContactLabel(phone);
    teacherContactPhone.href = contactHref('phone', phone);

    teacherContactModal.classList.remove('hidden');
}

function closeTeacherContactModal() {
    teacherContactModal.classList.add('hidden');
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
            <td>
                <button
                    type="button"
                    class="teacher-contact-button"
                    data-action="teacher-contact"
                    data-name="${escapeHtml(m.teacher_name)}"
                    data-email="${escapeHtml(m.teacher_email)}"
                    data-phone="${escapeHtml(m.teacher_phone)}"
                >
                    ${escapeHtml(m.teacher_name)}
                </button>
            </td>

            <td>${escapeHtml(m.student_name)}</td>

            <td>${escapeHtml(m.student_course || '-')}</td>

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

tableBody.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action]');

    if (!button) return;

    if (button.dataset.action === 'teacher-contact') {
        openTeacherContactModal({
            name: button.dataset.name,
            email: button.dataset.email,
            phone: button.dataset.phone
        });
    }
});

closeTeacherContactModalBtn?.addEventListener('click', closeTeacherContactModal);

closeTeacherContactModalActionBtn?.addEventListener(
    'click',
    closeTeacherContactModal
);

window.addEventListener('storage', (event) => {
    if (event.key === MEETINGS_REFRESH_EVENT_KEY) {
        loadMeetings(false);
    }
});

setInterval(() => loadMeetings(false), MEETINGS_REFRESH_INTERVAL_MS);

loadMeetings();
