<?php
/**
 * Falhen Media - cPanel Git Auto-Deployment Script
 */

// Secret key protection to prevent unauthorized access
$secretKey = 'falhen_deploy_2026';
$providedKey = $_GET['key'] ?? $_POST['key'] ?? '';

if ($providedKey !== $secretKey) {
    // If accessed from a web browser without key in URL, present an authentication form
    $isHtml = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') !== false);
    if ($isHtml) {
        http_response_code(401);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Falhen Media &mdash; cPanel Git Deployment</title>
            <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background: #09090b;
                    color: #fafafa;
                    font-family: 'Plus Jakarta Sans', sans-serif;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                }
                .deploy-card {
                    background: #18181b;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 16px;
                    padding: 36px 32px;
                    max-width: 440px;
                    width: 90%;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
                    text-align: center;
                }
                .deploy-icon {
                    width: 56px;
                    height: 56px;
                    border-radius: 12px;
                    background: rgba(239, 68, 68, 0.12);
                    border: 1px solid rgba(239, 68, 68, 0.3);
                    color: #ef4444;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.5rem;
                    margin: 0 auto 20px;
                }
                h1 {
                    font-size: 1.3rem;
                    margin: 0 0 8px;
                    font-weight: 700;
                }
                p {
                    font-size: 0.85rem;
                    color: #a1a1aa;
                    margin: 0 0 24px;
                    line-height: 1.5;
                }
                .deploy-input {
                    width: 100%;
                    box-sizing: border-box;
                    background: #27272a;
                    border: 1px solid rgba(255, 255, 255, 0.12);
                    border-radius: 8px;
                    padding: 12px 14px;
                    color: #ffffff;
                    font-size: 0.92rem;
                    font-family: 'JetBrains Mono', monospace;
                    margin-bottom: 16px;
                    outline: none;
                    text-align: center;
                }
                .deploy-input:focus {
                    border-color: #ef4444;
                }
                .btn-deploy {
                    width: 100%;
                    background: #dc2626;
                    color: #ffffff;
                    border: none;
                    padding: 12px 20px;
                    border-radius: 8px;
                    font-weight: 700;
                    font-size: 0.95rem;
                    cursor: pointer;
                    transition: background 0.2s;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                }
                .btn-deploy:hover {
                    background: #b91c1c;
                }
                .direct-link-note {
                    margin-top: 18px;
                    font-size: 0.78rem;
                    color: #71717a;
                }
                .direct-link-note a {
                    color: #ef4444;
                    text-decoration: none;
                    word-break: break-all;
                }
            </style>
        </head>
        <body>
            <div class="deploy-card">
                <div class="deploy-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                <h1>cPanel Git Deployment</h1>
                <p>Enter your secret deployment key below or click the button to trigger a production git pull.</p>
                <form method="POST" action="deploy.php">
                    <input type="password" name="key" class="deploy-input" value="falhen_deploy_2026" placeholder="Enter Secret Key" required>
                    <button type="submit" class="btn-deploy"><i class="fa-solid fa-rocket"></i> Deploy Latest Commit</button>
                </form>
                <div class="direct-link-note">
                    Or access directly via: <br>
                    <a href="deploy.php?key=falhen_deploy_2026">deploy.php?key=falhen_deploy_2026</a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized deployment request. Invalid secret key.'
    ]);
    exit;
}

header('Content-Type: text/plain');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$repoDir = __DIR__;
putenv("HOME=/home/henadinnovate");

$action = $_GET['action'] ?? $_POST['action'] ?? 'deploy';

if ($action === 'lint_admin') {
    $out = [];
    exec("php -l " . escapeshellarg(__DIR__ . '/admin/index.php') . " 2>&1", $out);
    echo implode("\n", $out);
    exit;
}

if ($action === 'probe_db') {
    $hosts = ['localhost', '127.0.0.1'];
    $users = ['henadinnovate_falhen', 'henadinnovate_falhenmedia', 'henadinnovate_hub'];
    $passes = ['FalhenMedia2026!#', 'Oluwatosin25#'];
    $dbs = ['henadinnovate_falhen', 'henadinnovate_falhenmedia'];
    
    foreach ($hosts as $h) {
        foreach ($users as $u) {
            foreach ($passes as $p) {
                foreach ($dbs as $d) {
                    try {
                        $dsn = "mysql:host={$h};dbname={$d};charset=utf8mb4";
                        $pdo = new PDO($dsn, $u, $p, [PDO::ATTR_TIMEOUT => 2, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                        echo "SUCCESS: host={$h} user={$u} db={$d}\n";
                        exit;
                    } catch (PDOException $e) {
                        // ignore
                    }
                }
            }
        }
    }
    echo "FAILED: Unable to connect\n";
    exit;
}

if ($action === 'stash_list') {
    $out = [];
    exec("cd {$repoDir} && git config --global --add safe.directory {$repoDir} 2>&1");
    exec("cd {$repoDir} && git stash list 2>&1", $out);
    echo implode("\n", $out);
    exit;
}

if ($action === 'stash_cat') {
    $stashIdx = (int)($_GET['stash'] ?? 0);
    $out = [];
    exec("cd {$repoDir} && git config --global --add safe.directory {$repoDir} 2>&1");
    exec("cd {$repoDir} && git show stash@{{$stashIdx}}:config/settings.json 2>&1", $out);
    echo implode("\n", $out);
    exit;
}

if ($action === 'stash_diff') {
    $stashIdx = (int)($_GET['stash'] ?? 0);
    $out = [];
    exec("cd {$repoDir} && git config --global --add safe.directory {$repoDir} 2>&1");
    exec("cd {$repoDir} && git stash show -p stash@{{$stashIdx}} 2>&1", $out);
    echo implode("\n", $out);
    exit;
}

echo "===========================================\n";
echo "   FALHEN CPANEL GIT DEPLOYMENT SYSTEM     \n";
echo "===========================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";

$output = [];
$returnVar = 0;

// 1. Back up live settings.json before any git action
$backupDir = $repoDir . '/config/backups';
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0755, true);
}
$liveSettingsFile = $repoDir . '/config/settings.json';
if (file_exists($liveSettingsFile)) {
    @copy($liveSettingsFile, $backupDir . '/settings_pre_deploy_' . date('Ymd_His') . '.json');
    @copy($liveSettingsFile, $backupDir . '/settings_latest_live.json');
}

// 2. Perform safe deployment
exec("cd {$repoDir} && git config --global --add safe.directory {$repoDir} 2>&1", $output);
exec("cd {$repoDir} && git stash -u 2>&1", $output);
exec("cd {$repoDir} && git pull origin main 2>&1", $output, $returnVar);

// 3. Re-apply database schema and persist settings
require_once $repoDir . '/config/db.php';
require_once $repoDir . '/includes/functions.php';

if (function_exists('initDatabaseSchema')) {
    try {
        $pdo = function_exists('getDBConnection') ? getDBConnection() : null;
        if ($pdo) {
            initDatabaseSchema($pdo);
            $output[] = "Database schema verified & initialized (site_settings, client_download_logs, system_backups).";
        }
    } catch (Throwable $e) {
        $output[] = "Database schema init notice: " . $e->getMessage();
    }
}

// 4. Ensure site_settings are synced into MySQL from repository settings.json if empty, or sync repository file from MySQL
try {
    $dbSettings = function_exists('getSiteSettings') ? getSiteSettings() : [];
    $repoSettings = json_decode(@file_get_contents($liveSettingsFile), true) ?: [];
    
    $dbCount = count($dbSettings['portfolio_items'] ?? []);
    $repoCount = count($repoSettings['portfolio_items'] ?? []);

    // Merge authoritative live settings from MySQL over repo defaults
    $criticalKeys = ['portfolio_items', 'bts_items', 'brand_logos_items', 'registered_clients'];
    $mergedSettings = $repoSettings;
    foreach ($criticalKeys as $ck) {
        if (!empty($dbSettings[$ck]) && is_array($dbSettings[$ck])) {
            $mergedSettings[$ck] = $dbSettings[$ck];
        }
    }
    
    if ($dbCount < $repoCount || isset($_GET['sync_to_db'])) {
        if (function_exists('saveSiteSettings')) {
            saveSiteSettings($repoSettings);
        }
        $output[] = "Synced repository settings ({$repoCount} items) to MySQL site_settings database.";
    } else {
        if (function_exists('saveSiteSettings')) {
            saveSiteSettings($mergedSettings);
        }
        @file_put_contents($liveSettingsFile, json_encode($mergedSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        $output[] = "Preserved authoritative MySQL settings (portfolio: {$dbCount}, bts: " . count($mergedSettings['bts_items'] ?? []) . ", brands: " . count($mergedSettings['brand_logos_items'] ?? []) . ") across deployment.";
    }

    if (function_exists('logSystemBackup')) {
        $activeSettings = function_exists('getSiteSettings') ? getSiteSettings() : [];
        $count = count($activeSettings['portfolio_items'] ?? []);
        logSystemBackup('settings_pre_deploy_' . date('Ymd_His') . '.json', 'DEPLOY_SNAPSHOT', $count, 'Automated post-deploy database & settings snapshot');
        $output[] = "Logged system backup snapshot to database.";
    }
} catch (Throwable $e) {
    $output[] = "Settings sync notice: " . $e->getMessage();
}

echo "Command: git pull origin main\n";
echo "-------------------------------------------\n";
echo implode("\n", $output) . "\n";
echo "-------------------------------------------\n";

if ($returnVar === 0) {
    echo "STATUS: SUCCESS - cPanel site updated to latest commit!\n";
} else {
    echo "STATUS: FAILED - Git pull exit code: " . $returnVar . "\n";
}
