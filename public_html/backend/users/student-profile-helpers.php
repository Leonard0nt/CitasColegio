<?php

function ensure_student_profiles_table(PDO $pdo): void
{
    ensure_students_table($pdo);
}

function ensure_students_table(PDO $pdo): void
{
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            course VARCHAR(120),
            rut VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            UNIQUE KEY unique_student_rut (rut),
            INDEX idx_students_active (active),
            INDEX idx_students_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
}

function normalize_student_rut_value(string $rut): string
{
    return trim(preg_replace('/\s+/', '', $rut));
}

function save_student_record(PDO $pdo, ?int $studentId, string $name, int $active, string $course, string $rut): int
{
    ensure_students_table($pdo);

    $name = trim($name);
    $course = trim($course);
    $rut = normalize_student_rut_value($rut);

    if ($studentId !== null && $studentId > 0) {
        $stmt = $pdo->prepare('
            UPDATE students
            SET name = :name, active = :active, course = :course, rut = :rut
            WHERE id = :id
        ');
        $stmt->execute([
            ':id' => $studentId,
            ':name' => $name,
            ':active' => $active === 1 ? 1 : 0,
            ':course' => $course !== '' ? $course : null,
            ':rut' => $rut !== '' ? $rut : null,
        ]);

        return $studentId;
    }

    $stmt = $pdo->prepare('
        INSERT INTO students (name, active, course, rut)
        VALUES (:name, :active, :course, :rut)
    ');
    $stmt->execute([
        ':name' => $name,
        ':active' => $active === 1 ? 1 : 0,
        ':course' => $course !== '' ? $course : null,
        ':rut' => $rut !== '' ? $rut : null,
    ]);

    return (int) $pdo->lastInsertId();
}

function save_student_profile(PDO $pdo, int $studentId, string $course, string $rut): void
{
    ensure_students_table($pdo);

    $course = trim($course);
    $rut = normalize_student_rut_value($rut);

    $stmt = $pdo->prepare('
        UPDATE students
        SET course = :course, rut = :rut
        WHERE id = :id
    ');

    $stmt->execute([
        ':id' => $studentId,
        ':course' => $course !== '' ? $course : null,
        ':rut' => $rut !== '' ? $rut : null,
    ]);
}

function delete_student_profile(PDO $pdo, int $studentId): void
{
    ensure_students_table($pdo);

    $stmt = $pdo->prepare('DELETE FROM students WHERE id = :id');
    $stmt->execute([':id' => $studentId]);
}
