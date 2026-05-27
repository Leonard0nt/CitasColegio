<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'profesor'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

require_once __DIR__ . '/../../includes/config-path.php';

$loggedRole = $_SESSION['user_role'] ?? '';
$loggedUserId = (int) ($_SESSION['user_id'] ?? 0);
$teacherId = $loggedRole === 'admin'
    ? (int) ($_POST['teacher_id'] ?? 0)
    : $loggedUserId;
$studentId = (int) ($_POST['student_id'] ?? 0);
$guardianType = $_POST['guardian_type'] ?? 'titular';
$meetingDate = trim($_POST['meeting_date'] ?? '');
$meetingTime = trim($_POST['meeting_time'] ?? '');
$status = $_POST['status'] ?? 'por_atender';
$notes = trim($_POST['notes'] ?? '');

if ($teacherId <= 0 || $studentId <= 0 || $meetingDate === '' || $meetingTime === '') {
    echo json_encode(['success' => false, 'message' => 'Profesor, alumno, fecha y hora son obligatorios.']);
    exit;
}

if (!in_array($guardianType, ['titular', 'suplente'], true)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de apoderado inválido.']);
    exit;
}

if (!in_array($status, ['por_atender', 'atendido'], true)) {
    echo json_encode(['success' => false, 'message' => 'Estado inválido.']);
    exit;
}

try {
    $stmtTeacher = $pdo->prepare("SELECT id FROM users WHERE id = :id AND role = 'profesor' AND active = 1 LIMIT 1");
    $stmtTeacher->execute([':id' => $teacherId]);
    if (!$stmtTeacher->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Profesor inválido o inactivo.']);
        exit;
    }

    $stmtStudent = $pdo->prepare("
        SELECT
            u.id,
            sg.guardian_name,
            sg.guardian_email,
            sg.guardian_phone,
            sg.backup_guardian_name,
            sg.backup_guardian_email,
            sg.backup_guardian_phone
        FROM users u
        INNER JOIN student_guardians sg ON sg.student_id = u.id
        WHERE u.id = :id AND u.role = 'alumno' AND u.active = 1
        LIMIT 1
    ");
    $stmtStudent->execute([':id' => $studentId]);
    $student = $stmtStudent->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Alumno inválido o sin apoderado.']);
        exit;
    }

    if ($guardianType === 'titular') {
        $guardianName = $student['guardian_name'];
        $guardianEmail = $student['guardian_email'];
        $guardianPhone = $student['guardian_phone'];
    } else {
        $guardianName = $student['backup_guardian_name'];
        $guardianEmail = $student['backup_guardian_email'];
        $guardianPhone = $student['backup_guardian_phone'];
    }

    if (trim((string) $guardianName) === '') {
        echo json_encode(['success' => false, 'message' => 'El alumno no tiene ese apoderado registrado.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO meetings (
            teacher_id, student_id, guardian_type, guardian_name, guardian_email, guardian_phone,
            meeting_date, meeting_time, status, notes
        ) VALUES (
            :teacher_id, :student_id, :guardian_type, :guardian_name, :guardian_email, :guardian_phone,
            :meeting_date, :meeting_time, :status, :notes
        )
    ");

    $stmt->execute([
        ':teacher_id' => $teacherId,
        ':student_id' => $studentId,
        ':guardian_type' => $guardianType,
        ':guardian_name' => $guardianName,
        ':guardian_email' => $guardianEmail,
        ':guardian_phone' => $guardianPhone,
        ':meeting_date' => $meetingDate,
        ':meeting_time' => $meetingTime,
        ':status' => $status,
        ':notes' => $notes,
    ]);

    echo json_encode(['success' => true, 'message' => 'Reunión agendada correctamente.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al agendar reunión.']);
}
