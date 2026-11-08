<?php
/**
 * Falhen Media Automated Scheduled Backup Utility (cPanel Cron & Webhook)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$isCli = (php_sapi_name() === 'cli' || empty($_SERVER['REMOTE_ADDR']));
if (!$isCli) {
    $token = $_GET['token'] ?? $_POST['token'] ?? '';
    if ($token !== 'falhen_cron_2026') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized cron access']);
        exit;
    }
    header('Content-Type: application/json');
}

$res = createFullSystemBackup('SCHEDULED_CRON', 'Automated Scheduled System Backup (Codebase + DB + Pipeline)');

if ($isCli) {
    echo "[" . date('Y-m-d H:i:s') . "] CRON SUCCESS: Full System Backup Generated.\n";
    echo "  - Codebase ZIP: " . ($res['codebase_zip'] ?? 'N/A') . "\n";
    echo "  - Database SQL: " . ($res['database_sql'] ?? 'N/A') . "\n";
    echo "  - Manifest JSON: " . ($res['manifest_json'] ?? 'N/A') . "\n";
    echo "  - Telemetry CSV: " . ($res['dl_csv'] ?? 'N/A') . "\n";
    echo "  - Backups CSV:   " . ($res['bk_csv'] ?? 'N/A') . "\n";
} else {
    echo json_encode($res, JSON_PRETTY_PRINT);
}
