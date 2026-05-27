<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/config-path.php';

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';
$guardianName = trim($_POST['guardian_name'] ?? '');

if ($name === '' || $email === '' || $password === '' || $passwordConfirm === '' || $guardianName === '') {
    echo json_encode(['success' => false, 'message' => 'Nombre, correo, contraseña y apoderado titular son obligatorios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'El correo no es válido.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener mínimo 8 caracteres.']);
    exit;
}

if ($password !== $passwordConfirm) {
    echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('
        INSERT INTO users (name, email, password, role, active)
        VALUES (:name, :email, :password, :role, 1)
    ');

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $hash,
        ':role' => 'alumno',
    ]);

    $studentId = (int) $pdo->lastInsertId();

    $stmtGuardian = $pdo->prepare('
        INSERT INTO student_guardians (
            student_id,
            guardian_name,
            guardian_rut,
            guardian_phone,
            guardian_email,
            guardian_relationship,
            backup_guardian_name,
            backup_guardian_rut,
            backup_guardian_phone,
            backup_guardian_email,
            backup_guardian_relationship
        ) VALUES (
            :student_id,
            :guardian_name,
            :guardian_rut,
            :guardian_phone,
            :guardian_email,
            :guardian_relationship,
            :backup_guardian_name,
            :backup_guardian_rut,
            :backup_guardian_phone,
            :backup_guardian_email,
            :backup_guardian_relationship
        )
    ');

    $stmtGuardian->execute([
        ':student_id' => $studentId,
        ':guardian_name' => $guardianName,
        ':guardian_rut' => trim($_POST['guardian_rut'] ?? ''),
        ':guardian_phone' => trim($_POST['guardian_phone'] ?? ''),
        ':guardian_email' => trim($_POST['guardian_email'] ?? ''),
        ':guardian_relationship' => trim($_POST['guardian_relationship'] ?? ''),
        ':backup_guardian_name' => trim($_POST['backup_guardian_name'] ?? ''),
        ':backup_guardian_rut' => trim($_POST['backup_guardian_rut'] ?? ''),
        ':backup_guardian_phone' => trim($_POST['backup_guardian_phone'] ?? ''),
        ':backup_guardian_email' => trim($_POST['backup_guardian_email'] ?? ''),
        ':backup_guardian_relationship' => trim($_POST['backup_guardian_relationship'] ?? ''),
    ]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Alumno registrado correctamente.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e->getCode() === '23000') {
        echo json_encode(['success' => false, 'message' => 'Ese correo ya está registrado.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar usuario.']);
    }
}
