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
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-body">
    <main class="auth-card">
        <div class="brand">
            <div class="brand-logo">U</div>
            <div>
                <h1>Iniciar sesión</h1>
                <p>Sistema de registro de reuniones</p>
            </div>
        </div>

        <div id="alert" class="alert hidden"></div>

        <form id="loginForm" class="form">
            <label>
                Usuario o correo
                <input type="text" name="email" placeholder="admin@demo.cl o 202677541" required>
            </label>

            <label>
                Contraseña
                <input type="password" name="password" placeholder="Admin12345" required>
            </label>

            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
        <p class="demo-box">Demo admin: <strong>admin@demo.cl</strong> / <strong>Admin12345</strong>. Profesores CSV: RUT sin puntos ni guion / últimos 4 dígitos antes del verificador.</p>
    </main>

    <script src="assets/js/auth.js"></script>
</body>
</html>
