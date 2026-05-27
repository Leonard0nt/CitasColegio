<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/config-path.php';

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$pin = trim((string) ($_POST['pin'] ?? ''));

if ($id <= 0 || !preg_match('/^\d{4}$/', $pin)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

try {
    $pinStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_pin' LIMIT 1");
    $savedPin = $pinStmt->fetchColumn();

    if (!$savedPin || !hash_equals((string) $savedPin, $pin)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'PIN incorrecto.']);
        exit;
    }

    $stmt = $pdo->prepare("\n        UPDATE meetings\n        SET status = 'atendido'\n        WHERE id = :id\n          AND meeting_date = CURDATE()\n    ");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No se encontró reunión del día para confirmar.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Asistencia confirmada.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al confirmar asistencia.']);
}