-- Migra alumnos desde cuentas de usuario a referencias sin acceso al sistema.
-- Ejecutar en una ventana de mantención y respaldar la base antes de aplicar.

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO students (id, name, active, course, rut, created_at, updated_at)
SELECT u.id, u.name, u.active, sp.course, sp.rut, u.created_at, u.updated_at
FROM users u
LEFT JOIN student_profiles sp ON sp.user_id = u.id
WHERE u.role = 'alumno'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    active = VALUES(active),
    course = VALUES(course),
    rut = VALUES(rut);

-- Si tu instalación ya tiene llaves foráneas desde student_guardians/meetings hacia users,
-- elimina esas llaves con ALTER TABLE ... DROP FOREIGN KEY usando el nombre real de cada
-- constraint antes de agregar las siguientes. Los nombres históricos del proyecto son:
-- fk_student_guardians_user y fk_meetings_student.
ALTER TABLE student_guardians DROP FOREIGN KEY fk_student_guardians_user;
ALTER TABLE meetings DROP FOREIGN KEY fk_meetings_student;

ALTER TABLE student_guardians
    ADD CONSTRAINT fk_student_guardians_student
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE;

ALTER TABLE meetings
    ADD CONSTRAINT fk_meetings_student
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE;

DELETE FROM users WHERE role = 'alumno';
ALTER TABLE users MODIFY role ENUM('admin', 'profesor') NOT NULL DEFAULT 'profesor';
