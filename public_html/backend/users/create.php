<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

require_once __DIR__ . '/../../includes/config-path.php';

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'alumno';
$active = isset($_POST['active']) ? (int) $_POST['active'] : 1;

if ($name === '' || $email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Nombre, correo y contraseña son obligatorios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Correo inválido.']);
    exit;
}

if (!in_array($role, ['admin', 'profesor', 'alumno'], true)) {
    echo json_encode(['success' => false, 'message' => 'Rol inválido.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener mínimo 8 caracteres.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        INSERT INTO users (name, email, password, role, active)
        VALUES (:name, :email, :password, :role, :active)
    ');

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => $role,
        ':active' => $active === 1 ? 1 : 0,
    ]);

    $userId = (int) $pdo->lastInsertId();

    if ($role === 'alumno') {
        $guardianName = trim($_POST['guardian_name'] ?? '');

        if ($guardianName === '') {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'El apoderado titular es obligatorio para alumnos.']);
            exit;
        }

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
            ':student_id' => $userId,
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
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Usuario creado correctamente.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getCode() === '23000' ? 'El correo ya existe.' : 'Error al crear usuario.'
    ]);
}
