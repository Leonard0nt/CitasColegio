<?php
require_once __DIR__ . '/includes/session.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumnos</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header class="topbar">
        <div class="container topbar-content">
            <div class="brand small">
                <div class="brand-logo">A</div>
                <div>
                    <h1>Alumnos</h1>
                    <p>Administración de estudiantes</p>
                </div>
            </div>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="meetings.php">Reuniones</a>
                <a href="users.php">Profesores</a>
                <a href="students.php">Alumnos</a>
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
                    <h2>Gestión de alumnos</h2>
                    <p>Crea y administra alumnos con apoderado titular y apoderado suplente.</p>
                </div>
                <button class="btn btn-primary" id="openCreateBtn">Nuevo alumno</button>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Estado</th>
                            <th>Apoderado titular</th>
                            <th>Apoderado suplente</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody">
                        <tr><td colspan="7">Cargando alumnos...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div class="modal hidden" id="studentModal">
        <div class="modal-card modal-card-wide">
            <div class="section-header">
                <h2 id="modalTitle">Nuevo alumno</h2>
                <button class="icon-btn" id="closeModalBtn">×</button>
            </div>

            <form id="studentForm" class="form">
                <input type="hidden" name="id" id="studentId">

                <div class="form-grid">
                    <label>
                        Nombre
                        <input type="text" name="name" id="name" required>
                    </label>

                    <label>
                        Correo
                        <input type="email" name="email" id="email" required>
                    </label>

                    <label>
                        Contraseña
                        <input type="password" name="password" id="password" placeholder="Obligatoria al crear. Opcional al editar.">
                    </label>

                    <label>
                        Estado
                        <select name="active" id="active">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </label>
                </div>

                <div class="guardian-box">
                    <h3>Datos del apoderado titular</h3>

                    <div class="form-grid">
                        <label>
                            Nombre apoderado
                            <input type="text" name="guardian_name" id="guardian_name" required>
                        </label>

                        <label>
                            RUT apoderado
                            <input type="text" name="guardian_rut" id="guardian_rut" placeholder="12.345.678-9">
                        </label>

                        <label>
                            Teléfono apoderado
                            <input type="text" name="guardian_phone" id="guardian_phone" placeholder="+56 9 1234 5678">
                        </label>

                        <label>
                            Correo apoderado
                            <input type="email" name="guardian_email" id="guardian_email" placeholder="apoderado@dominio.cl">
                        </label>

                        <label>
                            Parentesco
                            <input type="text" name="guardian_relationship" id="guardian_relationship" placeholder="Madre, padre, tío, etc.">
                        </label>
                    </div>

                    <h3>Datos del apoderado suplente</h3>

                    <div class="form-grid">
                        <label>
                            Nombre suplente
                            <input type="text" name="backup_guardian_name" id="backup_guardian_name">
                        </label>

                        <label>
                            RUT suplente
                            <input type="text" name="backup_guardian_rut" id="backup_guardian_rut" placeholder="12.345.678-9">
                        </label>

                        <label>
                            Teléfono suplente
                            <input type="text" name="backup_guardian_phone" id="backup_guardian_phone" placeholder="+56 9 1234 5678">
                        </label>

                        <label>
                            Correo suplente
                            <input type="email" name="backup_guardian_email" id="backup_guardian_email" placeholder="suplente@dominio.cl">
                        </label>

                        <label>
                            Parentesco suplente
                            <input type="text" name="backup_guardian_relationship" id="backup_guardian_relationship" placeholder="Abuela, hermano, vecino, etc.">
                        </label>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn" id="cancelBtn">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/students.js"></script>
</body>
</html>
