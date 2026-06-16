<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reuniones de hoy</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <main class="container page">
        <section class="panel">
            <div class="section-header">
                <div>
                    <h2>Reuniones del día</h2>
                    <p>Vista directa sin inicio de sesión.</p>
                </div>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Profesor</th>
                            <th>Alumno</th>
                            <th>Curso</th>
                            <th>Apoderado</th>
                            <th>Hora</th>
                        </tr>
                    </thead>
                    <tbody id="todayMeetingsBody">
                        <tr><td colspan="5">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
<div class="modal hidden" id="teacherContactModal">
    <div class="modal-card teacher-contact-card">
        <div class="section-header">
            <div>
                <h2>Datos del profesor</h2>
                <p>Contacto del profesor.</p>
            </div>
            <button class="icon-btn" id="closeTeacherContactModalBtn">×</button>
        </div>

        <div class="contact-details">
            <div class="contact-detail contact-detail-full">
                <span>Profesor</span>
                <strong id="teacherContactName">-</strong>
            </div>

            <div class="contact-detail">
                <span>Correo</span>
                <a id="teacherContactEmail" href="#">No registrado</a>
            </div>

            <div class="contact-detail">
                <span>Teléfono</span>
                <a id="teacherContactPhone" href="#">No registrado</a>
            </div>
        </div>

        <div class="modal-actions">
            <button type="button" class="btn" id="closeTeacherContactModalActionBtn">
                Cerrar
            </button>
        </div>
    </div>
</div>
    <script src="assets/js/today-meetings.js"></script>
</body>
</html>