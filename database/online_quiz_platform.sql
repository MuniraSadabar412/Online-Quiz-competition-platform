-- ========================================================
-- ONLINE QUIZ COMPETITION PLATFORM
-- Database Schema: online_quiz_platform
-- Compatible with MySQL 5.7+, MySQL 8.0+, and MariaDB 10.3+
-- ========================================================

CREATE DATABASE IF NOT EXISTS `online_quiz_platform` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `online_quiz_platform`;

-- --------------------------------------------------------
-- Table: users
-- Roles: 'student', 'admin'
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: quizzes
-- Status: 'draft', 'published', 'inactive'
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `quizzes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `category` VARCHAR(100) NOT NULL,
  `difficulty` ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'medium',
  `duration_minutes` INT UNSIGNED NOT NULL DEFAULT 15,
  `status` ENUM('draft', 'published', 'inactive') NOT NULL DEFAULT 'draft',
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_quizzes_status` (`status`),
  INDEX `idx_quizzes_category` (`category`),
  INDEX `idx_quizzes_difficulty` (`difficulty`),
  CONSTRAINT `fk_quizzes_created_by` FOREIGN KEY (`created_by`) 
    REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: questions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `questions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quiz_id` INT UNSIGNED NOT NULL,
  `question_text` TEXT NOT NULL,
  `marks` INT UNSIGNED NOT NULL DEFAULT 1,
  `question_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_questions_quiz_id` (`quiz_id`),
  CONSTRAINT `fk_questions_quiz_id` FOREIGN KEY (`quiz_id`) 
    REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: options
-- Four options per question; exactly one marked is_correct = 1
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `options` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` INT UNSIGNED NOT NULL,
  `option_text` TEXT NOT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_options_question_id` (`question_id`),
  CONSTRAINT `fk_options_question_id` FOREIGN KEY (`question_id`) 
    REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: attempts
-- Single attempt per student per quiz enforced by unique constraint
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `quiz_id` INT UNSIGNED NOT NULL,
  `started_at` DATETIME NOT NULL,
  `deadline_at` DATETIME NOT NULL,
  `submitted_at` DATETIME NULL,
  `status` ENUM('in_progress', 'completed', 'expired') NOT NULL DEFAULT 'in_progress',
  `score` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `total_marks` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `time_taken_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_quiz_attempt` (`user_id`, `quiz_id`),
  INDEX `idx_attempts_quiz_score` (`quiz_id`, `score` DESC, `time_taken_seconds` ASC),
  INDEX `idx_attempts_user_id` (`user_id`),
  INDEX `idx_attempts_status` (`status`),
  CONSTRAINT `fk_attempts_user_id` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_attempts_quiz_id` FOREIGN KEY (`quiz_id`) 
    REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: attempt_answers
-- Records each student's response to an individual question
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attempt_answers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attempt_id` INT UNSIGNED NOT NULL,
  `question_id` INT UNSIGNED NOT NULL,
  `selected_option_id` INT UNSIGNED NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  `marks_awarded` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attempt_question` (`attempt_id`, `question_id`),
  INDEX `idx_attempt_answers_attempt` (`attempt_id`),
  CONSTRAINT `fk_attempt_answers_attempt` FOREIGN KEY (`attempt_id`) 
    REFERENCES `attempts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_attempt_answers_question` FOREIGN KEY (`question_id`) 
    REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_attempt_answers_option` FOREIGN KEY (`selected_option_id`) 
    REFERENCES `options` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
