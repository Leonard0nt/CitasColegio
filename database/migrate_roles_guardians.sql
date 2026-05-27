-- Ejecuta este archivo si ya tenías instalada la versión anterior.
-- Importante: haz respaldo antes de aplicar cambios.

-- 1) Permitimos temporalmente el rol antiguo user y los roles nuevos.
ALTER TABLE users
MODIFY role ENUM('admin', 'user', 'profesor', 'alumno') NOT NULL DEFAULT 'alumno';

-- 2) Convertimos los usuarios antiguos role=user a role=alumno.
UPDATE users SET role = 'alumno' WHERE role = 'user';

-- 3) Eliminamos el rol antiguo user del ENUM.
ALTER TABLE users
MODIFY role ENUM('admin', 'profesor', 'alumno') NOT NULL DEFAULT 'alumno';

-- 4) Creamos la tabla de apoderados para alumnos.
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

    CONSTRAINT fk_student_guardians_user
        FOREIGN KEY (student_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_student_guardian (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
