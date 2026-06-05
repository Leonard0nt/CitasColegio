<?php
require_once __DIR__ . '/includes/session.php';
require_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header class="topbar">
        <div class="container topbar-content">
            <div class="brand small">
                <div class="brand-logo">P</div>
                <div>
                    <h1>Panel</h1>
                    <p>Sistema de registro de reuniones</p>
                </div>
            </div>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <?php if (in_array($_SESSION['user_role'] ?? '', ['admin', 'profesor'], true)): ?>
                    <a href="meetings.php">Reuniones</a>
                <?php endif; ?>
                <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                    <a href="users.php">Profesores</a>
                    <a href="students.php">Alumnos</a>
                <?php endif; ?>
                <a href="profile.php">Perfil</a>
                <a class="btn btn-danger" href="backend/auth/logout.php">Salir</a>
            </nav>
        </div>
    </header>

    <main class="container page">
        <section class="panel hero-panel">
            <h2>Bienvenido, <?= e($_SESSION['user_name']) ?></h2>
            <p>Tu sesión está activa correctamente.</p>

            <div class="stats-grid">
                <div class="stat-card">
                    <span>Correo</span>
                    <strong><?= e($_SESSION['user_email']) ?></strong>
                </div>
                <div class="stat-card">
                    <span>Rol</span>
                    <strong><?= e($_SESSION['user_role']) ?></strong>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
