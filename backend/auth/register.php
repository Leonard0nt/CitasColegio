<?php

header('Content-Type: application/json; charset=utf-8');

http_response_code(403);
echo json_encode([
    'success' => false,
    'message' => 'El registro de alumnos está deshabilitado. Solicita la creación del alumno a un administrador.',
]);
