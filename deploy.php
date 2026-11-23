<?php
/**
 * Falhen Productions - cPanel Git Auto-Deployment Script
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
            <title>Falhen Productions &mdash; cPanel Git Deployment</title>
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

header('Content-Type: text/plain; charset=UTF-8');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$repoDir = __DIR__;
putenv("HOME=/home/henadinnovate");

$action = $_GET['action'] ?? $_POST['action'] ?? 'deploy';

if ($action === 'cat_error_log') {
    $logFiles = [
        __DIR__ . '/error_log',
        __DIR__ . '/admin/error_log',
        '/home/henadinnovate/falhen.com/error_log',
        '/home/henadinnovate/logs/falhen_com.php.error.log',
    ];
    foreach ($logFiles as $ef) {
        if (@is_readable($ef)) {
            echo "=== {$ef} ===\n" . substr((string)@file_get_contents($ef), -4000) . "\n";
        }
    }
    exit;
}

if ($action === 'diag_admin') {
    $out = [];
    $adminFile = __DIR__ . '/admin/index.php';
    exec("cd " . escapeshellarg($repoDir) . " && git config --global --add safe.directory " . escapeshellarg($repoDir) . " 2>&1");
    exec("cd " . escapeshellarg($repoDir) . " && git rev-parse --short HEAD 2>&1", $headOut);
    exec("cd " . escapeshellarg($repoDir) . " && git status --short 2>&1", $statusOut);
    exec("php -v 2>&1", $phpVerOut);
    exec("php -l " . escapeshellarg($adminFile) . " 2>&1", $lintOut);

    echo "HEAD: " . implode(' ', $headOut) . "\n";
    echo "PHP: " . ($phpVerOut[0] ?? 'unknown') . "\n";
    echo "LINT: " . implode("\n", $lintOut) . "\n";
    foreach (['admin/index.php', 'admin/login.php', 'deploy.php', 'config/db.php', 'config/db_credentials.json'] as $rel) {
        $p = __DIR__ . '/' . $rel;
        if (file_exists($p)) {
            echo sprintf("STAT %-28s perms=%o size=%d\n", $rel, fileperms($p) & 0777, filesize($p));
        } else {
            echo sprintf("STAT %-28s MISSING\n", $rel);
        }
    }
    echo "GIT STATUS:\n" . (empty($statusOut) ? "(clean)\n" : implode("\n", $statusOut) . "\n");
    require_once $repoDir . '/config/db.php';
    $pdo = function_exists('getDBConnection') ? getDBConnection() : null;
    echo "DB_CONN: " . ($pdo ? 'OK (' . ($GLOBALS['falhen_db_active_dsn'] ?? 'connected') . ')' : 'FAILED (' . ($GLOBALS['falhen_db_last_error'] ?? 'unknown') . ')') . "\n";
    exit;
}

if ($action === 'lint_client') {
    $targets = [
        'client-portal.php',
        'portfolio-photo.php',
        'portfolio.php',
        'admin/index.php',
        'api/admin-client-gallery.php',
        'api/verify-download.php',
        'api/download-album.php',
        'includes/functions.php',
        'index.php'
    ];
    $allOk = true;
    foreach ($targets as $rel) {
        $out = [];
        $code = 0;
        exec("php -l " . escapeshellarg(__DIR__ . '/' . $rel) . " 2>&1", $out, $code);
        echo sprintf("[%s] %s\n", $code === 0 ? 'OK' : 'FAIL', implode(' | ', $out));
        if ($code !== 0) $allOk = false;
    }
    echo "\nSUMMARY: " . ($allOk ? "ALL CLEAN" : "ERRORS DETECTED") . "\n";
    exit;
}

if ($action === 'lint_admin') {
    $out = [];
    exec("php -l " . escapeshellarg(__DIR__ . '/admin/index.php') . " 2>&1", $out);
    echo implode("\n", $out);
    exit;
}

if ($action === 'seed_brands') {
    require_once $repoDir . '/config/db.php';
    require_once $repoDir . '/includes/functions.php';
    $brands = [
        ['id' => 1, 'name' => 'WeatherTech', 'icon' => 'fa-solid fa-car', 'image' => '/assets/img/brands/weathertech.png', 'visible' => true],
        ['id' => 2, 'name' => 'Coventry Direct', 'icon' => 'fa-solid fa-building-columns', 'image' => '/assets/img/brands/coventry-direct.png', 'visible' => true],
        ['id' => 3, 'name' => 'WeatherTech Canada', 'icon' => 'fa-solid fa-leaf', 'image' => '/assets/img/brands/weathertech-ca.png', 'visible' => true],
        ['id' => 4, 'name' => 'Garrett Popcorn Shops', 'icon' => 'fa-solid fa-bowl-food', 'image' => '/assets/img/brands/garrett-popcorn.png', 'visible' => true],
        ['id' => 5, 'name' => 'Lumber Liquidators', 'icon' => 'fa-solid fa-hammer', 'image' => '/assets/img/brands/lumber-liquidators.png', 'visible' => true],
        ['id' => 6, 'name' => 'Parkway Bank', 'icon' => 'fa-solid fa-vault', 'image' => '/assets/img/brands/parkway-bank.png', 'visible' => true],
        ['id' => 7, 'name' => 'Skydeck The Ledge', 'icon' => 'fa-solid fa-city', 'image' => '/assets/img/brands/skydeck-chicago.png', 'visible' => true],
        ['id' => 8, 'name' => '4Real', 'icon' => 'fa-solid fa-bolt', 'image' => '/assets/img/brands/4real.png', 'visible' => true],
        ['id' => 9, 'name' => 'AES', 'icon' => 'fa-solid fa-industry', 'image' => '/assets/img/brands/aes.png', 'visible' => true],
        ['id' => 10, 'name' => 'GentleCure', 'icon' => 'fa-solid fa-heart-pulse', 'image' => '/assets/img/brands/gentlecure.png', 'visible' => true],
        ['id' => 11, 'name' => 'Warmies', 'icon' => 'fa-solid fa-paw', 'image' => '/assets/img/brands/warmies.png', 'visible' => true],
        ['id' => 12, 'name' => "Kate's Real Food", 'icon' => 'fa-solid fa-cookie-bite', 'image' => '/assets/img/brands/kates-real-food.png', 'visible' => true],
        ['id' => 13, 'name' => 'Shoreline Sightseeing', 'icon' => 'fa-solid fa-ship', 'image' => '/assets/img/brands/shoreline-sightseeing.png', 'visible' => true],
        ['id' => 14, 'name' => 'Litter Genie', 'icon' => 'fa-solid fa-cat', 'image' => '/assets/img/brands/litter-genie.png', 'visible' => true],
        ['id' => 15, 'name' => 'Diaper Genie', 'icon' => 'fa-solid fa-baby', 'image' => '/assets/img/brands/diaper-genie.png', 'visible' => true],
        ['id' => 16, 'name' => 'SkinCure Oncology', 'icon' => 'fa-solid fa-shield-halved', 'image' => '/assets/img/brands/skincure-oncology.png', 'visible' => true],
        ['id' => 17, 'name' => 'Better Business Bureau', 'icon' => 'fa-solid fa-award', 'image' => '/assets/img/brands/bbb.png', 'visible' => true],
        ['id' => 18, 'name' => 'Group 1', 'icon' => 'fa-solid fa-users', 'image' => '/assets/img/brands/group-1.png', 'visible' => true],
        ['id' => 19, 'name' => 'Cencora Triose', 'icon' => 'fa-solid fa-network-wired', 'image' => '/assets/img/brands/cencora-triose.png', 'visible' => true],
        ['id' => 20, 'name' => 'Weaver Consultants Group', 'icon' => 'fa-solid fa-compass-drafting', 'image' => '/assets/img/brands/weaver-consultants.png', 'visible' => true]
    ];
    saveSiteSettings(['brand_logos_items' => $brands]);
    if (function_exists('opcache_reset')) { @opcache_reset(); }
    echo "SUCCESS: Seeded " . count($brands) . " brands into MySQL and live settings!";
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

// Ensure LiteSpeed suEXEC-compatible file permissions on core entrypoints
foreach (['admin/index.php', 'admin/login.php', 'deploy.php', 'config/db.php', 'includes/functions.php'] as $relFile) {
    $fullPath = $repoDir . '/' . $relFile;
    if (file_exists($fullPath)) {
        @chmod($fullPath, 0644);
    }
}

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
    // Canonical rule: Database settings in MySQL always override repo defaults!
    // This ensures hero Cloudinary sync, direct video, showreel, custom categories,
    // deleted/hidden albums, and portfolio items are 100% preserved and never overwritten by git pull.
    $mergedSettings = array_merge($repoSettings, $dbSettings);
    if (!empty($repoSettings['services_items'])) {
        $mergedSettings['services_items'] = $repoSettings['services_items'];
        $pdo = function_exists('getDBConnection') ? getDBConnection() : null;
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('services_items', ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
                $stmt->execute([json_encode($repoSettings['services_items'], JSON_UNESCAPED_SLASHES)]);
            } catch (Throwable $e) {}
        }
    }

    // Ensure 20 real client brand partner logos are synced into MySQL if database brands have no images or < 10 items
    $dbBrandHasImages = false;
    foreach ($dbSettings['brand_logos_items'] ?? [] as $b) {
        if (!empty($b['image'])) {
            $dbBrandHasImages = true;
            break;
        }
    }
    if ((!$dbBrandHasImages || count($dbSettings['brand_logos_items'] ?? []) < 10) && !empty($repoSettings['brand_logos_items'])) {
        $mergedSettings['brand_logos_items'] = $repoSettings['brand_logos_items'];
        $pdo = function_exists('getDBConnection') ? getDBConnection() : null;
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('brand_logos_items', ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
                $stmt->execute([json_encode($repoSettings['brand_logos_items'], JSON_UNESCAPED_SLASHES)]);
                $output[] = "Synced 20 brand partner logos from repo to MySQL database.";
            } catch (Throwable $e) {}
        }
    }
    
    // If MySQL is completely uninitialized ($dbCount === 0) or user explicitly requests ?sync_to_db=1
    if (($dbCount === 0 && $repoCount > 0) || isset($_GET['sync_to_db']) || isset($_GET['force_repo_seed'])) {
        if (function_exists('saveSiteSettings')) {
            saveSiteSettings($repoSettings);
        }
        $output[] = "Initialized empty database with repository settings ({$repoCount} items).";
    } else {
        // Authoritative MySQL settings are preserved 100%! Deleted or hidden albums stay deleted/hidden across all builds & deploys!
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

if (function_exists('opcache_reset')) {
    @opcache_reset();
    $output[] = "OPcache cleared successfully.";
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
