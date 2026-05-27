<?php

// IMPORTANTE EN CPANEL:
// Idealmente esta carpeta config debe quedar FUERA de public_html.
// Ejemplo: /home/usuario_cpanel/config/database.php

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'php_users_backend';
$username = getenv('DB_USER') ?: 'phpuser';
$password = getenv('DB_PASS') ?: '123456';
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
    if (PHP_SAPI === 'cli-server') {
        error_log('DB connection error: ' . $e->getMessage());
    }
}
