-- db/migrations/004_workouts_and_sets.sql (MySQL 8.0 / MariaDB)

-- Control de días de rutina tachados por fecha/semana
CREATE TABLE IF NOT EXISTS `routine_check_ins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `routine_day_id` INT NOT NULL,
  `check_in_date` DATE NOT NULL,
  `week_number` INT NOT NULL,
  `year` INT NOT NULL,
  `is_completed` TINYINT(1) DEFAULT 1,
  `completed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_routine_day_date` (`user_id`, `routine_day_id`, `check_in_date`),
  INDEX `idx_check_ins_user_week` (`user_id`, `year`, `week_number`),
  INDEX `idx_check_ins_date` (`check_in_date`),
  CONSTRAINT `fk_checkins_account` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checkins_day` FOREIGN KEY (`routine_day_id`) REFERENCES `routine_days` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sesión de entrenamiento en vivo
CREATE TABLE IF NOT EXISTS `workout_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `routine_day_id` INT DEFAULT NULL,
  `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'in_progress',
  `total_volume_kg` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT DEFAULT NULL,
  INDEX `idx_workout_logs_user` (`user_id`, `started_at` DESC),
  CONSTRAINT `fk_workout_logs_account` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_workout_logs_day` FOREIGN KEY (`routine_day_id`) REFERENCES `routine_days` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial granular de series (Sets)
CREATE TABLE IF NOT EXISTS `workout_sets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `workout_log_id` INT NOT NULL,
  `exercise_id` INT NOT NULL,
  `set_number` INT NOT NULL,
  `weight_kg` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `reps` INT NOT NULL DEFAULT 0,
  `rpe` DECIMAL(3,1) DEFAULT 8.0,
  `rir` INT DEFAULT NULL,
  `is_warmup` TINYINT(1) DEFAULT 0,
  `is_completed` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_workout_sets_log` (`workout_log_id`, `exercise_id`),
  INDEX `idx_workout_sets_pr` (`exercise_id`, `weight_kg` DESC, `reps` DESC),
  CONSTRAINT `fk_workout_sets_log` FOREIGN KEY (`workout_log_id`) REFERENCES `workout_logs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_workout_sets_exercise` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
