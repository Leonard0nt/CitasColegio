<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'logged_in' => isset($_SESSION['user_id']),
    'user' => isset($_SESSION['user_id']) ? [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
    ] : null,
]);
