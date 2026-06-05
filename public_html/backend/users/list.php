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
ensure_student_profiles_table($pdo);

$role = $_GET['role'] ?? '';
$allowedRoles = ['admin', 'profesor', 'alumno'];
$whereSql = '';
$params = [];

if ($role !== '') {
    if (!in_array($role, $allowedRoles, true)) {
        echo json_encode(['success' => false, 'message' => 'Rol inválido.']);
        exit;
    }

    $whereSql = 'WHERE u.role = :role';
    $params[':role'] = $role;
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

        sp.course AS student_course,
        sp.rut AS student_rut,

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
    FROM users u
    LEFT JOIN student_guardians sg ON sg.student_id = u.id
    LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
    LEFT JOIN student_profiles sp ON sp.user_id = u.id
    $whereSql
    ORDER BY u.id DESC
");

$stmt->execute($params);
$users = array_map(static function (array $user): array {
    foreach (['name', 'email', 'role', 'teacher_cost_center', 'teacher_rut', 'teacher_phone', 'student_course', 'student_rut', 'guardian_name', 'guardian_rut', 'guardian_phone', 'guardian_email', 'backup_guardian_name', 'backup_guardian_rut', 'backup_guardian_phone', 'backup_guardian_email'] as $key) {
        if (isset($user[$key])) {
            $user[$key] = repair_text_encoding((string) $user[$key]);
        }
    }

    return $user;
}, $stmt->fetchAll());

echo json_encode(['success' => true, 'users' => $users]);
