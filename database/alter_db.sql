-- Database alterations for LMS system
-- Add file_path column to lessons table

ALTER TABLE `lessons` ADD COLUMN `file_path` VARCHAR(500) DEFAULT NULL AFTER `is_activity`;

-- Add index for file_path for better performance
CREATE INDEX IF NOT EXISTS `idx_lessons_file_path` ON `lessons` (`file_path`);

-- Create content table for storing lesson content separately
CREATE TABLE IF NOT EXISTS `content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lesson_id` int(11) NOT NULL,
  `content_type` enum('text','html','markdown') NOT NULL DEFAULT 'html',
  `content` longtext NOT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_content_lesson_id` (`lesson_id`),
  KEY `idx_content_version` (`lesson_id`, `version`),
  CONSTRAINT `fk_content_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;