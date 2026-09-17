-- db/migrations/003_routines.sql (MySQL 8.0 / MariaDB)

CREATE TABLE IF NOT EXISTS `routines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_routines_user_active` (`user_id`, `is_active`),
  CONSTRAINT `fk_routines_account` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `routine_days` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `routine_id` INT NOT NULL,
  `day_name` VARCHAR(50) NOT NULL,
  `day_order` INT NOT NULL,
  `is_rest_day` TINYINT(1) DEFAULT 0,
  `suggested_time` TIME DEFAULT NULL,
  INDEX `idx_routine_days_order` (`routine_id`, `day_order`),
  CONSTRAINT `fk_routine_days_routine` FOREIGN KEY (`routine_id`) REFERENCES `routines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `routine_exercises` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `routine_day_id` INT NOT NULL,
  `exercise_id` INT NOT NULL,
  `order_index` INT NOT NULL,
  `target_sets` INT NOT NULL DEFAULT 3,
  `target_reps` VARCHAR(20) NOT NULL DEFAULT '10-12',
  `target_rpe` DECIMAL(3,1) DEFAULT 8.0,
  `rest_seconds` INT DEFAULT 90,
  INDEX `idx_routine_exercises_day` (`routine_day_id`, `order_index`),
  CONSTRAINT `fk_routine_exercises_day` FOREIGN KEY (`routine_day_id`) REFERENCES `routine_days` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_routine_exercises_exercise` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
