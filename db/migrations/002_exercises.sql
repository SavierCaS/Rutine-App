-- db/migrations/002_exercises.sql (MySQL 8.0 / MariaDB)
use kynap_db;
CREATE TABLE IF NOT EXISTS `exercises` (
  `id` int AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `muscle_group` VARCHAR(50) NOT NULL,
  `secondary_muscles` VARCHAR(255) DEFAULT NULL,
  `equipment` VARCHAR(50) NOT NULL DEFAULT 'corporal',
  `media_url` TEXT DEFAULT NULL,
  `instructions` TEXT DEFAULT NULL,
  `is_custom` TINYINT(1) DEFAULT 0,
  `created_by_user_id` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_exercises_slug` (`slug`),
  INDEX `idx_exercises_muscle` (`muscle_group`),
  INDEX `idx_exercises_equipment` (`equipment`),
  INDEX `idx_exercises_custom` (`is_custom`, `created_by_user_id`),
  CONSTRAINT `fk_exercises_account` FOREIGN KEY (`created_by_user_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
