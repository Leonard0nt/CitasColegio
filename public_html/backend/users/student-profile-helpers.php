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

function ensure_student_guardians_table(PDO $pdo): void
{
    ensure_students_table($pdo);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS student_guardians (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,

            guardian_name VARCHAR(120) NOT NULL,
            guardian_rut VARCHAR(20),
            guardian_phone VARCHAR(30),
            guardian_email VARCHAR(150),
            guardian_relationship VARCHAR(80),

            backup_guardian_name VARCHAR(120),
            backup_guardian_rut VARCHAR(20),
            backup_guardian_phone VARCHAR(30),
            backup_guardian_email VARCHAR(150),
            backup_guardian_relationship VARCHAR(80),

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            CONSTRAINT fk_student_guardians_student
                FOREIGN KEY (student_id)
                REFERENCES students(id)
                ON DELETE CASCADE,

            UNIQUE KEY unique_student_guardian (student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    migrate_legacy_student_guardians_fk($pdo);
}

function migrate_legacy_student_guardians_fk(PDO $pdo): void
{
    $stmt = $pdo->prepare(<<<SQL
        SELECT
            constraint_name,
            referenced_table_name,
            referenced_column_name
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE table_schema = DATABASE()
          AND table_name = 'student_guardians'
          AND column_name = 'student_id'
          AND referenced_table_name IS NOT NULL
SQL);
    $stmt->execute();
    $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasStudentsForeignKey = false;
    foreach ($foreignKeys as $foreignKey) {
        $constraintName = (string) $foreignKey['constraint_name'];
        $referencedTable = (string) $foreignKey['referenced_table_name'];
        $referencedColumn = (string) $foreignKey['referenced_column_name'];

        if ($referencedTable === 'students' && $referencedColumn === 'id') {
            $hasStudentsForeignKey = true;
            continue;
        }

        $pdo->exec('ALTER TABLE student_guardians DROP FOREIGN KEY `' . str_replace('`', '``', $constraintName) . '`');
    }

    if ($hasStudentsForeignKey) {
        return;
    }

    copy_legacy_student_users_to_students($pdo);

    $pdo->exec(<<<SQL
        DELETE sg
        FROM student_guardians sg
        LEFT JOIN students s ON s.id = sg.student_id
        WHERE s.id IS NULL
SQL);

    $pdo->exec(<<<SQL
        ALTER TABLE student_guardians
        ADD CONSTRAINT fk_student_guardians_student
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
SQL);
}

function copy_legacy_student_users_to_students(PDO $pdo): void
{
    $stmt = $pdo->prepare(<<<SQL
        SELECT COUNT(*)
        FROM information_schema.TABLES
        WHERE table_schema = DATABASE()
          AND table_name = 'users'
SQL);
    $stmt->execute();

    if ((int) $stmt->fetchColumn() === 0) {
        return;
    }

    $stmt = $pdo->prepare(<<<SQL
        SELECT COUNT(*)
        FROM information_schema.TABLES
        WHERE table_schema = DATABASE()
          AND table_name = 'student_profiles'
SQL);
    $stmt->execute();
    $hasStudentProfiles = (int) $stmt->fetchColumn() > 0;

    $courseSelect = $hasStudentProfiles ? 'sp.course' : 'NULL';
    $rutSelect = $hasStudentProfiles ? 'sp.rut' : 'NULL';
    $profileJoin = $hasStudentProfiles ? 'LEFT JOIN student_profiles sp ON sp.user_id = u.id' : '';

    $pdo->exec("
        INSERT INTO students (id, name, active, course, rut, created_at, updated_at)
        SELECT u.id, u.name, u.active, {$courseSelect}, {$rutSelect}, u.created_at, u.updated_at
        FROM users u
        {$profileJoin}
        WHERE u.role = 'alumno'
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            active = VALUES(active),
            course = VALUES(course),
            rut = VALUES(rut)
    ");
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
