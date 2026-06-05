<?php

function ensure_student_profiles_table(PDO $pdo): void
{
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS student_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            course VARCHAR(120),
            rut VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            CONSTRAINT fk_student_profiles_user
                FOREIGN KEY (user_id)
                REFERENCES users(id)
                ON DELETE CASCADE,

            UNIQUE KEY unique_student_profile_user (user_id),
            UNIQUE KEY unique_student_profile_rut (rut)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
}

function normalize_student_rut_value(string $rut): string
{
    return trim(preg_replace('/\s+/', '', $rut));
}

function save_student_profile(PDO $pdo, int $userId, string $course, string $rut): void
{
    ensure_student_profiles_table($pdo);

    $course = trim($course);
    $rut = normalize_student_rut_value($rut);

    $stmt = $pdo->prepare('
        INSERT INTO student_profiles (user_id, course, rut)
        VALUES (:user_id, :course, :rut)
        ON DUPLICATE KEY UPDATE
            course = VALUES(course),
            rut = VALUES(rut)
    ');

    $stmt->execute([
        ':user_id' => $userId,
        ':course' => $course !== '' ? $course : null,
        ':rut' => $rut !== '' ? $rut : null,
    ]);
}

function delete_student_profile(PDO $pdo, int $userId): void
{
    ensure_student_profiles_table($pdo);

    $stmt = $pdo->prepare('DELETE FROM student_profiles WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $userId]);
}
