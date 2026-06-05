<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/config-path.php';
require_once __DIR__ . '/../users/teacher-profile-helpers.php';

ensure_teacher_profiles_table($pdo);

$identifier = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($identifier === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Usuario/correo y contraseña son obligatorios.']);
    exit;
}

$normalizedRut = strtolower(preg_replace('/[^0-9kK]+/', '', $identifier));

$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, u.password, u.role, u.active
    FROM users u
    LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
    WHERE u.email = :identifier
        OR LOWER(REPLACE(REPLACE(REPLACE(tp.rut, '.', ''), '-', ''), ' ', '')) = :rut
    LIMIT 1
");
$stmt->execute([
    ':identifier' => $identifier,
    ':rut' => $normalizedRut,
]);
$user = $stmt->fetch();

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
