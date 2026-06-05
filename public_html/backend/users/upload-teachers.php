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

function csv_value(array $row, array $aliases): string
{
    foreach ($aliases as $alias) {
        if (array_key_exists($alias, $row)) {
            return trim((string) $row[$alias]);
        }
    }

    return '';
}

function normalize_header(string $header): string
{
    $header = trim($header);
    $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
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

function normalize_file_encoding(string $contents): string
{
    if (!function_exists('mb_detect_encoding') || !function_exists('mb_convert_encoding')) {
        return $contents;
    }

    $encoding = mb_detect_encoding($contents, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

    if ($encoding !== false && $encoding !== 'UTF-8') {
        $converted = mb_convert_encoding($contents, 'UTF-8', $encoding);
        return $converted === false ? $contents : $converted;
    }

    return $contents;
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

function rut_to_email_key(string $rut): string
{
    $cleanRut = strtolower(preg_replace('/[^0-9kK]+/', '', $rut));
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

$defaultPassword = $_POST['default_password'] ?? 'Profesor12345';

if (strlen($defaultPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'La contraseña por defecto debe tener mínimo 8 caracteres.']);
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

    $findByEmail = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $findByRut = $pdo->prepare('
        SELECT u.id
        FROM users u
        INNER JOIN teacher_profiles tp ON tp.user_id = u.id
        WHERE tp.rut = :rut
        LIMIT 1
    ');
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
    $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);

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

        $name = csv_value($row, ['nombre_completo', 'nombre']);
        $email = strtolower(csv_value($row, ['email', 'correo', 'e_mail']));
        $rut = csv_value($row, ['rut', 'run']);
        $costCenter = csv_value($row, ['centro_costo', 'centro_de_costo', 'centro_costos']);
        $phone = csv_value($row, ['movil', 'mobile', 'telefono', 'fono', 'celular']);

        if ($name === '') {
            $skipped++;
            $errors[] = "Fila {$rowNumber}: falta el nombre del profesor.";
            continue;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = '';
        }

        if ($email === '') {
            $email = rut_to_email_key($rut) . '@sin-correo.local';
        }

        $userId = 0;
        $findByEmail->execute([':email' => $email]);
        $existingByEmail = $findByEmail->fetch();

        if ($existingByEmail) {
            $userId = (int) $existingByEmail['id'];
        } elseif ($rut !== '') {
            $findByRut->execute([':rut' => $rut]);
            $existingByRut = $findByRut->fetch();
            $userId = $existingByRut ? (int) $existingByRut['id'] : 0;
        }

        if ($userId > 0) {
            $updateUser->execute([
                ':id' => $userId,
                ':name' => $name,
                ':email' => $email,
            ]);
            $updated++;
        } else {
            $insertUser->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => $passwordHash,
            ]);
            $userId = (int) $pdo->lastInsertId();
            $created++;
        }

        save_teacher_profile($pdo, $userId, $costCenter, $rut, $phone);
        $deleteGuardian->execute([':student_id' => $userId]);
    }

    fclose($handle);
    $pdo->commit();

    $message = "Carga finalizada: {$created} creados, {$updated} actualizados, {$skipped} omitidos.";
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
