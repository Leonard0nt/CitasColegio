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

$id = (int) ($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'alumno';
$active = isset($_POST['active']) ? (int) $_POST['active'] : 1;
$password = $_POST['password'] ?? '';

if ($id <= 0 || $name === '') {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
}

if ($role !== 'alumno' && ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
    echo json_encode(['success' => false, 'message' => 'Correo inválido.']);
    exit;
}

if (!in_array($role, ['admin', 'profesor', 'alumno'], true)) {
    echo json_encode(['success' => false, 'message' => 'Rol inválido.']);
    exit;
}

if ($role === 'alumno') {
    if ($id <= 0 || $name === '') {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
        exit;
    }

    $guardianName = trim($_POST['guardian_name'] ?? '');
    if ($guardianName === '') {
        echo json_encode(['success' => false, 'message' => 'El apoderado titular es obligatorio para alumnos.']);
        exit;
    }

    try {
        ensure_students_table($pdo);

        $pdo->beginTransaction();
        save_student_record(
            $pdo,
            $id,
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
            ON DUPLICATE KEY UPDATE
                guardian_name = VALUES(guardian_name),
                guardian_rut = VALUES(guardian_rut),
                guardian_phone = VALUES(guardian_phone),
                guardian_email = VALUES(guardian_email),
                guardian_relationship = VALUES(guardian_relationship),
                backup_guardian_name = VALUES(backup_guardian_name),
                backup_guardian_rut = VALUES(backup_guardian_rut),
                backup_guardian_phone = VALUES(backup_guardian_phone),
                backup_guardian_email = VALUES(backup_guardian_email),
                backup_guardian_relationship = VALUES(backup_guardian_relationship)
        ');

        $stmtGuardian->execute([
            ':student_id' => $id,
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
        echo json_encode(['success' => true, 'message' => 'Alumno actualizado como referencia correctamente.']);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode([
            'success' => false,
            'message' => $e->getCode() === '23000' ? 'El RUT ya existe.' : 'Error al actualizar alumno.'
        ]);
    }
    exit;
}

try {
    ensure_teacher_profiles_table($pdo);
    ensure_student_profiles_table($pdo);

    $pdo->beginTransaction();

    if ($password !== '') {
        if (strlen($password) < 8) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'La contraseña debe tener mínimo 8 caracteres.']);
            exit;
        }

        $stmt = $pdo->prepare('
            UPDATE users
            SET name = :name, email = :email, password = :password, role = :role, active = :active
            WHERE id = :id
        ');

        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':email' => $email,
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':role' => $role,
            ':active' => $active === 1 ? 1 : 0,
        ]);
    } else {
        $stmt = $pdo->prepare('
            UPDATE users
            SET name = :name, email = :email, role = :role, active = :active
            WHERE id = :id
        ');

        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':email' => $email,
            ':role' => $role,
            ':active' => $active === 1 ? 1 : 0,
        ]);
    }

    if ($role === 'profesor') {
        save_teacher_profile(
            $pdo,
            $id,
            $_POST['teacher_cost_center'] ?? '',
            $_POST['teacher_rut'] ?? '',
            $_POST['teacher_phone'] ?? ''
        );
    } else {
        delete_teacher_profile($pdo, $id);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getCode() === '23000' ? 'El correo o RUT ya existe.' : 'Error al actualizar usuario.'
    ]);
}
