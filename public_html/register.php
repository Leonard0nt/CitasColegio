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
    <title>Registro</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-body">
    <main class="auth-card auth-card-wide">
        <div class="brand">
            <div class="brand-logo">U</div>
            <div>
                <h1>Crear cuenta alumno</h1>
                <p>Registro con apoderado titular y suplente</p>
            </div>
        </div>

        <div id="alert" class="alert hidden"></div>

        <form id="registerForm" class="form">
            <div class="form-grid">
                <label>
                    Nombre alumno
                    <input type="text" name="name" placeholder="Nombre del alumno" required>
                </label>

                <label>
                    Correo alumno
                    <input type="email" name="email" placeholder="correo@dominio.cl" required>
                </label>

                <label>
                    Contraseña
                    <input type="password" name="password" placeholder="Mínimo 8 caracteres" required>
                </label>

                <label>
                    Confirmar contraseña
                    <input type="password" name="password_confirm" placeholder="Repite tu contraseña" required>
                </label>
            </div>

            <div class="guardian-box">
                <h3>Datos del apoderado titular</h3>

                <div class="form-grid">
                    <label>
                        Nombre apoderado
                        <input type="text" name="guardian_name" required>
                    </label>

                    <label>
                        RUT apoderado
                        <input type="text" name="guardian_rut" placeholder="12.345.678-9">
                    </label>

                    <label>
                        Teléfono apoderado
                        <input type="text" name="guardian_phone" placeholder="+56 9 1234 5678">
                    </label>

                    <label>
                        Correo apoderado
                        <input type="email" name="guardian_email" placeholder="apoderado@dominio.cl">
                    </label>

                    <label>
                        Parentesco
                        <input type="text" name="guardian_relationship" placeholder="Madre, padre, tío, etc.">
                    </label>
                </div>

                <h3>Datos del apoderado suplente</h3>

                <div class="form-grid">
                    <label>
                        Nombre suplente
                        <input type="text" name="backup_guardian_name">
                    </label>

                    <label>
                        RUT suplente
                        <input type="text" name="backup_guardian_rut" placeholder="12.345.678-9">
                    </label>

                    <label>
                        Teléfono suplente
                        <input type="text" name="backup_guardian_phone" placeholder="+56 9 1234 5678">
                    </label>

                    <label>
                        Correo suplente
                        <input type="email" name="backup_guardian_email" placeholder="suplente@dominio.cl">
                    </label>

                    <label>
                        Parentesco suplente
                        <input type="text" name="backup_guardian_relationship" placeholder="Abuela, hermano, vecino, etc.">
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Registrarme</button>
        </form>

        <p class="muted center">¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a></p>
    </main>

    <script src="assets/js/auth.js"></script>
</body>
</html>
