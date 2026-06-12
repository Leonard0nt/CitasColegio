<?php

// IMPORTANTE EN CPANEL:
// Idealmente esta carpeta config debe quedar FUERA de public_html.
// Ejemplo: /home/usuario_cpanel/config/database.php

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'csfchill_CitasCsf';
$username = getenv('DB_USER') ?: 'csfchill_CitasUser';
$password = getenv('DB_PASS') ?: 'Csanfernando.2026';
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
    error_log('DB connection error: ' . $e->getMessage());
    http_response_code(500);

    $contentType = '';
    foreach (headers_list() as $header) {
        if (stripos($header, 'Content-Type:') === 0) {
            $contentType = $header;
            break;
        }
    }

    if (stripos($contentType, 'application/json') !== false) {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo completar la solicitud. Inténtalo nuevamente o contacta al administrador.',
        ]);
        exit;
    }

    die('No se pudo completar la solicitud. Inténtalo nuevamente o contacta al administrador.');
}
