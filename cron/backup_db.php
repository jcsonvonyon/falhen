<?php
/**
 * Falhen Media Automated Database & Repository Backup Utility
 * Generates timestamped data archives and logs backup activity.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0775, true);
}

$timestamp = date('Ymd_His');
$backupSubDir = $backupDir . '/backup_' . $timestamp;
if (!is_dir($backupSubDir)) {
    @mkdir($backupSubDir, 0775, true);
}

$configDir = __DIR__ . '/../config';
$jsonFiles = glob($configDir . '/*.json');
$backedUpCount = 0;

foreach ($jsonFiles as $file) {
    $baseName = basename($file);
    if (copy($file, $backupSubDir . '/' . $baseName)) {
        $backedUpCount++;
    }
}

// Log Backup Audit Activity
logDatabaseAudit('System Backup Worker', 'DB_BACKUP_CREATED', 'system', $timestamp, "Automated database backup created with {$backedUpCount} JSON repository snapshots.");

echo "[" . date('Y-m-d H:i:s') . "] Database Backup Completed Successfully: {$backedUpCount} files archived in backups/backup_{$timestamp}" . PHP_EOL;
