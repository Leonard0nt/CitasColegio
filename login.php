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
    <link rel="stylesheet" href="assets/css/styles.css?v=<?= filemtime(__DIR__ . '/assets/css/styles.css') ?>">
</head>
<body class="auth-body">
    <main class="auth-card">
        <div class="brand">
            <img src="https://csfchillan.cl/images/0/11526939/HojaA4DiaDelNinoIlustradaAmarilla1.png" alt="Logo" class="logo">
            <div>
                <h1>Iniciar sesión</h1>
                <p>Sistema de registro de reuniones o citas</p>
            </div>
        </div>

        <div id="alert" class="alert hidden"></div>

        <form id="loginForm" class="form">
            <label>
                Rut
                <input type="text" name="email" placeholder="" required>
            </label>

            <div class="form-field">
                <label for="loginPassword">Contraseña</label>
                <div class="password-field">
                    <input id="loginPassword" type="password" name="password" placeholder="" required>
                    <button
                        type="button"
                        class="password-toggle"
                        data-password-toggle="loginPassword"
                        aria-label="Mostrar contraseña"
                        aria-pressed="false"
                    >
                        Mostrar
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
    </main>

    <script src="assets/js/auth.js?v=<?= filemtime(__DIR__ . '/assets/js/auth.js') ?>"></script>
</body>
</html>
