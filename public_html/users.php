<?php
require_once __DIR__ . '/includes/session.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profesores</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header class="topbar">
        <div class="container topbar-content">
            <div class="brand small">
                <div class="brand-logo">P</div>
                <div>
                    <h1>Profesores</h1>
                    <p>Administración docente</p>
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
                    <h2>Gestión de profesores</h2>
                    <p>Administra los datos de acceso y atributos docentes de cada profesor.</p>
                </div>
                <div class="section-actions">
                    <button class="btn" id="openUploadBtn">Subir profesores CSV</button>
                    <button class="btn btn-primary" id="openCreateBtn">Nuevo profesor</button>
                </div>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Estado</th>
                            <th>RUT profesor</th>
                            <th>Centro costo</th>
                            <th>Móvil</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr><td colspan="7">Cargando profesores...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div class="modal hidden" id="userModal">
        <div class="modal-card modal-card-wide">
            <div class="section-header">
                <h2 id="modalTitle">Nuevo profesor</h2>
                <button class="icon-btn" id="closeModalBtn">×</button>
            </div>

            <form id="userForm" class="form">
                <input type="hidden" name="id" id="userId">

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
                    <h3>Atributos del profesor</h3>

                    <div class="form-grid">
                        <label>
                            Centro costo
                            <input type="text" name="teacher_cost_center" id="teacher_cost_center" placeholder="Profesores Media">
                        </label>

                        <label>
                            RUT profesor
                            <input type="text" name="teacher_rut" id="teacher_rut" placeholder="12.345.678-9">
                        </label>

                        <label>
                            Móvil
                            <input type="text" name="teacher_phone" id="teacher_phone" placeholder="56912345678">
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

    <div class="modal hidden" id="uploadModal">
        <div class="modal-card">
            <div class="section-header">
                <div>
                    <h2>Subir profesores CSV</h2>
                    <p>Usa columnas como Centro Costo, Rut, Nombre Completo, Móvil/Número y Email. Solo se importan filas con Centro Costo.</p>
                </div>
                <button class="icon-btn" id="closeUploadModalBtn">×</button>
            </div>

            <form id="uploadTeachersForm" class="form">
                <label>
                    Archivo CSV
                    <input type="file" name="teachers_csv" id="teachers_csv" accept=".csv,text/csv" required>
                </label>

                <p class="muted small-text">El usuario de cada profesor será su RUT sin puntos ni guion. La contraseña inicial será los últimos 4 números del RUT antes del verificador; por ejemplo: 20.267.754-1 → usuario 202677541 y clave 7754.</p>
                <p class="muted small-text">Si una fila no trae correo válido, se generará uno interno usando el RUT para permitir crear el perfil. Las filas sin Centro Costo serán omitidas.</p>

                <div id="uploadResult" class="info-box hidden"></div>

                <div class="modal-actions">
                    <button type="button" class="btn" id="cancelUploadBtn">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Subir</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/users.js?v=20260605-id-column"></script>
</body>
</html>
