<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'profesor'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

require_once __DIR__ . '/../../includes/config-path.php';

try {
    if (($_SESSION['user_role'] ?? '') === 'admin') {
        $stmtTeachers = $pdo->query("SELECT id, name, email FROM users WHERE role = 'profesor' AND active = 1 ORDER BY name ASC");
        $teachers = $stmtTeachers->fetchAll();
    } else {
        $teachers = [[
            'id' => (int) $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
        ]];
    }

    $stmtStudents = $pdo->query("
        SELECT
            u.id,
            u.name,
            u.email,
            sg.guardian_name,
            sg.guardian_email,
            sg.guardian_phone,
            sg.backup_guardian_name,
            sg.backup_guardian_email,
            sg.backup_guardian_phone
        FROM users u
        INNER JOIN student_guardians sg ON sg.student_id = u.id
        WHERE u.role = 'alumno' AND u.active = 1
        ORDER BY u.name ASC
    ");

    echo json_encode([
        'success' => true,
        'teachers' => $teachers,
        'students' => $stmtStudents->fetchAll(),
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al cargar opciones.']);
}
