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
    <title>Login | Backend Users PHP</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-body">
    <main class="auth-card">
        <div class="brand">
            <div class="brand-logo">U</div>
            <div>
                <h1>Iniciar sesión</h1>
                <p>Backend de usuarios con PHP puro</p>
            </div>
        </div>

        <div id="alert" class="alert hidden"></div>

        <form id="loginForm" class="form">
            <label>
                Correo
                <input type="email" name="email" placeholder="admin@demo.cl" required>
            </label>

            <label>
                Contraseña
                <input type="password" name="password" placeholder="Admin12345" required>
            </label>

            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>

        <p class="muted center">¿No tienes cuenta? <a href="register.php">Crear cuenta</a></p>
        <p class="demo-box">Demo admin: <strong>admin@demo.cl</strong> / <strong>Admin12345</strong></p>
    </main>

    <script src="assets/js/auth.js"></script>
</body>
</html>
