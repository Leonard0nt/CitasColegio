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


    $sql = "
        SELECT
            m.id,
            m.teacher_id,
            t.name AS teacher_name,
            m.student_id,
            s.name AS student_name,
            m.guardian_type,
            m.guardian_name,
            m.guardian_email,
            m.guardian_phone,
            m.meeting_date,
            m.meeting_time,
            m.notes,
            m.created_at
        FROM meetings m
        INNER JOIN users t ON t.id = m.teacher_id
        INNER JOIN students s ON s.id = m.student_id
    ";

    $params = [];
    $conditions = [];

    if (($_SESSION['user_role'] ?? '') === 'profesor') {
        $conditions[] = 'm.teacher_id = :teacher_id';
        $params[':teacher_id'] = (int) $_SESSION['user_id'];
    }


    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= " ORDER BY m.meeting_date ASC, m.meeting_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'meetings' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al listar reuniones.']);
}
