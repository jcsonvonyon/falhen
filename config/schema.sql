-- ============================================================
-- Falhen Media Database Schema Migration & DDL Initialization
-- Engine: InnoDB | Charset: utf8mb4_unicode_ci
-- ============================================================

-- 1. Staff Accounts Table
CREATE TABLE IF NOT EXISTS `staff_accounts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(150) NOT NULL,
    `username` VARCHAR(80) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `alt_email` VARCHAR(150) DEFAULT NULL,
    `password_hash` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('Super Admin', 'HR Manager', 'Operations Specialist', 'Staff', 'Content Editor') NOT NULL DEFAULT 'Staff',
    `status` ENUM('active', 'pending_activation', 'suspended') NOT NULL DEFAULT 'pending_activation',
    `activation_status` ENUM('activated', 'unverified') NOT NULL DEFAULT 'unverified',
    `activation_code` VARCHAR(10) DEFAULT NULL,
    `avatar` VARCHAR(500) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `dob` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    INDEX `idx_staff_email` (`email`),
    INDEX `idx_staff_status` (`status`),
    INDEX `idx_staff_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Staff Attendance Logs Table
CREATE TABLE IF NOT EXISTS `staff_attendance` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `staff_id` INT UNSIGNED DEFAULT NULL,
    `username` VARCHAR(80) NOT NULL,
    `clock_in` DATETIME NOT NULL,
    `clock_out` DATETIME DEFAULT NULL,
    `shift_date` DATE NOT NULL,
    `status` ENUM('present', 'late', 'half_day', 'absent') NOT NULL DEFAULT 'present',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_attendance_username_date` (`username`, `shift_date`),
    INDEX `idx_attendance_shift_date` (`shift_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Leave Applications Table
CREATE TABLE IF NOT EXISTS `leave_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `staff_id` INT UNSIGNED DEFAULT NULL,
    `username` VARCHAR(80) NOT NULL,
    `leave_type` VARCHAR(50) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `reason` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `reviewed_by` VARCHAR(80) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    INDEX `idx_leave_username` (`username`),
    INDEX `idx_leave_status_dates` (`status`, `start_date`, `end_date`),
    INDEX `idx_leave_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Onboarding Checklist Progress Table
CREATE TABLE IF NOT EXISTS `onboarding_checklist` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(80) NOT NULL,
    `task_key` VARCHAR(80) NOT NULL,
    `task_name` VARCHAR(150) NOT NULL,
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `completed_at` DATETIME DEFAULT NULL,
    `document_path` VARCHAR(500) DEFAULT NULL,
    UNIQUE KEY `uk_username_task` (`username`, `task_key`),
    INDEX `idx_onboarding_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Company Announcements Table
CREATE TABLE IF NOT EXISTS `announcements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'General',
    `created_by` VARCHAR(80) DEFAULT 'Admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    INDEX `idx_announcements_category` (`category`),
    INDEX `idx_announcements_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Structured Database Audit Logs Table
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `actor_username` VARCHAR(80) NOT NULL,
    `action_type` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(80) NOT NULL,
    `entity_id` VARCHAR(100) DEFAULT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_actor` (`actor_username`),
    INDEX `idx_audit_action` (`action_type`),
    INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
