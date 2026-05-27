<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'profesor'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

require_once __DIR__ . '/../../includes/config-path.php';

$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($id <= 0 || !in_array($status, ['por_atender', 'atendido'], true)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

try {
    $sql = 'UPDATE meetings SET status = :status WHERE id = :id';
    $params = [':status' => $status, ':id' => $id];

    if (($_SESSION['user_role'] ?? '') === 'profesor') {
        $sql .= ' AND teacher_id = :teacher_id';
        $params[':teacher_id'] = (int) $_SESSION['user_id'];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'No se encontró la reunión o no tienes permiso.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar estado.']);
}
