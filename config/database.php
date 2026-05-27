<?php

// IMPORTANTE EN CPANEL:
// Idealmente esta carpeta config debe quedar FUERA de public_html.
// Ejemplo: /home/usuario_cpanel/config/database.php

$host = 'localhost';
$dbname = 'TU_USUARIO_CPANEL_TU_BASE_DE_DATOS';
$username = 'TU_USUARIO_CPANEL_TU_USUARIO_MYSQL';
$password = 'TU_PASSWORD_MYSQL';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Error de conexión a la base de datos.');
}
