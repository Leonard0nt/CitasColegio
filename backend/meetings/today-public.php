<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/config-path.php';

try {
    $stmt = $pdo->prepare("
        SELECT
            m.id,
            t.name AS teacher_name,
            t.email AS teacher_email,
            tp.phone AS teacher_phone,
            s.name AS student_name,
            s.course AS student_course,
            m.guardian_name,
            m.meeting_time
        FROM meetings m
        LEFT JOIN users t ON t.id = m.teacher_id
        LEFT JOIN teacher_profiles tp ON tp.user_id = t.id
        LEFT JOIN students s ON s.id = m.student_id
        WHERE m.meeting_date = CURDATE()
        ORDER BY m.meeting_time ASC
    ");
    $stmt->execute();

    echo json_encode(['success' => true, 'meetings' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener reuniones del día.']);
}
