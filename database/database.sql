CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'profesor') NOT NULL DEFAULT 'profesor',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS teacher_profiles (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE IF NOT EXISTS meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    guardian_type ENUM('titular', 'suplente') NOT NULL DEFAULT 'titular',
    guardian_name VARCHAR(120) NOT NULL,
    guardian_email VARCHAR(150),
    guardian_phone VARCHAR(30),
    meeting_date DATE NOT NULL,
    meeting_time TIME NOT NULL,
    status ENUM('por_atender', 'atendido') NOT NULL DEFAULT 'por_atender',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_meetings_teacher
        FOREIGN KEY (teacher_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_meetings_student
        FOREIGN KEY (student_id)
        REFERENCES students(id)
        ON DELETE CASCADE,

    UNIQUE KEY uniq_meetings_teacher_datetime (teacher_id, meeting_date, meeting_time),
    INDEX idx_meetings_student (student_id),
    INDEX idx_meetings_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO system_settings (setting_key, setting_value)
VALUES ('attendance_pin', '1234')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- Usuario admin inicial
-- Email: admin@demo.cl
-- Password: Admin12345
INSERT INTO users (name, email, password, role, active)
VALUES (
    'Administrador',
    'admin@demo.cl',
    '$2y$12$NyY704Zok7I1Z0c.EU1gaumeJijxkQn5U2Y.Yb0tuSzPAOH.5Jw2e',
    'admin',
    1
)
ON DUPLICATE KEY UPDATE email = email;
