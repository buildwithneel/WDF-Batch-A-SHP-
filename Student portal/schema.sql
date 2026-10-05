CREATE DATABASE IF NOT EXISTS `studenthub_db`
DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `studenthub_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `event_registrations`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `contacts`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(25) NOT NULL UNIQUE,
    `fullname` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) DEFAULT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(15) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('Student', 'Admin') DEFAULT 'Student',
    `course` VARCHAR(50) NOT NULL,
    `year` VARCHAR(30) NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `status` ENUM('Active', 'Suspended', 'Pending') DEFAULT 'Active',
    `remember_token` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_students_email` (`email`),
    INDEX `idx_students_course` (`course`),
    INDEX `idx_students_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `event_date` DATE NOT NULL,
    `venue` VARCHAR(120) NOT NULL,
    `description` TEXT NOT NULL,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `max_participants` INT DEFAULT 100,
    `status` ENUM('Upcoming', 'Ongoing', 'Completed', 'Cancelled') DEFAULT 'Upcoming',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_events_category` (`category`),
    INDEX `idx_events_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_code` VARCHAR(35) NOT NULL UNIQUE,
    `student_id` INT NOT NULL,
    `event_id` INT NOT NULL,
    `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('Confirmed', 'Waitlisted', 'Cancelled') DEFAULT 'Confirmed',
    `remarks` VARCHAR(255) DEFAULT 'Standard Registration',
    CONSTRAINT `fk_registrations_student`
        FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_registrations_event`
        FOREIGN KEY (`event_id`) REFERENCES `events` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `unique_student_event_registration` (`student_id`, `event_id`),
    INDEX `idx_reg_student` (`student_id`),
    INDEX `idx_reg_event` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contacts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` VARCHAR(30) NOT NULL UNIQUE,
    `fullname` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `subject` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('New', 'In Progress', 'Resolved') DEFAULT 'New',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `events` (`id`, `name`, `category`, `event_date`, `venue`, `description`, `image_url`, `max_participants`) VALUES
(1, 'Hackathon 2026', 'Technical', '2026-08-15', 'Seminar Hall', 'A 24-hour coding competition for innovative software solutions.', 'https://blog.bham.ac.uk/lpip/wp-content/uploads/sites/140/2025/05/Hackathon.png', 120),
(2, 'AI Workshop', 'Workshop', '2026-08-22', 'Computer Lab 1', 'Learn Artificial Intelligence and Machine Learning from industry experts.', 'https://digitallearning.eletsonline.com/wp-content/uploads/2013/12/techfest.jpg', 60),
(3, 'Annual Sports Meet', 'Sports', '2026-09-05', 'College Ground', 'Participate in various indoor and outdoor sports competitions.', 'https://iper.ac.in/wp-content/uploads/2021/12/IPER-UG-Inter-College-Sports-2021-22.jpg', 250),
(4, 'Cultural Fest 2026', 'Cultural', '2026-09-15', 'University Auditorium', 'A celebration of music, dance, drama and student creativity.', 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30', 300),
(5, 'Cyber Security Seminar', 'Seminar', '2026-09-20', 'Conference Hall', 'Learn about modern cybersecurity threats and protection techniques.', 'https://images.unsplash.com/photo-1563013544-824ae1b704d3', 80),
(6, 'Web Development Workshop', 'Workshop', '2026-10-05', 'Computer Lab 2', 'Hands-on workshop covering HTML, CSS, JavaScript, PHP and MySQL.', 'https://images.unsplash.com/photo-1498050108023-c5249f4df085', 50)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `students` (`id`, `student_id`, `fullname`, `username`, `email`, `phone`, `password_hash`, `role`, `course`, `year`, `gender`) VALUES
(1, 'ADM-2026-00001', 'System Administrator', 'admin', 'admin@studenthub.com', '9876500000', '$2y$10$ifWKXiwRcj630GnqrRT28eLV0YtIjwtZtc4941/3v/1bdxjHrq1ta', 'Admin', 'Administration', 'Staff', 'Other'),
(2, 'STU-2026-00001', 'Rahul Sharma', 'rahul', 'rahul.sharma@example.com', '9876543210', '$2y$10$ZTYozZ/HStwBtRazOqJvvenwY6mxXBNn/smoeyOhCqA5z.iNRcBfi', 'Student', 'B.Tech', 'Second Year', 'Male'),
(3, 'STU-2026-00002', 'Priya Patel', 'priya', 'priya.patel@example.com', '9876543211', '$2y$10$ZTYozZ/HStwBtRazOqJvvenwY6mxXBNn/smoeyOhCqA5z.iNRcBfi', 'Student', 'BCA', 'Third Year', 'Female')
ON DUPLICATE KEY UPDATE `password_hash`=VALUES(`password_hash`), `fullname`=VALUES(`fullname`);

INSERT INTO `event_registrations` (`registration_code`, `student_id`, `event_id`, `status`, `remarks`) VALUES
('REG-202609-001', 2, 1, 'Confirmed', 'Team Lead for Hackathon'),
('REG-202609-002', 2, 2, 'Confirmed', 'Enrolled in AI Workshop'),
('REG-202609-003', 3, 4, 'Confirmed', 'Solo Vocal Performance')
ON DUPLICATE KEY UPDATE `status`=VALUES(`status`);
