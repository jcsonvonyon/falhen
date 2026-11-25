<?php
/**
 * Falhen Productions - cPanel Git Auto-Deployment Script
 */

// Secret key protection to prevent unauthorized access
$secretKey = getenv('FALHEN_DEPLOY_KEY') ?: 'falhen_deploy_2026';
$providedKey = $_GET['key'] ?? $_POST['key'] ?? '';

if (!hash_equals((string)$secretKey, (string)$providedKey)) {
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
                <p>Enter your secret deployment key below to trigger a production git pull.</p>
                <form method="POST" action="deploy.php">
                    <input type="password" name="key" class="deploy-input" placeholder="Enter deployment secret key" required>
                    <button type="submit" class="btn-deploy"><i class="fa-solid fa-rocket"></i> Deploy Latest Commit</button>
                </form>
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

if ($action === 'diag_auth') {
    require_once $repoDir . '/config/db.php';
    require_once $repoDir . '/includes/functions.php';
    require_once $repoDir . '/includes/mxroute_auth.php';
    $pdo = function_exists('getDBConnection') ? getDBConnection() : null;
    echo "=== ADMIN_USERS (DB) ===\n";
    if ($pdo) {
        try {
            $rows = $pdo->query("SELECT id, username, email, alt_email, role, is_owner, status, LEFT(password_hash, 7) AS h_alg, LENGTH(password_hash) AS h_len, SUBSTR(SHA2(password_hash, 256), 1, 8) AS h_fp, (reset_otp IS NOT NULL) AS has_otp, last_login_at FROM `admin_users` ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                echo sprintf("id=%s user=%s email=%s alt=%s role=%s owner=%s status=%s h=%s(%s:%s) otp=%s\n",
                    $r['id'], $r['username'], $r['email'], maskEmailForDisplay((string)$r['alt_email']), $r['role'], $r['is_owner'], $r['status'], $r['h_alg'], $r['h_len'], $r['h_fp'], $r['has_otp']);
            }
        } catch (Throwable $e) {
            echo "DB QUERY ERR: " . $e->getMessage() . "\n";
        }
    }
    echo "\n=== STAFF_ACCOUNTS (SETTINGS) ===\n";
    $repo = getStaffAccountsRepo();
    foreach ($repo as $st) {
        $h = (string)($st['password_hash'] ?? '');
        echo sprintf("id=%s user=%s email=%s alt=%s role=%s owner=%s status=%s act=%s h=%s(%d:%s)\n",
            $st['id'] ?? '', $st['username'] ?? '', $st['email'] ?? '', maskEmailForDisplay((string)($st['alt_email'] ?? '')), $st['role'] ?? '', $st['is_owner'] ?? 0, $st['status'] ?? '', $st['activation_status'] ?? '', substr($h, 0, 7), strlen($h), $h ? substr(hash('sha256', $h), 0, 8) : 'none');
    }
    echo "\n=== RESOLVER CHECK (henry.falonipe@falhen.com) ===\n";
    $acc = findStaffAccountByIdentifier('henry.falonipe@falhen.com', false);
    if ($acc) {
        $ah = (string)($acc['password_hash'] ?? '');
        echo sprintf("FOUND: id=%s user=%s email=%s role=%s owner=%s status=%s act=%s h=%s(%d:%s)\n",
            $acc['id'] ?? '', $acc['username'] ?? '', $acc['email'] ?? '', $acc['role'] ?? '', $acc['is_owner'] ?? 0, $acc['status'] ?? '', $acc['activation_status'] ?? '', substr($ah, 0, 7), strlen($ah), $ah ? substr(hash('sha256', $ah), 0, 8) : 'none');
    } else {
        echo "NOT FOUND (null)!\n";
    }
    echo "\n=== RECENT AUDIT_LOGS ===\n";
    if ($pdo) {
        try {
            $logs = $pdo->query("SELECT id, actor_username, action_type, entity_id, details, created_at FROM `audit_logs` ORDER BY id DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($logs as $l) {
                echo sprintf("[%s] #%s actor=%s action=%s eid=%s details=%s\n", $l['created_at'], $l['id'], $l['actor_username'], $l['action_type'], $l['entity_id'], $l['details']);
            }
        } catch (Throwable $e) {
            echo "AUDIT_LOGS ERR: " . $e->getMessage() . "\n";
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
        'services.php',
        'admin/index.php',
        'admin/login.php',
        'api/admin-client-gallery.php',
        'api/verify-download.php',
        'api/download-album.php',
        'includes/functions.php',
        'includes/mxroute_auth.php',
        'includes/smtp_mailer.php',
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
    require_once $repoDir . '/config/db.php';
    $pdo = function_exists('getDBConnection') ? getDBConnection(true) : false;
    if ($pdo) {
        $dsnInfo = $GLOBALS['falhen_db_active_dsn'] ?? 'connected';
        echo "SUCCESS: Connected via config/db.php ({$dsnInfo})\n";
        exit;
    }
    $errDetail = $GLOBALS['falhen_db_last_error'] ?? 'Unable to connect';
    echo "FAILED: {$errDetail}\n";
    exit;
}

if ($action === 'sync_team_accounts') {
    require_once $repoDir . '/config/db.php';
    require_once $repoDir . '/includes/functions.php';
    $pdo = function_exists('getDBConnection') ? getDBConnection() : null;
    if ($pdo && function_exists('initDatabaseSchema')) {
        initDatabaseSchema($pdo);
    }
    $syncRes = function_exists('syncTeamAndStaffAccounts') ? syncTeamAndStaffAccounts(true) : ['added' => 0, 'updated' => 0, 'error' => 'Function unavailable'];
    if (function_exists('opcache_reset')) { @opcache_reset(); }
    echo "TEAM & STAFF ACCOUNT SYNC RESULT\n";
    echo "Added: " . (int)($syncRes['added'] ?? 0) . "\n";
    echo "Updated: " . (int)($syncRes['updated'] ?? 0) . "\n";
    echo "Total: " . (int)($syncRes['total'] ?? 0) . "\n";
    if (!empty($syncRes['error'])) {
        echo "ERROR: " . $syncRes['error'] . "\n";
    } else {
        echo "STATUS: SUCCESS - Team roster, settings.json, and MySQL admin_users synchronized.\n";
    }
    exit;
}

if ($action === 'snapshot') {
    require_once $repoDir . '/config/db.php';
    require_once $repoDir . '/includes/functions.php';
    $snapRes = function_exists('createFullSystemBackup')
        ? createFullSystemBackup('DEPLOY_FULL_SNAPSHOT', 'Deployment full system snapshot (Codebase ZIP + MySQL SQL + SHA-256 Manifest)')
        : ['success' => false, 'error' => 'createFullSystemBackup unavailable'];
    echo "===========================================\n";
    echo "   FALHEN FULL SYSTEM SNAPSHOT REPORT      \n";
    echo "===========================================\n";
    echo "Timestamp:          " . ($snapRes['timestamp'] ?? date('Y-m-d H:i:s')) . "\n";
    echo "Codebase Archive:   " . ($snapRes['codebase_zip'] ?? 'N/A') . " (" . (int)($snapRes['codebase_size'] ?? 0) . " bytes)\n";
    echo "Codebase SHA-256:   " . ($snapRes['codebase_sha256'] ?? 'N/A') . "\n";
    echo "Database SQL Dump:  " . ($snapRes['database_sql'] ?? 'N/A') . " (" . (int)($snapRes['database_size'] ?? 0) . " bytes)\n";
    echo "Database SHA-256:   " . ($snapRes['database_sha256'] ?? 'N/A') . "\n";
    echo "Pipeline Manifest:  " . ($snapRes['manifest_json'] ?? 'N/A') . "\n";
    echo "Staff Count:        " . (int)($snapRes['staff_count'] ?? 0) . "\n";
    echo "Integrity Verified: " . (!empty($snapRes['integrity_verified']) ? 'TRUE (SHA-256 OK)' : 'FALSE') . "\n";
    exit;
}

if ($action === 'stash_list') {
    $out = [];
    exec("cd " . escapeshellarg($repoDir) . " && git config --global --add safe.directory " . escapeshellarg($repoDir) . " 2>&1");
    exec("cd " . escapeshellarg($repoDir) . " && git stash list 2>&1", $out);
    echo implode("\n", $out);
    exit;
}

echo "===========================================\n";
echo "   FALHEN CPANEL GIT DEPLOYMENT SYSTEM     \n";
echo "===========================================\n";
$deployTimestamp = date('Ymd_His');
$preDeployFilename = 'settings_pre_deploy_' . $deployTimestamp . '.json';
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
    @copy($liveSettingsFile, $backupDir . '/' . $preDeployFilename);
    @copy($liveSettingsFile, $backupDir . '/settings_latest_live.json');
}

// 2. Perform safe deployment (never stash untracked secrets or backups)
exec("cd " . escapeshellarg($repoDir) . " && git config --global --add safe.directory " . escapeshellarg($repoDir) . " 2>&1", $output);
exec("cd " . escapeshellarg($repoDir) . " && git stash push -m " . escapeshellarg("pre_deploy_{$deployTimestamp}") . " -- config/settings.json 2>&1", $output);
exec("cd " . escapeshellarg($repoDir) . " && git pull origin main 2>&1", $output, $returnVar);

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
            $output[] = "Database schema verified & initialized (admin_users, site_settings, client_download_logs, system_backups).";
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

    // Ensure client brand partner logos are initialized into MySQL only if database brands have no images or are empty
    $dbBrandHasImages = false;
    foreach ($dbSettings['brand_logos_items'] ?? [] as $b) {
        if (!empty($b['image'])) {
            $dbBrandHasImages = true;
            break;
        }
    }
    if ((empty($dbSettings['brand_logos_items']) || !$dbBrandHasImages) && !empty($repoSettings['brand_logos_items'])) {
        $mergedSettings['brand_logos_items'] = $repoSettings['brand_logos_items'];
        $pdo = function_exists('getDBConnection') ? getDBConnection() : null;
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('brand_logos_items', ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
                $stmt->execute([json_encode($repoSettings['brand_logos_items'], JSON_UNESCAPED_SLASHES)]);
                $output[] = "Synced brand partner logos from repo to MySQL database.";
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

    // 5. Automatically synchronize website team roster, settings.json staff_accounts, and MySQL admin_users
    if (function_exists('syncTeamAndStaffAccounts')) {
        $teamSync = syncTeamAndStaffAccounts(true);
        $output[] = "Synchronized team & staff accounts across settings.json and MySQL admin_users (added: " . (int)($teamSync['added'] ?? 0) . ", updated: " . (int)($teamSync['updated'] ?? 0) . ", total: " . (int)($teamSync['total'] ?? 0) . ").";
    }

    if (function_exists('logSystemBackup')) {
        $activeSettings = function_exists('getSiteSettings') ? getSiteSettings() : [];
        $count = count($activeSettings['portfolio_items'] ?? []);
        logSystemBackup($preDeployFilename, 'DEPLOY_SNAPSHOT', $count, 'Automated post-deploy database & settings snapshot');
        $output[] = "Logged system backup snapshot ({$preDeployFilename}) to database.";
    }

    // 6. Optional full system snapshot (?with_snapshot=1) with SHA-256 verification
    if (!empty($_GET['with_snapshot']) || !empty($_POST['with_snapshot'])) {
        if (function_exists('createFullSystemBackup')) {
            $snapRes = createFullSystemBackup('DEPLOY_FULL_SNAPSHOT', 'Automated full codebase + database deployment snapshot');
            $output[] = "Full System Snapshot created: ZIP=" . ($snapRes['codebase_zip'] ?? 'N/A')
                . " (SHA256=" . substr((string)($snapRes['codebase_sha256'] ?? 'N/A'), 0, 16) . "...), SQL=" . ($snapRes['database_sql'] ?? 'N/A')
                . " (SHA256=" . substr((string)($snapRes['database_sha256'] ?? 'N/A'), 0, 16) . "...), Integrity=" . (!empty($snapRes['integrity_verified']) ? 'VERIFIED' : 'FAILED');
        }
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
