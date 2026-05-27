<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

require_once __DIR__ . '/../../includes/config-path.php';

$pin = trim((string) ($_POST['pin'] ?? ''));

if (!preg_match('/^\d{4}$/', $pin)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'El PIN debe tener 4 números.']);
    exit;
}

try {
    $stmt = $pdo->prepare("\n        INSERT INTO system_settings (setting_key, setting_value)\n        VALUES ('attendance_pin', :pin)\n        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)\n    ");
    $stmt->execute([':pin' => $pin]);

    echo json_encode(['success' => true, 'message' => 'PIN de asistencia guardado.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo guardar el PIN.']);
}