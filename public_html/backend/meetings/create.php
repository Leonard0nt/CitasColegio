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
$studentCourse = trim($_POST['student_course'] ?? '');
$guardianType = $_POST['guardian_type'] ?? 'titular';
$meetingDate = trim($_POST['meeting_date'] ?? '');
$meetingTime = trim($_POST['meeting_time'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($teacherId <= 0 || $studentId <= 0 || $studentCourse === '' || $meetingDate === '' || $meetingTime === '') {
    echo json_encode(['success' => false, 'message' => 'Profesor, curso, alumno, fecha y hora son obligatorios.']);
    exit;
}

if (!in_array($guardianType, ['titular', 'suplente'], true)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de apoderado inválido.']);
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
            u.course AS student_course,
            sg.guardian_name,
            sg.guardian_email,
            sg.guardian_phone,
            sg.backup_guardian_name,
            sg.backup_guardian_email,
            sg.backup_guardian_phone
        FROM students u
        INNER JOIN student_guardians sg ON sg.student_id = u.id
        WHERE u.id = :id AND u.active = 1
        LIMIT 1
    ");
    $stmtStudent->execute([':id' => $studentId]);
    $student = $stmtStudent->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Alumno inválido o sin apoderado.']);
        exit;
    }

    if (trim((string) $student['student_course']) !== $studentCourse) {
        echo json_encode(['success' => false, 'message' => 'El alumno no pertenece al curso seleccionado.']);
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

    $stmtConflict = $pdo->prepare(
        "
        SELECT
            m.id,
            t.id AS current_teacher_id,
            s.id AS current_student_id
        FROM meetings m
        LEFT JOIN users t ON t.id = m.teacher_id
        LEFT JOIN students s ON s.id = m.student_id
        WHERE m.teacher_id = :teacher_id
          AND m.meeting_date = :meeting_date
          AND m.meeting_time = :meeting_time
        LIMIT 1
        "
    );
    $stmtConflict->execute([
        ':teacher_id' => $teacherId,
        ':meeting_date' => $meetingDate,
        ':meeting_time' => $meetingTime,
    ]);
    $conflict = $stmtConflict->fetch();

    if ($conflict) {
        $hasMissingReference = empty($conflict['current_teacher_id']) || empty($conflict['current_student_id']);

        if ($hasMissingReference) {
            $stmtDeleteStale = $pdo->prepare('DELETE FROM meetings WHERE id = :id LIMIT 1');
            $stmtDeleteStale->execute([':id' => (int) $conflict['id']]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Ya existe una reunión para este profesor en la misma fecha y hora.',
            ]);
            exit;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO meetings (
            teacher_id, student_id, guardian_type, guardian_name, guardian_email, guardian_phone,
            meeting_date, meeting_time, notes
        ) VALUES (
            :teacher_id, :student_id, :guardian_type, :guardian_name, :guardian_email, :guardian_phone,
            :meeting_date, :meeting_time, :notes
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
        ':notes' => $notes,
    ]);

    echo json_encode(['success' => true, 'message' => 'Reunión agendada correctamente.']);
} catch (PDOException $e) {
    if ((string) $e->getCode() === '23000') {
        echo json_encode([
            'success' => false,
            'message' => 'No se puede agendar: ya existe una reunión para este profesor en la misma fecha y hora.',
        ]);
        exit;
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al agendar reunión.']);
}
