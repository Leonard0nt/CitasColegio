<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

require_once __DIR__ . '/../../includes/config-path.php';

$stmt = $pdo->query("
    SELECT
        u.id,
        u.name,
        u.email,
        u.role,
        u.active,
        u.created_at,

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
    ORDER BY u.id DESC
");

$users = $stmt->fetchAll();

echo json_encode(['success' => true, 'users' => $users]);
