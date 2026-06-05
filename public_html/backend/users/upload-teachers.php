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
require_once __DIR__ . '/encoding-helpers.php';

function csv_value(array $row, array $aliases): string
{
    foreach ($aliases as $alias) {
        if (array_key_exists($alias, $row)) {
            return trim((string) $row[$alias]);
        }
    }

    return '';
}

function csv_name_value(array $row): string
{
    $name = csv_value($row, [
        'nombre_completo',
        'nombres_completos',
        'nombre_y_apellido',
        'nombre_apellido',
        'profesor',
        'docente',
        'funcionario',
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

function csv_phone_value(array $row): string
{
    $aliases = [
        'movil',
        'm_vil',
        'mobile',
        'telefono',
        'fono',
        'celular',
        'numero',
        'numero_telefono',
        'numero_de_telefono',
        'telefono_movil',
        'telefono_celular',
        'movil_numero',
        'numero_movil',
        'numero_celular',
        'n_movil',
        'no_movil',
        'nro_movil',
        'n_celular',
        'no_celular',
        'nro_celular',
    ];

    $phone = csv_value($row, $aliases);
    if ($phone !== '') {
        return $phone;
    }

    foreach ($row as $header => $value) {
        $normalizedHeader = normalize_header((string) $header);
        $value = trim((string) $value);

        if ($value === '') {
            continue;
        }

        if (preg_match('/(^|_)(movil|mobile|celular|telefono|fono)($|_)/', $normalizedHeader) === 1) {
            return $value;
        }
    }

    return '';
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
    return $cleanRut !== '' ? $cleanRut : uniqid('profesor_', true);
}

if (!isset($_FILES['teachers_csv']) || !is_uploaded_file($_FILES['teachers_csv']['tmp_name'])) {
    echo json_encode(['success' => false, 'message' => 'Debes seleccionar un archivo CSV.']);
    exit;
}

if (($_FILES['teachers_csv']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No se pudo subir el archivo CSV.']);
    exit;
}

$contents = file_get_contents($_FILES['teachers_csv']['tmp_name']);
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
    $pdo->beginTransaction();

    $findByEmail = $pdo->prepare('
        SELECT
            u.id,
            u.name,
            u.email,
            u.role,
            u.active,
            tp.cost_center AS teacher_cost_center,
            tp.rut AS teacher_rut,
            tp.phone AS teacher_phone
        FROM users u
        LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
        WHERE u.email = :email
        LIMIT 1
    ');
    $findByRut = $pdo->prepare("
        SELECT
            u.id,
            u.name,
            u.email,
            u.role,
            u.active,
            tp.cost_center AS teacher_cost_center,
            tp.rut AS teacher_rut,
            tp.phone AS teacher_phone
        FROM users u
        INNER JOIN teacher_profiles tp ON tp.user_id = u.id
        WHERE LOWER(REPLACE(REPLACE(REPLACE(tp.rut, '.', ''), '-', ''), ' ', '')) = :rut
        LIMIT 1
    ");
    $insertUser = $pdo->prepare('
        INSERT INTO users (name, email, password, role, active)
        VALUES (:name, :email, :password, "profesor", 1)
    ');
    $updateUser = $pdo->prepare('
        UPDATE users
        SET name = :name, email = :email, role = "profesor", active = 1
        WHERE id = :id
    ');
    $deleteGuardian = $pdo->prepare('DELETE FROM student_guardians WHERE student_id = :student_id');
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

        $name = csv_name_value($row);
        $email = strtolower(csv_value($row, ['email', 'correo', 'e_mail']));
        $rut = csv_value($row, ['rut', 'run']);
        $costCenter = csv_value($row, [
            'centro_costo',
            'centro_de_costo',
            'centro_costos',
            'centro_de_costos',
            'centro_de_costo_s',
            'centro_coste',
            'centro_de_coste',
            'centro_costo_profesor',
            'centro_de_costos_profesor',
            'centro_costos_profesor',
            'centro_de_costo_profesor',
            'centro',
            'ceco',
            'cc',
        ]);
        $phone = csv_phone_value($row);

        if ($name === '') {
            $skipped++;
            $errors[] = "Fila {$rowNumber}: falta el nombre del profesor.";
            continue;
        }

        if ($costCenter === '') {
            $skipped++;
            $errors[] = "Fila {$rowNumber}: falta el Centro Costo, por eso no se importó.";
            continue;
        }

        $phone = normalize_teacher_phone_value($phone) ?? '';
        $loginRut = normalize_rut_login($rut);
        $initialPassword = rut_password($rut);

        if ($loginRut === '' || $initialPassword === '') {
            $skipped++;
            $errors[] = "Fila {$rowNumber}: falta un RUT válido para generar usuario y contraseña.";
            continue;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = '';
        }

        if ($email === '') {
            $email = rut_to_email_key($rut) . '@sin-correo.local';
        }

        $userId = 0;
        $existingUser = null;
        $findByEmail->execute([':email' => $email]);
        $existingByEmail = $findByEmail->fetch();

        if ($existingByEmail) {
            $existingUser = $existingByEmail;
            $userId = (int) $existingByEmail['id'];
        } elseif ($rut !== '') {
            $findByRut->execute([':rut' => $loginRut]);
            $existingByRut = $findByRut->fetch();
            if ($existingByRut) {
                $existingUser = $existingByRut;
                $userId = (int) $existingByRut['id'];
            }
        }

        if ($userId > 0) {
            $incomingUser = [
                'name' => $name,
                'email' => $email,
                'role' => 'profesor',
                'active' => '1',
                'teacher_cost_center' => $costCenter,
                'teacher_rut' => $rut,
                'teacher_phone' => $phone,
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

        save_teacher_profile($pdo, $userId, $costCenter, $rut, $phone);
        $deleteGuardian->execute([':student_id' => $userId]);
    }

    fclose($handle);
    $pdo->commit();

    $message = "Carga finalizada: {$created} creados, {$updated} actualizados, {$skipped} omitidos. Los profesores ingresan con su RUT sin puntos ni guion y clave de los últimos 4 dígitos antes del verificador.";
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

    echo json_encode(['success' => false, 'message' => 'Error al procesar el CSV de profesores.']);
}
