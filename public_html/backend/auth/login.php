<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/config-path.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Correo y contraseña son obligatorios.']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, name, email, password, role, active FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
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
