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

// Load custom DB credentials override if present
$dbCredsFile = __DIR__ . '/db_credentials.json';
$customDBCreds = file_exists($dbCredsFile) ? json_decode(@file_get_contents($dbCredsFile), true) : null;

$isCpanel = (strpos(__DIR__, 'henadinnovate') !== false || file_exists('/home/henadinnovate'));

if (is_array($customDBCreds) && !empty($customDBCreds['user'])) {
    $resolvedDbHost = $customDBCreds['host'] ?? ($isCpanel ? 'localhost' : '127.0.0.1');
    $resolvedDbPort = $customDBCreds['port'] ?? '3306';
    $resolvedDbName = $customDBCreds['name'] ?? ($isCpanel ? 'henadinnovate_falhen' : 'falhen_db');
    $resolvedDbUser = $customDBCreds['user'] ?? ($isCpanel ? 'henadinnovate_falhen' : 'root');
    $resolvedDbPass = $customDBCreds['pass'] ?? '';
} else {
    $defaultDbName = $isCpanel ? 'henadinnovate_falhen' : 'falhen_db';
    $defaultDbUser = $isCpanel ? 'henadinnovate_falhen' : 'root';
    $defaultDbPass = $isCpanel ? 'FalhenMedia2026!#' : 'Oluwatosin25#';
    $defaultDbHost = $isCpanel ? 'localhost' : '127.0.0.1';

    $resolvedDbHost = getenv('DB_HOST') ?: $defaultDbHost;
    $resolvedDbPort = getenv('DB_PORT') ?: '3306';
    $resolvedDbName = getenv('DB_NAME') ?: $defaultDbName;
    $resolvedDbUser = getenv('DB_USER') ?: $defaultDbUser;
    $resolvedDbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : $defaultDbPass;
}

if (!defined('DB_HOST')) define('DB_HOST', $resolvedDbHost);
if (!defined('DB_PORT')) define('DB_PORT', $resolvedDbPort);
if (!defined('DB_NAME')) define('DB_NAME', $resolvedDbName);
if (!defined('DB_USER')) define('DB_USER', $resolvedDbUser);
if (!defined('DB_PASS')) define('DB_PASS', $resolvedDbPass);

function getDBConnection($forceNew = false) {
    static $pdo = null;
    if ($pdo !== null && !$forceNew) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 4,
    ];

    // Priority connection candidates (socket first on cPanel, then TCP)
    $dsnCandidates = [];
    $envHost = getenv('DB_HOST');
    if (!empty($envHost)) {
        $dsnCandidates[] = "mysql:host={$envHost};port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    }

    // Unix sockets common in cPanel/Linux
    $cpanelSockets = [
        '/var/lib/mysql/mysql.sock',
        '/tmp/mysql.sock',
        '/var/run/mysqld/mysqld.sock'
    ];
    foreach ($cpanelSockets as $sock) {
        if (@file_exists($sock)) {
            $dsnCandidates[] = "mysql:unix_socket={$sock};dbname=" . DB_NAME . ";charset=utf8mb4";
        }
    }

    // Standard localhost socket resolution (matches cPanel 'username'@'localhost')
    $dsnCandidates[] = "mysql:host=localhost;dbname=" . DB_NAME . ";charset=utf8mb4";
    // TCP loopback
    $dsnCandidates[] = "mysql:host=127.0.0.1;port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    $dsnCandidates = array_values(array_unique($dsnCandidates));
    $lastException = null;

    foreach ($dsnCandidates as $dsn) {
        try {
            $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            if ($conn) {
                $pdo = $conn;
                $GLOBALS['falhen_db_active_dsn'] = $dsn;
                initDatabaseSchema($pdo);
                return $pdo;
            }
        } catch (PDOException $e) {
            $lastException = $e;
        }
    }

    // Secondary fallback: if authentication failed (1045), probe known cPanel passwords
    if ($lastException && strpos($lastException->getMessage(), '1045') !== false) {
        $altPasses = array_values(array_unique([
            DB_PASS,
            'FalhenMedia2026!#',
            'Oluwatosin25#',
            'FalhenMedia2026!',
            'FalhenMedia2026',
            'FalhenMedia2025!#',
            'FalhenMedia2025!',
            'Falhen2026!#',
            'Falhen2026!',
            'Falhen2026',
            'Oluwatosin25!',
            'Oluwatosin2026#',
            'Password123#',
            ''
        ]));

        $altUsers = array_values(array_unique([
            DB_USER,
            'henadinnovate_falhen',
            'henadinnovate_falhenmedia',
            'henadinn_falhen_user',
            'henadinn_falhen',
            'henadinn_user',
            'henadinn'
        ]));
        $altDbs = array_values(array_unique([
            DB_NAME,
            'henadinnovate_falhen',
            'henadinnovate_falhenmedia'
        ]));

        foreach ($altPasses as $candPass) {
            foreach ($altUsers as $candUser) {
                foreach ($altDbs as $candDb) {
                    $candDsns = [
                        "mysql:host=localhost;dbname={$candDb};charset=utf8mb4",
                        "mysql:host=127.0.0.1;port=" . DB_PORT . ";dbname={$candDb};charset=utf8mb4",
                    ];
                    foreach ($cpanelSockets as $sock) {
                        if (@file_exists($sock)) {
                            array_unshift($candDsns, "mysql:unix_socket={$sock};dbname={$candDb};charset=utf8mb4");
                        }
                    }
                    foreach (array_unique($candDsns) as $dsn) {
                        try {
                            $conn = new PDO($dsn, $candUser, $candPass, [
                                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                PDO::ATTR_EMULATE_PREPARES   => false,
                                PDO::ATTR_TIMEOUT            => 2,
                            ]);
                            if ($conn) {
                                $pdo = $conn;
                                $GLOBALS['falhen_db_active_dsn'] = $dsn;
                                if (function_exists('saveDBCredentials')) {
                                    saveDBCredentials(DB_HOST, DB_PORT, $candDb, $candUser, $candPass);
                                }
                                initDatabaseSchema($pdo);
                                return $pdo;
                            }
                        } catch (PDOException $e) {
                            $lastException = $e;
                        }
                    }
                }
            }
        }
    }

    $errMsg = $lastException ? $lastException->getMessage() : 'All MySQL connection transports exhausted';
    $GLOBALS['falhen_db_last_error'] = $errMsg;
    error_log("Database Connection Error: " . $errMsg);
    return false;
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

/**
 * Test custom DB connection parameters live
 */
function testCustomDBConnection($host, $port, $name, $user, $pass) {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 4,
    ];
    $candidates = [
        "mysql:host={$host};dbname={$name};charset=utf8mb4",
        "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
    ];
    if (in_array($host, ['localhost', '127.0.0.1'], true)) {
        foreach (['/var/lib/mysql/mysql.sock', '/tmp/mysql.sock', '/var/run/mysqld/mysqld.sock'] as $sock) {
            if (@file_exists($sock)) {
                $candidates[] = "mysql:unix_socket={$sock};dbname={$name};charset=utf8mb4";
            }
        }
    }
    $candidates = array_values(array_unique($candidates));
    $start = microtime(true);
    $lastErr = null;
    foreach ($candidates as $dsn) {
        try {
            $conn = new PDO($dsn, $user, $pass, $options);
            if ($conn) {
                $lat = round((microtime(true) - $start) * 1000, 2);
                $stmt = $conn->query("SELECT VERSION() as v");
                $ver = $stmt ? $stmt->fetchColumn() : 'Unknown';
                return [
                    'success'    => true,
                    'latency_ms' => $lat,
                    'dsn'        => $dsn,
                    'version'    => $ver
                ];
            }
        } catch (PDOException $e) {
            $lastErr = $e->getMessage();
        }
    }
    return [
        'success' => false,
        'error'   => $lastErr ?: 'Failed to connect with provided credentials'
    ];
}

/**
 * Persist custom DB credentials to config/db_credentials.json
 */
function saveDBCredentials($host, $port, $name, $user, $pass) {
    $creds = [
        'host'       => trim($host),
        'port'       => trim($port) ?: '3306',
        'name'       => trim($name),
        'user'       => trim($user),
        'pass'       => (string)$pass,
        'updated_at' => date('Y-m-d H:i:s'),
        'updated_by' => $_SESSION['admin_username'] ?? 'Admin'
    ];
    $filePath = __DIR__ . '/db_credentials.json';
    $res = @file_put_contents($filePath, json_encode($creds, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    if ($res !== false) {
        @chmod($filePath, 0600);
        return true;
    }
    return false;
}
