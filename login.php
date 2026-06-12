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
                <p>Sistema de registro de reuniones o citas</p>
            </div>
        </div>

        <div id="alert" class="alert hidden"></div>

        <form id="loginForm" class="form">
            <label>
                Rut
                <input type="text" name="email" placeholder="202677541" required>
            </label>

            <label>
                Contraseña
                <input type="password" name="password" placeholder="" required>
            </label>

            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
    </main>

    <script src="assets/js/auth.js"></script>
</body>
</html>
