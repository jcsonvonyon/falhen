<?php
/**
 * Database Configuration & Connection Management
 * Falhen Media PHP Application
 */

// Auto-load .env configuration if present
if (!function_exists('falhenLoadEnv')) {
    function falhenLoadEnv($envFile = null) {
        if ($envFile === null) {
            $envFile = dirname(__DIR__) . '/.env';
        }
        if (!file_exists($envFile) || !is_readable($envFile)) {
            return;
        }
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($key, $val) = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);
                if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                    (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                    $val = substr($val, 1, -1);
                }
                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV) && getenv($key) === false) {
                    putenv("{$key}={$val}");
                    $_ENV[$key] = $val;
                    $_SERVER[$key] = $val;
                }
            }
        }
    }
}
falhenLoadEnv();

$isCpanel = (strpos(__DIR__, 'henadinnovate') !== false || file_exists('/home/henadinnovate'));
$defaultDbName = $isCpanel ? 'henadinn_falhen_db' : 'falhen_db';
$defaultDbUser = $isCpanel ? 'henadinn_falhen_user' : 'root';
$defaultDbPass = $isCpanel ? 'FalhenMedia2026!#' : 'Oluwatosin25#';

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: $defaultDbName);
define('DB_USER', getenv('DB_USER') ?: $defaultDbUser);
define('DB_PASS', getenv('DB_PASS') ?: $defaultDbPass);

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            initDatabaseSchema($pdo);
        } catch (PDOException $e) {
            // In development, return graceful fallback object or raise clear message
            error_log("Database Connection Error: " . $e->getMessage());
            return false;
        }
    }
    return $pdo;
}

function initDatabaseSchema($pdo = null) {
    if (!$pdo) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return;
    static $initialized = false;
    if ($initialized) return;

    try {
        // 1. Ensure admin_users table exists with complete schema
        $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(100) NOT NULL UNIQUE,
            `email` VARCHAR(150) NOT NULL UNIQUE,
            `alt_email` VARCHAR(150) DEFAULT NULL,
            `password_hash` VARCHAR(255) NOT NULL,
            `full_name` VARCHAR(150) NOT NULL DEFAULT 'Staff Member',
            `role` VARCHAR(50) DEFAULT 'Staff',
            `is_owner` TINYINT(1) DEFAULT 0,
            `status` ENUM('active', 'pending_activation', 'suspended') DEFAULT 'active',
            `reset_otp` VARCHAR(64) DEFAULT NULL,
            `reset_token` VARCHAR(255) DEFAULT NULL,
            `reset_expires` DATETIME DEFAULT NULL,
            `remember_token` VARCHAR(255) DEFAULT NULL,
            `last_login_at` DATETIME DEFAULT NULL,
            `last_login_ip` VARCHAR(45) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_admin_users_email_status` (`email`, `status`),
            INDEX `idx_admin_users_username_status` (`username`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 2. Perform non-destructive migrations for existing installations
        $columns = [];
        try {
            $colStmt = $pdo->query("SHOW COLUMNS FROM `admin_users`");
            $columns = $colStmt ? $colStmt->fetchAll(PDO::FETCH_COLUMN) : [];
        } catch (Exception $e) {}

        if (!in_array('alt_email', $columns)) {
            @$pdo->exec("ALTER TABLE `admin_users` ADD COLUMN `alt_email` VARCHAR(150) DEFAULT NULL AFTER `email`");
        }
        if (!in_array('full_name', $columns)) {
            @$pdo->exec("ALTER TABLE `admin_users` ADD COLUMN `full_name` VARCHAR(150) NOT NULL DEFAULT 'Staff Member' AFTER `password_hash`");
        }
        if (!in_array('is_owner', $columns)) {
            @$pdo->exec("ALTER TABLE `admin_users` ADD COLUMN `is_owner` TINYINT(1) DEFAULT 0 AFTER `role`");
        }
        if (!in_array('status', $columns)) {
            @$pdo->exec("ALTER TABLE `admin_users` ADD COLUMN `status` ENUM('active', 'pending_activation', 'suspended') DEFAULT 'active' AFTER `is_owner`");
        }
        if (!in_array('reset_otp', $columns)) {
            @$pdo->exec("ALTER TABLE `admin_users` ADD COLUMN `reset_otp` VARCHAR(64) DEFAULT NULL AFTER `status`");
        }
        if (!in_array('last_login_at', $columns)) {
            @$pdo->exec("ALTER TABLE `admin_users` ADD COLUMN `last_login_at` DATETIME DEFAULT NULL AFTER `remember_token`");
        }
        if (!in_array('last_login_ip', $columns)) {
            @$pdo->exec("ALTER TABLE `admin_users` ADD COLUMN `last_login_ip` VARCHAR(45) DEFAULT NULL AFTER `last_login_at`");
        }

        // 3. Ensure Henry Falonipe exists as Owner / Super Admin
        $checkStmt = $pdo->prepare("SELECT id FROM `admin_users` WHERE LOWER(email) = 'henry.falonipe@falhen.com' OR username = 'henry.falonipe' LIMIT 1");
        $checkStmt->execute();
        if (!$checkStmt->fetch()) {
            $defaultHash = password_hash('Password123#', PASSWORD_DEFAULT);
            $insStmt = $pdo->prepare("INSERT INTO `admin_users` (`username`, `email`, `alt_email`, `password_hash`, `role`, `full_name`, `is_owner`, `status`)
                VALUES ('henry.falonipe', 'henry.falonipe@falhen.com', 'henry@falhen.com', ?, 'Owner / Super Admin', 'Henry Falonipe', 1, 'active')");
            $insStmt->execute([$defaultHash]);
        } else {
            @$pdo->exec("UPDATE `admin_users` SET `role` = 'Owner / Super Admin', `is_owner` = 1, `status` = 'active', `alt_email` = 'henry@falhen.com' WHERE LOWER(email) = 'henry.falonipe@falhen.com' OR username = 'henry.falonipe'");
        }

        // 4. Ensure site_settings, client_download_logs, and system_backups tables exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS `site_settings` (
            `setting_key` VARCHAR(100) NOT NULL PRIMARY KEY,
            `setting_value` LONGTEXT NOT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_site_settings_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `client_download_logs` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `client_name` VARCHAR(150) NOT NULL DEFAULT 'Client',
            `client_email` VARCHAR(150) NOT NULL DEFAULT '',
            `client_phone` VARCHAR(60) DEFAULT NULL,
            `album_id` INT NOT NULL DEFAULT 0,
            `album_title` VARCHAR(255) NOT NULL DEFAULT '',
            `download_type` VARCHAR(100) NOT NULL DEFAULT 'ZIP Archive',
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `user_agent` TEXT DEFAULT NULL,
            `downloaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_dl_email` (`client_email`),
            INDEX `idx_dl_album` (`album_id`),
            INDEX `idx_dl_date` (`downloaded_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `system_backups` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `backup_type` VARCHAR(50) NOT NULL DEFAULT 'auto_deploy',
            `filename` VARCHAR(255) NOT NULL,
            `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
            `items_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `notes` TEXT DEFAULT NULL,
            `created_by` VARCHAR(80) NOT NULL DEFAULT 'System',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_backup_type` (`backup_type`),
            INDEX `idx_backup_date` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 5. Run additional project DDL if present
        $schemaFile = __DIR__ . '/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            if (!empty($sql)) {
                $pdo->exec($sql);
            }
        }

        $initialized = true;
    } catch (Exception $e) {
        error_log("Schema Init Warning: " . $e->getMessage());
    }
}
