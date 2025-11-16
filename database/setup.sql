-- Environmental Science LMS Database Schema
-- This file contains all table definitions and initial data

-- Set charset and collation
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL DEFAULT 'student',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lessons table
CREATE TABLE IF NOT EXISTS `lessons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text,
  `content` longtext,
  `teacher_id` int(11) NOT NULL,
  `is_activity` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `due_date` timestamp NULL DEFAULT NULL,
  `max_points` int(11) DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_status` (`status`),
  KEY `idx_is_activity` (`is_activity`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_lessons_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Submissions table
CREATE TABLE IF NOT EXISTS `submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lesson_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `content` longtext,
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('draft','submitted','graded') NOT NULL DEFAULT 'draft',
  `grade` decimal(5,2) DEFAULT NULL,
  `feedback` text,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_lesson` (`lesson_id`, `student_id`),
  KEY `idx_lesson_id` (`lesson_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_status` (`status`),
  KEY `idx_submitted_at` (`submitted_at`),
  CONSTRAINT `fk_submissions_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_submissions_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login attempts table (for security)
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_ip` (`ip`),
  KEY `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login history table
CREATE TABLE IF NOT EXISTS `login_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `user_agent` text,
  `login_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logout_at` timestamp NULL DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_login_at` (`login_at`),
  CONSTRAINT `fk_login_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Access logs table
CREATE TABLE IF NOT EXISTS `access_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `page` varchar(100) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `user_agent` text,
  `accessed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_page` (`page`),
  KEY `idx_accessed_at` (`accessed_at`),
  CONSTRAINT `fk_access_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- File uploads table
CREATE TABLE IF NOT EXISTS `file_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `submission_id` int(11) DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_lesson_id` (`lesson_id`),
  KEY `idx_submission_id` (`submission_id`),
  CONSTRAINT `fk_file_uploads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_file_uploads_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_file_uploads_submission` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `data` json DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_read_at` (`read_at`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System settings table
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES 
('System Administrator', 'admin@lms.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Insert sample teacher (password: teacher123)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES 
('Dr. Jane Smith', 'teacher@lms.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Insert sample student (password: student123)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES 
('John Doe', 'student@lms.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Insert default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES 
('site_name', 'Environmental Science LMS', 'The name of the LMS site'),
('site_description', 'Interactive learning platform for environmental science education', 'Site description'),
('max_file_size', '10485760', 'Maximum file upload size in bytes (10MB)'),
('allowed_file_types', 'pdf,doc,docx,txt,jpg,jpeg,png,gif,mp4,zip', 'Comma-separated list of allowed file extensions'),
('session_timeout', '28800', 'Session timeout in seconds (8 hours)'),
('maintenance_mode', '0', 'Enable maintenance mode (1 = enabled, 0 = disabled)')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_users_role_status` ON `users` (`role`, `status`);
CREATE INDEX IF NOT EXISTS `idx_lessons_teacher_status` ON `lessons` (`teacher_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_submissions_student_status` ON `submissions` (`student_id`, `status`);

SET FOREIGN_KEY_CHECKS = 1;

-- Sample lesson data (optional)
INSERT INTO `lessons` (`title`, `description`, `content`, `teacher_id`, `is_activity`, `status`) 
SELECT 
    'Introduction to Environmental Science',
    'Learn the fundamentals of environmental science and its importance in our daily lives.',
    '<h2>Welcome to Environmental Science</h2><p>Environmental science is an interdisciplinary field that combines physical, biological, and information sciences to study the environment and solve environmental problems.</p><h3>Key Topics:</h3><ul><li>Ecosystems and biodiversity</li><li>Climate change</li><li>Pollution and waste management</li><li>Sustainable development</li></ul>',
    u.id,
    0,
    'published'
FROM `users` u WHERE u.role = 'teacher' LIMIT 1
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

INSERT INTO `lessons` (`title`, `description`, `content`, `teacher_id`, `is_activity`, `status`) 
SELECT 
    'Climate Change Impact Assessment',
    'Activity: Analyze the impact of climate change on local ecosystems.',
    '<h2>Climate Change Impact Assessment Activity</h2><p>In this activity, you will research and analyze the impact of climate change on a local ecosystem of your choice.</p><h3>Instructions:</h3><ol><li>Choose a local ecosystem (forest, wetland, coastal area, etc.)</li><li>Research current climate trends in your area</li><li>Identify specific impacts on flora and fauna</li><li>Propose mitigation strategies</li></ol><h3>Submission Requirements:</h3><ul><li>2-3 page report</li><li>Include at least 3 credible sources</li><li>Provide specific examples and data</li></ul>',
    u.id,
    1,
    'published'
FROM `users` u WHERE u.role = 'teacher' LIMIT 1
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);