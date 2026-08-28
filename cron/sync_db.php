<?php
/**
 * Falhen Media Database Sync Worker
 * Bi-Directional Data Synchronization Engine (JSON <-> MySQL)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

if (!$pdo) {
    echo "[" . date('Y-m-d H:i:s') . "] DB Sync Aborted: Relational MySQL database connection offline. Operating on JSON file repository fallback." . PHP_EOL;
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Starting Falhen Media Database Synchronization Worker..." . PHP_EOL;

// 1. Sync Staff Accounts Repository
$staffRepo = getStaffAccountsRepo();
$syncedStaff = 0;

$staffStmt = $pdo->prepare("INSERT INTO `staff_accounts` 
    (`full_name`, `username`, `email`, `alt_email`, `password_hash`, `role`, `status`, `activation_status`, `activation_code`, `avatar`, `bio`, `dob`) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
    ON DUPLICATE KEY UPDATE 
        `full_name` = VALUES(`full_name`),
        `alt_email` = VALUES(`alt_email`),
        `role` = VALUES(`role`),
        `status` = VALUES(`status`),
        `activation_status` = VALUES(`activation_status`),
        `activation_code` = VALUES(`activation_code`),
        `avatar` = VALUES(`avatar`),
        `bio` = VALUES(`bio`),
        `dob` = VALUES(`dob`)");

foreach ($staffRepo as $st) {
    if (empty($st['username']) || empty($st['email'])) continue;
    
    $staffStmt->execute([
        $st['full_name'] ?? $st['username'],
        $st['username'],
        $st['email'],
        $st['alt_email'] ?? null,
        $st['password_hash'] ?? null,
        $st['role'] ?? 'Staff',
        $st['status'] ?? 'pending_activation',
        $st['activation_status'] ?? 'unverified',
        $st['activation_code'] ?? null,
        $st['avatar'] ?? null,
        $st['bio'] ?? null,
        !empty($st['dob']) ? $st['dob'] : null
    ]);
    $syncedStaff++;
}
echo "  [✓] Staff Accounts Synced: {$syncedStaff} records." . PHP_EOL;

// 2. Sync Leave Applications Repository
$leaveRepo = getLeaveRequests();
$syncedLeaves = 0;

$leaveStmt = $pdo->prepare("INSERT INTO `leave_requests` 
    (`username`, `leave_type`, `start_date`, `end_date`, `reason`, `status`, `reviewed_by`) 
    VALUES (?, ?, ?, ?, ?, ?, ?)");

foreach ($leaveRepo as $l) {
    if (empty($l['username'])) continue;
    $startDate = !empty($l['start_date']) ? $l['start_date'] : date('Y-m-d');
    $endDate = !empty($l['end_date']) ? $l['end_date'] : date('Y-m-d');
    
    // Check if record exists
    $chk = $pdo->prepare("SELECT id FROM `leave_requests` WHERE `username` = ? AND `created_at` = ? LIMIT 1");
    $chk->execute([$l['username'], $l['created_at'] ?? '']);
    if (!$chk->fetch()) {
        $leaveStmt->execute([
            $l['username'],
            $l['type'] ?? 'Annual Leave',
            $startDate,
            $endDate,
            $l['reason'] ?? '',
            $l['status_type'] ?? 'pending',
            $l['reviewed_by'] ?? null
        ]);
        $syncedLeaves++;
    }
}
echo "  [✓] Leave Applications Synced: {$syncedLeaves} new records." . PHP_EOL;

// Log Sync Execution in Audit Trail
logDatabaseAudit('System Cron Worker', 'DB_SYNC_COMPLETED', 'system', null, "Automated database synchronization completed successfully.");

echo "[" . date('Y-m-d H:i:s') . "] Database Synchronization Completed Successfully!" . PHP_EOL;
