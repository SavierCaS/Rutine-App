-- db/migrations/005_nutrition.sql (MySQL 8.0 / MariaDB)

CREATE TABLE IF NOT EXISTS `nutrition_goals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `daily_calories` INT NOT NULL,
  `protein_g` INT NOT NULL,
  `carbs_g` INT NOT NULL,
  `fats_g` INT NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_nutrition_goals_user` (`user_id`, `is_active`),
  CONSTRAINT `fk_nutrition_goals_account` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nutrition_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `log_date` DATE NOT NULL,
  `water_ml` INT NOT NULL DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  UNIQUE KEY `uq_user_nutrition_date` (`user_id`, `log_date`),
  INDEX `idx_nutrition_logs_date` (`user_id`, `log_date`),
  CONSTRAINT `fk_nutrition_logs_account` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `meal_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nutrition_log_id` INT NOT NULL,
  `meal_type` VARCHAR(20) NOT NULL,       -- 'desayuno', 'almuerzo', 'cena', 'snack'
  `food_name` VARCHAR(150) NOT NULL,
  `portion_amount` DECIMAL(6,2) DEFAULT NULL,
  `portion_unit` VARCHAR(30) DEFAULT 'g', -- 'g', 'ml', 'unidad'
  `calories` INT NOT NULL,
  `protein_g` DECIMAL(5,1) DEFAULT 0.0,
  `carbs_g` DECIMAL(5,1) DEFAULT 0.0,
  `fats_g` DECIMAL(5,1) DEFAULT 0.0,
  `logged_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_meal_items_log` (`nutrition_log_id`, `meal_type`),
  CONSTRAINT `fk_meal_items_log` FOREIGN KEY (`nutrition_log_id`) REFERENCES `nutrition_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
