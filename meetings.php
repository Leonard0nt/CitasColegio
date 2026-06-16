<?php
require_once __DIR__ . '/includes/session.php';
require_roles(['admin', 'profesor']);
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reuniones</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header class="topbar">
        <div class="container topbar-content">
            <div class="brand small">
                <div class="brand-logo">R</div>
                <div>
                    <h1>Reuniones</h1>
                    <p>Profesor - apoderado</p>
                </div>
            </div>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="meetings.php">Reuniones</a>
                <?php if ($isAdmin): ?>
                    <a href="users.php">Profesores</a>
                    <a href="students.php">Alumnos</a>
                <?php endif; ?>
                <a href="profile.php">Perfil</a>
                <a class="btn btn-danger" href="backend/auth/logout.php">Salir</a>
            </nav>
        </div>
    </header>

    <main class="container page">
        <div id="alert" class="alert hidden"></div>

        <section class="panel">
            <div class="section-header">
                <div>
                    <h2>Registros de citas</h2>
                    <p>Administra y consulta las citas entre profesor y apoderado.</p>
                    <p><a href="today-meetings.php" target="_blank">Abrir vista pública de reuniones del día</a></p>
                </div>
                <button class="btn btn-primary" id="openCreateMeetingBtn">Nueva reunión</button>
            </div>


            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Profesor</th>
                            <th>Alumno</th>
                            <th>Curso</th>
                            <th>Apoderado</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="meetingsTableBody">
                        <tr><td colspan="7">Cargando reuniones...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <div class="modal hidden" id="meetingModal">
        <div class="modal-card modal-card-wide">
            <div class="section-header">
                <h2 id="meetingModalTitle">Nueva reunión</h2>
                <button class="icon-btn" id="closeMeetingModalBtn">×</button>
            </div>

            <form id="meetingForm" class="form">
                <?php if ($isAdmin): ?>
                    <label>
                        Profesor
                        <select name="teacher_id" id="teacher_id" required></select>
                    </label>
                <?php else: ?>
                    <input type="hidden" name="teacher_id" id="teacher_id" value="<?= (int) ($_SESSION['user_id'] ?? 0) ?>">
                    <div class="info-box">
                        Profesor: <strong><?= e($_SESSION['user_name']) ?></strong>
                    </div>
                <?php endif; ?>

                <div class="form-grid">
                    <label>
                        Curso
                        <select name="student_course" id="student_course" required>
                            <option value="">Seleccione un curso</option>
                        </select>
                    </label>

                    <label>
                        Alumno
                        <select name="student_id" id="student_id" required disabled>
                            <option value="">Primero seleccione un curso</option>
                        </select>
                    </label>

                    <label>
                        Apoderado
                        <select name="guardian_type" id="guardian_type" required>
                            <option value="titular">Apoderado titular</option>
                            <option value="suplente">Apoderado suplente</option>
                        </select>
                    </label>

                    <label>
                        Fecha
                        <input type="date" name="meeting_date" id="meeting_date" required>
                    </label>

                    <label>
                        Hora
                        <input type="time" name="meeting_time" id="meeting_time" required>
                    </label>
                </div>

                <label>
                    Observación / motivo
                    <textarea name="notes" id="notes" rows="4" placeholder="Motivo de la reunión o comentario opcional."></textarea>
                </label>

                <div class="modal-actions">
                    <button type="button" class="btn" id="cancelMeetingBtn">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal hidden" id="teacherContactModal">
        <div class="modal-card teacher-contact-card">
            <div class="section-header">
                <div>
                    <h2>Datos del profesor</h2>
                    <p>Contacto para avisar que llegó el apoderado.</p>
                </div>
                <button class="icon-btn" id="closeTeacherContactModalBtn" aria-label="Cerrar datos del profesor">×</button>
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
                <button type="button" class="btn" id="closeTeacherContactModalActionBtn">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        window.CURRENT_ROLE = <?= json_encode($_SESSION['user_role'] ?? '') ?>;
    </script>
    <script src="assets/js/meetings.js?v=20260608-teacher-contact"></script>
</body>
</html>
