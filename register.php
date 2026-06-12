<?php
require_once __DIR__ . '/includes/session.php';
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro deshabilitado</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-body">
    <main class="auth-card auth-card-wide">
        <div class="brand">
            <div class="brand-logo">U</div>
            <div>
                <h1>Registro deshabilitado</h1>
                <p>Los alumnos no poseen cuenta de acceso</p>
            </div>
        </div>

        <div class="alert error">El registro de alumnos está deshabilitado.</div>

        <p class="muted center">Los alumnos no tienen cuenta de acceso ni perfil propio. Solicita a un administrador que cree o importe el alumno para dejarlo disponible en reuniones.</p>

        <p class="muted center"><a href="login.php">Volver al inicio de sesión</a></p>
    </main>

    <script src="assets/js/auth.js"></script>
</body>
</html>
