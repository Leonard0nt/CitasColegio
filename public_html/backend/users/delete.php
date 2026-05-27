<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

require_once __DIR__ . '/../../includes/config-path.php';

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido.']);
    exit;
}

if ($id === (int) $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propio usuario.']);
    exit;
}

$stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
$stmt->execute([':id' => $id]);

echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente.']);
