<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/config-path.php';

try {
    $stmt = $pdo->prepare("\n        SELECT\n            m.id,\n            t.name AS teacher_name,\n            s.name AS student_name,\n            m.guardian_name,\n            m.meeting_date,\n            m.meeting_time,\n            m.status\n        FROM meetings m\n        INNER JOIN users t ON t.id = m.teacher_id\n        INNER JOIN students s ON s.id = m.student_id\n        WHERE m.meeting_date = CURDATE()\n        ORDER BY m.meeting_time ASC\n    ");
    $stmt->execute();

    echo json_encode(['success' => true, 'meetings' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener reuniones del día.']);
}