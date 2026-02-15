CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE,
    phone VARCHAR(30),
    section ENUM('Primary(1-5)', 'Secondary(6-11)', 'A Level(12 & 13)') NOT NULL,
    max_periods_per_day INT NOT NULL DEFAULT 6,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) UNIQUE NOT NULL,
    section VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    UNIQUE KEY uq_teacher_subject (teacher_id, subject_id),
    CONSTRAINT fk_teacher_subject_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_teacher_subject_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
    period_number TINYINT NOT NULL,
    UNIQUE KEY uq_class_slot (class_id, day_of_week, period_number),
    CONSTRAINT fk_timetable_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    CONSTRAINT fk_timetable_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    CONSTRAINT fk_timetable_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS absences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    absence_date DATE NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
    period_number TINYINT NOT NULL,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_teacher_absence_slot (teacher_id, absence_date, day_of_week, period_number),
    CONSTRAINT fk_absences_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS relief_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    absence_id INT NOT NULL,
    absent_teacher_id INT NOT NULL,
    relief_teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    relief_date DATE NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
    period_number TINYINT NOT NULL,
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_relief_slot (relief_teacher_id, relief_date, day_of_week, period_number),
    CONSTRAINT fk_relief_absence FOREIGN KEY (absence_id) REFERENCES absences(id) ON DELETE CASCADE,
    CONSTRAINT fk_relief_absent_teacher FOREIGN KEY (absent_teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_relief_relief_teacher FOREIGN KEY (relief_teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_relief_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    CONSTRAINT fk_relief_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

INSERT INTO admins (username, password_hash)
VALUES ('admin', '$2y$12$6Uh2kldh7IbYrpAOGVmZhOBqvEPuWJEjoRSCVZo1zcIA7IGgt69aG')
ON DUPLICATE KEY UPDATE username = VALUES(username);
