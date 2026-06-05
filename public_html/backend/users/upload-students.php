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

function photo_student_csv_columns(): array
{
    return [
        'course' => [
            'label' => 'Nombre Curso',
            'aliases' => ['nombre_curso'],
            'required' => true,
        ],
        'rut' => [
            'label' => 'Número Rut',
            'aliases' => ['numero_rut'],
            'required' => true,
        ],
        'name' => [
            'label' => 'Nombre Completo Alumno',
            'aliases' => ['nombre_completo_alumno'],
            'required' => true,
        ],
        'guardian_name' => [
            'label' => 'Nombre Apoderado',
            'aliases' => ['nombre_apoderado'],
            'required' => true,
        ],
        'guardian_email' => [
            'label' => 'Email Apoderado',
            'aliases' => ['email_apoderado'],
            'required' => false,
        ],
        'guardian_phone' => [
            'label' => 'Móvil Apoderado',
            'aliases' => ['movil_apoderado', 'm_vil_apoderado'],
            'required' => false,
        ],
        'backup_name' => [
            'label' => 'Nombre Apoderado Suplente',
            'aliases' => ['nombre_apoderado_suplente'],
            'required' => false,
        ],
        'backup_email' => [
            'label' => 'Email Apoderado Suplente',
            'aliases' => ['email_apoderado_suplente'],
            'required' => false,
        ],
        'backup_phone' => [
            'label' => 'Móvil Apoderado Suplente',
            'aliases' => ['movil_apoderado_suplente', 'm_vil_apoderado_suplente'],
            'required' => false,
        ],
    ];
}

function csv_photo_value(array $row, string $column): string
{
    $columns = photo_student_csv_columns();
    return csv_value($row, $columns[$column]['aliases'] ?? []);
}

function normalize_student_csv_headers(array $headers): array
{
    $normalizedHeaders = array_map('normalize_header', $headers);

    if (($normalizedHeaders[0] ?? '') === '' && ($normalizedHeaders[1] ?? '') === 'numero_rut') {
        $normalizedHeaders[0] = 'nombre_curso';
    }

    return $normalizedHeaders;
}

function missing_required_csv_columns(array $normalizedHeaders): array
{
    $missing = [];

    foreach (photo_student_csv_columns() as $column) {
        if (!($column['required'] ?? false)) {
            continue;
        }

        if (count(array_intersect($column['aliases'], $normalizedHeaders)) === 0) {
            $missing[] = $column['label'];
        }
    }

    return $missing;
}

function expected_csv_header_text(): string
{
    return implode(', ', array_map(
        static fn(array $column): string => $column['label'],
        photo_student_csv_columns()
    ));
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
        'nombre_completo',
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

function skip_student_row(int $rowNumber, string $reason, int &$skipped, array &$errors, array &$skipReasons): void
{
    $skipped++;
    $skipReasons[$reason] = ($skipReasons[$reason] ?? 0) + 1;
    $errors[] = "Fila {$rowNumber}: {$reason}.";
}

function skip_summary_text(array $skipReasons): string
{
    if (count($skipReasons) === 0) {
        return '';
    }

    arsort($skipReasons);
    $parts = [];

    foreach ($skipReasons as $reason => $count) {
        $parts[] = "{$reason}: {$count}";
    }

    return implode('; ', $parts);
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

$normalizedHeaders = normalize_student_csv_headers($headers);
$missingColumns = missing_required_csv_columns($normalizedHeaders);

if (count($missingColumns) > 0) {
    fclose($handle);
    echo json_encode([
        'success' => false,
        'message' => 'El CSV no tiene las columnas obligatorias de la planilla de alumnos: ' . implode(', ', $missingColumns) . '. Usa exactamente: ' . expected_csv_header_text() . '.',
        'missing_columns' => $missingColumns,
        'found_columns' => $headers,
    ]);
    exit;
}

$created = 0;
$updated = 0;
$skipped = 0;
$errors = [];
$skipReasons = [];
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
            skip_student_row($rowNumber, 'formato inválido', $skipped, $errors, $skipReasons);
            continue;
        }

        $row = array_map(static fn($value) => normalize_csv_cell_value((string) $value), $row);

        $course = csv_photo_value($row, 'course');
        $rut = csv_photo_value($row, 'rut');
        $name = csv_photo_value($row, 'name');
        $guardianName = csv_photo_value($row, 'guardian_name');
        $guardianEmail = strtolower(csv_photo_value($row, 'guardian_email'));
        $guardianPhone = csv_photo_value($row, 'guardian_phone');
        $backupName = csv_photo_value($row, 'backup_name');
        $backupEmail = strtolower(csv_photo_value($row, 'backup_email'));
        $backupPhone = csv_photo_value($row, 'backup_phone');

        if ($course === '') {
            skip_student_row($rowNumber, 'falta Nombre Curso', $skipped, $errors, $skipReasons);
            continue;
        }

        if ($rut === '' || normalize_rut_login($rut) === '') {
            skip_student_row($rowNumber, 'falta Número Rut', $skipped, $errors, $skipReasons);
            continue;
        }

        if ($name === '') {
            skip_student_row($rowNumber, 'falta Nombre Completo Alumno', $skipped, $errors, $skipReasons);
            continue;
        }

        if ($guardianName === '') {
            skip_student_row($rowNumber, 'falta Nombre Apoderado', $skipped, $errors, $skipReasons);
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
            skip_student_row($rowNumber, 'el RUT no permite generar contraseña inicial', $skipped, $errors, $skipReasons);
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

    $summary = skip_summary_text($skipReasons);
    $message = "Carga finalizada: {$created} creados, {$updated} actualizados, {$skipped} omitidos.";

    if ($summary !== '') {
        $message .= " Motivos de omisión: {$summary}.";
    }

    $message .= " Los alumnos ingresan con su RUT sin puntos ni guion y clave de los últimos 4 dígitos antes del verificador.";

    echo json_encode([
        'success' => true,
        'message' => $message,
        'created' => $created,
        'updated' => $updated,
        'skipped' => $skipped,
        'skip_reasons' => $skipReasons,
        'errors' => array_slice($errors, 0, 20),
    ]);
} catch (Throwable $e) {
    fclose($handle);

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode(['success' => false, 'message' => 'Error al procesar el CSV de alumnos.']);
}
