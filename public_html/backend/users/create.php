<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

require_once __DIR__ . '/../../includes/config-path.php';
require_once __DIR__ . '/teacher-profile-helpers.php';
require_once __DIR__ . '/student-profile-helpers.php';

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'profesor';
$active = isset($_POST['active']) ? (int) $_POST['active'] : 1;

if (!in_array($role, ['admin', 'profesor', 'alumno'], true)) {
    echo json_encode(['success' => false, 'message' => 'Rol inválido.']);
    exit;
}

if ($role === 'alumno') {
    if ($name === '') {
        echo json_encode(['success' => false, 'message' => 'Nombre es obligatorio.']);
        exit;
    }

    $guardianName = trim($_POST['guardian_name'] ?? '');
    if ($guardianName === '') {
        echo json_encode(['success' => false, 'message' => 'El apoderado titular es obligatorio para alumnos.']);
        exit;
    }

    try {
        ensure_student_guardians_table($pdo);

        $pdo->beginTransaction();
        $studentId = save_student_record(
            $pdo,
            null,
            $name,
            $active,
            $_POST['student_course'] ?? '',
            $_POST['student_rut'] ?? ''
        );

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
        echo json_encode(['success' => true, 'message' => 'Alumno creado como referencia correctamente.']);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode([
            'success' => false,
            'message' => $e->getCode() === '23000' ? 'El RUT ya existe.' : 'Error al crear alumno.'
        ]);
    }
    exit;
}

if ($name === '' || $email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Nombre, correo y contraseña son obligatorios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Correo inválido.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener mínimo 8 caracteres.']);
    exit;
}

try {
    ensure_teacher_profiles_table($pdo);
    ensure_student_profiles_table($pdo);

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

    if ($role === 'profesor') {
        save_teacher_profile(
            $pdo,
            $userId,
            $_POST['teacher_cost_center'] ?? '',
            $_POST['teacher_rut'] ?? '',
            $_POST['teacher_phone'] ?? ''
        );
    } else {
        delete_teacher_profile($pdo, $userId);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Usuario creado correctamente.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getCode() === '23000' ? 'El correo o RUT ya existe.' : 'Error al crear usuario.'
    ]);
}
