-- ==============================================================================
-- KYNAP FITNESS PLATFORM - MASTER UNIFIED DATABASE SCHEMA (schema.sql)
-- ==============================================================================
-- Motor: MySQL 8.0+ / 9.x / MariaDB 10.4+
-- Base de datos: kynap_db
-- Descripción:
--   Consolidación unificada de las 5 migraciones en un único script maestro.
--   Estructurado en estricto orden de dependencias para garantizar 0 errores de
--   claves foráneas (Error 1215 / Error 1452).
-- ==============================================================================

-- 1. Configuraciones de seguridad para importación masiva en MySQL Workbench
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

-- 2. Creación y selección de la base de datos
CREATE DATABASE IF NOT EXISTS `kynap_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `kynap_db`;

-- 3. Limpieza segura previa (en orden inverso de dependencias)
DROP VIEW IF EXISTS `meal_items`;
DROP VIEW IF EXISTS `nutrition_logs`;
DROP VIEW IF EXISTS `usuarios`;
DROP VIEW IF EXISTS `accounts`;

DROP TABLE IF EXISTS `logged_meals`;
DROP TABLE IF EXISTS `meal_items`;
DROP TABLE IF EXISTS `daily_nutrition_logs`;
DROP TABLE IF EXISTS `nutrition_logs`;
DROP TABLE IF EXISTS `nutrition_goals`;
DROP TABLE IF EXISTS `workout_sets`;
DROP TABLE IF EXISTS `workout_logs`;
DROP TABLE IF EXISTS `routine_check_ins`;
DROP TABLE IF EXISTS `routine_exercises`;
DROP TABLE IF EXISTS `routine_days`;
DROP TABLE IF EXISTS `routines`;
DROP TABLE IF EXISTS `exercises`;
DROP TABLE IF EXISTS `user_biometrics`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `accounts`;
DROP TABLE IF EXISTS `users`;

-- ==============================================================================
-- TABLAS INDEPENDIENTES (NIVEL 1)
-- ==============================================================================

-- 1. Tabla de Usuarios y Credenciales de Acceso
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `imagen_user` VARCHAR(255) DEFAULT 'iconos/profile.svg',
  `chat_activado` TINYINT(1) DEFAULT 0,
  `mensajes_enviados` INT DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_users_email` (`email`),
  INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Catálogo Maestro de Ejercicios
CREATE TABLE `exercises` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
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
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_exercises_slug` (`slug`),
  INDEX `idx_exercises_muscle` (`muscle_group`),
  INDEX `idx_exercises_equipment` (`equipment`),
  INDEX `idx_exercises_custom` (`is_custom`, `created_by_user_id`),
  CONSTRAINT `fk_exercises_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- TABLAS DEPENDIENTES DE USUARIO (NIVEL 2)
-- ==============================================================================

-- 3. Perfil Biométrico y Parámetros Metabólicos
CREATE TABLE `user_biometrics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `peso` DECIMAL(5,2) NOT NULL DEFAULT 70.00,
  `altura` DECIMAL(5,2) NOT NULL DEFAULT 170.00,
  `edad` INT NOT NULL DEFAULT 25,
  `sexo` VARCHAR(20) DEFAULT 'masculino',
  `objetivo` VARCHAR(50) DEFAULT 'ganar_musculo',
  `nivel_actividad` VARCHAR(50) DEFAULT 'moderado',
  `agua` INT NOT NULL DEFAULT 3,
  `condiciones` TEXT DEFAULT NULL,
  `alergias` TEXT DEFAULT NULL,
  `preferencia` VARCHAR(255) DEFAULT 'cualquierCosa',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_biometrics_user` (`user_id`),
  CONSTRAINT `fk_biometrics_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Rutinas de Entrenamiento
CREATE TABLE `routines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_routines_user_active` (`user_id`, `is_active`),
  CONSTRAINT `fk_routines_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Metas y Objetivos Nutricionales Diarios
CREATE TABLE `nutrition_goals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `daily_calories` INT NOT NULL DEFAULT 2200,
  `protein_g` INT NOT NULL DEFAULT 150,
  `carbs_g` INT NOT NULL DEFAULT 230,
  `fats_g` INT NOT NULL DEFAULT 65,
  `water_ml` INT NOT NULL DEFAULT 2500,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_nutrition_goals_user` (`user_id`, `is_active`),
  CONSTRAINT `fk_nutrition_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Registro Diario de Nutrición e Hidratación
CREATE TABLE `daily_nutrition_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `log_date` DATE NOT NULL,
  `water_ml` INT NOT NULL DEFAULT 0,
  `calories_target` INT DEFAULT 2200,
  `water_target_ml` INT DEFAULT 2500,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_nutrition_date` (`user_id`, `log_date`),
  INDEX `idx_nutrition_logs_date` (`user_id`, `log_date`),
  CONSTRAINT `fk_nutrition_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- TABLAS DE ESTRUCTURA Y DETALLE (NIVEL 3)
-- ==============================================================================

-- 7. Días de la Rutina
CREATE TABLE `routine_days` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `routine_id` INT NOT NULL,
  `day_name` VARCHAR(50) NOT NULL,
  `day_key` VARCHAR(20) NOT NULL,
  `day_order` INT NOT NULL,
  `is_rest_day` TINYINT(1) DEFAULT 0,
  `suggested_time` TIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_routine_days_order` (`routine_id`, `day_order`),
  INDEX `idx_routine_days_key` (`routine_id`, `day_key`),
  CONSTRAINT `fk_routine_days_routine` FOREIGN KEY (`routine_id`) REFERENCES `routines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Ejercicios Programados por Día
CREATE TABLE `routine_exercises` (
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

-- 9. Check-ins de Adherencia a la Rutina Semanal
CREATE TABLE `routine_check_ins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `day_id` VARCHAR(50) NOT NULL,
  `routine_day_id` INT DEFAULT NULL,
  `check_in_date` DATE NOT NULL,
  `week_number` INT NOT NULL,
  `year` INT NOT NULL,
  `is_completed` TINYINT(1) DEFAULT 1,
  `completed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_day_date` (`user_id`, `day_id`, `check_in_date`),
  INDEX `idx_check_ins_user_week` (`user_id`, `year`, `week_number`),
  INDEX `idx_check_ins_date` (`check_in_date`),
  CONSTRAINT `fk_checkins_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checkins_day` FOREIGN KEY (`routine_day_id`) REFERENCES `routine_days` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Sesión de Entrenamiento en Vivo (Workouts)
CREATE TABLE `workout_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `routine_day_id` INT DEFAULT NULL,
  `workout_date` DATE NOT NULL DEFAULT (CURRENT_DATE),
  `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'in_progress',
  `total_volume_kg` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT DEFAULT NULL,
  INDEX `idx_workout_logs_user` (`user_id`, `started_at` DESC),
  CONSTRAINT `fk_workout_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_workout_logs_day` FOREIGN KEY (`routine_day_id`) REFERENCES `routine_days` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- TABLAS DE REGISTRO GRANULAR (NIVEL 4)
-- ==============================================================================

-- 11. Series Ejecutadas en el Entrenamiento (Sets)
CREATE TABLE `workout_sets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `workout_log_id` INT NOT NULL,
  `exercise_id` INT DEFAULT NULL,
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
  CONSTRAINT `fk_workout_sets_exercise` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Alimentos y Comidas Registradas del Día
CREATE TABLE `logged_meals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nutrition_log_id` INT NOT NULL,
  `category` VARCHAR(30) NOT NULL DEFAULT 'Almuerzo',
  `food_name` VARCHAR(150) NOT NULL,
  `portion_amount` DECIMAL(6,2) DEFAULT NULL,
  `portion_unit` VARCHAR(30) DEFAULT 'g',
  `calories` INT NOT NULL DEFAULT 0,
  `protein_g` DECIMAL(5,1) DEFAULT 0.0,
  `carbs_g` DECIMAL(5,1) DEFAULT 0.0,
  `fats_g` DECIMAL(5,1) DEFAULT 0.0,
  `logged_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_logged_meals_log` (`nutrition_log_id`, `category`),
  CONSTRAINT `fk_logged_meals_log` FOREIGN KEY (`nutrition_log_id`) REFERENCES `daily_nutrition_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- VISTAS DE COMPATIBILIDAD (Garantizan retrocompatibilidad absoluta)
-- ==============================================================================
CREATE OR REPLACE VIEW `accounts` AS SELECT * FROM `users`;
CREATE OR REPLACE VIEW `usuarios` AS SELECT * FROM `user_biometrics`;
CREATE OR REPLACE VIEW `nutrition_logs` AS SELECT * FROM `daily_nutrition_logs`;
CREATE OR REPLACE VIEW `meal_items` AS SELECT 
  id, 
  nutrition_log_id, 
  category AS meal_type, 
  food_name, 
  portion_amount, 
  portion_unit, 
  calories, 
  protein_g, 
  carbs_g, 
  fats_g, 
  logged_at 
FROM `logged_meals`;

-- ==============================================================================
-- POBLACIÓN DE DATOS INICIALES (SEEDS)
-- ==============================================================================

-- Catálogo de ejercicios estándar de Kynap con assets reales
INSERT INTO `exercises` (`name`, `slug`, `muscle_group`, `secondary_muscles`, `equipment`, `media_url`, `instructions`) VALUES
('Sentadillas', 'squats', 'cuadriceps', 'gluteos,pantorrillas', 'corporal', 'public/assets/exercises/Squats.gif', 'Pies al ancho de hombros, desciende flexionando cadera y rodillas hasta romper el paralelo con la espalda recta.'),
('Puente de Glúteos', 'butt-bridge', 'gluteos', 'isquiotibiales', 'corporal', 'public/assets/exercises/Butt bridge.gif', 'Túmbate boca arriba y eleva la pelvis contrayendo glúteos fuertemente 2 segundos en el punto más alto.'),
('Crunch Abdominal', 'crunch-abdominales', 'abdomen', '', 'corporal', 'public/assets/exercises/Crunch Abdominales.gif', 'Flexión controlada de tronco expulsando el aire arriba sin tirar del cuello.'),
('Crunch de Bicicleta', 'crunch-bicicleta', 'abdomen', 'oblicuos', 'corporal', 'public/assets/exercises/Crunch de bicicleta.gif', 'Alterna codo hacia la rodilla opuesta manteniendo la zona lumbar pegada al suelo.'),
('Crunch Inverso', 'crunch-inverso', 'abdomen', '', 'corporal', 'public/assets/exercises/Crunch inverso.gif', 'Lleva las rodillas hacia el pecho elevando suavemente la pelvis del suelo.'),
('Burpees', 'burpees', 'cardio', 'pecho,cuadriceps,hombros', 'corporal', 'public/assets/exercises/Burpess.gif', 'Cuerpo al suelo con flexión completa de pecho y salto explosivo vertical.'),
('Elevación de Rodillas', 'elevacion-rodillas', 'cardio', 'abdomen', 'corporal', 'public/assets/exercises/Elevacion de rodillas.gif', 'Carrera en el sitio elevando rodillas a 90 grados con ritmo constante.'),
('Elevación de Piernas para Glúteos', 'elevacion-piernas-gluteos', 'gluteos', 'isquiotibiales', 'corporal', 'public/assets/exercises/Elevaciones de piernas para gluteos.gif', 'Patada de glúteo controlada en cuatro apoyos manteniendo la pelvis neutra.'),
('Mountain Climbers', 'mountain-climbers', 'abdomen', 'cardio,hombros', 'corporal', 'public/assets/exercises/Escalada de montaña.gif', 'Posición de plancha alta alternando rodillas al pecho con velocidad y control.'),
('Estocada Hacia Atrás', 'estocada-hacia-atras', 'cuadriceps', 'gluteos', 'corporal', 'public/assets/exercises/Estocada hacia atras.gif', 'Paso largo hacia atrás descendiendo rodilla a 90 grados manteniendo torso erguido.'),
('Flexiones con Inclinación', 'flexiones-inclinadas', 'pecho', 'triceps,hombros', 'corporal', 'public/assets/exercises/Flexiones con inclinación.gif', 'Manos en superficie elevada manteniendo alineación corporal recta en todo el rango.'),
('Step en Silla', 'step-silla', 'cuadriceps', 'gluteos', 'silla', 'public/assets/exercises/Step en silla.gif', 'Sube apoyando toda la planta del pie en la silla y empuja extendiendo la pierna.'),
('Plancha Tradicional', 'plancha-tradicional', 'abdomen', 'hombros,gluteos', 'corporal', 'public/assets/exercises/Tablon.jpg', 'Apoyo en antebrazos y puntas de pie manteniendo abdomen y glúteos contraídos.'),
('Fondos de Tríceps en Silla', 'triceps-en-silla', 'triceps', 'pecho,hombros', 'silla', 'public/assets/exercises/Triceps en silla.gif', 'Desciende flexionando los codos hacia atrás a 90 grados junto al cuerpo.'),
('Twist Ruso', 'twist-ruso', 'abdomen', 'oblicuos', 'corporal', 'public/assets/exercises/Twist Ruso.gif', 'Torso inclinado a 45 grados rotando suavemente a ambos lados.'),
('Zancada Frontal', 'zancada-frontal', 'cuadriceps', 'gluteos', 'corporal', 'public/assets/exercises/Zancada Frontal.gif', 'Paso amplio al frente flexionando ambas piernas a 90 grados con estabilidad.')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `instructions` = VALUES(`instructions`);

-- Usuario Demo verificado (Password: '123456')
-- Hash generado mediante BCRYPT con coste 12
INSERT INTO `users` (`id`, `nombre`, `email`, `password`, `imagen_user`, `chat_activado`, `mensajes_enviados`) VALUES
(1, 'Savier CaS', 'atleta@kynap.com', '$2y$12$Z0vK4hB0pQnF8041o7tGheV1y0C4LwZk03U65WkWG4rE1f0tT81iC', 'icon_users/Boy.jpg', 1, 0)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`), `password` = VALUES(`password`);

-- Biometría inicial para el usuario demo
INSERT INTO `user_biometrics` (`user_id`, `peso`, `altura`, `edad`, `sexo`, `objetivo`, `nivel_actividad`, `agua`) VALUES
(1, 75.00, 175.00, 25, 'masculino', 'ganar_musculo', 'moderado', 3)
ON DUPLICATE KEY UPDATE `peso` = VALUES(`peso`), `altura` = VALUES(`altura`);

-- Metas nutricionales del usuario demo
INSERT INTO `nutrition_goals` (`user_id`, `daily_calories`, `protein_g`, `carbs_g`, `fats_g`, `water_ml`, `is_active`) VALUES
(1, 2350, 160, 250, 70, 2600, 1)
ON DUPLICATE KEY UPDATE `daily_calories` = VALUES(`daily_calories`);

-- 15. Restaurar variables de entorno MySQL
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
