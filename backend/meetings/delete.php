<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'profesor'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

require_once __DIR__ . '/../../includes/config-path.php';

$id = (int) ($_POST['id'] ?? 0);
$loggedRole = $_SESSION['user_role'] ?? '';
$loggedUserId = (int) ($_SESSION['user_id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de reunión inválido.']);
    exit;
}

try {
    $sql = 'DELETE FROM meetings WHERE id = :id';
    $params = [':id' => $id];

    if ($loggedRole === 'profesor') {
        $sql .= ' AND teacher_id = :teacher_id';
        $params[':teacher_id'] = $loggedUserId;
    }

    $sql .= ' LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró la reunión o no tienes permiso para eliminarla.',
        ]);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Reunión eliminada correctamente.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al eliminar reunión.']);
}
