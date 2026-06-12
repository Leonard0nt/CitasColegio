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
require_once __DIR__ . '/encoding-helpers.php';

ensure_teacher_profiles_table($pdo);

$role = $_GET['role'] ?? '';
$allowedRoles = ['admin', 'profesor', 'alumno'];

if ($role !== '' && !in_array($role, $allowedRoles, true)) {
    echo json_encode(['success' => false, 'message' => 'Rol inválido.']);
    exit;
}

if ($role === 'alumno') {
    ensure_student_guardians_table($pdo);

    $stmt = $pdo->query("
        SELECT
            s.id,
            s.name,
            '' AS email,
            'alumno' AS role,
            s.active,
            s.created_at,

            NULL AS teacher_cost_center,
            NULL AS teacher_rut,
            NULL AS teacher_phone,

            s.course AS student_course,
            s.rut AS student_rut,

            sg.guardian_name,
            sg.guardian_rut,
            sg.guardian_phone,
            sg.guardian_email,
            sg.guardian_relationship,

            sg.backup_guardian_name,
            sg.backup_guardian_rut,
            sg.backup_guardian_phone,
            sg.backup_guardian_email,
            sg.backup_guardian_relationship
        FROM students s
        LEFT JOIN (
            SELECT sg.*
            FROM student_guardians sg
            INNER JOIN (
                SELECT student_id, MAX(id) AS id
                FROM student_guardians
                GROUP BY student_id
            ) latest_sg ON latest_sg.id = sg.id
        ) sg ON sg.student_id = s.id
        ORDER BY s.id DESC
    ");

    $users = $stmt->fetchAll();
} else {
    ensure_students_table($pdo);

    $whereSql = '';
    $params = [];

    if ($role !== '') {
        $whereSql = 'WHERE u.role = :role';
        $params[':role'] = $role;
    } else {
        $whereSql = "WHERE u.role IN ('admin', 'profesor')";
    }

    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.name,
            u.email,
            u.role,
            u.active,
            u.created_at,

            tp.cost_center AS teacher_cost_center,
            tp.rut AS teacher_rut,
            tp.phone AS teacher_phone,

            NULL AS student_course,
            NULL AS student_rut,

            NULL AS guardian_name,
            NULL AS guardian_rut,
            NULL AS guardian_phone,
            NULL AS guardian_email,
            NULL AS guardian_relationship,

            NULL AS backup_guardian_name,
            NULL AS backup_guardian_rut,
            NULL AS backup_guardian_phone,
            NULL AS backup_guardian_email,
            NULL AS backup_guardian_relationship
        FROM users u
        LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
        $whereSql
        ORDER BY u.id DESC
    ");

    $stmt->execute($params);
    $users = $stmt->fetchAll();
}

$users = array_map(static function (array $user): array {
    foreach (['name', 'email', 'role', 'teacher_cost_center', 'teacher_rut', 'teacher_phone', 'student_course', 'student_rut', 'guardian_name', 'guardian_rut', 'guardian_phone', 'guardian_email', 'backup_guardian_name', 'backup_guardian_rut', 'backup_guardian_phone', 'backup_guardian_email'] as $key) {
        if (isset($user[$key])) {
            $user[$key] = repair_text_encoding((string) $user[$key]);
        }
    }

    return $user;
}, $users);

echo json_encode(['success' => true, 'users' => $users]);
