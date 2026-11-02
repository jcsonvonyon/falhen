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

function initDatabaseSchema($pdo) {
    if (!$pdo) return;
    static $initialized = false;
    if ($initialized) return;

    try {
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
