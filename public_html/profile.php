<?php
require_once __DIR__ . '/includes/session.php';
require_login();
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil | Backend Users PHP</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header class="topbar">
        <div class="container topbar-content">
            <div class="brand small">
                <div class="brand-logo">P</div>
                <div>
                    <h1>Mi perfil</h1>
                    <p>Configura tus datos de acceso</p>
                </div>
            </div>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <?php if (in_array($_SESSION['user_role'] ?? '', ['admin', 'profesor'], true)): ?>
                    <a href="meetings.php">Reuniones</a>
                <?php endif; ?>
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
                    <h2>Editar perfil</h2>
                    <p>Puedes actualizar tu nombre, correo y contraseña.</p>
                </div>
            </div>

            <form id="profileForm" class="form">
                <div class="form-grid">
                    <label>
                        Nombre
                        <input type="text" name="name" id="name" required value="<?= e($_SESSION['user_name']) ?>">
                    </label>

                    <label>
                        Correo
                        <input type="email" name="email" id="email" required value="<?= e($_SESSION['user_email']) ?>">
                    </label>
                </div>

                <label>
                    Contraseña actual
                    <input type="password" name="current_password" id="current_password" required placeholder="Requerida para guardar cambios">
                </label>

                <div class="form-grid">
                    <label>
                        Nueva contraseña (opcional)
                        <input type="password" name="new_password" id="new_password" placeholder="Mínimo 8 caracteres">
                    </label>

                    <label>
                        Confirmar nueva contraseña
                        <input type="password" name="confirm_new_password" id="confirm_new_password" placeholder="Repite la nueva contraseña">
                    </label>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </section>
    </main>

    <script src="assets/js/profile.js"></script>
</body>
</html>
