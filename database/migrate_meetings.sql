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
        REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_meetings_teacher_date (teacher_id, meeting_date, meeting_time),
    INDEX idx_meetings_student (student_id),
    INDEX idx_meetings_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
