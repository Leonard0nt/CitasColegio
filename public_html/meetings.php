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
    <title>Reuniones | Backend Users PHP</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header class="topbar">
        <div class="container topbar-content">
            <div class="brand small">
                <div class="brand-logo">R</div>
                <div>
                    <h1>Registros de citas</h1>
                    <p>Profesor - apoderado</p>
                </div>
            </div>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="meetings.php">Registros de citas</a>
                <?php if ($isAdmin): ?>
                    <a href="users.php">Usuarios</a>
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
                <?php if ($isAdmin): ?>
                    <button class="btn btn-primary" id="openCreateMeetingBtn">Nueva reunión</button>
                <?php else: ?>
                    <span class="muted">Solo lectura para profesores</span>
                <?php endif; ?>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Profesor</th>
                            <th>Alumno</th>
                            <th>Apoderado</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="meetingsTableBody">
                        <tr><td colspan="8">Cargando reuniones...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($isAdmin): ?>
            <section class="panel">
                <h3>PIN de confirmación de asistencia</h3>
                <p class="muted">Define el PIN de 4 números usado en la vista pública para confirmar asistencia.</p>
                <form id="attendancePinForm" class="form-inline">
                    <input type="password" name="pin" id="attendancePin" maxlength="4" pattern="\d{4}" inputmode="numeric" placeholder="1234" required>
                    <button type="submit" class="btn btn-primary">Guardar PIN</button>
                </form>
            </section>
        <?php endif; ?>
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
                    <div class="info-box">
                        Profesor: <strong><?= e($_SESSION['user_name']) ?></strong>
                    </div>
                <?php endif; ?>

                <div class="form-grid">
                    <label>
                        Alumno
                        <select name="student_id" id="student_id" required></select>
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

                    <input type="hidden" name="status" id="status" value="por_atender">
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

    <script>
        window.CURRENT_ROLE = <?= json_encode($_SESSION['user_role'] ?? '') ?>;
    </script>
    <script src="assets/js/meetings.js"></script>
</body>
</html>
