<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

require_once __DIR__ . '/../../includes/config-path.php';
require_once __DIR__ . '/teacher-profile-helpers.php';
require_once __DIR__ . '/student-profile-helpers.php';
require_once __DIR__ . '/encoding-helpers.php';

function normalize_header(string $header): string
{
    $header = normalize_csv_cell_value($header);
    if (function_exists('iconv')) {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header);
        $normalized = $normalized === false ? $header : $normalized;
    } else {
        $normalized = $header;
    }
    $normalized = strtolower($normalized);
    $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized);
    return trim((string) $normalized, '_');
}

function detect_delimiter(string $headerLine): string
{
    $delimiters = [',' => 0, ';' => 0, "\t" => 0];

    foreach ($delimiters as $delimiter => $count) {
        $delimiters[$delimiter] = substr_count($headerLine, $delimiter);
    }

    arsort($delimiters);
    return (string) array_key_first($delimiters);
}

function csv_value(array $row, array $aliases): string
{
    foreach ($aliases as $alias) {
        if (array_key_exists($alias, $row)) {
            return trim((string) $row[$alias]);
        }
    }

    return '';
}

function csv_student_name_value(array $row): string
{
    $name = csv_value($row, [
        'nombre_alumno',
        'nombre_completo_alumno',
        'nombre_completa_alumno',
        'nombre_estudiante',
        'nombre_completo_estudiante',
        'alumno',
        'estudiante',
        'nombre',
    ]);

    if ($name !== '') {
        return $name;
    }

    $parts = [
        csv_value($row, ['nombres', 'primer_nombre', 'segundo_nombre']),
        csv_value($row, ['apellido_paterno', 'primer_apellido', 'ap_paterno', 'paterno']),
        csv_value($row, ['apellido_materno', 'segundo_apellido', 'ap_materno', 'materno']),
    ];

    $parts = array_filter($parts, static fn($part) => $part !== '');
    return trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));
}

function normalize_rut_login(string $rut): string
{
    return strtolower(preg_replace('/[^0-9kK]+/', '', $rut));
}

function rut_body(string $rut): string
{
    $cleanRut = normalize_rut_login($rut);

    if (strlen($cleanRut) < 2) {
        return '';
    }

    return substr($cleanRut, 0, -1);
}

function rut_password(string $rut): string
{
    $body = preg_replace('/\D+/', '', rut_body($rut));

    if (strlen($body) < 4) {
        return '';
    }

    return substr($body, -4);
}

function rut_to_email_key(string $rut): string
{
    $cleanRut = normalize_rut_login($rut);
    return $cleanRut !== '' ? $cleanRut : uniqid('alumno_', true);
}

function row_values_changed(array $current, array $incoming): bool
{
    foreach ($incoming as $key => $value) {
        if (trim((string) ($current[$key] ?? '')) !== trim((string) $value)) {
            return true;
        }
    }

    return false;
}

if (!isset($_FILES['students_csv']) || !is_uploaded_file($_FILES['students_csv']['tmp_name'])) {
    echo json_encode(['success' => false, 'message' => 'Debes seleccionar un archivo CSV.']);
    exit;
}

if (($_FILES['students_csv']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No se pudo subir el archivo CSV.']);
    exit;
}

$contents = file_get_contents($_FILES['students_csv']['tmp_name']);
if ($contents === false || trim($contents) === '') {
    echo json_encode(['success' => false, 'message' => 'El archivo CSV está vacío o no se puede leer.']);
    exit;
}

$contents = normalize_file_encoding($contents);
$lines = preg_split('/\R/u', trim($contents));
$firstLine = $lines[0] ?? '';
$delimiter = detect_delimiter($firstLine);
$handle = fopen('php://temp', 'r+');
fwrite($handle, $contents);
rewind($handle);

$headers = fgetcsv($handle, 0, $delimiter);
if ($headers === false) {
    fclose($handle);
    echo json_encode(['success' => false, 'message' => 'No se pudieron leer los encabezados del CSV.']);
    exit;
}

$normalizedHeaders = array_map('normalize_header', $headers);

$created = 0;
$updated = 0;
$skipped = 0;
$errors = [];
$rowNumber = 1;

try {
    ensure_teacher_profiles_table($pdo);
    ensure_student_profiles_table($pdo);
    $pdo->beginTransaction();

    $findByRut = $pdo->prepare("
        SELECT
            u.id,
            u.name,
            u.email,
            u.role,
            u.active,
            sp.course AS student_course,
            sp.rut AS student_rut,
            sg.guardian_name,
            sg.guardian_phone,
            sg.guardian_email,
            sg.backup_guardian_name,
            sg.backup_guardian_phone,
            sg.backup_guardian_email
        FROM users u
        INNER JOIN student_profiles sp ON sp.user_id = u.id
        LEFT JOIN student_guardians sg ON sg.student_id = u.id
        WHERE LOWER(REPLACE(REPLACE(REPLACE(sp.rut, '.', ''), '-', ''), ' ', '')) = :rut
        LIMIT 1
    ");
    $insertUser = $pdo->prepare('
        INSERT INTO users (name, email, password, role, active)
        VALUES (:name, :email, :password, "alumno", 1)
    ');
    $updateUser = $pdo->prepare('
        UPDATE users
        SET name = :name, email = :email, role = "alumno", active = 1
        WHERE id = :id
    ');
    $upsertGuardian = $pdo->prepare('
        INSERT INTO student_guardians (
            student_id,
            guardian_name,
            guardian_rut,
            guardian_phone,
            guardian_email,
            guardian_relationship,
            backup_guardian_name,
            backup_guardian_rut,
            backup_guardian_phone,
            backup_guardian_email,
            backup_guardian_relationship
        ) VALUES (
            :student_id,
            :guardian_name,
            "",
            :guardian_phone,
            :guardian_email,
            "",
            :backup_guardian_name,
            "",
            :backup_guardian_phone,
            :backup_guardian_email,
            ""
        )
        ON DUPLICATE KEY UPDATE
            guardian_name = VALUES(guardian_name),
            guardian_phone = VALUES(guardian_phone),
            guardian_email = VALUES(guardian_email),
            backup_guardian_name = VALUES(backup_guardian_name),
            backup_guardian_phone = VALUES(backup_guardian_phone),
            backup_guardian_email = VALUES(backup_guardian_email)
    ');
    $deleteTeacher = $pdo->prepare('DELETE FROM teacher_profiles WHERE user_id = :user_id');

    while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
        $rowNumber++;

        if (count(array_filter($data, static fn($value) => trim((string) $value) !== '')) === 0) {
            continue;
        }

        $data = array_pad($data, count($normalizedHeaders), '');
        $row = array_combine($normalizedHeaders, array_slice($data, 0, count($normalizedHeaders)));

        if ($row === false) {
            $skipped++;
            $errors[] = "Fila {$rowNumber}: formato inválido.";
            continue;
        }

        $row = array_map(static fn($value) => normalize_csv_cell_value((string) $value), $row);

        $course = csv_value($row, ['nombre_curso', 'curso', 'nombre_del_curso', '']);
        $rut = csv_value($row, ['numero_rut', 'numero_de_rut', 'rut', 'run', 'rut_alumno', 'run_alumno']);
        $name = csv_student_name_value($row);
        $guardianName = csv_value($row, ['nombre_apoderado', 'apoderado', 'nombre_apoderado_titular']);
        $guardianEmail = strtolower(csv_value($row, ['email_apoderado', 'correo_apoderado', 'mail_apoderado']));
        $guardianPhone = csv_value($row, ['movil_apoderado', 'm_vil_apoderado', 'telefono_apoderado', 'celular_apoderado']);
        $backupName = csv_value($row, ['nombre_suplente', 'nombre_apoderado_suplente', 'apoderado_suplente']);
        $backupEmail = strtolower(csv_value($row, ['email_suplente', 'email_apoderado_suplente', 'correo_suplente', 'correo_apoderado_suplente']));
        $backupPhone = csv_value($row, ['movil_suplente', 'movil_apoderado_suplente', 'm_vil_suplente', 'm_vil_apoderado_suplente', 'telefono_suplente', 'telefono_apoderado_suplente']);

        if ($course === '') {
            $skipped++;
            $errors[] = "Fila {$rowNumber}: falta Nombre Curso.";
            continue;
        }

        if ($rut === '' || normalize_rut_login($rut) === '') {
            $skipped++;
            $errors[] = "Fila {$rowNumber}: falta Número Rut.";
            continue;
        }

        if ($name === '') {
            $skipped++;
            $errors[] = "Fila {$rowNumber}: falta Nombre Alumno.";
            continue;
        }

        if ($guardianName === '') {
            $skipped++;
            $errors[] = "Fila {$rowNumber}: falta Nombre Apoderado.";
            continue;
        }

        if ($guardianEmail !== '' && !filter_var($guardianEmail, FILTER_VALIDATE_EMAIL)) {
            $guardianEmail = '';
        }

        if ($backupEmail !== '' && !filter_var($backupEmail, FILTER_VALIDATE_EMAIL)) {
            $backupEmail = '';
        }

        $loginRut = normalize_rut_login($rut);
        $initialPassword = rut_password($rut);

        if ($initialPassword === '') {
            $skipped++;
            $errors[] = "Fila {$rowNumber}: el RUT no permite generar contraseña inicial.";
            continue;
        }

        $email = rut_to_email_key($rut) . '@alumno.local';
        $userId = 0;
        $existingUser = null;
        $findByRut->execute([':rut' => $loginRut]);
        $existingByRut = $findByRut->fetch();

        if ($existingByRut) {
            $existingUser = $existingByRut;
            $userId = (int) $existingByRut['id'];
        }

        if ($userId > 0) {
            $incomingUser = [
                'name' => $name,
                'email' => $email,
                'role' => 'alumno',
                'active' => '1',
                'student_course' => $course,
                'student_rut' => $rut,
                'guardian_name' => $guardianName,
                'guardian_phone' => $guardianPhone,
                'guardian_email' => $guardianEmail,
                'backup_guardian_name' => $backupName,
                'backup_guardian_phone' => $backupPhone,
                'backup_guardian_email' => $backupEmail,
            ];

            if ($existingUser === null || row_values_changed($existingUser, $incomingUser)) {
                $updated++;
            }

            $updateUser->execute([
                ':id' => $userId,
                ':name' => $name,
                ':email' => $email,
            ]);
        } else {
            $insertUser->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => password_hash($initialPassword, PASSWORD_DEFAULT),
            ]);
            $userId = (int) $pdo->lastInsertId();
            $created++;
        }

        save_student_profile($pdo, $userId, $course, $rut);
        $upsertGuardian->execute([
            ':student_id' => $userId,
            ':guardian_name' => $guardianName,
            ':guardian_phone' => $guardianPhone,
            ':guardian_email' => $guardianEmail,
            ':backup_guardian_name' => $backupName,
            ':backup_guardian_phone' => $backupPhone,
            ':backup_guardian_email' => $backupEmail,
        ]);
        $deleteTeacher->execute([':user_id' => $userId]);
    }

    fclose($handle);
    $pdo->commit();

    $message = "Carga finalizada: {$created} creados, {$updated} actualizados, {$skipped} omitidos. Los alumnos ingresan con su RUT sin puntos ni guion y clave de los últimos 4 dígitos antes del verificador.";
    echo json_encode([
        'success' => true,
        'message' => $message,
        'created' => $created,
        'updated' => $updated,
        'skipped' => $skipped,
        'errors' => array_slice($errors, 0, 10),
    ]);
} catch (Throwable $e) {
    fclose($handle);

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode(['success' => false, 'message' => 'Error al procesar el CSV de alumnos.']);
}
