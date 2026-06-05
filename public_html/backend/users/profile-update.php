<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'profesor'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

require_once __DIR__ . '/../../includes/config-path.php';

$userId = (int) $_SESSION['user_id'];
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmNewPassword = $_POST['confirm_new_password'] ?? '';

if ($name === '' || $email === '' || $currentPassword === '') {
    echo json_encode(['success' => false, 'message' => 'Completa todos los campos obligatorios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Correo inválido.']);
    exit;
}

if ($newPassword !== '' || $confirmNewPassword !== '') {
    if (strlen($newPassword) < 8) {
        echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener mínimo 8 caracteres.']);
        exit;
    }

    if ($newPassword !== $confirmNewPassword) {
        echo json_encode(['success' => false, 'message' => 'La confirmación de contraseña no coincide.']);
        exit;
    }
}

try {
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'La contraseña actual no es correcta.']);
        exit;
    }

    if ($newPassword !== '') {
        $updateStmt = $pdo->prepare('UPDATE users SET name = :name, email = :email, password = :password WHERE id = :id');
        $updateStmt->execute([
            ':id' => $userId,
            ':name' => $name,
            ':email' => $email,
            ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);
    } else {
        $updateStmt = $pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
        $updateStmt->execute([
            ':id' => $userId,
            ':name' => $name,
            ':email' => $email,
        ]);
    }

    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;

    echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente.']);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getCode() === '23000' ? 'El correo ya está en uso.' : 'No se pudo actualizar el perfil.'
    ]);
}
