<?php

function ensure_teacher_profiles_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS teacher_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        cost_center VARCHAR(120),
        rut VARCHAR(20),
        phone VARCHAR(30),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_teacher_profiles_user
            FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE CASCADE,
        UNIQUE KEY unique_teacher_profile_user (user_id),
        UNIQUE KEY unique_teacher_profile_rut (rut)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function normalize_teacher_profile_value(?string $value): ?string
{
    $value = trim((string) $value);
    return $value === '' ? null : $value;
}

function save_teacher_profile(PDO $pdo, int $userId, ?string $costCenter, ?string $rut, ?string $phone): void
{
    $rut = normalize_teacher_profile_value($rut);

    if ($rut !== null) {
        $stmtExistingRut = $pdo->prepare('SELECT user_id FROM teacher_profiles WHERE rut = :rut AND user_id <> :user_id LIMIT 1');
        $stmtExistingRut->execute([
            ':rut' => $rut,
            ':user_id' => $userId,
        ]);

        if ($stmtExistingRut->fetch()) {
            throw new PDOException('El RUT del profesor ya está asignado a otro usuario.', '23000');
        }
    }

    $stmt = $pdo->prepare('
        INSERT INTO teacher_profiles (user_id, cost_center, rut, phone)
        VALUES (:user_id, :cost_center, :rut, :phone)
        ON DUPLICATE KEY UPDATE
            cost_center = VALUES(cost_center),
            rut = VALUES(rut),
            phone = VALUES(phone)
    ');

    $stmt->execute([
        ':user_id' => $userId,
        ':cost_center' => normalize_teacher_profile_value($costCenter),
        ':rut' => $rut,
        ':phone' => normalize_teacher_profile_value($phone),
    ]);
}

function delete_teacher_profile(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('DELETE FROM teacher_profiles WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $userId]);
}
