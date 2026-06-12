<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/config-path.php';
require_once __DIR__ . '/../users/teacher-profile-helpers.php';

$identifier = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($identifier === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Usuario/correo y contraseña son obligatorios.']);
    exit;
}

$normalizedRut = strtolower(preg_replace('/[^0-9kK]+/', '', $identifier));

try {
    ensure_teacher_profiles_table($pdo);

    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.password, u.role, u.active
        FROM users u
        LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
        WHERE u.role IN ('admin', 'profesor')
            AND (
                u.email = :identifier
                OR LOWER(REPLACE(REPLACE(REPLACE(tp.rut, '.', ''), '-', ''), ' ', '')) = :rut
            )
        LIMIT 1
    ");
    $stmt->execute([
        ':identifier' => $identifier,
        ':rut' => $normalizedRut,
    ]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Login database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno al iniciar sesión. Revisa la conexión y la estructura de la base de datos.',
    ]);
    exit;
}

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas.']);
    exit;
}

if ((int) $user['active'] !== 1) {
    echo json_encode(['success' => false, 'message' => 'Tu usuario está desactivado.']);
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];

echo json_encode([
    'success' => true,
    'message' => 'Inicio de sesión correcto.',
    'redirect' => 'dashboard.php',
]);
