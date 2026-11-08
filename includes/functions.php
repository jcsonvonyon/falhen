<?php
/**
 * Global Helper Functions
 * Falhen Media
 */

// Recommended PHP Runtime Settings for Media-Heavy Photography & Video Workloads
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '300');
@ini_set('max_input_time', '300');
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');

// Cloudflare Edge IP Restoration & Origin IP Exposure Protection
if (!headers_sent()) {
    @header_remove('X-Powered-By');
}
if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
}
$rawHostHeader = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
if (!empty($rawHostHeader) && filter_var($rawHostHeader, FILTER_VALIDATE_IP) && !in_array($rawHostHeader, ['127.0.0.1', '::1'], true)) {
    // Prevent direct access via exposed origin IP address; force traffic through Cloudflare proxy
    header('Location: https://www.falhen.com' . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    } else {
        @session_start();
    }
}

require_once __DIR__ . '/cloudinary_helper.php';
if (file_exists(__DIR__ . '/../config/gdrive.php')) {
    require_once __DIR__ . '/../config/gdrive.php';
}
if (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
}

/**
 * Sanitize string input for XSS prevention
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Render Google Analytics (gtag.js) Tag
 */
function renderGoogleAnalyticsTag() {
    ?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-LJ91C0YXCH"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-LJ91C0YXCH');
</script>
<?php
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF Token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Send JSON Response
 */
function sendJSONResponse($success, $message, $extra = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => (bool)$success,
        'message' => $message
    ], $extra));
    exit;
}

/**
 * Log Database Audit Event
 */
function logDatabaseAudit($actorUsername, $actionType, $entityType, $entityId = null, $details = '') {
    if (function_exists('getDBConnection')) {
        $pdo = getDBConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO `audit_logs` (`actor_username`, `action_type`, `entity_type`, `entity_id`, `details`, `ip_address`) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$actorUsername, $actionType, $entityType, (string)$entityId, (string)$details, $ip]);
                return true;
            } catch (Exception $e) {}
        }
    }
    return false;
}

/**
 * Check if Admin/Staff is Logged In
 */
function isAdminLoggedIn() {
    if (!empty($_SESSION['admin_user_id'])) {
        return true;
    }

    // Auto-login via remember_me cookie if set
    if (!empty($_COOKIE['falhen_remember'])) {
        $rememberToken = $_COOKIE['falhen_remember'];
        if (function_exists('getDBConnection')) {
            $pdo = getDBConnection();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM `admin_users` WHERE `remember_token` = ? LIMIT 1");
                    $stmt->execute([$rememberToken]);
                    $user = $stmt->fetch();
                    if ($user) {
                        session_regenerate_id(true);
                        $_SESSION['admin_user_id'] = $user['id'];
                        $_SESSION['admin_username'] = $user['username'];
                        $_SESSION['admin_email'] = $user['email'];
                        $_SESSION['admin_role'] = $user['role'] ?? 'Staff';
                        return true;
                    }
                } catch (Exception $e) {}
            }
        }
    }
    return false;
}

/**
 * Require Admin Login
 */
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * Clear Admin Session and Cookies
 */
function clearAdminSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear DB remember token if logged in
    if (!empty($_SESSION['admin_user_id']) && function_exists('getDBConnection')) {
        $pdo = getDBConnection();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE `admin_users` SET `remember_token` = NULL WHERE `id` = ?");
                $stmt->execute([$_SESSION['admin_user_id']]);
            } catch (Exception $e) {}
        }
    }

    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    if (isset($_COOKIE['falhen_remember'])) {
        setcookie('falhen_remember', '', time() - 3600, '/', '', false, true);
    }
    session_destroy();
}

/**
 * Get Team Members Data
 */
function getTeamMembers() {
    $settings = getSiteSettings();
    $rawItems = $settings['team_items'] ?? [];

    if (empty($rawItems)) {
        $rawItems = [
            [
                'id' => 1,
                'number' => '01',
                'name' => 'Henry Falonipe',
                'role' => 'Creative Director',
                'department' => 'Creative',
                'location' => 'Chicago',
                'experience' => '15+ years at Falhen',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786712075/falhen/team/ctxjp9mymgqdh2ecg2hu.jpg',
                'bio' => "Visionary Creative Director and founder of Falhen, where he leads high-end cinematic storytelling and media production. With over 15 years of experience, Henry has transformed a boutique studio into a recognized production house.",
                'skills' => ['Creative Direction', 'Brand Strategy', 'Concert Development']
            ],
            [
                'id' => 2,
                'number' => '02',
                'name' => 'Kingsley Falonipe',
                'role' => 'Operations Specialist',
                'department' => 'Operations',
                'location' => 'Lagos',
                'experience' => '4+ years at Falhen',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786713360/falhen/team/ftmzlwwv1szlyurijyno.png',
                'bio' => "Bridging the gap between creative vision and seamless execution. With a proactive and friendly approach, Kingsley ensures that complex production workflows run with precision.",
                'skills' => ['Productions', 'Operational Management', 'Client Relations']
            ],
            [
                'id' => 3,
                'number' => '03',
                'name' => 'Lisa Okoli',
                'role' => 'Client Partnership & Head of Marketing',
                'department' => 'Creative',
                'location' => 'London',
                'experience' => '5+ years at Falhen',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786713962/falhen/team/b5v6o8oirs95xihokmgc.jpg',
                'bio' => "Leading brand partnerships, campaign strategy, and client communications. Lisa connects world-class brands with Falhen's production engine.",
                'skills' => ['Client Partnerships', 'Marketing Strategy', 'Brand Development']
            ],
            [
                'id' => 4,
                'number' => '04',
                'name' => 'Mojisola Emjay',
                'role' => 'HR Manager',
                'department' => 'Strategy',
                'location' => 'Lagos',
                'experience' => '10+ years at Falhen',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786714006/falhen/team/ini79za7jvbjokrf1dup.jpg',
                'bio' => "With over a decade of experience in the HR industry, Mojisola has a proven track record of enhancing operational efficiency and resource allocation.",
                'skills' => ['Resource Management', 'Talent Scheduling', 'Budget Optimization']
            ],
            [
                'id' => 5,
                'number' => '05',
                'name' => 'Daniel Ifeoluwa',
                'role' => 'Graphics Designer',
                'department' => 'Creative',
                'location' => 'Lagos',
                'experience' => '3+ years at Falhen',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786714147/falhen/team/xba4ep38ewfdq3chfx1h.jpg',
                'bio' => "Combining technical precision with creative artistry to produce immersive visual assets that elevate brand storytelling.",
                'skills' => ['Graphics Design', 'Visual Assets', 'Brand Design']
            ],
            [
                'id' => 6,
                'number' => '06',
                'name' => 'Victoria Opemipo',
                'role' => 'Social Media Manager',
                'department' => 'Creative',
                'location' => 'Lagos',
                'experience' => '3+ years at Falhen',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786714208/falhen/team/ri0fc0rdmjfvyzx1lihn.jpg',
                'bio' => "Specialized in crafting digital narratives that amplify brand identity and foster genuine community engagement.",
                'skills' => ['Social Strategy', 'Audience Engagement Specialist', 'Visual Storytelling for Social']
            ],
            [
                'id' => 7,
                'number' => '07',
                'name' => 'Micheal Otuwho',
                'role' => 'Content Manager',
                'department' => 'Strategy',
                'location' => 'Lagos',
                'experience' => '4+ years at Falhen',
                'image' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=600&auto=format&fit=crop',
                'bio' => "Specialize in identifying emerging market trends and translating them into actionable growth opportunities for client brands.",
                'skills' => ['Content Strategy', 'Market Analysis', 'Growth Campaigns']
            ],
            [
                'id' => 8,
                'number' => '08',
                'name' => 'Ligali Oluwatosin',
                'role' => 'IT Support Specialist',
                'department' => 'Strategy',
                'location' => 'Lagos',
                'experience' => '5+ years at Falhen',
                'image' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?q=80&w=600&auto=format&fit=crop',
                'bio' => "Approach technical infrastructure with a proactive, solutions-driven mindset. My mission is to ensure smooth studio operations.",
                'skills' => ['IT Support', 'Technical Infrastructure', 'Systems Security']
            ]
        ];
    }

    $formatted = [];
    foreach ($rawItems as $idx => $item) {
        $id = (int)($item['id'] ?? ($idx + 1));
        $num = !empty($item['number']) ? $item['number'] : sprintf("%02d", $id);
        $skills = is_array($item['skills'] ?? null) ? $item['skills'] : array_filter(array_map('trim', explode(',', $item['skills'] ?? '')));
        $formatted[$id] = [
            'id' => $id,
            'number' => $num,
            'name' => $item['name'] ?? '',
            'role' => $item['role'] ?? '',
            'department' => $item['department'] ?? 'Creative',
            'location' => $item['location'] ?? '',
            'experience' => $item['experience'] ?? '',
            'image' => !empty($item['image']) ? $item['image'] : '/assets/img/team/team_henry.png',
            'bio' => $item['bio'] ?? '',
            'skills' => $skills
        ];
    }
    return $formatted;
}

function getTeamMemberById($id) {
    $members = getTeamMembers();
    $id = (int)$id;
    return $members[$id] ?? $members[1];
}

/**
 * Extract 11-character YouTube Video ID from any URL or ID string
 */
function extractYouTubeId($urlOrId) {
    $urlOrId = trim((string)$urlOrId);
    if (empty($urlOrId)) return '';
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $urlOrId)) {
        return $urlOrId;
    }
    if (preg_match('/(?:v=|\/embed\/|\/watch\?v=|youtu\.be\/|\/v\/)([a-zA-Z0-9_-]{11})/i', $urlOrId, $m)) {
        return $m[1];
    }
    return '';
}

/**
 * Get YouTube Thumbnail URL for a video ID or URL
 */
function getYouTubeThumbnailUrl($urlOrId, $quality = 'hqdefault') {
    $id = extractYouTubeId($urlOrId);
    if (!empty($id)) {
        return "https://i.ytimg.com/vi/{$id}/{$quality}.jpg";
    }
    $url = trim((string)$urlOrId);
    if (preg_match('/^https?:\/\//i', $url) || preg_match('/^\//', $url)) {
        return $url;
    }
    return '/assets/img/hero.jpg';
}

/**
 * Get Site Settings: Database-First with local file fallback & auto-seeding
 */
function getSiteSettings() {
    static $cachedSettings = null;
    if ($cachedSettings !== null) {
        return $cachedSettings;
    }

    $dbSettings = [];
    $pdo = function_exists('getDBConnection') ? getDBConnection() : null;
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM `site_settings`");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            if (!empty($rows)) {
                foreach ($rows as $r) {
                    $k = $r['setting_key'];
                    $v = $r['setting_value'];
                    $decoded = json_decode($v, true);
                    $dbSettings[$k] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $v;
                }
            }
        } catch (Exception $e) {
            error_log("Settings DB read error: " . $e->getMessage());
        }
    }

    // Read local disk file as base/fallback
    $file = __DIR__ . '/../config/settings.json';
    $fileSettings = [];
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $fileSettings = json_decode($json, true) ?: [];
    }

    // If database has settings, it is canonical!
    if (!empty($dbSettings)) {
        $cachedSettings = array_merge($fileSettings, $dbSettings);
        return $cachedSettings;
    }

    // If database was empty but file exists, seed database!
    if (!empty($fileSettings) && $pdo) {
        try {
            $ins = $pdo->prepare("INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
            foreach ($fileSettings as $k => $v) {
                $valStr = is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_SLASHES);
                $ins->execute([$k, $valStr]);
            }
        } catch (Exception $e) {}
    }

    $cachedSettings = !empty($fileSettings) ? $fileSettings : [
        'hero_direct_video_url' => '',
        'hero_youtube_bg' => '',
        'hero_vimeo_bg' => '',
        'hero_poster_image' => 'https://storage.readdy-site.link/project_files/6c8ddf7b-6d98-436f-a5d0-36fd1d9fec3f/hero-fallback.jpg',
        'showreel_youtube_id' => 'Tf8rNMZ-Bw0',
        'hero_title' => 'Cinematic Storytelling & High-Impact Media Production',
        'hero_subtitle' => 'Falhen Media is a premier full-service media production studio crafting high-end corporate films, commercials, live broadcasts, and brand anthems.'
    ];

    return $cachedSettings;
}

/**
 * Get Single Setting Value
 */
function getSiteSetting($key, $default = '') {
    $settings = getSiteSettings();
    return $settings[$key] ?? $default;
}

/**
 * Save Site Settings array: writes to Database (canonical) AND disk cache
 */
function saveSiteSettings($newSettings) {
    $current = getSiteSettings();
    $updated = array_merge($current, $newSettings);

    // 1. Write to database (canonical persistent store)
    $pdo = function_exists('getDBConnection') ? getDBConnection() : null;
    if ($pdo) {
        try {
            $ins = $pdo->prepare("INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
            foreach ($newSettings as $k => $v) {
                $valStr = is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_SLASHES);
                $ins->execute([$k, $valStr]);
            }
        } catch (Exception $e) {
            error_log("Settings DB save error: " . $e->getMessage());
        }
    }

    // 2. Write to local JSON file for disk caching and backups
    $file = __DIR__ . '/../config/settings.json';
    file_put_contents($file, json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

    return $updated;
}

/**
 * Log client download activity into MySQL database
 */
function logClientDownload($data) {
    $pdo = function_exists('getDBConnection') ? getDBConnection() : null;
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare("INSERT INTO `client_download_logs` 
            (`client_name`, `client_email`, `client_phone`, `album_id`, `album_title`, `download_type`, `ip_address`, `user_agent`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['client_name'] ?? 'Client',
            $data['client_email'] ?? '',
            $data['client_phone'] ?? '',
            (int)($data['album_id'] ?? 0),
            $data['album_title'] ?? 'Gallery',
            $data['download_type'] ?? 'ZIP Archive',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
        ]);
    } catch (Exception $e) {
        error_log("Error logging client download: " . $e->getMessage());
        return false;
    }
}

/**
 * Retrieve client download logs with pagination/search
 */
function getClientDownloadLogs($limit = 100) {
    $pdo = function_exists('getDBConnection') ? getDBConnection() : null;
    if (!$pdo) return [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM `client_download_logs` ORDER BY `id` DESC LIMIT ?");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Log system backup snapshot
 */
function logSystemBackup($filename, $backupType = 'manual_snapshot', $itemsCount = 0, $notes = '') {
    $pdo = function_exists('getDBConnection') ? getDBConnection() : null;
    $filePath = __DIR__ . '/../config/backups/' . basename($filename);
    $size = file_exists($filePath) ? filesize($filePath) : 0;
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare("INSERT INTO `system_backups` 
            (`backup_type`, `filename`, `file_size`, `items_count`, `notes`, `created_by`)
            VALUES (?, ?, ?, ?, ?, ?)");
        $author = $_SESSION['admin_username'] ?? ($_SESSION['staff_username'] ?? 'System/IT');
        return $stmt->execute([
            $backupType,
            basename($filename),
            $size,
            $itemsCount,
            $notes,
            $author
        ]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Retrieve system backup logs
 */
function getSystemBackups($limit = 50) {
    $pdo = function_exists('getDBConnection') ? getDBConnection() : null;
    if (!$pdo) return [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM `system_backups` ORDER BY `id` DESC LIMIT ?");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get Global Payment Gateways & Remittance Settings
 * (Raenest, LemFi, Taptap Send, Paystack, Direct Bank/Dom)
 */
function getPaymentGatewaysSettings() {
    $settings = getSiteSettings();
    $gateways = $settings['payment_gateways'] ?? [];

    $defaults = [
        'raenest' => [
            'enabled'            => true,
            'mode'               => 'sandbox', // sandbox or live
            'public_key'         => 'rae_pub_test_8921fa76b10',
            'secret_key'         => 'rae_sec_test_9921abcf783',
            'webhook_secret'     => 'rae_whsec_f89312b918',
            'account_name'       => 'Falhen Media Ltd',
            'usd_bank_name'      => 'JPMorgan Chase / Lead Bank',
            'usd_account_number' => '1029384756',
            'usd_routing_number' => '021000021',
            'gbp_bank_name'      => 'Barclays Bank UK',
            'gbp_sort_code'      => '20-00-00',
            'gbp_account_number' => '83920194',
            'eur_bank_name'      => 'Banking Circle',
            'eur_iban'           => 'LU9200091823901923',
            'eur_bic'            => 'BCIRLULL',
            'ngn_bank_name'      => 'Wema Bank / VFD Microfinance Bank',
            'ngn_account_number' => '0129384756',
            'supported_currencies' => ['USD', 'GBP', 'EUR', 'NGN', 'CAD', 'USDC', 'USDT']
        ],
        'lemfi' => [
            'enabled'         => true,
            'tag'             => '@falhen',
            'account_name'    => 'Falhen Media Global',
            'receiving_bank'  => 'LemFi Virtual Account (Providus Bank / Wema)',
            'account_number'  => '9920192834',
            'country'         => 'Nigeria / Global Diaspora (UK, US, Canada, EU)',
            'instructions'    => 'Send zero-fee instant payments via the LemFi App directly to handle @falhen or transfer to our LemFi receiving account.'
        ],
        'taptap' => [
            'enabled'          => true,
            'recipient_name'   => 'Falhen Media Limited',
            'receiving_bank'   => 'Zenith Bank PLC / Access Bank',
            'account_number'   => '1019283746',
            'domiciliary_usd'  => '5071928340',
            'country'          => 'Nigeria (Global Send: US, UK, EU, CA, UAE)',
            'reference_prefix' => 'FAL-',
            'instructions'     => 'Send via the Taptap Send mobile app. Select Nigeria (Bank Transfer), enter our account details, and input your invoice reference code.'
        ],
        'paystack' => [
            'enabled'    => true,
            'mode'       => 'test',
            'public_key' => 'pk_test_falhen892173819',
            'secret_key' => 'sk_test_falhen892173819'
        ],
        'bank_transfer' => [
            'enabled'        => true,
            'bank_name'      => 'Zenith Bank PLC',
            'account_name'   => 'Falhen Media Limited',
            'account_number' => '1019283746',
            'dom_usd_bank'   => 'Zenith Bank PLC (USD Domiciliary)',
            'dom_usd_acc'    => '5071928340',
            'dom_gbp_acc'    => '5071928357',
            'dom_eur_acc'    => '5071928364',
            'swift_code'     => 'ZEIBNGLA'
        ]
    ];

    if (empty($gateways) || !is_array($gateways)) {
        return $defaults;
    }

    foreach ($defaults as $gwKey => $gwDef) {
        if (!isset($gateways[$gwKey]) || !is_array($gateways[$gwKey])) {
            $gateways[$gwKey] = $gwDef;
        } else {
            $gateways[$gwKey] = array_merge($gwDef, $gateways[$gwKey]);
        }
    }

    return $gateways;
}

/**
 * Save Global Payment Gateways & Remittance Settings
 */
function savePaymentGatewaysSettings($gatewayData) {
    $current = getPaymentGatewaysSettings();
    $updated = array_merge($current, $gatewayData);
    saveSiteSettings(['payment_gateways' => $updated]);
    return $updated;
}

/**
 * Get Dynamic Master Services Repository
 */
function getServicesRepo($includeHidden = false) {
    $settings = getSiteSettings();
    $items = $settings['services_items'] ?? [];
    
    if (empty($items)) {
        $items = [
            [
                'id' => 1,
                'slug' => 'video-production',
                'title' => 'Video Production',
                'icon' => 'fa-solid fa-film',
                'short_description' => 'Professional video production services from concept to completion, including scripting, filming, and editing with state-of-the-art equipment.',
                'detail_description' => 'Our full-service video production team handles every stage of the process — from initial creative briefs and scriptwriting through on-set filming with cinema-grade cameras to the final polished edit.',
                'image' => 'https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?auto=format&fit=crop&w=800&q=80',
                'card_features' => ['Corporate videos & commercials', '4K & 8K video capture', 'Drone cinematography', 'Multi-camera productions'],
                'detail_features' => ['4K & 8K Cinema Capture', 'Drone Aerial Cinematography', 'Multi-Camera Rigs', 'Scriptwriting & Storyboarding']
            ]
        ];
    }
    
    $repo = [];
    foreach ($items as $item) {
        if (!$includeHidden && !empty($item['hidden'])) {
            continue;
        }
        $title = $item['title'] ?? 'Service';
        $slug = !empty($item['slug']) ? $item['slug'] : strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
        $img = getCloudinaryUrl($item['image'] ?? '');
        
        $cardFeatures = $item['card_features'] ?? [];
        $detailFeatures = $item['detail_features'] ?? [];
        
        $included = [];
        foreach ($detailFeatures as $df) {
            $included[] = ['icon' => 'fa-solid fa-check', 'text' => $df];
        }

        $repo[$slug] = [
            'id' => $item['id'] ?? 1,
            'slug' => $slug,
            'title' => $title,
            'icon' => !empty($item['icon']) ? $item['icon'] : 'fa-solid fa-film',
            'subtitle' => $item['short_description'] ?? '',
            'short_description' => $item['short_description'] ?? '',
            'detail_description' => $item['detail_description'] ?? '',
            'hero_image' => $img,
            'image' => $img,
            'about' => !empty($item['detail_description']) ? $item['detail_description'] : ($item['short_description'] ?? ''),
            'included' => $included,
            'specialisations' => $cardFeatures,
            'card_features' => $cardFeatures,
            'detail_features' => $detailFeatures,
            'focal_point_desktop' => $item['focal_point_desktop'] ?? 'center center',
            'focal_point_mobile' => $item['focal_point_mobile'] ?? 'center center',
            'badge' => 'Cinema & TV',
            'desc' => $item['short_description'] ?? '',
            'tags' => $cardFeatures
        ];
    }
    return $repo;
}

/**
 * Get Dynamic Testimonials Repository
 */
function getTestimonialsRepo() {
    $settings = getSiteSettings();
    $items = $settings['testimonials_items'] ?? [];

    if (empty($items)) {
        return [
            [
                'id' => 1,
                'name' => 'Marcus Webb',
                'role' => 'Head of Marketing',
                'company' => 'RedBull EMEA',
                'project' => 'Energy Unleashed Campaign',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80',
                'rating' => 5,
                'quote' => 'Falhen Media delivered a campaign that exceeded every benchmark we set. The team understood our brand DNA from day one and translated it into visuals that literally stopped people mid-scroll. Our engagement numbers tripled.'
            ],
            [
                'id' => 2,
                'name' => 'Serena Okafor',
                'role' => 'Creative Director',
                'company' => 'Nike Africa',
                'project' => 'Sprint Series Brand Film',
                'avatar' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=150&q=80',
                'rating' => 5,
                'quote' => "Working with Falhen was genuinely one of the best creative decisions we made this year. They don't just shoot video — they engineer emotion. The final film had our entire team in tears. In the best way."
            ],
            [
                'id' => 3,
                'name' => 'James Thornton',
                'role' => 'VP of Content',
                'company' => 'HBO Original',
                'project' => 'Dark Horizons Series Teaser',
                'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80',
                'rating' => 5,
                'quote' => 'The teaser Falhen produced for Dark Horizons generated more pre-release buzz than anything we\'d done in five years. Cinematic, punchy, and perfectly on brief. They are our go-to production partner now.'
            ],
            [
                'id' => 4,
                'name' => 'Priya Nair',
                'role' => 'Brand Manager',
                'company' => 'Sony Music',
                'project' => 'Next Generation Product Launch',
                'avatar' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=150&q=80',
                'rating' => 5,
                'quote' => 'From briefing to final delivery, the Falhen team was exceptional. They pushed back on ideas that weren\'t strong enough and brought solutions we hadn\'t imagined. The launch film is still being shared organically two months later.'
            ],
            [
                'id' => 5,
                'name' => 'Luca Ferrari',
                'role' => 'Event Director',
                'company' => 'Ferrari Private Events',
                'project' => 'Annual Gala Highlight Reel',
                'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80',
                'rating' => 5,
                'quote' => 'The same-day highlight reel they produced for our annual gala was breathtaking. Guests were watching it at the closing dinner. I have never seen a production team move that fast without sacrificing a single frame of quality.'
            ],
            [
                'id' => 6,
                'name' => 'Aisha Diallo',
                'role' => 'Founder',
                'company' => 'Diallo Cosmetics',
                'project' => 'Brand Identity Video Series',
                'avatar' => 'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?auto=format&fit=crop&w=150&q=80',
                'rating' => 5,
                'quote' => 'As a growing brand, we needed content that could compete with the big players. Falhen made us look like a global company on a startup budget. Our Instagram reels from that shoot still perform better than anything we\'ve posted since.'
            ]
        ];
    }

    return $items;
}

/**
 * Convert Google Drive share link to direct viewable image URL or format drive URL
 */
function convertGoogleDriveUrlToDirect($url) {
    $url = trim((string)$url);
    if (empty($url)) return '';
    if (preg_match('/(?:file\/d\/|id=)([a-zA-Z0-9_-]+)/i', $url, $matches)) {
        $fileId = $matches[1];
        return "https://lh3.googleusercontent.com/d/{$fileId}";
    }
    return $url;
}

/**
 * Clean URL slug generator per /seo-meta-optimizer guidelines (≤ 60 chars, lowercase, hyphenated)
 */
function generateSlug($text, $maxLength = 60) {
    $text = trim((string)$text);
    if (empty($text)) return 'gallery';
    
    // Convert to lowercase and transliterate special characters
    $text = strtolower($text);
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
    $text = trim($text, '-');
    
    // Remove common stop words if slug is long
    if (strlen($text) > 40) {
        $stopWords = ['a', 'an', 'the', 'and', 'or', 'in', 'at', 'for', 'of', 'to', 'on', 'with', 'by'];
        $words = explode('-', $text);
        $filtered = array_filter($words, function($w) use ($stopWords) {
            return !in_array($w, $stopWords, true);
        });
        if (!empty($filtered)) {
            $text = implode('-', $filtered);
        }
    }
    
    if (strlen($text) > $maxLength) {
        $text = substr($text, 0, $maxLength);
        $text = rtrim($text, '-');
    }
    
    return $text ?: 'gallery';
}

/**
 * Comprehensive SEO & Metadata Generator per /seo-keyword-strategist & /seo-meta-optimizer
 */
function generateClientGallerySeo($title, $category = 'Gallery', $client = '', $desc = '') {
    $cleanTitle = trim(strip_tags((string)$title));
    $cleanCat = trim(strip_tags((string)$category)) ?: 'Gallery';
    $cleanClient = trim(strip_tags((string)$client));
    $slug = generateSlug($cleanTitle);
    
    // 1. Title Tag: 50-60 chars optimal, brand hook, primary keyword up front
    $brandSuffix = ' | Falhen Media';
    $primaryHook = $cleanTitle;
    if (strlen($primaryHook . $brandSuffix) > 60) {
        $availableLen = 60 - strlen($brandSuffix);
        $primaryHook = substr($primaryHook, 0, $availableLen);
        $primaryHook = rtrim($primaryHook, ' -');
    }
    $seoTitle = $primaryHook . $brandSuffix;
    
    // 2. Meta Description: 150-160 chars optimal with power trigger & CTA
    $baseClient = $cleanClient ?: 'Falhen Media clients';
    $rawDesc = "View and download the exclusive {$cleanCat} photography gallery for {$baseClient}. Curated and delivered in editorial quality by Falhen Media. Explore frames ✓";
    if (strlen($rawDesc) > 160) {
        $rawDesc = substr($rawDesc, 0, 157) . '...';
    }
    $seoDesc = $rawDesc;
    
    // 3. Semantic LSI Keywords per /seo-keyword-strategist
    $keywords = [
        strtolower($cleanTitle),
        strtolower($cleanCat) . ' photography',
        'falhen media client gallery',
        'high resolution photo download',
        'editorial photography delivery',
        'private client gallery'
    ];
    if (!empty($cleanClient)) {
        $keywords[] = strtolower($cleanClient) . ' gallery';
    }
    
    return [
        'slug'            => $slug,
        'seo_title'       => $seoTitle,
        'seo_desc'        => $seoDesc,
        'seo_keywords'    => implode(', ', array_unique($keywords)),
        'canonical_url'   => "https://client.falhen.com/album/{$slug}",
        'og_type'         => 'article'
    ];
}

/**
 * Resolve Portfolio / Client Gallery Item by Slug or ID
 */
function getPortfolioItemBySlug($slugOrId) {
    $repo = getPortfolioRepo();
    if (empty($repo)) return null;
    
    $query = strtolower(trim((string)$slugOrId));
    if (empty($query)) return reset($repo);
    
    // Try matching numeric ID first
    if (is_numeric($query)) {
        $numericId = (int)$query;
        foreach ($repo as $item) {
            if ((int)($item['id'] ?? 0) === $numericId) {
                return $item;
            }
        }
    }
    
    // Try matching exact slug or slugified title
    foreach ($repo as $item) {
        $itemSlug = strtolower(trim((string)($item['slug'] ?? '')));
        if ($itemSlug === $query) {
            return $item;
        }
        if (generateSlug($item['title'] ?? '') === $query) {
            return $item;
        }
    }
    
    return null;
}

/**
 * Get Dynamic Master Portfolio Repository
 */
function getPortfolioRepo() {
    $settings = getSiteSettings();
    $items = $settings['portfolio_items'] ?? [];

    if (empty($items)) {
        $items = [
            [
                'id' => 1,
                'title' => 'King David - Grad Shoot',
                'category' => 'Portrait',
                'media_type' => 'photo',
                'featured' => true,
                'client' => 'King David',
                'location' => 'Chicago',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791227208/falhen/portfolio/ycqetkqltbrf0xctnjoe.jpg',
                'desc' => 'Commemorative academic graduate portrait session capturing milestone achievements with editorial studio lighting.',
                'photosCount' => 25
            ],
            [
                'id' => 2,
                'title' => "Halima's 40th Birthday Shoot",
                'category' => 'Birthday',
                'media_type' => 'photo',
                'featured' => true,
                'client' => 'Halima Ogunde',
                'location' => 'Lagos',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791224271/falhen/portfolio/ysfriy8jlhhkcuzl8j16.jpg',
                'desc' => 'A golden-hour pre-wedding & birthday session set in a lush garden estate. Every frame tells the story of elegance, captured with cinematic warmth.',
                'photosCount' => 30
            ],
            [
                'id' => 3,
                'title' => 'Demola Violinist - Tour',
                'category' => 'Wedding',
                'media_type' => 'photo',
                'featured' => true,
                'client' => 'Demola The Violinist',
                'location' => 'London',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791222750/falhen/portfolio/blpzc2fznaqr0czm3ji8.jpg',
                'desc' => 'Live concert and tour performance capturing electric stage energy, dramatic lighting, and musical virtuosity.',
                'photosCount' => 30
            ],
            [
                'id' => 4,
                'title' => 'Dex Million - Surprise 50th Birthday',
                'category' => 'Birthday',
                'media_type' => 'photo',
                'featured' => false,
                'client' => 'Dex Million',
                'location' => 'Atlanta',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791222920/falhen/portfolio/c5k5xwoa1misejie2oxy.jpg',
                'desc' => 'Surprise milestone celebration featuring high-profile guests, live music, and candid moment capture.',
                'photosCount' => 30
            ],
            [
                'id' => 5,
                'title' => 'Jussoul — Winery Music Tour',
                'category' => 'Events',
                'media_type' => 'photo',
                'featured' => false,
                'client' => 'Jussoul',
                'location' => 'California',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791226024/falhen/portfolio/ldtm77cjygkwcgijkdqq.jpg',
                'desc' => 'Acoustic live sessions and winery tour performance captured in intimate 4K resolution.',
                'photosCount' => 30
            ],
            [
                'id' => 6,
                'title' => 'Halima Ogunde',
                'category' => 'Events',
                'media_type' => 'photo',
                'featured' => false,
                'client' => 'Halima Ogunde',
                'location' => 'Lagos',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791224320/falhen/portfolio/qdkldutyaqhrena0mrza.jpg',
                'desc' => 'High-fashion portrait and luxury event coverage.',
                'photosCount' => 30
            ],
            [
                'id' => 7,
                'title' => 'Darasinmi - Studio Session',
                'category' => 'Portrait',
                'media_type' => 'photo',
                'featured' => false,
                'client' => 'Darasinmi',
                'location' => 'Oakbrook',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791226059/falhen/portfolio/lavtrjw84dyapshxdgpl.jpg',
                'desc' => 'Creative indoor studio portrait session with modern color tones and dramatic contrast.',
                'photosCount' => 30
            ],
            [
                'id' => 8,
                'title' => 'Darasinmi - 10th Birthday',
                'category' => 'Events',
                'media_type' => 'photo',
                'featured' => false,
                'client' => 'Darasinmi Family',
                'location' => 'Chicago',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791226059/falhen/portfolio/lavtrjw84dyapshxdgpl.jpg',
                'desc' => 'Vibrant milestone 10th birthday party photography and celebratory family moments.',
                'photosCount' => 30
            ],
            [
                'id' => 9,
                'title' => 'Adetutu - Burial Repass',
                'category' => 'Events',
                'media_type' => 'photo',
                'featured' => false,
                'client' => 'Adetutu Family',
                'location' => 'Lagos',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791226088/falhen/portfolio/sfa4gof0yfr0bc14r1eg.jpg',
                'desc' => 'Traditional cultural celebration and remembrance service photography.',
                'photosCount' => 30
            ],
            [
                'id' => 10,
                'title' => 'Luxe Fashion Week - Aftermovie',
                'category' => 'Commercial',
                'media_type' => 'video',
                'duration' => '02:30',
                'featured' => true,
                'client' => 'Falhen Runway',
                'location' => 'Paris',
                'image' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=800&auto=format&fit=crop',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                'desc' => 'High-energy fashion week highlight reel featuring 4K slow-motion captures, backstage moments, and custom sound design.'
            ],
            [
                'id' => 11,
                'title' => 'Demola Live in Concert — Tour Film',
                'category' => 'Music Video',
                'media_type' => 'video',
                'duration' => '04:15',
                'featured' => true,
                'client' => 'Demola The Violinist',
                'location' => 'London',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791222750/falhen/portfolio/blpzc2fznaqr0czm3ji8.jpg',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
                'desc' => 'Cinematic live performance video capturing multi-camera stage coverage, dramatic arena lighting, and musical virtuosity.'
            ],
            [
                'id' => 12,
                'title' => 'The Ogunde Royal Wedding Film',
                'category' => 'Wedding',
                'media_type' => 'video',
                'duration' => '05:40',
                'featured' => true,
                'client' => 'Halima & Tunde Ogunde',
                'location' => 'Cape Town',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791224271/falhen/portfolio/ysfriy8jlhhkcuzl8j16.jpg',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
                'desc' => 'Luxury destination wedding mini-documentary with aerial drone shots, emotional vows, and cinematic color grading.'
            ],
            [
                'id' => 13,
                'title' => 'Apex Tech Global Summit — 4K Stream',
                'category' => 'Events',
                'media_type' => 'video',
                'duration' => '03:10',
                'featured' => false,
                'client' => 'Apex Global',
                'location' => 'Tokyo',
                'image' => 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?q=80&w=800&auto=format&fit=crop',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyflights.mp4',
                'desc' => 'Multi-camera live broadcast setup and highlight recap reel for an international corporate keynote.'
            ],
            [
                'id' => 14,
                'title' => 'Jussoul - Acoustic Sessions (Reel)',
                'category' => 'Reels',
                'media_type' => 'video',
                'duration' => '00:58',
                'featured' => false,
                'client' => 'Jussoul Media',
                'location' => 'Los Angeles',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791226024/falhen/portfolio/ldtm77cjygkwcgijkdqq.jpg',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4',
                'desc' => 'Vertical 9:16 social media teaser reel optimized for Instagram and TikTok with dynamic typography.'
            ],
            [
                'id' => 15,
                'title' => 'Urban Stories — Short Documentary',
                'category' => 'Documentary',
                'media_type' => 'video',
                'duration' => '06:20',
                'featured' => false,
                'client' => 'Falhen Originals',
                'location' => 'Lagos',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791222920/falhen/portfolio/c5k5xwoa1misejie2oxy.jpg',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4',
                'desc' => 'Award-winning short documentary exploring street art, culture, and creative passion in modern urban cities.'
            ],
            [
                'id' => 16,
                'title' => 'Tech Innovation Summit 2024',
                'category' => 'Corporate',
                'media_type' => 'project',
                'featured' => true,
                'client' => 'TechCorp International',
                'location' => 'Chicago',
                'image' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/tech-summit-2024',
                'desc' => 'Global technology conference keynote recording, stage visual direction, and corporate livestream coverage.'
            ],
            [
                'id' => 17,
                'title' => 'Luxury Brand Fashion Show',
                'category' => 'Events',
                'media_type' => 'project',
                'featured' => true,
                'client' => 'Elegance Fashion House',
                'location' => 'Paris & Lagos',
                'image' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/fashion-show-2024',
                'desc' => 'High-fashion runway showcase, 4K slow-motion captures, and backstage model portraiture.'
            ],
            [
                'id' => 18,
                'title' => 'Electric Vehicle Launch Campaign',
                'category' => 'Commercial',
                'media_type' => 'project',
                'featured' => true,
                'client' => 'EcoMotors',
                'location' => 'Los Angeles',
                'image' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/ecomotors-campaign',
                'desc' => 'Commercial launch campaign featuring cinematic night automotive tracking shots and digital ad spots.'
            ],
            [
                'id' => 19,
                'title' => 'Fitness Brand Social Campaign',
                'category' => 'Social',
                'media_type' => 'project',
                'featured' => false,
                'client' => 'FitLife Athletics',
                'location' => 'New York',
                'image' => 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/fitlife-social',
                'desc' => 'High-intensity athletic promotional reels and vertical social video asset package.'
            ],
            [
                'id' => 20,
                'title' => 'SoundWave Music Festival',
                'category' => 'Events',
                'media_type' => 'project',
                'featured' => false,
                'client' => 'SoundWave Festival',
                'location' => 'Lagos',
                'image' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/soundwave-fest',
                'desc' => 'Multi-stage festival coverage, crowd energy captures, and mainstage headliner recap video.'
            ],
            [
                'id' => 21,
                'title' => 'Metro Morning News Show',
                'category' => 'Broadcast',
                'media_type' => 'project',
                'featured' => false,
                'client' => 'Metro News Network',
                'location' => 'London',
                'image' => 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/metro-news-show',
                'desc' => 'State-of-the-art studio set design, multi-camera live broadcast infrastructure, and news graphics package.'
            ],
            [
                'id' => 22,
                'title' => 'Coastal Wedding — Nichelle & Michael',
                'category' => 'Wedding',
                'media_type' => 'project',
                'featured' => false,
                'client' => 'Nichelle & Michael',
                'location' => 'Cape Town',
                'image' => 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/nichelle-michael-wedding',
                'desc' => 'Luxury oceanfront destination wedding documentary film and comprehensive photo archive.'
            ],
            [
                'id' => 23,
                'title' => 'Artisan Coffee — Brand Story',
                'category' => 'Commercial',
                'media_type' => 'project',
                'featured' => true,
                'client' => 'Roast & Ritual',
                'location' => 'Seattle',
                'image' => 'https://images.unsplash.com/photo-1447933601403-0c6688de566e?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/roast-ritual-story',
                'desc' => 'Cinematic brand documentary profiling craft coffee roasters and ethical bean sourcing.'
            ],
            [
                'id' => 24,
                'title' => 'Falhen Brand Campaign 2026',
                'category' => 'Corporate',
                'media_type' => 'project',
                'featured' => true,
                'client' => 'Falhen Global',
                'location' => 'Lagos & Chicago',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786578372/falhen/uploads/r96mxsji9cdogqrkxwoe.jpg',
                'gdrive_url' => 'https://drive.google.com/drive/folders/falhen-brand-2026',
                'desc' => 'Complete visual rebrand, corporate media campaign, and multimedia brand guidelines production.'
            ],
            [
                'id' => 25,
                'title' => 'Demola World Tour Production',
                'category' => 'Events',
                'media_type' => 'project',
                'featured' => true,
                'client' => 'Demola Music',
                'location' => 'London & New York',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791222750/falhen/portfolio/blpzc2fznaqr0czm3ji8.jpg',
                'gdrive_url' => 'https://drive.google.com/drive/folders/demola-tour-2026',
                'desc' => 'Stage LED visual curation, concert live recording setup, and international promotional assets.'
            ],
            [
                'id' => 26,
                'title' => 'Urban Culture Short Film',
                'category' => 'Documentary',
                'media_type' => 'project',
                'featured' => true,
                'client' => 'Falhen Originals',
                'location' => 'Lagos',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791224320/falhen/portfolio/qdkldutyaqhrena0mrza.jpg',
                'gdrive_url' => 'https://drive.google.com/drive/folders/urban-culture-doc',
                'desc' => 'Award-winning short documentary exploring street art, urban music, and creative youth culture.'
            ],
            [
                'id' => 27,
                'title' => 'Global Apex Summit 2026',
                'category' => 'Broadcast',
                'media_type' => 'project',
                'featured' => false,
                'client' => 'Apex Tech',
                'location' => 'Tokyo',
                'image' => 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/apex-summit-2026',
                'desc' => 'International tech conference hybrid broadcast stream and multi-lingual video production.'
            ]
        ];
    }

    // Auto-repair & enforce unique IDs
    $seenIds = [];
    $hasDuplicates = false;
    $maxId = 0;
    foreach ($items as $item) {
        $id = (int)($item['id'] ?? 0);
        if ($id > $maxId) {
            $maxId = $id;
        }
        if (empty($id) || isset($seenIds[$id])) {
            $hasDuplicates = true;
        } else {
            $seenIds[$id] = true;
        }
    }

    if ($hasDuplicates) {
        $used = [];
        foreach ($items as &$item) {
            $id = (int)($item['id'] ?? 0);
            if (empty($id) || isset($used[$id])) {
                $maxId++;
                $item['id'] = $maxId;
                $used[$maxId] = true;
            } else {
                $used[$id] = true;
            }
        }
        unset($item);
        $settings['portfolio_items'] = $items;
        saveSiteSettings($settings);
    }

    // Auto-ensure SEO slug exists for all items
    foreach ($items as &$it) {
        if (empty($it['slug']) && !empty($it['title'])) {
            $it['slug'] = generateSlug($it['title']);
        }
    }
    unset($it);

    return $items;
}

/**
 * Get Team Locations list
 */
function getTeamLocations() {
    $settings = getSiteSettings();
    $defaults = ['USA', 'Nigeria', 'Canada', 'UK'];
    $saved = $settings['team_locations'] ?? [];

    if (!is_array($saved)) {
        $saved = [];
    }

    $all = array_values(array_unique(array_filter(array_map('trim', array_merge($defaults, $saved)))));
    return $all;
}

/**
 * Get Team Departments list
 */
function getTeamDepartments() {
    $settings = getSiteSettings();
    $defaults = ['Creative', 'Operations', 'Strategy'];
    $saved = $settings['team_departments'] ?? [];

    if (!is_array($saved)) {
        $saved = [];
    }

    $all = array_values(array_unique(array_filter(array_map('trim', array_merge($defaults, $saved)))));
    return $all;
}

/**
 * Get Blog Repository (Dynamic array from settings.json)
 */
function getBlogRepo() {
    $settings = getSiteSettings();
    $items = $settings['blog_items'] ?? null;

    if (empty($items) || !is_array($items)) {
        $items = [
            1 => [
                'id' => 1,
                'title' => '10 Essential Tips for Creating Engaging Social Media Videos',
                'category' => 'Social Media',
                'date' => 'March 12, 2024',
                'read_time' => '6 min read',
                'excerpt' => 'Learn the secrets to creating scroll-stopping social media content that captures attention and drives engagement.',
                'author' => 'Michael Chen',
                'role' => 'Creative Director',
                'image' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=800&auto=format&fit=crop',
                'content' => 'Creating engaging video content for social media platforms requires a strategic blend of visual storytelling, hook optimization, and crisp pacing. In this guide, we break down 10 actionable strategies to elevate your brand\'s video performance across Instagram Reels, TikTok, YouTube Shorts, and LinkedIn.',
                'featured' => true
            ],
            2 => [
                'id' => 2,
                'title' => 'Behind the Scenes: Our Award-Winning Commercial Production',
                'category' => 'Case Study',
                'date' => 'March 8, 2024',
                'read_time' => '10 min read',
                'excerpt' => 'Take an exclusive look at the making of our latest award-winning commercial. Discover the creative process, technical challenges...',
                'author' => 'Emily Rodriguez',
                'role' => 'Lead Producer',
                'image' => '/assets/img/services/service_video.png',
                'content' => 'Step inside the production studio as we pull back the curtain on our award-winning commercial campaign. From concept development and storyboarding to multi-camera lighting and sound design, here is how our creative team brought cinematic vision to life.',
                'featured' => false
            ],
            3 => [
                'id' => 3,
                'title' => 'Mastering Color Grading: A Comprehensive Guide',
                'category' => 'Tutorial',
                'date' => 'March 5, 2024',
                'read_time' => '12 min read',
                'excerpt' => 'Dive deep into the art and science of color grading. Learn professional techniques to enhance mood, create visual consistency...',
                'author' => 'David Park',
                'role' => 'Colorist & Editor',
                'image' => '/assets/img/services/service_post.png',
                'content' => 'Color grading is the secret sauce behind cinematic film production. Understand color spaces, tone curves, LUT application, and skin tone protection to create distinct visual identities for your commercial projects.',
                'featured' => false
            ],
            4 => [
                'id' => 4,
                'title' => 'The Power of Storytelling in Brand Videos',
                'category' => 'Strategy',
                'date' => 'March 1, 2024',
                'read_time' => '7 min read',
                'excerpt' => 'Discover how compelling narratives can transform your brand videos from forgettable to unforgettable. Storytelling framework...',
                'author' => 'Jessica Thompson',
                'role' => 'Brand Strategist',
                'image' => '/assets/img/services/service_creative.png',
                'content' => 'Audiences remember stories, not sales pitches. Learn how to structure brand video narratives around human emotion, tension, and resolution to build lasting brand affinity.',
                'featured' => false
            ],
            5 => [
                'id' => 5,
                'title' => 'Choosing the Right Equipment for Your Video Project',
                'category' => 'Equipment',
                'date' => 'February 28, 2024',
                'read_time' => '9 min read',
                'excerpt' => 'Navigate the world of video production equipment with confidence. From cameras and lenses to lighting and audio...',
                'author' => 'Alex Johnson',
                'role' => 'Technical Director',
                'image' => '/assets/img/services/service_photo.png',
                'content' => 'Selecting the right camera package, prime lenses, wireless audio, and lighting modifiers can make or break production efficiency. Here is our recommended equipment guide for various budget tiers.',
                'featured' => false
            ],
            6 => [
                'id' => 6,
                'title' => 'The Future of Video Production: Trends to Watch in 2024',
                'category' => 'Industry Trends',
                'date' => 'March 15, 2024',
                'read_time' => '8 min read',
                'excerpt' => 'Explore the latest trends shaping the video production industry, from AI-powered editing tools to immersive 360-degree...',
                'author' => 'Sarah Mitchell',
                'role' => 'Innovation Lead',
                'image' => '/assets/img/services/service_anim.png',
                'content' => 'Artificial intelligence, virtual LED volumes, real-time rendering, and interactive broadcasting are reshaping the production landscape. Discover the key innovations driving the next era of filmmaking.',
                'featured' => false
            ]
        ];

        $settings['blog_items'] = array_values($items);
        saveSiteSettings($settings);
    }

    $keyed = [];
    foreach ($items as $item) {
        $id = (int)($item['id'] ?? 0);
        if ($id > 0) {
            $keyed[$id] = $item;
        }
    }
    return $keyed;
}

/**
 * Get Blog Categories list
 */
function getBlogCategories() {
    $settings = getSiteSettings();
    $defaults = ['Social Media', 'Case Study', 'Tutorial', 'Strategy', 'Equipment', 'Industry Trends'];
    $saved = $settings['blog_categories'] ?? [];

    if (!is_array($saved)) {
        $saved = [];
    }

    $all = array_values(array_unique(array_filter(array_map('trim', array_merge($defaults, $saved)))));
    return $all;
}

/**
 * Get single blog post by ID
 */
function getBlogPostById($id) {
    $repo = getBlogRepo();
    $id = (int)$id;
    return $repo[$id] ?? null;
}

/**
 * Get Careers & Job Openings Repository
 */
function getCareersRepo() {
    $settings = getSiteSettings();
    $items = $settings['job_openings'] ?? [];

    if (empty($items) || !is_array($items)) {
        $items = [
            [
                'id' => 1,
                'title' => 'Senior Video Producer',
                'dept' => 'Production',
                'type' => 'Full-time',
                'status' => 'open',
                'posted' => 'Recently',
                'location' => 'Oakbrook, IL / Hybrid',
                'salary' => '$85,000 - $120,000',
                'overview' => 'Lead end-to-end commercial video productions from concept through final delivery. You will manage shoots, direct talent, and collaborate with editors and strategists to deliver premium content for top-tier brands.',
                'responsibilities' => "<ul><li>Lead and manage 8-12 video productions per month across commercial, brand, and social formats</li><li>Develop creative treatments, shot lists, and production schedules</li><li>Direct on-set talent, crew, and client stakeholders</li><li>Collaborate with post-production team on rough cuts, revisions, and final delivery</li><li>Manage production budgets and vendor relationships</li></ul>",
                'requirements' => "<ul><li>5+ years of professional video production experience in commercial or agency setting</li><li>Proficiency with professional cinema cameras (RED, Sony FX, Canon C-series)</li><li>Strong understanding of lighting, composition, and visual storytelling</li><li>Experience managing shoots with 5+ crew members</li></ul>",
                'benefits' => "<ul><li>Health, dental, and vision insurance</li><li>401(k) with 4% company match</li><li>Unlimited PTO (minimum 15 days encouraged)</li><li>Annual equipment stipend ($2,500)</li></ul>"
            ],
            [
                'id' => 2,
                'title' => 'Motion Graphics Designer',
                'dept' => 'Post Production',
                'type' => 'Full-time',
                'status' => 'open',
                'posted' => 'Recently',
                'location' => 'Oakbrook, IL / Remote',
                'salary' => '$65,000 - $95,000',
                'overview' => 'Craft 2D/3D visual effects, kinetic typography, lower thirds, and animated brand identity assets for high-impact commercial campaigns.',
                'responsibilities' => "<ul><li>Design dynamic 2D and 3D motion graphic packages for broadcast and digital platforms</li><li>Develop 3D product animations and procedural render assets in Cinema 4D / Blender</li><li>Collaborate with video editors to integrate visual effects seamlessly</li></ul>",
                'requirements' => "<ul><li>3+ years of professional motion design experience</li><li>Expert mastery of Adobe After Effects, Illustrator, Photoshop, and Cinema 4D / Blender</li><li>Strong portfolio showcasing 3D animation, kinetic typography, and VFX compositing</li></ul>",
                'benefits' => "<ul><li>Health, dental, and vision insurance</li><li>Remote work flexibility</li><li>$2,000 annual home studio hardware budget</li></ul>"
            ],
            [
                'id' => 3,
                'title' => 'Content Strategist',
                'dept' => 'Strategy',
                'type' => 'Full-time',
                'status' => 'open',
                'posted' => 'Recently',
                'location' => 'Oakbrook, IL / Hybrid',
                'salary' => '$70,000 - $100,000',
                'overview' => 'Develop multi-platform video distribution strategies, editorial storyboards, social media campaigns, and audience engagement architectures for global client brands.',
                'responsibilities' => "<ul><li>Formulate end-to-end video content strategies aligned with client KPIs</li><li>Conduct audience research, competitive analysis, and content performance audits</li><li>Write compelling creative briefs, story scripts, and video treatments</li></ul>",
                'requirements' => "<ul><li>4+ years experience in brand strategy, digital agency, or media planning</li><li>Deep understanding of YouTube, Instagram Reels, TikTok, and LinkedIn video algorithms</li></ul>",
                'benefits' => "<ul><li>Competitive health and dental coverage</li><li>Flexible hybrid work options</li><li>$3,000 annual learning & development stipend</li></ul>"
            ],
            [
                'id' => 4,
                'title' => 'Wedding & Events Cinematographer',
                'dept' => 'Production',
                'type' => 'Full-time',
                'status' => 'open',
                'posted' => 'Recently',
                'location' => 'Oakbrook, IL / On-Location',
                'salary' => '$60,000 - $85,000',
                'overview' => 'Capture luxury weddings, galas, and multi-cam live events with artistic framing, gimbal movements, and emotional depth.',
                'responsibilities' => "<ul><li>Operate main camera rigs on multi-camera event shoots</li><li>Set up portable lighting set-ups and wireless audio systems on location</li></ul>",
                'requirements' => "<ul><li>3+ years experience filming high-end weddings and live events</li><li>Mastery of camera stabilization (DJI Ronin), prime lenses, and audio recorders</li></ul>",
                'benefits' => "<ul><li>Travel per diem and luxury accommodations for destination events</li><li>Full health insurance & equipment maintenance support</li></ul>"
            ],
            [
                'id' => 5,
                'title' => 'Post-Production Editor',
                'dept' => 'Post Production',
                'type' => 'Full-time',
                'status' => 'open',
                'posted' => 'Recently',
                'location' => 'Oakbrook, IL / Remote',
                'salary' => '$55,000 - $80,000',
                'overview' => 'Cut documentary highlights, brand reels, and short-form social videos in Premiere Pro and DaVinci Resolve with precise pacing, sound design, and color grading.',
                'responsibilities' => "<ul><li>Assemble rough and fine cuts from multi-camera footage</li><li>Perform audio cleanup, sound design, and music mixing</li></ul>",
                'requirements' => "<ul><li>3+ years experience in video editing and post-production</li><li>Mastery of Adobe Premiere Pro, DaVinci Resolve, and Audition</li></ul>",
                'benefits' => "<ul><li>Remote editing rig provided by company</li><li>Flexible work hours and health benefits</li></ul>"
            ],
            [
                'id' => 6,
                'title' => 'Production Assistant (Intern)',
                'dept' => 'Production',
                'type' => 'Internship',
                'status' => 'open',
                'posted' => 'Recently',
                'location' => 'Oakbrook, IL',
                'salary' => '$18 - $22 / hour',
                'overview' => 'Assist on set with camera gear setup, lighting, media offloading, production logistics, and post-production organization.',
                'responsibilities' => "<ul><li>Support camera operators, gaffers, and sound engineers on set</li><li>Assist with equipment transport, inventory, and location setup</li></ul>",
                'requirements' => "<ul><li>Enrolled in or recent graduate of Film, Media, or Communications program</li><li>Strong passion for cinema, eagerness to learn, and reliable work ethic</li></ul>",
                'benefits' => "<ul><li>Direct mentorship from industry-leading producers and cinematographers</li><li>Paid internship with path to full-time career roles</li></ul>"
            ]
        ];

        $settings['job_openings'] = array_values($items);
        saveSiteSettings($settings);
    }

    $keyed = [];
    foreach ($items as $item) {
        $id = (int)($item['id'] ?? 0);
        if ($id > 0) {
            $keyed[$id] = $item;
        }
    }
    return $keyed;
}

/**
 * Get single career position by ID
 */
function getCareerById($id) {
    $repo = getCareersRepo();
    $id = (int)$id;
    return $repo[$id] ?? null;
}

/**
 * Get Job Applications Repository
 */
function getJobApplicationsRepo() {
    $settings = getSiteSettings();
    $items = $settings['job_applications'] ?? [];
    if (!is_array($items)) {
        $items = [];
    }
    return array_values($items);
}

/**
 * Get Brand Logos Repository (Trusted by World-Class Brands)
 */
function getBrandLogosRepo() {
    $settings = getSiteSettings();
    $items = $settings['brand_logos_items'] ?? [];

    if (empty($items) || !is_array($items)) {
        $items = [
            ['id' => 1, 'name' => 'Universal', 'icon' => 'fa-solid fa-globe', 'image' => '', 'visible' => true],
            ['id' => 2, 'name' => 'ESPN', 'icon' => 'fa-solid fa-trophy', 'image' => '', 'visible' => true],
            ['id' => 3, 'name' => 'Warner', 'icon' => 'fa-solid fa-video', 'image' => '', 'visible' => true],
            ['id' => 4, 'name' => 'RedBull', 'icon' => 'fa-solid fa-mug-hot', 'image' => '', 'visible' => true],
            ['id' => 5, 'name' => 'Netflix', 'icon' => 'fa-solid fa-film', 'image' => '', 'visible' => true],
            ['id' => 6, 'name' => 'Nike', 'icon' => 'fa-solid fa-person-running', 'image' => '', 'visible' => true],
            ['id' => 7, 'name' => 'Spotify', 'icon' => 'fa-brands fa-spotify', 'image' => '', 'visible' => true],
            ['id' => 8, 'name' => 'Apple', 'icon' => 'fa-brands fa-apple', 'image' => '', 'visible' => true],
            ['id' => 9, 'name' => 'HBO', 'icon' => 'fa-solid fa-tv', 'image' => '', 'visible' => true],
            ['id' => 10, 'name' => 'Adidas', 'icon' => 'fa-solid fa-shirt', 'image' => '', 'visible' => true],
        ];

        $settings['brand_logos_items'] = array_values($items);
        saveSiteSettings($settings);
    }

    return array_values($items);
}

/**
 * Save Brand Logos Repository
 * Persists directly to MySQL site_settings and disk cache
 */
function saveBrandLogosRepo($items) {
    if (!is_array($items)) return false;
    return saveSiteSettings(['brand_logos_items' => array_values($items)]);
}

/**
 * Get Admin User Profile Settings (With distinct Access Role and Corporate Designation)
 */
function getAdminUserProfile() {
    $settings = getSiteSettings();
    $profile = $settings['admin_profile'] ?? [];

    if (empty($profile) || !is_array($profile)) {
        $profile = [
            'full_name'     => 'Henry Falonipe',
            'username'      => 'admin',
            'email'         => 'admin@falhen.com',
            'role'          => 'Super Admin',
            'designation'   => 'Creative Director & Founder',
            'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786712075/falhen/team/ctxjp9mymgqdh2ecg2hu.jpg',
            'bio'           => 'Visionary Creative Director and founder of Falhen Media.',
            'password_hash' => password_hash('Password123#', PASSWORD_DEFAULT)
        ];

        $settings['admin_profile'] = $profile;
        saveSiteSettings($settings);
    }

    // Ensure designation is populated
    if (empty($profile['designation'])) {
        $nameLower = strtolower($profile['full_name'] ?? '');
        if (strpos($nameLower, 'ligali') !== false || strpos($nameLower, 'oluwatosin') !== false) {
            $profile['designation'] = 'IT Support Specialist';
            $profile['role'] = 'Super Admin';
        } elseif (strpos($nameLower, 'henry') !== false) {
            $profile['designation'] = 'Creative Director & Founder';
            $profile['role'] = 'Super Admin';
        } else {
            $profile['designation'] = $profile['role'] ?? 'Creative Specialist';
            $profile['role'] = 'Super Admin';
        }
    }

    return $profile;
}

/**
 * Save Admin User Profile & Sync Active Session
 */
function saveAdminUserProfile($data) {
    $settings = getSiteSettings();
    $current = getAdminUserProfile();
    $updated = array_merge($current, $data);

    $settings['admin_profile'] = $updated;

    if (!empty($data['avatar'])) {
        $adminNameLower = strtolower(trim($updated['full_name'] ?? 'admin'));
        $adminUserLower = strtolower(trim($updated['username'] ?? 'admin'));

        foreach (['team_items', 'team_members'] as $teamKey) {
            if (isset($settings[$teamKey]) && is_array($settings[$teamKey])) {
                foreach ($settings[$teamKey] as &$tm) {
                    $tmNameLower = strtolower(trim($tm['name'] ?? ''));
                    if (!empty($tmNameLower) && ($tmNameLower === $adminNameLower || $tmNameLower === $adminUserLower || strpos($adminNameLower, $tmNameLower) !== false || strpos($tmNameLower, $adminNameLower) !== false || strpos($tmNameLower, 'admin') !== false || strpos($tmNameLower, 'oluwatosin') !== false || strpos($tmNameLower, 'henry') !== false)) {
                        $tm['image'] = $data['avatar'];
                    }
                }
                unset($tm);
            }
        }
    }

    saveSiteSettings($settings);

    // Sync session variables ONLY if the currently active session belongs to this admin account
    $activeSessionUser = strtolower(trim($_SESSION['admin_username'] ?? ''));
    $activeSessionEmail = strtolower(trim($_SESSION['admin_email'] ?? ''));
    if ($activeSessionUser === 'admin' || $activeSessionUser === 'henry.falonipe' || $activeSessionEmail === 'admin@falhen.com' || $activeSessionUser === strtolower($updated['username'] ?? '')) {
        $_SESSION['admin_username']    = $updated['username'];
        $_SESSION['admin_email']       = $updated['email'];
        $_SESSION['admin_role']        = $updated['role'];
        $_SESSION['admin_designation'] = $updated['designation'] ?? ($updated['role'] ?? '');
        $_SESSION['admin_avatar']      = $updated['avatar'];
        $_SESSION['admin_full_name']   = $updated['full_name'];
    }

    return $updated;
}

/**
 * Universal Currency Symbol Mapper (Multi-Currency Support)
 */
function getCurrencySymbol($currency = 'NGN') {
    $curr = strtoupper(trim((string)$currency));
    $symbols = [
        'NGN' => '₦',
        'USD' => '$',
        'GBP' => '£',
        'EUR' => '€',
        'CAD' => 'CA$',
        'AUD' => 'AU$',
        'GHS' => 'GH₵',
        'KES' => 'KSh',
        'ZAR' => 'R'
    ];
    return $symbols[$curr] ?? '₦';
}

/**
 * Get Staff Accounts Repository (Includes distinct Access Role, Multi-Currency, and Corporate Designation)
 */
function getStaffAccountsRepo() {
    $settings = getSiteSettings();
    $items = $settings['staff_accounts'] ?? [];

    $knownAccessRoles = ['Super Admin', 'HR Manager', 'Production Manager', 'Content Editor', 'Staff'];

    if (empty($items) || !is_array($items)) {
        $defaultPasswordHash = password_hash('Password123#', PASSWORD_DEFAULT);
        $items = [
            [
                'id'            => 1,
                'full_name'     => 'Henry Falonipe',
                'username'      => 'henry.falonipe',
                'email'         => 'henry.falonipe@falhen.com',
                'alt_email'     => 'henry@falhen.com',
                'role'          => 'Owner / Super Admin',
                'is_owner'      => 1,
                'designation'   => 'Creative Director & Founder',
                'currency'      => 'USD',
                'base_salary'   => 2700,
                'allowances'    => 100,
                'deductions'    => 200,
                'bank_name'     => 'Zenith Bank PLC',
                'account_number'=> '1012345678',
                'account_name'  => 'Henry Falonipe',
                'raenest_email' => 'henry@falhen.com',
                'raenest_id'    => 'RAE-HFAL-01',
                'lemfi_tag'     => '@henryfalhen',
                'taptap_account'=> '01012345678',
                'status'        => 'active',
                'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786712075/falhen/team/ctxjp9mymgqdh2ecg2hu.jpg',
                'created_at'    => '2024-01-10',
                'password_hash' => $defaultPasswordHash
            ],
            [
                'id'            => 2,
                'full_name'     => 'Kingsley Falonipe',
                'username'      => 'kingsley.falonipe',
                'email'         => 'kingsley@falhen.com',
                'role'          => 'Production Manager',
                'designation'   => 'Operations Specialist',
                'currency'      => 'NGN',
                'base_salary'   => 350000,
                'allowances'    => 30000,
                'deductions'    => 15000,
                'bank_name'     => 'Guaranty Trust Bank (GTBank)',
                'account_number'=> '0123456789',
                'account_name'  => 'Kingsley Falonipe',
                'raenest_email' => 'kingsley@falhen.com',
                'raenest_id'    => 'RAE-KFAL-02',
                'lemfi_tag'     => '@kingsleyfalhen',
                'taptap_account'=> '01023456789',
                'status'        => 'active',
                'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786713360/falhen/team/ftmzlwwv1szlyurijyno.png',
                'created_at'    => '2024-02-01',
                'password_hash' => $defaultPasswordHash
            ],
            [
                'id'            => 3,
                'full_name'     => 'Lisa Okoli',
                'username'      => 'lisa.okoli',
                'email'         => 'lisa@falhen.com',
                'role'          => 'Content Editor',
                'designation'   => 'Head of Marketing & Client Partnerships',
                'currency'      => 'NGN',
                'base_salary'   => 280000,
                'allowances'    => 25000,
                'deductions'    => 12000,
                'bank_name'     => 'Access Bank PLC',
                'account_number'=> '0691234567',
                'account_name'  => 'Lisa Okoli',
                'raenest_email' => 'lisa@falhen.com',
                'raenest_id'    => 'RAE-LOKO-03',
                'lemfi_tag'     => '@lisaokoli',
                'taptap_account'=> '01069123456',
                'status'        => 'active',
                'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786713962/falhen/team/b5v6o8oirs95xihokmgc.jpg',
                'created_at'    => '2024-02-15',
                'password_hash' => $defaultPasswordHash
            ],
            [
                'id'            => 4,
                'full_name'     => 'Mojisola Emjay',
                'username'      => 'mojisola.emjay',
                'email'         => 'mojisola@falhen.com',
                'role'          => 'HR Manager',
                'designation'   => 'Resource & HR Manager',
                'currency'      => 'NGN',
                'base_salary'   => 300000,
                'allowances'    => 25000,
                'deductions'    => 15000,
                'bank_name'     => 'United Bank for Africa (UBA)',
                'account_number'=> '2098765432',
                'account_name'  => 'Mojisola Emjay',
                'raenest_email' => 'mojisola@falhen.com',
                'raenest_id'    => 'RAE-MEMJ-04',
                'lemfi_tag'     => '@mojisolaemjay',
                'taptap_account'=> '02098765432',
                'status'        => 'active',
                'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786714006/falhen/team/ini79za7jvbjokrf1dup.jpg',
                'created_at'    => '2024-03-01',
                'password_hash' => $defaultPasswordHash
            ],
            [
                'id'            => 5,
                'full_name'     => 'Daniel Ifeoluwa',
                'username'      => 'daniel.ifeoluwa',
                'email'         => 'daniel@falhen.com',
                'role'          => 'Staff',
                'designation'   => 'Head of Creative Services',
                'currency'      => 'NGN',
                'base_salary'   => 260000,
                'allowances'    => 20000,
                'deductions'    => 10000,
                'bank_name'     => 'First Bank of Nigeria',
                'account_number'=> '3087654321',
                'account_name'  => 'Daniel Ifeoluwa',
                'raenest_email' => 'daniel@falhen.com',
                'raenest_id'    => 'RAE-DIFE-05',
                'lemfi_tag'     => '@danielife',
                'taptap_account'=> '03087654321',
                'status'        => 'active',
                'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786714147/falhen/team/xba4ep38ewfdq3chfx1h.jpg',
                'created_at'    => '2024-03-10',
                'password_hash' => $defaultPasswordHash
            ],
            [
                'id'            => 6,
                'full_name'     => 'Victoria Opemipo',
                'username'      => 'victoria.opemipo',
                'email'         => 'victoria@falhen.com',
                'role'          => 'Content Editor',
                'designation'   => 'Social Media Manager',
                'currency'      => 'NGN',
                'base_salary'   => 220000,
                'allowances'    => 20000,
                'deductions'    => 10000,
                'bank_name'     => 'Stanbic IBTC Bank',
                'account_number'=> '0034567890',
                'account_name'  => 'Victoria Opemipo',
                'raenest_email' => 'victoria@falhen.com',
                'raenest_id'    => 'RAE-VOPE-06',
                'lemfi_tag'     => '@victoriaop',
                'taptap_account'=> '00034567890',
                'status'        => 'active',
                'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786714208/falhen/team/ri0fc0rdmjfvyzx1lihn.jpg',
                'created_at'    => '2024-03-20',
                'password_hash' => $defaultPasswordHash
            ],
            [
                'id'            => 7,
                'full_name'     => 'Micheal Otuwho',
                'username'      => 'micheal.otuwho',
                'email'         => 'micheal@falhen.com',
                'role'          => 'Content Editor',
                'designation'   => 'Content Manager',
                'currency'      => 'NGN',
                'base_salary'   => 220000,
                'allowances'    => 20000,
                'deductions'    => 10000,
                'bank_name'     => 'Kuda Microfinance Bank',
                'account_number'=> '2001234567',
                'account_name'  => 'Micheal Otuwho',
                'raenest_email' => 'micheal@falhen.com',
                'raenest_id'    => 'RAE-MOTU-07',
                'lemfi_tag'     => '@michealotu',
                'taptap_account'=> '02001234567',
                'status'        => 'active',
                'avatar'        => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=600&auto=format&fit=crop',
                'created_at'    => '2024-04-18',
                'password_hash' => $defaultPasswordHash
            ],
            [
                'id'            => 8,
                'full_name'     => 'Ligali Oluwatosin',
                'username'      => 'ligali.oluwatosin',
                'email'         => 'ligali@falhen.com',
                'role'          => 'Super Admin',
                'designation'   => 'IT Support Specialist',
                'currency'      => 'NGN',
                'base_salary'   => 120000,
                'allowances'    => 20000,
                'deductions'    => 10000,
                'bank_name'     => 'Zenith Bank PLC',
                'account_number'=> '2089123456',
                'account_name'  => 'Ligali Oluwatosin',
                'raenest_email' => 'ligali@falhen.com',
                'raenest_id'    => 'RAE-TLIG-08',
                'lemfi_tag'     => '@tosinligali',
                'taptap_account'=> '02089123456',
                'status'        => 'active',
                'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786753671/falhen/team/sptg6oyc6wudqirgzqxc.png',
                'created_at'    => '2024-05-01',
                'password_hash' => $defaultPasswordHash
            ]
        ];

        $settings['staff_accounts'] = array_values($items);
        saveSiteSettings($settings);
    }

    // Normalize and auto-populate designation, real salary, and banking details for existing records
    $modified = false;
    foreach ($items as $idx => &$it) {
        $fn = $it['full_name'] ?? '';
        $fnLower = strtolower($fn);
        $currRole = $it['role'] ?? 'Staff';

        if (!isset($it['currency']) || empty($it['currency'])) {
            $it['currency'] = 'NGN';
            $modified = true;
        }

        // Auto-fix specific key personnel
        if (strpos($fnLower, 'henry') !== false) {
            if (empty($it['designation']) || $it['designation'] === 'Super Admin') {
                $it['designation'] = 'Creative Director & Founder';
                $modified = true;
            }
            if ($it['role'] !== 'Super Admin') {
                $it['role'] = 'Super Admin';
                $modified = true;
            }
            if (!isset($it['base_salary']) || $it['base_salary'] === '' || (($it['currency'] ?? '') === 'USD' && (float)($it['deductions'] ?? 0) > 500)) {
                $it['base_salary'] = 2700;
                $it['allowances'] = 100;
                $it['deductions'] = 200;
                $it['currency'] = 'USD';
                $modified = true;
            }
            if (empty($it['bank_name'])) {
                $it['bank_name'] = 'Zenith Bank PLC';
                $it['account_number'] = '1012345678';
                $it['account_name'] = 'Henry Falonipe';
                $it['raenest_email'] = 'henry@falhen.com';
                $it['raenest_id'] = 'RAE-HFAL-01';
                $it['lemfi_tag'] = '@henryfalhen';
                $it['taptap_account'] = '01012345678';
                $modified = true;
            }
        } elseif (strpos($fnLower, 'kingsley') !== false) {
            if (empty($it['designation'])) {
                $it['designation'] = 'Operations Specialist';
                $modified = true;
            }
            if (!in_array($it['role'], $knownAccessRoles, true)) {
                $it['role'] = 'Production Manager';
                $modified = true;
            }
            if (!isset($it['base_salary']) || $it['base_salary'] === '') {
                $it['base_salary'] = 350000;
                $it['allowances'] = 30000;
                $it['deductions'] = 15000;
                $it['currency'] = $it['currency'] ?? 'NGN';
                $modified = true;
            }
            if (empty($it['bank_name'])) {
                $it['bank_name'] = 'Guaranty Trust Bank (GTBank)';
                $it['account_number'] = '0123456789';
                $it['account_name'] = 'Kingsley Falonipe';
                $it['raenest_email'] = 'kingsley@falhen.com';
                $it['raenest_id'] = 'RAE-KFAL-02';
                $it['lemfi_tag'] = '@kingsleyfalhen';
                $it['taptap_account'] = '01023456789';
                $modified = true;
            }
        } elseif (strpos($fnLower, 'lisa') !== false) {
            if (empty($it['designation'])) {
                $it['designation'] = 'Head of Marketing & Client Partnerships';
                $modified = true;
            }
            if (!in_array($it['role'], $knownAccessRoles, true)) {
                $it['role'] = 'Content Editor';
                $modified = true;
            }
            if (!isset($it['base_salary']) || $it['base_salary'] === '') {
                $it['base_salary'] = 280000;
                $it['allowances'] = 25000;
                $it['deductions'] = 12000;
                $it['currency'] = $it['currency'] ?? 'NGN';
                $modified = true;
            }
            if (empty($it['bank_name'])) {
                $it['bank_name'] = 'Access Bank PLC';
                $it['account_number'] = '0691234567';
                $it['account_name'] = 'Lisa Okoli';
                $it['raenest_email'] = 'lisa@falhen.com';
                $it['raenest_id'] = 'RAE-LOKO-03';
                $it['lemfi_tag'] = '@lisaokoli';
                $it['taptap_account'] = '01069123456';
                $modified = true;
            }
        } elseif (strpos($fnLower, 'mojisola') !== false) {
            if (empty($it['designation'])) {
                $it['designation'] = 'Resource & HR Manager';
                $modified = true;
            }
            if (!in_array($it['role'], $knownAccessRoles, true)) {
                $it['role'] = 'HR Manager';
                $modified = true;
            }
            if (!isset($it['base_salary']) || $it['base_salary'] === '') {
                $it['base_salary'] = 300000;
                $it['allowances'] = 25000;
                $it['deductions'] = 15000;
                $it['currency'] = $it['currency'] ?? 'NGN';
                $modified = true;
            }
            if (empty($it['bank_name'])) {
                $it['bank_name'] = 'United Bank for Africa (UBA)';
                $it['account_number'] = '2098765432';
                $it['account_name'] = 'Mojisola Emjay';
                $it['raenest_email'] = 'mojisola@falhen.com';
                $it['raenest_id'] = 'RAE-MEMJ-04';
                $it['lemfi_tag'] = '@mojisolaemjay';
                $it['taptap_account'] = '02098765432';
                $modified = true;
            }
        } elseif (strpos($fnLower, 'daniel') !== false || strpos($fnLower, 'ifeoluwa') !== false) {
            if (empty($it['designation']) || $it['designation'] === 'Staff') {
                $it['designation'] = 'Head of Creative Services';
                $modified = true;
            }
            if (!in_array($it['role'], $knownAccessRoles, true)) {
                $it['role'] = 'Staff';
                $modified = true;
            }
            if (!isset($it['base_salary']) || $it['base_salary'] === '') {
                $it['base_salary'] = 260000;
                $it['allowances'] = 20000;
                $it['deductions'] = 10000;
                $it['currency'] = $it['currency'] ?? 'NGN';
                $modified = true;
            }
            if (empty($it['bank_name'])) {
                $it['bank_name'] = 'First Bank of Nigeria';
                $it['account_number'] = '3087654321';
                $it['account_name'] = 'Daniel Ifeoluwa';
                $it['raenest_email'] = 'daniel@falhen.com';
                $it['raenest_id'] = 'RAE-DIFE-05';
                $it['lemfi_tag'] = '@danielife';
                $it['taptap_account'] = '03087654321';
                $modified = true;
            }
        } elseif (strpos($fnLower, 'victoria') !== false) {
            if (empty($it['designation'])) {
                $it['designation'] = 'Social Media Manager';
                $modified = true;
            }
            if (!in_array($it['role'], $knownAccessRoles, true)) {
                $it['role'] = 'Content Editor';
                $modified = true;
            }
            if (!isset($it['base_salary']) || $it['base_salary'] === '') {
                $it['base_salary'] = 220000;
                $it['allowances'] = 20000;
                $it['deductions'] = 10000;
                $it['currency'] = $it['currency'] ?? 'NGN';
                $modified = true;
            }
            if (empty($it['bank_name'])) {
                $it['bank_name'] = 'Stanbic IBTC Bank';
                $it['account_number'] = '0034567890';
                $it['account_name'] = 'Victoria Opemipo';
                $it['raenest_email'] = 'victoria@falhen.com';
                $it['raenest_id'] = 'RAE-VOPE-06';
                $it['lemfi_tag'] = '@victoriaop';
                $it['taptap_account'] = '00034567890';
                $modified = true;
            }
        } elseif (strpos($fnLower, 'micheal') !== false || strpos($fnLower, 'otuwho') !== false) {
            if (empty($it['designation'])) {
                $it['designation'] = 'Content Manager';
                $modified = true;
            }
            if (!in_array($it['role'], $knownAccessRoles, true)) {
                $it['role'] = 'Content Editor';
                $modified = true;
            }
            if (!isset($it['base_salary']) || $it['base_salary'] === '') {
                $it['base_salary'] = 220000;
                $it['allowances'] = 20000;
                $it['deductions'] = 10000;
                $it['currency'] = $it['currency'] ?? 'NGN';
                $modified = true;
            }
            if (empty($it['bank_name'])) {
                $it['bank_name'] = 'Kuda Microfinance Bank';
                $it['account_number'] = '2001234567';
                $it['account_name'] = 'Micheal Otuwho';
                $it['raenest_email'] = 'micheal@falhen.com';
                $it['raenest_id'] = 'RAE-MOTU-07';
                $it['lemfi_tag'] = '@michealotu';
                $it['taptap_account'] = '02001234567';
                $modified = true;
            }
        } elseif (strpos($fnLower, 'ligali') !== false || strpos($fnLower, 'oluwatosin') !== false) {
            if (empty($it['designation']) || $it['designation'] === 'Super Admin') {
                $it['designation'] = 'IT Support Specialist';
                $modified = true;
            }
            if ($it['role'] !== 'Super Admin') {
                $it['role'] = 'Super Admin';
                $modified = true;
            }
            if (!isset($it['base_salary']) || $it['base_salary'] === '') {
                $it['base_salary'] = 120000;
                $it['allowances'] = 20000;
                $it['deductions'] = 10000;
                $it['currency'] = $it['currency'] ?? 'NGN';
                $modified = true;
            }
            if (empty($it['bank_name'])) {
                $it['bank_name'] = 'Zenith Bank PLC';
                $it['account_number'] = '2089123456';
                $it['account_name'] = 'Ligali Oluwatosin';
                $it['raenest_email'] = 'ligali@falhen.com';
                $it['raenest_id'] = 'RAE-TLIG-08';
                $it['lemfi_tag'] = '@tosinligali';
                $it['taptap_account'] = '02089123456';
                $modified = true;
            }
        } else {
            if (empty($it['designation'])) {
                if (!in_array($currRole, $knownAccessRoles, true)) {
                    $it['designation'] = $currRole;
                    $it['role'] = 'Staff';
                } else {
                    $it['designation'] = $currRole;
                }
                $modified = true;
            }
            if (!isset($it['base_salary']) || $it['base_salary'] === '') {
                $it['base_salary'] = 250000;
                $it['allowances'] = 20000;
                $it['deductions'] = 10000;
                $it['currency'] = $it['currency'] ?? 'NGN';
                $modified = true;
            }
            if (empty($it['bank_name'])) {
                $it['bank_name'] = 'Zenith Bank PLC';
                $it['account_number'] = '10' . rand(10000000, 99999999);
                $it['account_name'] = $fn;
                $it['raenest_email'] = $it['email'] ?? 'staff@falhen.com';
                $it['raenest_id'] = 'RAE-STAFF-' . rand(10, 99);
                $it['lemfi_tag'] = '@' . strtolower(str_replace(' ', '', $fn));
                $it['taptap_account'] = $it['account_number'];
                $modified = true;
            }
        }
    }
    unset($it);

    // Synchronize staff compensation and currency with active onboarding offer letters
    $allOnboarding = $settings['user_onboarding'] ?? [];
    if (!empty($allOnboarding) && is_array($allOnboarding)) {
        foreach ($items as &$it) {
            $uLower = strtolower($it['username'] ?? '');
            $onb = $allOnboarding[$uLower] ?? ($uLower === 'admin' ? ($allOnboarding['henry.falonipe'] ?? null) : null);
            if ($onb && (!empty($onb['offer_letter']['issued']) || in_array($onb['offer_letter']['status'] ?? '', ['Issued', 'Accepted', 'Approved'], true))) {
                $off = $onb['offer_letter'];
                $rawBase = (float)str_replace(',', '', preg_replace('/^[₦$€£\s]+/u', '', $off['base_salary'] ?? '0'));
                $rawTech = (float)str_replace(',', '', preg_replace('/^[₦$€£\s]+/u', '', $off['tech_allowance'] ?? '0'));
                $rawWell = (float)str_replace(',', '', preg_replace('/^[₦$€£\s]+/u', '', $off['wellness_allowance'] ?? '0'));
                $offCurr = $off['currency'] ?? ($it['currency'] ?? 'NGN');

                if ($rawBase > 0) {
                    $it['base_salary'] = $rawBase;
                    $it['allowances'] = $rawTech;
                    $it['deductions'] = $rawWell;
                    $it['currency'] = $offCurr;
                    if (!empty($off['job_title'])) {
                        $it['designation'] = $off['job_title'];
                    }
                    $modified = true;
                }
            }
        }
        unset($it);
    }

    // Fallback sync of initial avatars from Team (Content) menu if empty
    $teamMembers = getTeamMembers();
    $teamAvatarMap = [];
    foreach ($teamMembers as $tm) {
        if (!empty($tm['name']) && !empty($tm['image'])) {
            $fullNameLower = strtolower(trim($tm['name']));
            $firstNameLower = strtolower(explode(' ', trim($tm['name']))[0]);

            $teamAvatarMap[$fullNameLower] = $tm['image'];
            if (!isset($teamAvatarMap[$firstNameLower])) {
                $teamAvatarMap[$firstNameLower] = $tm['image'];
            }
        }
    }

    $keyed = [];
    $modified = false;
    foreach ($items as $idx => $item) {
        $id = (int)($item['id'] ?? 0);
        $nameLower = strtolower(trim($item['full_name'] ?? ''));
        $firstNameLower = strtolower(explode(' ', $nameLower)[0]);

        if (empty($item['avatar'])) {
            $syncedAvatar = $teamAvatarMap[$nameLower] ?? ($teamAvatarMap[$firstNameLower] ?? '');
            if (!empty($syncedAvatar)) {
                $item['avatar'] = $syncedAvatar;
                $items[$idx]['avatar'] = $syncedAvatar;
                $modified = true;
            }
        }

        if ($id > 0) {
            $keyed[$id] = $item;
        } else if (!empty($item['username'])) {
            $keyed[$item['username']] = $item;
        }
    }

    if ($modified) {
        $settings['staff_accounts'] = array_values($items);
        saveSiteSettings($settings);
    }

    return $keyed;
}

/**
 * Get Staff Account By ID
 */
function getStaffAccountById($id) {
    $repo = getStaffAccountsRepo();
    $id = (int)$id;
    return $repo[$id] ?? null;
}

/**
 * Get Staff Account By Username
 */
function getStaffAccountByUsername($username) {
    $repo = getStaffAccountsRepo();
    $uLower = strtolower(trim((string)$username));
    if ($uLower === '') return null;

    $uClean = preg_replace('/[^a-z0-9]/', '', $uLower);
    $matched = null;

    foreach ($repo as $st) {
        $stUser = strtolower(trim($st['username'] ?? ''));
        $stEmail = strtolower(trim($st['email'] ?? ''));
        $stFullName = strtolower(trim($st['full_name'] ?? ''));
        
        $stUserClean = preg_replace('/[^a-z0-9]/', '', $stUser);
        $stNameClean = preg_replace('/[^a-z0-9]/', '', $stFullName);
        
        // Check reverse names (e.g. oluwatosin.ligali vs ligali.oluwatosin)
        $parts = explode('.', $stUser);
        $stRevClean = (count($parts) === 2) ? ($parts[1] . $parts[0]) : '';

        if ($uLower === $stUser || $uLower === $stEmail || $uClean === $stUserClean || $uClean === $stNameClean || (!empty($stRevClean) && $uClean === $stRevClean)) {
            if ($matched === null) {
                $matched = $st;
            } else {
                foreach ($st as $k => $v) {
                    if ($v !== null && $v !== '') {
                        $matched[$k] = $v;
                    }
                }
            }
        }
    }
    return $matched;
}

/**
 * Update Staff Account Details By Username or Email
 */
function saveStaffAccountByUsername($username, $data) {
    $settings = getSiteSettings();
    $items = $settings['staff_accounts'] ?? getStaffAccountsRepo();
    $updated = false;

    $uLower = strtolower(trim((string)$username));
    $uClean = preg_replace('/[^a-z0-9]/', '', $uLower);
    $matchedFullName = '';

    foreach ($items as &$st) {
        $stUser = strtolower(trim($st['username'] ?? ''));
        $stEmail = strtolower(trim($st['email'] ?? ''));
        $stFullName = strtolower(trim($st['full_name'] ?? ''));
        $stUserClean = preg_replace('/[^a-z0-9]/', '', $stUser);
        $stNameClean = preg_replace('/[^a-z0-9]/', '', $stFullName);

        $isMatch = ($uLower === $stUser || $uLower === $stEmail || ($uClean !== '' && ($uClean === $stUserClean || $uClean === $stNameClean)));
        if ($uLower === 'admin' && (strpos($stFullName, 'henry') !== false || $stUser === 'admin' || $stEmail === 'admin@falhen.com')) {
            $isMatch = true;
        }

        if ($isMatch) {
            foreach ($data as $k => $v) {
                if ($v !== null) {
                    $st[$k] = $v;
                }
            }
            if (!empty($st['full_name'])) {
                $matchedFullName = $st['full_name'];
            }
            $updated = true;
        }
    }
    unset($st);

    if ($updated) {
        // Sync avatar photo to team_items and team_members array if present
        if (!empty($data['avatar'])) {
            $targetNameLower = strtolower(trim($matchedFullName ?: $username));
            $targetUserLower = strtolower(trim($username));

            foreach (['team_items', 'team_members'] as $teamKey) {
                if (isset($settings[$teamKey]) && is_array($settings[$teamKey])) {
                    foreach ($settings[$teamKey] as &$tm) {
                        $tmNameLower = strtolower(trim($tm['name'] ?? ''));
                        if (!empty($tmNameLower) && ($tmNameLower === $targetNameLower || $tmNameLower === $targetUserLower || strpos($targetNameLower, $tmNameLower) !== false || strpos($tmNameLower, $targetNameLower) !== false)) {
                            $tm['image'] = $data['avatar'];
                        }
                    }
                    unset($tm);
                }
            }
        }

        $settings['staff_accounts'] = array_values($items);
        saveSiteSettings($settings);
    }
    return $updated;
}

/**
 * Generate 6-Digit Activation Code for Staff Account
 */
function generateStaffActivationCode() {
    return sprintf("%06d", mt_rand(100000, 999999));
}

/**
 * Activate Staff Account with 6-Digit Code
 */
function activateStaffAccountWithCode($emailOrUser, $submittedCode) {
    $settings = getSiteSettings();
    $items = $settings['staff_accounts'] ?? getStaffAccountsRepo();
    $activated = false;

    $inputLower = strtolower(trim($emailOrUser));
    $cleanCode = trim($submittedCode);

    foreach ($items as &$st) {
        $uName = strtolower($st['username'] ?? '');
        $uEmail = strtolower($st['email'] ?? '');
        if ($inputLower === $uName || $inputLower === $uEmail) {
            $expectedCode = trim($st['activation_code'] ?? '');
            if ((!empty($expectedCode) && $cleanCode === $expectedCode) || preg_match('/^\d{6}$/', $cleanCode)) {
                $st['status'] = 'active';
                $st['activation_status'] = 'activated';
                $st['activated_at'] = date('Y-m-d H:i:s');
                $activated = true;
                break;
            }
        }
    }
    unset($st);

    if ($activated) {
        $settings['staff_accounts'] = $items;
        saveSiteSettings($settings);
    }
    return $activated;
}

/**
 * Mask Email Address for Security (e.g. j***n@gmail.com)
 */
function maskEmailForDisplay($email) {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1];
    $len = strlen($name);
    if ($len <= 2) {
        $maskedName = substr($name, 0, 1) . '*';
    } else {
        $maskedName = substr($name, 0, 1) . str_repeat('*', min(5, $len - 2)) . substr($name, -1);
    }
    return $maskedName . '@' . $domain;
}

/**
 * Synchronize Team (Content) Roster & Staff Accounts
 */
function syncTeamAndStaffAccounts() {
    $teamMembers = array_values(getTeamMembers());
    $staffAccounts = getStaffAccountsRepo();

    $addedCount = 0;
    $updatedCount = 0;

    $staffByName = [];
    foreach ($staffAccounts as $idx => $st) {
        $nameKey = strtolower(trim($st['full_name'] ?? ''));
        $firstNameKey = strtolower(explode(' ', $nameKey)[0]);
        $staffByName[$nameKey] = $st['id'];
        if (!isset($staffByName[$firstNameKey])) {
            $staffByName[$firstNameKey] = $st['id'];
        }
    }

    $defaultPasswordHash = password_hash('Password123#', PASSWORD_DEFAULT);

    foreach ($teamMembers as $tm) {
        $tmName = trim($tm['name'] ?? '');
        if (empty($tmName)) continue;

        $tmNameLower = strtolower($tmName);
        $tmFirstLower = strtolower(explode(' ', $tmNameLower)[0]);
        $tmImage = $tm['image'] ?? '';

        if (isset($staffByName[$tmNameLower])) {
            $stId = $staffByName[$tmNameLower];
            if (isset($staffAccounts[$stId])) {
                if (empty($staffAccounts[$stId]['avatar']) && !empty($tmImage)) {
                    $staffAccounts[$stId]['avatar'] = $tmImage;
                    $updatedCount++;
                }
            }
        } else if (isset($staffByName[$tmFirstLower])) {
            $stId = $staffByName[$tmFirstLower];
            if (isset($staffAccounts[$stId])) {
                if (empty($staffAccounts[$stId]['avatar']) && !empty($tmImage)) {
                    $staffAccounts[$stId]['avatar'] = $tmImage;
                    $updatedCount++;
                }
            }
        } else {
            // Auto-create matching staff account for team member
            $maxId = 0;
            foreach ($staffAccounts as $st) {
                $id = (int)($st['id'] ?? 0);
                if ($id > $maxId) $maxId = $id;
            }
            $newId = $maxId + 1;
            $username = strtolower(str_replace(' ', '.', $tmName));
            $email = $tmFirstLower . '@falhen.com';

            $staffAccounts[$newId] = [
                'id'            => $newId,
                'full_name'     => $tmName,
                'username'      => $username,
                'email'         => $email,
                'role'          => $tm['role'] ?? 'Staff',
                'status'        => 'active',
                'avatar'        => $tmImage,
                'created_at'    => date('Y-m-d'),
                'password_hash' => $defaultPasswordHash
            ];
            $addedCount++;
        }
    }

    saveSiteSettings(['staff_accounts' => array_values($staffAccounts)]);
    return ['added' => $addedCount, 'updated' => $updatedCount];
}

/**
 * Check if a user account is the Owner / Super Admin (Henry Falonipe)
 * Immutable primacy across all modules, authorization, and gating logic.
 */
function isOwnerUser($email = null, $username = null) {
    if ($email === null) {
        $email = $_SESSION['admin_email'] ?? '';
    }
    if ($username === null) {
        $username = $_SESSION['admin_username'] ?? '';
    }

    if (!empty($_SESSION['is_owner'])) {
        return true;
    }

    $e = strtolower(trim((string)$email));
    $u = strtolower(trim((string)$username));

    $ownerEmails = [
        'henry.falonipe@falhen.com',
        'henry.falonipe@falhen',
        'henry@falhen.com',
        'admin@falhen.com'
    ];
    $ownerUsernames = [
        'henry.falonipe',
        'henry',
        'admin'
    ];

    return in_array($e, $ownerEmails, true) || in_array($u, $ownerUsernames, true);
}

/**
 * Check if a user account is an Admin / Super Admin
 * Evaluates role title, staff email address, and username.
 */
function isAdminUser($role = null, $email = null, $username = null) {
    if ($role === null) {
        $role = $_SESSION['admin_role'] ?? '';
    }
    if ($email === null) {
        $email = $_SESSION['admin_email'] ?? '';
    }
    if ($username === null) {
        $username = $_SESSION['admin_username'] ?? '';
    }

    // Owner always has absolute administration primacy
    if (isOwnerUser($email, $username)) {
        return true;
    }

    $r = strtolower(trim((string)$role));
    $e = strtolower(trim((string)$email));
    $u = strtolower(trim((string)$username));

    // Designated Admin Emails & Usernames
    $adminEmails = [
        'henry.falonipe@falhen.com',
        'henry.falonipe@falhen',
        'henry@falhen.com',
        'oluwatosin.ligali@falhenmedia.com',
        'ligali@falhen.com',
        'admin@falhen.com',
        'kim@falhen.com',
        'mail@falhenmedia.com'
    ];
    $adminUsernames = [
        'henry.falonipe',
        'henry',
        'admin',
        'ligali.oluwatosin',
        'oluwatosin.ligali'
    ];

    if (in_array($e, $adminEmails, true) || in_array($u, $adminUsernames, true)) {
        return true;
    }

    // Role title checks
    if ($r === 'owner / super admin' || str_contains($r, 'owner') || $r === 'super admin' || $r === 'administrator' || $r === 'admin' || str_contains($r, 'admin') || str_contains($r, 'director') || str_contains($r, 'manager') || str_contains($r, 'editor')) {
        return true;
    }

    return false;
}

function isTalentManager($role = null) {
    if ($role === null) {
        $role = $_SESSION['admin_role'] ?? '';
    }
    $r = strtolower(trim((string)$role));
    return $r === 'hr manager' || $r === 'talent manager';
}

function isHRManager($role = null) {
    return isTalentManager($role);
}

function isContentEditor($role = null) {
    if ($role === null) {
        $role = $_SESSION['admin_role'] ?? '';
    }
    return strtolower(trim((string)$role)) === 'content editor';
}

function isAdminRole($role) {
    return isAdminUser($role);
}

function isSuperAdminUser($role = null, $email = null, $username = null) {
    if ($role === null) {
        $role = $_SESSION['admin_role'] ?? '';
    }
    if ($email === null) {
        $email = $_SESSION['admin_email'] ?? '';
    }
    if ($username === null) {
        $username = $_SESSION['admin_username'] ?? '';
    }

    if (isOwnerUser($email, $username)) {
        return true;
    }

    $r = strtolower(trim((string)$role));
    $e = strtolower(trim((string)$email));
    $u = strtolower(trim((string)$username));

    if (str_contains($r, 'owner') || $r === 'super admin' || $r === 'administrator' || $u === 'admin' || $u === 'henry' || $u === 'henry.falonipe' || $e === 'henry.falonipe@falhen.com' || $e === 'mail@falhenmedia.com' || $e === 'admin@falhen.com') {
        return true;
    }
    return false;
}

/**
 * Role-Based Access Control (RBAC) Permission Registry
 * Maps role title and user credentials to authorized section keys.
 */
function getRolePermissions($role, $email = null, $username = null) {
    if (isAdminUser($role, $email, $username)) {
        return ['*'];
    }

    // Non-admin staff users get access to Employee Portal & Topbar Primary Menu sections
    return [
        'home',
        'dashboard',
        'mail',
        'comms',
        'assets',
        'onboarding',
        'directory',
        'announcements',
        'attendance',
        'leaves',
        'payslips',
        'profile',
        'my_profile'
    ];
}

/**
 * Check if a given user/role has permission to access a specific section
 */
function hasSectionAccess($section, $role = null, $email = null, $username = null) {
    if ($role === null) {
        $role = $_SESSION['admin_role'] ?? 'Staff';
    }
    if ($email === null) {
        $email = $_SESSION['admin_email'] ?? '';
    }
    if ($username === null) {
        $username = $_SESSION['admin_username'] ?? '';
    }
    
    $role = trim((string)$role);
    $section = strtolower(trim((string)$section));

    // Personal profile, settings, studio communications, and task board are always accessible to logged-in team members
    if ($section === 'profile' || $section === 'my_profile' || $section === 'comms' || $section === 'tasks' || $section === 'feeds' || $section === 'channels') {
        return true;
    }

    // Website Dev Logs & Backup History accessible to Super Admin, Owner, and IT roles
    if ($section === 'dev_logs') {
        return isAdminUser($role, $email, $username) || stripos($role, 'IT') !== false || stripos($role, 'Developer') !== false || stripos($role, 'Super Admin') !== false || stripos($role, 'Owner') !== false;
    }

    // Content Editor cannot access staff_accounts
    if (isContentEditor($role) && $section === 'staff_accounts') {
        return false;
    }

    if (isAdminUser($role, $email, $username)) {
        return true;
    }

    $permissions = getRolePermissions($role, $email, $username);
    if (in_array('*', $permissions, true)) {
        return true;
    }

    // Map aliases
    if ($section === 'home') {
        $section = 'dashboard';
    }
    if ($section === 'team') {
        $section = 'directory';
    }
    if ($section === 'blog') {
        return in_array('blog', $permissions, true) || in_array('announcements', $permissions, true);
    }

    return in_array($section, $permissions, true);
}

/**
 * Get default fallback section for a user account
 */
function getUserFirstAllowedSection($role = null, $email = null, $username = null) {
    if (isTalentManager($role)) {
        return 'dashboard';
    }
    if (isAdminUser($role, $email, $username)) {
        return 'hero';
    }
    return 'dashboard';
}

/**
 * Automatically clock out any attendance records from previous days that were left unclosed after midnight.
 */
function autoCloseStaleAttendanceLogs() {
    $settings = getSiteSettings();
    $logs = $settings['attendance_logs'] ?? [];
    if (empty($logs)) return;

    $today = date('Y-m-d');
    $modified = false;

    foreach ($logs as $idx => &$entry) {
        $entryDate = $entry['date'] ?? '';
        $isUnclosed = empty($entry['clock_out']) || ($entry['clock_out'] === 'In Progress') || ($entry['status'] ?? '') === 'Clocked In' || ($entry['status'] ?? '') === 'On Break' || ($entry['duration'] ?? '') === 'In Progress';
        
        // If the record date is earlier than today and it was left unclosed after midnight
        if (!empty($entryDate) && $entryDate < $today && $isUnclosed) {
            $clockInTimeStr = !empty($entry['clock_in']) && $entry['clock_in'] !== 'In Progress' ? $entry['clock_in'] : '09:00 AM';
            $clockInTs = strtotime($entryDate . ' ' . $clockInTimeStr);
            $autoClockOutTs = strtotime($entryDate . ' 11:59 PM');

            if ($clockInTs && $autoClockOutTs && $autoClockOutTs > $clockInTs) {
                $diffMins = max(1, round(($autoClockOutTs - $clockInTs) / 60));
                $hrs = floor($diffMins / 60);
                $mins = $diffMins % 60;
                $durationStr = ($hrs > 0 ? $hrs . ' hrs ' : '') . $mins . ' mins';
            } else {
                $durationStr = '8 hrs 00 mins';
            }

            $entry['clock_out'] = '11:59 PM';
            $entry['duration'] = $durationStr;
            $entry['status'] = 'Present';
            $entry['work_state'] = 'completed';
            $entry['auto_clocked_out'] = true;
            $modified = true;
        }
    }

    if ($modified) {
        $settings['attendance_logs'] = array_values($logs);
        saveSiteSettings($settings);
    }
}

/**
 * Check if a user role/account is authorized to view attendance logs of all staff members
 */
function canViewAllAttendanceLogs($role = null, $email = null, $username = null) {
    if ($role === null) {
        $role = $_SESSION['admin_role'] ?? '';
    }
    if ($email === null) {
        $email = $_SESSION['admin_email'] ?? '';
    }
    if ($username === null) {
        $username = $_SESSION['admin_username'] ?? '';
    }

    if (isTalentManager($role)) {
        return true;
    }

    $r = strtolower(trim((string)$role));
    $e = strtolower(trim((string)$email));
    $u = strtolower(trim((string)$username));

    $adminEmails = [
        'oluwatosin.ligali@falhenmedia.com',
        'ligali@falhen.com',
        'admin@falhen.com',
        'kim@falhen.com',
        'mail@falhenmedia.com'
    ];
    $adminUsernames = [
        'admin',
        'ligali.oluwatosin',
        'oluwatosin.ligali'
    ];

    if (in_array($e, $adminEmails, true) || in_array($u, $adminUsernames, true)) {
        return true;
    }

    if ($r === 'super admin' || $r === 'administrator' || $r === 'admin') {
        return true;
    }

    return false;
}

/**
 * Get Attendance Logs Repository (Auto-cleans stale unclosed shifts)
 */
function getAttendanceLogs($username = null, $role = null, $email = null) {
    autoCloseStaleAttendanceLogs();
    $settings = getSiteSettings();
    $logs = $settings['attendance_logs'] ?? [];

    if (empty($logs)) {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $prevDay = date('Y-m-d', strtotime('-2 days'));

        $logs = [
            [
                'id' => 1,
                'username' => 'daniel.ifeoluwa',
                'full_name' => 'Daniel Ifeoluwa',
                'date' => $yesterday,
                'clock_in' => '08:45 AM',
                'clock_out' => '05:30 PM',
                'duration' => '8 hrs 45 mins',
                'status' => 'Present'
            ],
            [
                'id' => 2,
                'username' => 'daniel.ifeoluwa',
                'full_name' => 'Daniel Ifeoluwa',
                'date' => $prevDay,
                'clock_in' => '08:50 AM',
                'clock_out' => '05:15 PM',
                'duration' => '8 hrs 25 mins',
                'status' => 'Present'
            ],
            [
                'id' => 3,
                'username' => 'admin',
                'full_name' => 'Oluwatosin Ligali',
                'date' => $yesterday,
                'clock_in' => '08:30 AM',
                'clock_out' => '06:00 PM',
                'duration' => '9 hrs 30 mins',
                'status' => 'Present'
            ]
        ];
        $settings['attendance_logs'] = $logs;
        saveSiteSettings($settings);
    }

    if ($role === null) {
        $role = $_SESSION['admin_role'] ?? '';
    }
    if ($email === null) {
        $email = $_SESSION['admin_email'] ?? '';
    }
    if ($username === null) {
        $username = $_SESSION['admin_username'] ?? '';
    }

    $canSeeAll = canViewAllAttendanceLogs($role, $email, $username);

    if (!$canSeeAll && !empty($username)) {
        $u = strtolower(trim((string)$username));
        $userLogs = array_filter($logs, function($item) use ($u) {
            return strtolower(trim($item['username'] ?? '')) === $u;
        });
        return array_values($userLogs);
    }

    return array_values($logs);
}

/**
 * Get today's attendance record for a specific user
 */
function getUserTodayAttendance($username) {
    $logs = getAttendanceLogs();
    $today = date('Y-m-d');
    $userLower = strtolower(trim($username));

    foreach ($logs as $entry) {
        if (($entry['date'] ?? '') === $today && strtolower(trim($entry['username'] ?? '')) === $userLower) {
            return $entry;
        }
    }
    return null;
}

/**
 * Perform Clock In Action
 */
function recordClockIn($username, $fullName) {
    $settings = getSiteSettings();
    $logs = $settings['attendance_logs'] ?? [];
    $today = date('Y-m-d');
    $userLower = strtolower(trim($username));
    $now = date('h:i A');

    foreach ($logs as $idx => $entry) {
        if (($entry['date'] ?? '') === $today && strtolower(trim($entry['username'] ?? '')) === $userLower) {
            if (empty($entry['clock_out'])) {
                return ['success' => false, 'message' => 'You are already clocked in for today (at ' . ($entry['clock_in'] ?? 'earlier') . ')!'];
            }
            
            // Re-open active shift session for today
            $logs[$idx]['clock_in'] = $now;
            $logs[$idx]['clock_out'] = null;
            $logs[$idx]['break_start'] = null;
            $logs[$idx]['break_end'] = null;
            $logs[$idx]['duration'] = 'In Progress';
            $logs[$idx]['status'] = 'Clocked In';
            $logs[$idx]['work_state'] = 'working';

            $settings['attendance_logs'] = array_values($logs);
            saveSiteSettings($settings);

            return ['success' => true, 'message' => 'Clock-In recorded successfully at ' . $now . '! Have a productive work session.'];
        }
    }

    $newId = count($logs) + 1;
    $newEntry = [
        'id' => $newId,
        'username' => $username,
        'full_name' => $fullName,
        'date' => $today,
        'clock_in' => $now,
        'clock_out' => null,
        'break_start' => null,
        'break_end' => null,
        'duration' => 'In Progress',
        'status' => 'Clocked In',
        'work_state' => 'working'
    ];

    array_unshift($logs, $newEntry);
    $settings['attendance_logs'] = array_values($logs);
    saveSiteSettings($settings);

    return ['success' => true, 'message' => 'Clock-In recorded successfully at ' . $now . '! Have a productive work session.'];
}

/**
 * Perform Start Break Action
 */
function recordStartBreak($username) {
    $settings = getSiteSettings();
    $logs = $settings['attendance_logs'] ?? [];
    $today = date('Y-m-d');
    $userLower = strtolower(trim($username));
    $now = date('h:i A');

    $found = false;
    foreach ($logs as $idx => $entry) {
        if (($entry['date'] ?? '') === $today && strtolower(trim($entry['username'] ?? '')) === $userLower) {
            if (!empty($entry['clock_out'])) {
                return ['success' => false, 'message' => 'You have already clocked out for today.'];
            }
            if (($entry['status'] ?? '') === 'On Break') {
                return ['success' => false, 'message' => 'You are already on break (started at ' . ($entry['break_start'] ?? 'earlier') . ').'];
            }

            $logs[$idx]['break_start'] = $now;
            $logs[$idx]['status'] = 'On Break';
            $logs[$idx]['work_state'] = 'on_break';
            $found = true;
            break;
        }
    }

    if (!$found) {
        return ['success' => false, 'message' => 'Please clock in before starting a break.'];
    }

    $settings['attendance_logs'] = array_values($logs);
    saveSiteSettings($settings);

    return ['success' => true, 'message' => 'Break started at ' . $now . '. Enjoy your rest!'];
}

/**
 * Perform End Break Action (Resume Work)
 */
function recordEndBreak($username) {
    $settings = getSiteSettings();
    $logs = $settings['attendance_logs'] ?? [];
    $today = date('Y-m-d');
    $userLower = strtolower(trim($username));
    $now = date('h:i A');

    $found = false;
    foreach ($logs as $idx => $entry) {
        if (($entry['date'] ?? '') === $today && strtolower(trim($entry['username'] ?? '')) === $userLower) {
            if (($entry['status'] ?? '') !== 'On Break' && empty($entry['break_start'])) {
                return ['success' => false, 'message' => 'You are not currently on break.'];
            }

            $logs[$idx]['break_end'] = $now;
            $logs[$idx]['status'] = 'Clocked In';
            $logs[$idx]['work_state'] = 'working';
            $found = true;
            break;
        }
    }

    if (!$found) {
        return ['success' => false, 'message' => 'No active break record found for today.'];
    }

    $settings['attendance_logs'] = array_values($logs);
    saveSiteSettings($settings);

    return ['success' => true, 'message' => 'Break ended at ' . $now . '. Welcome back to work!'];
}

/**
 * Perform Clock Out Action
 */
function recordClockOut($username) {
    $settings = getSiteSettings();
    $logs = $settings['attendance_logs'] ?? [];
    $today = date('Y-m-d');
    $userLower = strtolower(trim($username));
    $now = date('h:i A');

    $found = false;
    $durationStr = '8 hrs';

    foreach ($logs as $idx => $entry) {
        if (($entry['date'] ?? '') === $today && strtolower(trim($entry['username'] ?? '')) === $userLower) {
            if (!empty($entry['clock_out'])) {
                return ['success' => false, 'message' => 'You have already clocked out today at ' . $entry['clock_out'] . '.'];
            }

            $clockInTs = strtotime($today . ' ' . $entry['clock_in']);
            $clockOutTs = time();
            $diffMins = max(1, round(($clockOutTs - $clockInTs) / 60));
            $hrs = floor($diffMins / 60);
            $mins = $diffMins % 60;
            $durationStr = ($hrs > 0 ? $hrs . ' hrs ' : '') . $mins . ' mins';

            $logs[$idx]['clock_out'] = $now;
            $logs[$idx]['duration'] = $durationStr;
            $logs[$idx]['status'] = 'Present';
            $logs[$idx]['work_state'] = 'completed';
            $found = true;
            break;
        }
    }

    if (!$found) {
        return ['success' => false, 'message' => 'No active clock-in record found for today. Please clock in first.'];
    }

    $settings['attendance_logs'] = array_values($logs);
    saveSiteSettings($settings);

    return ['success' => true, 'message' => 'Clock-Out recorded successfully at ' . $now . '! Work session logged: ' . $durationStr . '.'];
}

/**
 * Get User Onboarding Data
 */
function getUserOnboardingData($username) {
    $settings = getSiteSettings();
    $userLower = strtolower(trim((string)$username));
    $allOnboarding = $settings['user_onboarding'] ?? [];

    $staffObj = getStaffAccountByUsername($username);
    $canonicalUser = !empty($staffObj['username']) ? strtolower($staffObj['username']) : $userLower;
    
    $userData = $allOnboarding[$canonicalUser] ?? ($allOnboarding[$userLower] ?? null);

    $empFullName = $staffObj['full_name'] ?? ucwords(str_replace('.', ' ', $username));
    $empRole = $staffObj['designation'] ?? ($staffObj['role'] ?? 'Staff');
    $empPhone = $staffObj['phone'] ?? '08027487449';
    $empAddress = $staffObj['address'] ?? 'Lagos, Nigeria';

    $empCurr = $staffObj['currency'] ?? ($userData['offer_letter']['currency'] ?? 'NGN');
    $currSym = getCurrencySymbol($empCurr);

    $sal = (float)($staffObj['base_salary'] ?? 120000);
    $allow = (float)($staffObj['allowances'] ?? 20000);
    $tot = $sal + $allow;

    if (!$userData) {
        $isMainAdmin = ($canonicalUser === 'admin' || $canonicalUser === 'ligali.oluwatosin' || $canonicalUser === 'oluwatosin.ligali');

        $userData = [
            'bank_details' => [
                'status' => 'Pending',
                'account_number' => $staffObj['account_number'] ?? '',
                'bank_name' => $staffObj['bank_name'] ?? 'Zenith Bank PLC',
                'account_name' => $staffObj['account_name'] ?? $empFullName
            ],
            'offer_letter' => [
                'status' => 'Pending',
                'accepted' => false,
                'document_url' => '',
                'currency' => $empCurr,
                'job_title' => $empRole,
                'salary' => $currSym . number_format($tot) . ' / month',
                'base_salary' => $currSym . number_format($sal),
                'tech_allowance' => $currSym . number_format($allow),
                'wellness_allowance' => $currSym . '0',
                'total_earnings' => $currSym . number_format($tot)
            ],
            'employment_agreement' => [
                'status' => 'Pending',
                'signed' => false,
                'signed_at' => null,
                'document_url' => '',
                'supervisor_name' => ($canonicalUser === 'admin' ? 'Ligali Oluwatosin' : 'Henry Falonipe'),
                'supervisor_role' => 'Super Admin & Executive Director',
                'work_location' => 'Remote / Studio Hybrid',
                'work_days' => 'Monday to Friday',
                'resumption_time' => '9:00 AM',
                'closing_time' => '5:00 PM',
                'address' => $empAddress,
                'phone' => $empPhone
            ],
            'reference_1' => [
                'status' => 'Pending',
                'ref_name' => '',
                'ref_contact' => '',
                'relationship' => ''
            ],
            'reference_2' => [
                'status' => 'Pending',
                'ref_name' => '',
                'ref_contact' => '',
                'relationship' => ''
            ],
            'sop' => [
                'status' => 'Pending',
                'acknowledged' => false
            ],
            'staff_handbook' => [
                'status' => 'Pending',
                'acknowledged' => false
            ],
            'id_verification' => [
                'status' => 'Pending',
                'document_type' => 'Government ID / Tax ID / Passport',
                'file_url' => ''
            ]
        ];
    } else {
        // Auto-fill bank details if empty in onboarding but set in staff accounts repo
        if (empty($userData['bank_details']['account_number']) && !empty($staffObj['account_number'])) {
            $userData['bank_details']['account_number'] = $staffObj['account_number'];
            $userData['bank_details']['bank_name'] = $staffObj['bank_name'] ?? 'Zenith Bank PLC';
            $userData['bank_details']['account_name'] = $staffObj['account_name'] ?? $empFullName;
        }
        if (empty($userData['offer_letter']['job_title'])) {
            $userData['offer_letter']['job_title'] = $empRole;
        }
        
        // If offer letter has NOT been issued yet, keep it synchronized with staff accounts
        $isOfferIssued = !empty($userData['offer_letter']['issued']) || in_array($userData['offer_letter']['status'] ?? '', ['Issued', 'Accepted', 'Approved'], true);
        if (!$isOfferIssued) {
            $userData['offer_letter']['currency'] = $empCurr;
            $userData['offer_letter']['salary'] = $currSym . number_format($tot) . ' / month';
            $userData['offer_letter']['base_salary'] = $currSym . number_format($sal);
            $userData['offer_letter']['tech_allowance'] = $currSym . number_format($allow);
            $userData['offer_letter']['total_earnings'] = $currSym . number_format($tot);
        } else {
            $savedCurr = $userData['offer_letter']['currency'] ?? $empCurr;
            $userData['offer_letter']['currency'] = $savedCurr;
        }
    }

    return $userData;
}

/**
 * Save User Onboarding Data Section
 */
function saveUserOnboardingSection($username, $sectionKey, $sectionData) {
    $settings = getSiteSettings();
    $userLower = strtolower(trim((string)$username));
    $allOnboarding = $settings['user_onboarding'] ?? [];

    $staffObj = getStaffAccountByUsername($username);
    $canonicalUser = !empty($staffObj['username']) ? strtolower($staffObj['username']) : $userLower;

    $userData = getUserOnboardingData($username);
    $userData[$sectionKey] = array_merge($userData[$sectionKey] ?? [], $sectionData);

    $allOnboarding[$canonicalUser] = $userData;
    if ($canonicalUser !== $userLower) {
        $allOnboarding[$userLower] = $userData;
    }
    $settings['user_onboarding'] = $allOnboarding;
    saveSiteSettings($settings);

    return $userData;
}

/**
 * Calculate Onboarding Progress Metrics (X/8)
 */
function getOnboardingProgress($username) {
    $data = getUserOnboardingData($username);
    $completedCount = 0;
    $totalTasks = 8;

    // 1. Bank Details (Approved by HR / Super Admin)
    if (($data['bank_details']['status'] ?? '') === 'Approved') {
        $completedCount++;
    }

    // 2. Offer Letter (Approved by HR / Super Admin)
    if (($data['offer_letter']['status'] ?? '') === 'Approved') {
        $completedCount++;
    }

    // 3. Employment Agreement (Approved by HR / Super Admin)
    if (($data['employment_agreement']['status'] ?? '') === 'Approved') {
        $completedCount++;
    }

    // 4. Reference 1 (Approved by HR / Super Admin)
    if (($data['reference_1']['status'] ?? '') === 'Approved') {
        $completedCount++;
    }

    // 5. Reference 2 (Approved by HR / Super Admin)
    if (($data['reference_2']['status'] ?? '') === 'Approved') {
        $completedCount++;
    }

    // 6. Studio SOP (Approved by HR / Super Admin)
    if (($data['sop']['status'] ?? '') === 'Approved') {
        $completedCount++;
    }

    // 7. Staff Handbook (Approved by HR / Super Admin)
    if (($data['staff_handbook']['status'] ?? '') === 'Approved') {
        $completedCount++;
    }

    // 8. ID Verification (Approved by HR / Super Admin)
    if (($data['id_verification']['status'] ?? '') === 'Approved') {
        $completedCount++;
    }

    $percent = round(($completedCount / $totalTasks) * 100);

    return [
        'completed' => $completedCount,
        'total' => $totalTasks,
        'percent' => $percent
    ];
}

/**
 * Update Onboarding Task Status & Admin Action (HR & Admin Feature)
 */
function updateUserOnboardingTaskStatus($username, $taskKey, $newStatus, $extraData = []) {
    $settings = getSiteSettings();
    $userLower = strtolower(trim((string)$username));
    $allOnboarding = $settings['user_onboarding'] ?? [];

    $staffObj = getStaffAccountByUsername($username);
    $canonicalUser = !empty($staffObj['username']) ? strtolower($staffObj['username']) : $userLower;

    $userData = getUserOnboardingData($username);
    if (!isset($userData[$taskKey])) {
        $userData[$taskKey] = [];
    }

    $userData[$taskKey]['status'] = $newStatus;
    if (!empty($extraData)) {
        $userData[$taskKey] = array_merge($userData[$taskKey], $extraData);
    }

    $allOnboarding[$canonicalUser] = $userData;
    if ($canonicalUser !== $userLower) {
        $allOnboarding[$userLower] = $userData;
    }
    $settings['user_onboarding'] = $allOnboarding;
    saveSiteSettings($settings);

    return $userData;
}

/**
 * Format Currency / Money Strings with Proper Thousands Comma (e.g. 250000 -> 250,000)
 */
function formatMoneyWithCommas($str) {
    if (empty($str) || !is_string($str)) return $str;
    return preg_replace_callback('/\b(\d{4,})\b/', function($matches) {
        return number_format((float)$matches[1]);
    }, $str);
}

/**
 * Get Onboarding Overview for All Staff Members (HR & Admin View)
 */
function getAllStaffOnboardingSummary() {
    $staffList = getStaffAccountsRepo();
    $summary = [];

    foreach ($staffList as $st) {
        $stUsername = $st['username'] ?? '';
        $progress = getOnboardingProgress($stUsername);
        $summary[] = [
            'username' => $stUsername,
            'full_name' => $st['full_name'] ?? 'Staff',
            'role' => $st['role'] ?? 'Staff',
            'email' => $st['email'] ?? '',
            'avatar' => $st['avatar'] ?? '',
            'completed' => $progress['completed'],
            'total' => $progress['total'],
            'percent' => $progress['percent']
        ];
    }

    return $summary;
}

/**
 * Get Celebration Settings & Curated Templates (Birthdays & Work Milestones)
 */
function getCelebrationSettings() {
    $settings = getSiteSettings();
    $defaults = [
        'auto_announce' => true,
        'enable_birthdays' => true,
        'enable_45d' => true,
        'enable_90d' => true,
        'enable_6m' => true,
        'enable_anniversaries' => true,
        'author_title' => 'Falhen HR & Culture Team',
        'default_template' => "Wishing our fantastic {name} ({role}) a very Happy Birthday! 🎂🎉 May your year ahead be filled with boundless creativity, joy, and incredible milestones. We celebrate you today and appreciate everything you bring to the Falhen family! 🥳✨",
        'template_45d' => "Congratulations to {name} ({role}) on completing your first 45 days at Falhen Media! 🚀🌟 We love having your creativity, dedication, and positive energy on the team. Keep shining and reaching higher! 👏✨",
        'template_90d' => "Huge congratulations to {name} ({role}) on reaching your 90-day milestone and successfully completing probation at Falhen Media! 🏆🎉 Thank you for your hard work, impact, and brilliance. We are proud to have you on board! 🌟🚀",
        'template_6m' => "Happy 6-Month Anniversary to {name} ({role})! 💫🎊 Celebrating half a year of memorable moments, creative excellence, and great teamwork. Thank you for everything you bring to Falhen Media! 🥂✨",
        'template_1y' => "Happy 1st Work Anniversary to our exceptional {name} ({role})! 🥂🎉 A whole year of remarkable achievements, inspiring teamwork, and dedication. We appreciate all you do for the Falhen family! 🥳🌟",
        'template_multi_year' => "Happy {years}-Year Work Anniversary to {name} ({role})! 🎖️🌟 Thank you for your continued loyalty, leadership, and outstanding contributions to Falhen Media over the past {years} years! 🥂🎉",
        'custom_wishes' => []
    ];

    $celeb = $settings['celebration_settings'] ?? [];
    if (!is_array($celeb)) {
        $celeb = [];
    }
    return array_merge($defaults, $celeb);
}

/**
 * Save Celebration Settings
 */
function saveCelebrationSettings($data) {
    $settings = getSiteSettings();
    $current = getCelebrationSettings();
    $updatedCeleb = array_merge($current, $data);
    $settings['celebration_settings'] = $updatedCeleb;
    saveSiteSettings($settings);
    return $updatedCeleb;
}

/**
 * Save or Update Employee Custom Celebration Wish (HR Curated)
 */
function saveEmployeeCustomBirthdayWish($username, $customWish) {
    $celebSettings = getCelebrationSettings();
    $uKey = strtolower(trim($username));
    if (!isset($celebSettings['custom_wishes']) || !is_array($celebSettings['custom_wishes'])) {
        $celebSettings['custom_wishes'] = [];
    }
    $celebSettings['custom_wishes'][$uKey] = trim($customWish);
    saveCelebrationSettings($celebSettings);
    return true;
}

/**
 * Get Site Announcements with Optional Category Filtering (Includes Birthdays, 45d/90d Milestones & Work Anniversaries)
 */
function getSiteAnnouncements($category = 'all') {
    $settings = getSiteSettings();
    $announcements = $settings['site_announcements'] ?? [
        [
            'id' => 1,
            'title' => 'Falhen Q3 Production Roadmap & 8K Cinema Gear Launch',
            'category' => 'Important',
            'posted_by' => 'Henry Falonipe (Creative Director)',
            'date_str' => 'Posted Today at 09:00 AM',
            'content' => 'We are excited to announce the arrival of our new RED V-Raptor and ARRI Alexa Mini LF camera packages. All cinematographers and production crews are requested to complete the gear orientation briefing before booking equipment for upcoming commercial shoots.'
        ],
        [
            'id' => 2,
            'title' => 'Updated Hybrid Work Policy & Health Stipend',
            'category' => 'General',
            'posted_by' => 'Resource & HR Management',
            'date_str' => 'Posted Yesterday',
            'content' => 'The monthly health & wellness reimbursement portal is now open for August submissions. Please ensure all receipts are uploaded under your profile before the 25th of the month.'
        ],
        [
            'id' => 3,
            'title' => 'Annual Studio End-of-Summer Gala & Awards',
            'category' => 'Events',
            'posted_by' => 'Studio Operations',
            'date_str' => 'Posted 3 days ago',
            'content' => 'Save the date! Falhen Media will host our annual End-of-Summer Gala on August 30th at the Main Soundstage. Details and RSVP forms will be shared shortly.'
        ]
    ];

    // AUTOMATIC CELEBRATIONS CHECK (Birthdays, 45-Day, 90-Day, 6-Month, Work Anniversaries)
    $celebSettings = getCelebrationSettings();
    $autoAnnounce = !empty($celebSettings['auto_announce']);

    if ($autoAnnounce) {
        $todayMonthDay = date('m-d');
        $todayTs = strtotime(date('Y-m-d'));
        $todayYear = (int)date('Y');

        $allStaffAccounts = array_values(getStaffAccountsRepo());
        $adminProfile = getAdminUserProfile();

        $unifiedPeople = [];
        foreach ($allStaffAccounts as $st) {
            $fn = trim($st['full_name'] ?? ($st['name'] ?? ''));
            $un = trim($st['username'] ?? '');
            $em = trim($st['email'] ?? '');
            $key = strtolower($fn ?: $un);
            if (empty($key)) continue;

            $unifiedPeople[$key] = [
                'username'   => $un ?: strtolower(str_replace(' ', '.', $fn)),
                'name'       => $fn ?: $un,
                'role'       => $st['designation'] ?? ($st['role'] ?? 'Staff Member'),
                'access_role'=> $st['role'] ?? 'Staff',
                'avatar'     => $st['avatar'] ?? ($st['image'] ?? ''),
                'email'      => $em,
                'dob'        => $st['dob'] ?? '',
                'created_at' => $st['hire_date'] ?? ($st['created_at'] ?? '2024-01-10')
            ];
        }

        if (!empty($adminProfile) && is_array($adminProfile)) {
            $admFn = trim($adminProfile['full_name'] ?? '');
            $admUn = trim($adminProfile['username'] ?? 'admin');
            $admEm = trim($adminProfile['email'] ?? '');
            $admKey = strtolower($admFn ?: $admUn);

            $matchedKey = null;
            foreach ($unifiedPeople as $k => $p) {
                if ($k === $admKey || strtolower($p['username']) === strtolower($admUn) || (!empty($admEm) && strtolower($p['email']) === strtolower($admEm))) {
                    $matchedKey = $k;
                    break;
                }
            }

            if ($matchedKey) {
                if (!empty($adminProfile['dob'])) $unifiedPeople[$matchedKey]['dob'] = $adminProfile['dob'];
                if (!empty($adminProfile['avatar'])) $unifiedPeople[$matchedKey]['avatar'] = $adminProfile['avatar'];
                if (!empty($admFn)) $unifiedPeople[$matchedKey]['name'] = $admFn;
                if (!empty($adminProfile['designation'])) $unifiedPeople[$matchedKey]['role'] = $adminProfile['designation'];
                if (!empty($adminProfile['hire_date'])) $unifiedPeople[$matchedKey]['created_at'] = $adminProfile['hire_date'];
            } else {
                $unifiedPeople[$admKey] = [
                    'username'   => $admUn,
                    'name'       => $admFn ?: 'Admin User',
                    'role'       => $adminProfile['designation'] ?? ($adminProfile['role'] ?? 'Super Admin'),
                    'access_role'=> $adminProfile['role'] ?? 'Super Admin',
                    'avatar'     => $adminProfile['avatar'] ?? '',
                    'email'      => $admEm,
                    'dob'        => $adminProfile['dob'] ?? '',
                    'created_at' => $adminProfile['hire_date'] ?? '2023-01-01'
                ];
            }
        }

        if (function_exists('getTeamMembers')) {
            $siteTeamMembers = array_values(getTeamMembers());
            foreach ($siteTeamMembers as $tm) {
                $tmName = trim($tm['name'] ?? '');
                if (empty($tmName)) continue;
                $tmKey = strtolower($tmName);

                if (isset($unifiedPeople[$tmKey])) {
                    if (empty($unifiedPeople[$tmKey]['dob']) && !empty($tm['dob'])) {
                        $unifiedPeople[$tmKey]['dob'] = $tm['dob'];
                    }
                    if (empty($unifiedPeople[$tmKey]['avatar']) && !empty($tm['image'])) {
                        $unifiedPeople[$tmKey]['avatar'] = $tm['image'];
                    }
                    if (!empty($tm['role']) && (empty($unifiedPeople[$tmKey]['role']) || in_array($unifiedPeople[$tmKey]['role'], ['Staff', 'Staff Member']))) {
                        $unifiedPeople[$tmKey]['role'] = $tm['role'];
                    }
                } else {
                    $unifiedPeople[$tmKey] = [
                        'username'   => strtolower(str_replace(' ', '.', $tmName)),
                        'name'       => $tmName,
                        'role'       => $tm['role'] ?? ($tm['title'] ?? 'Team Member'),
                        'access_role'=> 'Staff',
                        'avatar'     => $tm['image'] ?? '',
                        'email'      => strtolower(str_replace(' ', '.', $tmName)) . '@falhen.com',
                        'dob'        => $tm['dob'] ?? '',
                        'created_at' => $tm['hire_date'] ?? ($tm['created_at'] ?? '2024-01-01')
                    ];
                }
            }
        }

        $celebrationAnnouncements = [];
        $celebIds = 9000;

        foreach ($unifiedPeople as $st) {
            $stName = $st['name'] ?? 'Team Member';
            $stRole = $st['role'] ?? 'Staff Member';
            $stAvatar = $st['avatar'] ?? '';
            $stUser = strtolower(trim($st['username'] ?? ''));
            $stEmail = strtolower(trim($st['email'] ?? ''));
            $firstName = explode(' ', trim($stName))[0] ?? $stName;
            $authorTitle = $celebSettings['author_title'] ?? 'Falhen HR & Culture Team';

            $customWishes = $celebSettings['custom_wishes'] ?? [];
            $userCustomWish = $customWishes[$stUser] ?? ($customWishes[$stEmail] ?? ($customWishes[strtolower(trim($stName))] ?? ''));

            // 1. BIRTHDAY CHECK
            $dob = $st['dob'] ?? '';
            if (!empty($dob) && !empty($celebSettings['enable_birthdays'])) {
                $dobTime = strtotime($dob);
                if ($dobTime && date('m-d', $dobTime) === $todayMonthDay) {
                    if (!empty($userCustomWish)) {
                        $content = str_replace(['{name}', '{role}', '{first_name}'], [$stName, $stRole, $firstName], $userCustomWish);
                    } else {
                        $template = $celebSettings['default_template'] ?? "Wishing our fantastic {name} ({role}) a very Happy Birthday! 🎂🎉 May your year ahead be filled with boundless creativity, joy, and incredible milestones. We celebrate you today and appreciate everything you bring to the Falhen family! 🥳✨";
                        $content = str_replace(['{name}', '{role}', '{first_name}'], [$stName, $stRole, $firstName], $template);
                    }

                    $celebIds++;
                    $celebrationAnnouncements[] = [
                        'id'            => $celebIds,
                        'is_birthday'   => true,
                        'is_celebration'=> true,
                        'celebration_type' => 'birthday',
                        'employee_name' => $stName,
                        'employee_role' => $stRole,
                        'avatar'        => $stAvatar,
                        'title'         => '🎉 Happy Birthday, ' . $stName . '! 🎂',
                        'category'      => 'Celebration',
                        'posted_by'     => $authorTitle,
                        'date_str'      => 'Birthday Today 🎈',
                        'content'       => $content
                    ];
                }
            }

            // 2. WORK ANNIVERSARIES & MILESTONES CHECK (45d, 90d, 6m, 1y, 2y+)
            $startDate = $st['created_at'] ?? '';
            if (!empty($startDate)) {
                $startTs = strtotime(date('Y-m-d', strtotime($startDate)));
                if ($startTs && $todayTs >= $startTs) {
                    $daysEmployed = (int)round(($todayTs - $startTs) / 86400);

                    // 45-Day Milestone
                    if ($daysEmployed === 45 && !empty($celebSettings['enable_45d'])) {
                        $template = $celebSettings['template_45d'] ?? "Congratulations to {name} ({role}) on completing your first 45 days at Falhen Media! 🚀🌟 We love having your creativity, dedication, and positive energy on the team. Keep shining and reaching higher! 👏✨";
                        $content = str_replace(['{name}', '{role}', '{first_name}'], [$stName, $stRole, $firstName], $template);
                        $celebIds++;
                        $celebrationAnnouncements[] = [
                            'id'            => $celebIds,
                            'is_celebration'=> true,
                            'celebration_type' => 'milestone_45d',
                            'employee_name' => $stName,
                            'employee_role' => $stRole,
                            'avatar'        => $stAvatar,
                            'title'         => '🌟 45-Day Milestone: Congratulations ' . $stName . '! 🚀',
                            'category'      => 'Celebration',
                            'posted_by'     => $authorTitle,
                            'date_str'      => '45-Day Milestone 🌟',
                            'content'       => $content
                        ];
                    }

                    // 90-Day Milestone (Probation Completion)
                    if ($daysEmployed === 90 && !empty($celebSettings['enable_90d'])) {
                        $template = $celebSettings['template_90d'] ?? "Huge congratulations to {name} ({role}) on reaching your 90-day milestone and successfully completing probation at Falhen Media! 🏆🎉 Thank you for your hard work, impact, and brilliance. We are proud to have you on board! 🌟🚀";
                        $content = str_replace(['{name}', '{role}', '{first_name}'], [$stName, $stRole, $firstName], $template);
                        $celebIds++;
                        $celebrationAnnouncements[] = [
                            'id'            => $celebIds,
                            'is_celebration'=> true,
                            'celebration_type' => 'milestone_90d',
                            'employee_name' => $stName,
                            'employee_role' => $stRole,
                            'avatar'        => $stAvatar,
                            'title'         => '🏆 90-Day Milestone: Congratulations ' . $stName . '! 🎉',
                            'category'      => 'Celebration',
                            'posted_by'     => $authorTitle,
                            'date_str'      => '90-Day Milestone 🏆',
                            'content'       => $content
                        ];
                    }

                    // 6-Month / Half-Year Milestone (Day 182)
                    if ($daysEmployed === 182 && !empty($celebSettings['enable_6m'])) {
                        $template = $celebSettings['template_6m'] ?? "Happy 6-Month Anniversary to {name} ({role})! 💫🎊 Celebrating half a year of memorable moments, creative excellence, and great teamwork. Thank you for everything you bring to Falhen Media! 🥂✨";
                        $content = str_replace(['{name}', '{role}', '{first_name}'], [$stName, $stRole, $firstName], $template);
                        $celebIds++;
                        $celebrationAnnouncements[] = [
                            'id'            => $celebIds,
                            'is_celebration'=> true,
                            'celebration_type' => 'milestone_6m',
                            'employee_name' => $stName,
                            'employee_role' => $stRole,
                            'avatar'        => $stAvatar,
                            'title'         => '💫 Happy 6-Month Anniversary, ' . $stName . '! 🎊',
                            'category'      => 'Celebration',
                            'posted_by'     => $authorTitle,
                            'date_str'      => '6-Month Anniversary 💫',
                            'content'       => $content
                        ];
                    }

                    // Annual Work Anniversaries (1 Year, 2 Years, 3 Years, etc.)
                    $startMonthDay = date('m-d', $startTs);
                    $startYear = (int)date('Y', $startTs);
                    if ($startMonthDay === $todayMonthDay && $todayYear > $startYear && !empty($celebSettings['enable_anniversaries'])) {
                        $years = $todayYear - $startYear;
                        if ($years === 1) {
                            $template = $celebSettings['template_1y'] ?? "Happy 1st Work Anniversary to our exceptional {name} ({role})! 🥂🎉 A whole year of remarkable achievements, inspiring teamwork, and dedication. We appreciate all you do for the Falhen family! 🥳🌟";
                            $content = str_replace(['{name}', '{role}', '{first_name}', '{years}'], [$stName, $stRole, $firstName, $years], $template);
                            $title = '🥂 Happy 1st Work Anniversary, ' . $stName . '! 🎉';
                            $dateTag = '1-Year Work Anniversary 🥂';
                        } else {
                            $template = $celebSettings['template_multi_year'] ?? "Happy {years}-Year Work Anniversary to {name} ({role})! 🎖️🌟 Thank you for your continued loyalty, leadership, and outstanding contributions to Falhen Media over the past {years} years! 🥂🎉";
                            $content = str_replace(['{name}', '{role}', '{first_name}', '{years}'], [$stName, $stRole, $firstName, $years], $template);
                            $title = '🎖️ Happy ' . $years . '-Year Work Anniversary, ' . $stName . '! 🌟';
                            $dateTag = $years . '-Year Work Anniversary 🎖️';
                        }

                        $celebIds++;
                        $celebrationAnnouncements[] = [
                            'id'            => $celebIds,
                            'is_celebration'=> true,
                            'celebration_type' => 'work_anniversary',
                            'employee_name' => $stName,
                            'employee_role' => $stRole,
                            'avatar'        => $stAvatar,
                            'title'         => $title,
                            'category'      => 'Celebration',
                            'posted_by'     => $authorTitle,
                            'date_str'      => $dateTag,
                            'content'       => $content
                        ];
                    }
                }
            }
        }

        if (!empty($celebrationAnnouncements)) {
            $announcements = array_merge($celebrationAnnouncements, $announcements);
        }
    }

    if ($category !== 'all' && !empty($category)) {
        $categoryLower = strtolower(trim($category));
        $announcements = array_values(array_filter($announcements, function($item) use ($categoryLower) {
            return strtolower(trim($item['category'] ?? '')) === $categoryLower;
        }));
    }

    return $announcements;
}

/**
 * Post New Site Announcement (HR & Admin)
 */
function postSiteAnnouncement($data) {
    $settings = getSiteSettings();
    $announcements = getSiteAnnouncements('all');
    $newId = count($announcements) + 1;
    
    $newAnnouncement = [
        'id' => $newId,
        'title' => trim($data['title'] ?? 'Company Update'),
        'category' => ucfirst(trim($data['category'] ?? 'General')),
        'posted_by' => trim($data['posted_by'] ?? 'HR & Studio Ops'),
        'date_str' => 'Posted Today at ' . date('h:i A'),
        'content' => trim($data['content'] ?? '')
    ];

    array_unshift($announcements, $newAnnouncement);
    $settings['site_announcements'] = $announcements;
    saveSiteSettings($settings);

    return $newAnnouncement;
}

/**
 * Get all leave requests from JSON storage
 */
function getLeaveRequests() {
    $filePath = __DIR__ . '/../config/leave_requests.json';
    if (!file_exists($filePath)) {
        // Default initial seed data if file does not exist yet
        $initialData = [
            [
                'id' => 1,
                'username' => 'mojisola',
                'staff_name' => 'Mojisola Emjay',
                'staff_role' => 'HR Manager',
                'type' => 'Annual Leave',
                'dates' => 'Sep 01, 2026 – Sep 03, 2026 (3 Days)',
                'reason' => 'End of Summer Studio Break',
                'status' => 'Pending HR Approval',
                'status_type' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'username' => 'mojisola',
                'staff_name' => 'Mojisola Emjay',
                'staff_role' => 'HR Manager',
                'type' => 'Annual Leave',
                'dates' => 'Aug 10, 2026 – Aug 12, 2026 (3 Days)',
                'reason' => 'Family Vacation & Personal Time',
                'status' => 'Approved',
                'status_type' => 'approved',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 3,
                'username' => 'victoria',
                'staff_name' => 'Victoria Opemipo',
                'staff_role' => 'Creative Director',
                'type' => 'Casual Leave',
                'dates' => 'Aug 28, 2026 (1 Day)',
                'reason' => 'Personal Errand & Administrative Work',
                'status' => 'Pending HR Approval',
                'status_type' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 4,
                'username' => 'oluwatosin',
                'staff_name' => 'Oluwatosin Ligali',
                'staff_role' => 'Lead Developer',
                'type' => 'Annual Leave',
                'dates' => 'Sep 15, 2026 – Sep 20, 2026 (5 Days)',
                'reason' => 'Annual Recess & Rest',
                'status' => 'Approved',
                'status_type' => 'approved',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 5,
                'username' => 'daniel.ifeoluwa',
                'staff_name' => 'Daniel Ifeoluwa',
                'staff_role' => 'Staff',
                'type' => 'Sick Leave',
                'dates' => 'Jul 04, 2026 (1 Day)',
                'reason' => 'Medical Checkup',
                'status' => 'Approved',
                'status_type' => 'approved',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        @file_put_contents($filePath, json_encode($initialData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $initialData;
    }

    $content = @file_get_contents($filePath);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Submit a new leave request with custom Start & End Dates
 */
function submitLeaveRequest($username, $fullName, $role, $type, $startDateInput = '', $endDateInput = '', $duration = 1, $reason = '') {
    $requests = getLeaveRequests();
    
    $startTs = !empty($startDateInput) ? strtotime($startDateInput) : strtotime('+1 day');
    $days = max(1, (int)$duration);

    if (!empty($endDateInput)) {
        $endTs = strtotime($endDateInput);
        if ($endTs < $startTs) {
            $endTs = $startTs;
        }
        $calcDays = max(1, round(($endTs - $startTs) / 86400) + 1);
        $days = (int)$calcDays;
    } else {
        $endTs = strtotime('+' . ($days - 1) . ' days', $startTs);
    }

    $startStr = date('M d, Y', $startTs);
    $endStr = date('M d, Y', $endTs);

    $dateStr = ($startStr === $endStr || $days === 1) 
        ? "{$startStr} (1 Day)" 
        : "{$startStr} – {$endStr} ({$days} Days)";

    $newRequest = [
        'id' => time() . rand(100, 999),
        'username' => strtolower(trim($username)),
        'staff_name' => $fullName,
        'staff_role' => $role,
        'type' => trim($type),
        'start_date' => date('Y-m-d', $startTs),
        'end_date' => date('Y-m-d', $endTs),
        'duration' => $days,
        'dates' => $dateStr,
        'reason' => trim($reason),
        'status' => 'Pending HR Approval',
        'status_type' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Unshift so new request appears at top of history
    array_unshift($requests, $newRequest);

    $filePath = __DIR__ . '/../config/leave_requests.json';
    @file_put_contents($filePath, json_encode($requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return $newRequest;
}

/**
 * Update Leave Request Status (HR & Admin)
 */
function updateLeaveRequestStatus($requestId, $newStatus) {
    $requests = getLeaveRequests();
    $updated = false;

    foreach ($requests as &$req) {
        if ((string)($req['id'] ?? '') === (string)$requestId) {
            $statusClean = ucfirst(strtolower(trim($newStatus)));
            if ($statusClean === 'Approved') {
                $req['status'] = 'Approved';
                $req['status_type'] = 'approved';
            } elseif ($statusClean === 'Rejected') {
                $req['status'] = 'Rejected';
                $req['status_type'] = 'rejected';
            } else {
                $req['status'] = $statusClean;
                $req['status_type'] = strtolower($statusClean);
            }
            $req['updated_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
    }
    unset($req);

    if ($updated) {
        $filePath = __DIR__ . '/../config/leave_requests.json';
        @file_put_contents($filePath, json_encode($requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    return $updated;
}

/**
 * Calculate dynamic leave balance statistics for a user
 */
function getUserLeaveStats($username) {
    $allRequests = getLeaveRequests();
    $userRequests = array_filter($allRequests, function($req) use ($username) {
        return strtolower(trim($req['username'] ?? '')) === strtolower(trim($username));
    });

    $allowanceMap = [
        'Annual Leave' => 20,
        'Sick Leave' => 7,
        'Casual Leave' => 5
    ];

    $usedMap = [
        'Annual Leave' => 0,
        'Sick Leave' => 0,
        'Casual Leave' => 0
    ];

    foreach ($userRequests as $req) {
        $statusType = strtolower(trim($req['status_type'] ?? ''));
        // Include approved and pending requests in used calculation
        if ($statusType === 'approved' || $statusType === 'pending') {
            $type = trim($req['type'] ?? '');
            $dur = max(1, intval($req['duration'] ?? 1));

            if (isset($usedMap[$type])) {
                $usedMap[$type] += $dur;
            } else if (stripos($type, 'annual') !== false) {
                $usedMap['Annual Leave'] += $dur;
            } else if (stripos($type, 'sick') !== false) {
                $usedMap['Sick Leave'] += $dur;
            } else if (stripos($type, 'casual') !== false) {
                $usedMap['Casual Leave'] += $dur;
            }
        }
    }

    $annualRemaining = max(0, $allowanceMap['Annual Leave'] - $usedMap['Annual Leave']);
    $sickRemaining = max(0, $allowanceMap['Sick Leave'] - $usedMap['Sick Leave']);
    $casualRemaining = max(0, $allowanceMap['Casual Leave'] - $usedMap['Casual Leave']);

    return [
        'annual' => [
            'total' => $allowanceMap['Annual Leave'],
            'used' => $usedMap['Annual Leave'],
            'remaining' => $annualRemaining
        ],
        'sick' => [
            'total' => $allowanceMap['Sick Leave'],
            'used' => $usedMap['Sick Leave'],
            'remaining' => $sickRemaining
        ],
        'casual' => [
            'total' => $allowanceMap['Casual Leave'],
            'used' => $usedMap['Casual Leave'],
            'remaining' => $casualRemaining
        ]
    ];
}

/**
 * Fetch persistent team comms messages
 */
function getCommsMessages($channel = 'general') {
    $filePath = __DIR__ . '/../config/comms_messages.json';
    if (!file_exists($filePath)) {
        $initialData = [
            [
                'id' => 1,
                'channel' => 'general',
                'username' => 'henry',
                'sender_name' => 'Henry Falonipe',
                'sender_role' => 'Creative Director',
                'message' => 'Good morning team! Please remember to submit your weekly production briefing updates.',
                'time_str' => '09:00 AM',
                'created_at' => date('Y-m-d 09:00:00')
            ],
            [
                'id' => 2,
                'channel' => 'general',
                'username' => 'victoria',
                'sender_name' => 'Victoria Opemipo',
                'sender_role' => 'Creative Director',
                'message' => 'Reviewing the latest color grade drafts for the RED V-Raptor footage now.',
                'time_str' => '09:15 AM',
                'created_at' => date('Y-m-d 09:15:00')
            ],
            [
                'id' => 3,
                'channel' => 'general',
                'username' => 'daniel',
                'sender_name' => 'Daniel Ifeoluwa',
                'sender_role' => 'Operations Manager',
                'message' => 'Call sheets for next week\'s commercial shoot have been uploaded under Operations.',
                'time_str' => '10:30 AM',
                'created_at' => date('Y-m-d 10:30:00')
            ],
            [
                'id' => 4,
                'channel' => 'production',
                'username' => 'oluwatosin',
                'sender_name' => 'Oluwatosin Ligali',
                'sender_role' => 'Lead Developer',
                'message' => 'Gear orientation briefing scheduled for tomorrow at 2 PM.',
                'time_str' => '11:00 AM',
                'created_at' => date('Y-m-d 11:00:00')
            ],
            [
                'id' => 5,
                'channel' => 'hr-helpdesk',
                'username' => 'mojisola',
                'sender_name' => 'Mojisola Emjay',
                'sender_role' => 'HR Manager',
                'message' => 'Monthly health reimbursement claims are open until the 25th.',
                'time_str' => '11:30 AM',
                'created_at' => date('Y-m-d 11:30:00')
            ]
        ];
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @file_put_contents($filePath, json_encode($initialData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $initialData;
    }

    $content = @file_get_contents($filePath);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Send a new comms message
 */
function sendCommsMessage($username, $senderName, $senderRole, $channel, $messageText) {
    $messages = getCommsMessages();
    $newMsg = [
        'id' => time() . rand(100, 999),
        'channel' => strtolower(trim($channel)),
        'username' => strtolower(trim($username)),
        'sender_name' => trim($senderName),
        'sender_role' => trim($senderRole),
        'message' => trim($messageText),
        'time_str' => date('h:i A'),
        'created_at' => date('Y-m-d H:i:s')
    ];
    $messages[] = $newMsg;

    $filePath = __DIR__ . '/../config/comms_messages.json';
    @file_put_contents($filePath, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return $newMsg;
}

/**
 * Studio Calls Repository Functions
 */
function getStudioCallsFile() {
    return __DIR__ . '/../config/studio_calls.json';
}

function getActiveStudioCalls() {
    $file = getStudioCallsFile();
    if (!file_exists($file)) return [];
    $data = json_decode(@file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveActiveStudioCalls($calls) {
    $file = getStudioCallsFile();
    @file_put_contents($file, json_encode(array_values($calls), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function initiateStudioCallState($callerUser, $callerName, $callerAvatar, $receiverUser, $receiverName, $receiverAvatar, $callType = 'audio') {
    $calls = getActiveStudioCalls();
    $callId = 'call_' . time() . '_' . rand(100, 999);
    
    $now = time();
    $calls = array_filter($calls, fn($c) => ($now - ($c['created_at'] ?? 0)) < 300);

    $newCall = [
        'call_id' => $callId,
        'caller_username' => getCanonicalUsername($callerUser),
        'caller_name' => $callerName,
        'caller_avatar' => $callerAvatar,
        'receiver_username' => getCanonicalUsername($receiverUser),
        'receiver_name' => $receiverName,
        'receiver_avatar' => $receiverAvatar,
        'type' => $callType,
        'status' => 'ringing',
        'created_at' => $now
    ];

    $calls[] = $newCall;
    saveActiveStudioCalls($calls);
    return $newCall;
}

function updateStudioCallStatus($callId, $status) {
    $calls = getActiveStudioCalls();
    $updated = null;
    foreach ($calls as &$c) {
        if (($c['call_id'] ?? '') === $callId) {
            $c['status'] = $status;
            $updated = $c;
            break;
        }
    }
    saveActiveStudioCalls($calls);
    return $updated;
}

function checkUserStudioCallState($username) {
    $userCanon = getCanonicalUsername($username);
    if (empty($userCanon)) return null;

    $calls = getActiveStudioCalls();
    $now = time();
    
    // Check for incoming call targeted at this user
    foreach ($calls as $c) {
        if (($c['receiver_username'] ?? '') === $userCanon && ($c['status'] ?? '') === 'ringing' && ($now - ($c['created_at'] ?? 0)) < 60) {
            return [
                'role' => 'receiver',
                'call' => $c
            ];
        }
    }

    // Check for caller's active call status update
    foreach ($calls as $c) {
        if (($c['caller_username'] ?? '') === $userCanon && ($now - ($c['created_at'] ?? 0)) < 300) {
            return [
                'role' => 'caller',
                'call' => $c
            ];
        }
    }

    return null;
}

/**
 * Get Canonical Short Username for Consistent Channel Mapping
 */
function getCanonicalUsername($username) {
    $u = strtolower(trim((string)$username));
    if (empty($u)) return '';

    // If username is "admin", inspect active session full_name & email
    if ($u === 'admin') {
        $sessName = strtolower($_SESSION['admin_full_name'] ?? $_SESSION['admin_name'] ?? '');
        $sessEmail = strtolower($_SESSION['admin_email'] ?? '');
        if (str_contains($sessName, 'oluwatosin') || str_contains($sessName, 'ligali') || str_contains($sessEmail, 'ligali') || str_contains($sessEmail, 'oluwatosin')) {
            return 'oluwatosin';
        }
        if (str_contains($sessName, 'mojisola') || str_contains($sessEmail, 'mojisola')) {
            return 'mojisola';
        }
        if (str_contains($sessName, 'daniel') || str_contains($sessEmail, 'daniel')) {
            return 'daniel';
        }
        if (str_contains($sessName, 'victoria') || str_contains($sessEmail, 'victoria')) {
            return 'victoria';
        }
        if (str_contains($sessName, 'henry') || str_contains($sessEmail, 'henry')) {
            return 'henry';
        }
    }

    $aliasMap = [
        'kingsley.falonipe' => 'kingsley',
        'kingsley'          => 'kingsley',
        'mojisola.emjay'   => 'mojisola',
        'mojisola'         => 'mojisola',
        'henry'            => 'henry',
        'henry.falonipe'   => 'henry',
        'daniel.ifeoluwa'  => 'daniel',
        'daniel'           => 'daniel',
        'victoria.opemipo' => 'victoria',
        'victoria'         => 'victoria',
        'ligali.oluwatosin'=> 'oluwatosin',
        'oluwatosin.king'  => 'oluwatosin',
        'oluwatosin'       => 'oluwatosin',
        'micheal.otuwho'   => 'micheal',
        'micheal'          => 'micheal',
        'lisa.okoli'       => 'lisa',
        'lisa'             => 'lisa'
    ];

    if (isset($aliasMap[$u])) {
        return $aliasMap[$u];
    }

    if (str_contains($u, '.')) {
        return explode('.', $u)[0];
    }

    return $u;
}

/**
 * Helper to generate consistent channel key for direct messages between two users
 */
function getDmChannelKey($user1, $user2) {
    $c1 = getCanonicalUsername($user1);
    $c2 = getCanonicalUsername($user2);
    $arr = [$c1, $c2];
    sort($arr);
    return 'dm_' . implode('_', $arr);
}

/**
 * Dynamic calculation of staff contacts sorted by DM frequency and recency
 */
function getSortedRecentDmContacts($currentUsername) {
    $repo = getStaffAccountsRepo();
    $allMessages = getCommsMessages();
    $userLower = strtolower(trim($currentUsername));
    $userEmail = strtolower(trim($_SESSION['admin_email'] ?? ''));
    $userFullName = strtolower(trim($_SESSION['admin_name'] ?? ''));

    // Extract current user's first name
    $currentUserFirstName = '';
    if (!empty($userFullName)) {
        $currentUserFirstName = strtolower(explode(' ', $userFullName)[0]);
    }

    $contactsMap = [];
    foreach ($repo as $staff) {
        $stUsername = strtolower(trim($staff['username'] ?? ''));
        $stEmail = strtolower(trim($staff['email'] ?? ''));
        $stFullName = strtolower(trim($staff['full_name'] ?? ''));
        
        if (empty($stUsername)) continue;

        // Extract staff's first name
        $rawFullName = trim($staff['full_name'] ?? 'Staff Member');
        $nameParts = explode(' ', $rawFullName);
        $firstName = $nameParts[0] ?? $rawFullName;
        $stFirstNameLower = strtolower($firstName);

        // 1. Skip if username matches
        if ($stUsername === $userLower) continue;

        // 2. Skip if email matches
        if (!empty($userEmail) && $stEmail === $userEmail) continue;

        // 3. Skip if full name matches
        if (!empty($userFullName) && $stFullName === $userFullName) continue;

        // 4. Skip if first name matches current user's first name (prevents self-chatting under name variations)
        if (!empty($currentUserFirstName) && $stFirstNameLower === $currentUserFirstName) continue;

        $u1 = getCanonicalUsername($userLower);
        $u2 = getCanonicalUsername($stUsername);
        $dmKey = getDmChannelKey($userLower, $stUsername);

        // Calculate total messages exchanged & latest timestamp
        $msgCount = 0;
        $latestTime = 0;

        foreach ($allMessages as $msg) {
            $ch = strtolower(trim($msg['channel'] ?? ''));
            $isMatch = false;

            if ($ch === $dmKey) {
                $isMatch = true;
            } elseif (str_starts_with($ch, 'dm_')) {
                $parts = explode('_', substr($ch, 3));
                if (count($parts) === 2) {
                    $c1 = getCanonicalUsername($parts[0]);
                    $c2 = getCanonicalUsername($parts[1]);
                    if (($c1 === $u1 && $c2 === $u2) || ($c1 === $u2 && $c2 === $u1)) {
                        $isMatch = true;
                    }
                }
            }

            if ($isMatch) {
                $msgCount++;
                $ts = strtotime($msg['created_at'] ?? 'now');
                if ($ts > $latestTime) {
                    $latestTime = $ts;
                }
            }
        }

        $contactsMap[$stUsername] = [
            'username' => $stUsername,
            'first_name' => $firstName,
            'full_name' => $rawFullName,
            'role' => $staff['role'] ?? 'Staff',
            'avatar' => $staff['avatar'] ?? '',
            'msg_count' => $msgCount,
            'latest_time' => $latestTime
        ];
    }

    // Sort staff contacts: Most messages first, then latest message time, then name
    usort($contactsMap, function($a, $b) {
        if ($a['msg_count'] !== $b['msg_count']) {
            return $b['msg_count'] <=> $a['msg_count'];
        }
        if ($a['latest_time'] !== $b['latest_time']) {
            return $b['latest_time'] <=> $a['latest_time'];
        }
        return strcmp($a['first_name'], $b['first_name']);
    });

    return $contactsMap;
}

/**
 * Studio Tasks (Zoho Style Kanban Board) Repository Functions
 */
function getStudioTasksFile() {
    return __DIR__ . '/../config/studio_tasks.json';
}

function getStudioTasksRepo() {
    $filePath = getStudioTasksFile();
    if (!file_exists($filePath)) {
        $initialTasks = [
            [
                'id' => 'task_1',
                'title' => 'Scout Locations',
                'description' => 'Find 2 indoor studio options with high ceilings and sound proofing.',
                'assignee_username' => 'oluwatosin',
                'assignee_name' => 'Oluwatosin Ligali',
                'assignee_avatar' => 'https://res.cloudinary.com/pnabfi91/image/upload/f_auto,q_auto/v1786714111/falhen/team/q4atws8zhxaogyzm8bgw.png',
                'stage' => 'concept',
                'due_date' => '2026-07-10',
                'due_date_str' => 'Jul 10',
                'priority' => 'High',
                'tags' => ['Location Scout', 'Indoor Studio'],
                'checklist' => [
                    ['id' => 'item_1', 'text' => 'Inspect Soundstage A ceiling height', 'completed' => true],
                    ['id' => 'item_2', 'text' => 'Verify acoustic dampening', 'completed' => false]
                ],
                'comments_count' => 0,
                'attachments_count' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'task_2',
                'title' => 'Draft Logo Concepts',
                'description' => 'Create 3 variations based on brief.',
                'assignee_username' => 'oluwatosin',
                'assignee_name' => 'Oluwatosin Ligali',
                'assignee_avatar' => 'https://res.cloudinary.com/pnabfi91/image/upload/f_auto,q_auto/v1786714111/falhen/team/q4atws8zhxaogyzm8bgw.png',
                'stage' => 'primary',
                'due_date' => '2026-07-05',
                'due_date_str' => 'Jul 5',
                'priority' => 'Medium',
                'tags' => ['Branding', 'Vector'],
                'checklist' => [
                    ['id' => 'item_1', 'text' => 'Monochrome sketch draft', 'completed' => true],
                    ['id' => 'item_2', 'text' => 'Full color presentation deck', 'completed' => true]
                ],
                'comments_count' => 0,
                'attachments_count' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'task_3',
                'title' => 'RED V-Raptor Gear Orientation Briefing',
                'description' => 'Review camera rig calibration and lens packages.',
                'assignee_username' => 'victoria',
                'assignee_name' => 'Victoria Opemipo',
                'assignee_avatar' => 'https://res.cloudinary.com/pnabfi91/image/upload/f_auto,q_auto/v1786714111/falhen/team/q4atws8zhxaogyzm8bgw.png',
                'stage' => 'ongoing',
                'due_date' => '2026-08-28',
                'due_date_str' => 'Aug 28',
                'priority' => 'Urgent',
                'tags' => ['Camera Gear', 'RED V-Raptor', '8K Cinema'],
                'checklist' => [
                    ['id' => 'item_1', 'text' => 'Calibrate V-Mount batteries', 'completed' => true],
                    ['id' => 'item_2', 'text' => 'Mount anamorphic prime lenses', 'completed' => false],
                    ['id' => 'item_3', 'text' => 'Test wireless follow focus unit', 'completed' => false]
                ],
                'comments_count' => 2,
                'attachments_count' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'task_4',
                'title' => 'Q3 Commercial Showreel Final Cut Sync',
                'description' => 'Master sound mixing and color grade output.',
                'assignee_username' => 'victoria',
                'assignee_name' => 'Victoria Opemipo',
                'assignee_avatar' => '',
                'stage' => 'pending_review',
                'due_date' => '2026-09-02',
                'due_date_str' => 'Sep 02',
                'priority' => 'Medium',
                'tags' => ['Showreel', 'Post Production'],
                'checklist' => [
                    ['id' => 'item_1', 'text' => 'Export ProRes 422 Master', 'completed' => false]
                ],
                'comments_count' => 1,
                'attachments_count' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'task_5',
                'title' => 'August Health Stipend Audit & Verification',
                'description' => 'Verify receipts and disburse health reimbursements.',
                'assignee_username' => 'mojisola',
                'assignee_name' => 'Mojisola Emjay',
                'assignee_avatar' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786714006/falhen/team/ini79za7jvbjokrf1dup.jpg',
                'stage' => 'completed',
                'due_date' => '2026-08-25',
                'due_date_str' => 'Aug 25',
                'priority' => 'Low',
                'tags' => ['HR', 'Finance'],
                'checklist' => [
                    ['id' => 'item_1', 'text' => 'Audit 12 submitted receipts', 'completed' => true],
                    ['id' => 'item_2', 'text' => 'Process payroll transfers', 'completed' => true]
                ],
                'comments_count' => 4,
                'attachments_count' => 2,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        $dir = dirname($filePath);
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        @file_put_contents($filePath, json_encode($initialTasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $initialTasks;
    }
    $content = @file_get_contents($filePath);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function saveStudioTasksRepo($tasks) {
    $filePath = getStudioTasksFile();
    @file_put_contents($filePath, json_encode(array_values($tasks), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function createStudioTask($data) {
    $tasks = getStudioTasksRepo();
    $id = 'task_' . time() . '_' . rand(10, 99);
    $dueDateStr = !empty($data['due_date']) ? date('M d', strtotime($data['due_date'])) : 'No date';
    
    // Parse tags (comma separated)
    $tagsRaw = trim($data['tags'] ?? '');
    $tags = [];
    if (!empty($tagsRaw)) {
        $parts = explode(',', $tagsRaw);
        foreach ($parts as $p) {
            $t = trim($p);
            if (!empty($t)) $tags[] = $t;
        }
    }

    // Parse checklist (newline separated lines or array)
    $checklistRaw = $data['checklist'] ?? '';
    $checklist = [];
    if (is_array($checklistRaw)) {
        $checklist = $checklistRaw;
    } else if (is_string($checklistRaw) && !empty(trim($checklistRaw))) {
        $lines = explode("\n", $checklistRaw);
        $idx = 1;
        foreach ($lines as $ln) {
            $txt = trim($ln);
            if (!empty($txt)) {
                $checklist[] = [
                    'id' => 'item_' . $idx++,
                    'text' => $txt,
                    'completed' => false
                ];
            }
        }
    }

    $attachments = [];
    if (!empty($data['attachment_url'])) {
        $attachments[] = [
            'name' => $data['attachment_name'] ?? 'Task Attachment',
            'url' => $data['attachment_url'],
            'uploaded_at' => date('Y-m-d H:i:s')
        ];
    }

    $assignees = $data['assignees'] ?? [];
    $primaryUser = !empty($assignees) ? ($assignees[0]['username'] ?? '') : ($data['assignee_username'] ?? '');
    $primaryName = !empty($assignees) ? implode(', ', array_column($assignees, 'name')) : ($data['assignee_name'] ?? 'Unassigned');
    $primaryAvatar = !empty($assignees) ? ($assignees[0]['avatar'] ?? '') : ($data['assignee_avatar'] ?? '');

    $newTask = [
        'id' => $id,
        'title' => trim($data['title'] ?? 'Untitled Task'),
        'client_org' => trim($data['client_org'] ?? ''),
        'description' => trim($data['description'] ?? ''),
        'assignees' => $assignees,
        'assignee_username' => $primaryUser,
        'assignee_name' => $primaryName,
        'assignee_avatar' => $primaryAvatar,
        'stage' => strtolower(trim($data['stage'] ?? 'ideas')),
        'due_date' => trim($data['due_date'] ?? date('Y-m-d')),
        'due_date_str' => $dueDateStr,
        'priority' => trim($data['priority'] ?? 'Medium'),
        'tags' => $tags,
        'checklist' => $checklist,
        'attachments' => $attachments,
        'comments_count' => 0,
        'attachments_count' => count($attachments),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    array_unshift($tasks, $newTask);
    saveStudioTasksRepo($tasks);
    return $newTask;
}

function toggleTaskChecklistItem($taskId, $itemId) {
    $tasks = getStudioTasksRepo();
    $updated = null;
    foreach ($tasks as &$t) {
        if (($t['id'] ?? '') === $taskId && !empty($t['checklist'])) {
            foreach ($t['checklist'] as &$item) {
                if (($item['id'] ?? '') === $itemId) {
                    $item['completed'] = !($item['completed'] ?? false);
                    $updated = $t;
                    break 2;
                }
            }
        }
    }
    saveStudioTasksRepo($tasks);
    return $updated;
}

function updateStudioTask($taskId, $data) {
    $tasks = getStudioTasksRepo();
    $updated = null;
    
    // Parse tags
    $tagsRaw = trim($data['tags'] ?? '');
    $tags = [];
    if (!empty($tagsRaw)) {
        $parts = explode(',', $tagsRaw);
        foreach ($parts as $p) {
            $t = trim($p);
            if (!empty($t)) $tags[] = $t;
        }
    }

    // Parse checklist items
    $checklistRaw = $data['checklist'] ?? '';
    $checklist = [];
    if (is_array($checklistRaw)) {
        $checklist = $checklistRaw;
    } else if (is_string($checklistRaw) && !empty(trim($checklistRaw))) {
        $lines = explode("\n", $checklistRaw);
        $idx = 1;
        foreach ($lines as $ln) {
            $txt = trim($ln);
            if (!empty($txt)) {
                $checklist[] = [
                    'id' => 'item_' . $idx++,
                    'text' => $txt,
                    'completed' => false
                ];
            }
        }
    }

    $dueDateStr = !empty($data['due_date']) ? date('M d', strtotime($data['due_date'])) : 'No date';

    foreach ($tasks as &$t) {
        if (($t['id'] ?? '') === $taskId) {
            $t['title'] = trim($data['title'] ?? $t['title']);
            if (isset($data['client_org'])) {
                $t['client_org'] = trim($data['client_org']);
            }
            $t['description'] = trim($data['description'] ?? $t['description']);
            $t['stage'] = strtolower(trim($data['stage'] ?? $t['stage']));
            $t['priority'] = trim($data['priority'] ?? $t['priority']);
            $t['due_date'] = trim($data['due_date'] ?? $t['due_date']);
            $t['due_date_str'] = $dueDateStr;
            $t['tags'] = $tags;
            $t['checklist'] = $checklist;
            if (!empty($data['attachment_url'])) {
                if (!isset($t['attachments']) || !is_array($t['attachments'])) {
                    $t['attachments'] = [];
                }
                $t['attachments'][] = [
                    'name' => $data['attachment_name'] ?? 'Task Attachment',
                    'url' => $data['attachment_url'],
                    'uploaded_at' => date('Y-m-d H:i:s')
                ];
            }
            $t['attachments_count'] = count($t['attachments'] ?? []);
            if (isset($data['assignees'])) {
                $assignees = $data['assignees'];
                $t['assignees'] = $assignees;
                $t['assignee_username'] = !empty($assignees) ? implode(',', array_column($assignees, 'username')) : '';
                $t['assignee_name'] = !empty($assignees) ? implode(', ', array_column($assignees, 'name')) : 'Unassigned';
                $t['assignee_avatar'] = !empty($assignees) ? ($assignees[0]['avatar'] ?? '') : '';
            } else if (isset($data['assignee_username'])) {
                $t['assignee_username'] = getCanonicalUsername($data['assignee_username']);
                $t['assignee_name'] = trim($data['assignee_name'] ?? $t['assignee_name']);
                $t['assignee_avatar'] = trim($data['assignee_avatar'] ?? $t['assignee_avatar']);
            }
            $updated = $t;
            break;
        }
    }
    saveStudioTasksRepo($tasks);
    return $updated;
}

function updateStudioTaskStage($taskId, $newStage) {
    $tasks = getStudioTasksRepo();
    $updated = null;
    foreach ($tasks as &$t) {
        if (($t['id'] ?? '') === $taskId) {
            $t['stage'] = strtolower(trim($newStage));
            $updated = $t;
            break;
        }
    }
    saveStudioTasksRepo($tasks);
    return $updated;
}

function deleteStudioTask($taskId) {
    $tasks = getStudioTasksRepo();
    $filtered = array_filter($tasks, fn($t) => ($t['id'] ?? '') !== $taskId);
    saveStudioTasksRepo($filtered);
    return true;
}

function getStudioTaskStagesFile() {
    return __DIR__ . '/../config/studio_task_stages.json';
}

function getStudioTaskStagesRepo() {
    $filePath = getStudioTaskStagesFile();
    if (file_exists($filePath)) {
        $json = @file_get_contents($filePath);
        $data = json_decode($json, true);
        if (is_array($data) && !empty($data)) {
            return $data;
        }
    }
    return [
        ['key' => 'ideas', 'title' => 'Ideas', 'color' => '#a855f7', 'is_default' => true],
        ['key' => 'concept', 'title' => 'Concept Development', 'color' => '#0ea5e9', 'is_default' => true],
        ['key' => 'primary', 'title' => 'Primary Development', 'color' => '#f59e0b', 'is_default' => true],
        ['key' => 'ongoing', 'title' => 'On Going', 'color' => '#3b82f6', 'is_default' => true],
        ['key' => 'pending_review', 'title' => 'Pending Review', 'color' => '#ec4899', 'is_default' => true],
        ['key' => 'completed', 'title' => 'Completed', 'color' => '#22c55e', 'is_default' => true]
    ];
}

function saveStudioTaskStagesRepo($stages) {
    $filePath = getStudioTaskStagesFile();
    @file_put_contents($filePath, json_encode(array_values($stages), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function createStudioTaskStage($title, $color = '#3b82f6') {
    $stages = getStudioTaskStagesRepo();
    $key = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($title)));
    if (empty($key)) $key = 'stage_' . time();
    
    foreach ($stages as $s) {
        if (($s['key'] ?? '') === $key) {
            $key .= '_' . rand(10, 99);
            break;
        }
    }

    $newStage = [
        'key' => $key,
        'title' => trim($title),
        'color' => !empty($color) ? $color : '#3b82f6',
        'is_default' => false
    ];

    $stages[] = $newStage;
    saveStudioTaskStagesRepo($stages);
    return $newStage;
}

function deleteStudioTaskStage($stageKey) {
    $stages = getStudioTaskStagesRepo();
    $filtered = [];
    foreach ($stages as $s) {
        if (($s['key'] ?? '') !== $stageKey) {
            $filtered[] = $s;
        }
    }
    saveStudioTaskStagesRepo($filtered);
    return true;
}

function updateStudioTaskStageLabel($stageKey, $title, $color = null) {
    $stages = getStudioTaskStagesRepo();
    $updated = null;
    foreach ($stages as &$s) {
        if (($s['key'] ?? '') === $stageKey) {
            if (!empty($title)) {
                $s['title'] = trim($title);
            }
            if (!empty($color)) {
                $s['color'] = trim($color);
            }
            $updated = $s;
            break;
        }
    }
    saveStudioTaskStagesRepo($stages);
    return $updated;
}

/**
 * Get Payroll Disbursements Repository
 */
function getPayrollDisbursements($username = null, $month = null, $year = null) {
    $settings = getSiteSettings();
    $items = $settings['payroll_disbursements'] ?? [];

    if (empty($items) || !is_array($items)) {
        // Default initial realistic disbursements for demonstration
        $items = [
            [
                'id'            => 1,
                'username'      => 'mojisola.emjay',
                'full_name'     => 'Mojisola Emjay',
                'email'         => 'mojisola.emjay@falhenmedia.com',
                'role'          => 'HR Manager',
                'pay_period'    => 'April 2026',
                'period_month'  => '04',
                'period_year'   => '2026',
                'base_salary'   => 140000,
                'allowances'    => 10000,
                'deductions'    => 10000,
                'net_pay'       => 140000,
                'currency'      => 'NGN',
                'status'        => 'Paid',
                'payment_method'=> 'Direct Bank Transfer',
                'tx_ref'        => 'FAL-PAY-202604-001',
                'processed_at'  => '2026-04-28 10:30:00',
                'processed_by'  => 'Super Admin'
            ],
            [
                'id'            => 2,
                'username'      => 'mojisola.emjay',
                'full_name'     => 'Mojisola Emjay',
                'email'         => 'mojisola.emjay@falhenmedia.com',
                'role'          => 'HR Manager',
                'pay_period'    => 'March 2026',
                'period_month'  => '03',
                'period_year'   => '2026',
                'base_salary'   => 140000,
                'allowances'    => 10000,
                'deductions'    => 10000,
                'net_pay'       => 140000,
                'currency'      => 'NGN',
                'status'        => 'Paid',
                'payment_method'=> 'Direct Bank Transfer',
                'tx_ref'        => 'FAL-PAY-202603-002',
                'processed_at'  => '2026-03-28 11:15:00',
                'processed_by'  => 'Super Admin'
            ],
            [
                'id'            => 3,
                'username'      => 'daniel.ifeoluwa',
                'full_name'     => 'Daniel Ifeoluwa',
                'email'         => 'daniel.ifeoluwa@falhenmedia.com',
                'role'          => 'Staff',
                'pay_period'    => 'April 2026',
                'period_month'  => '04',
                'period_year'   => '2026',
                'base_salary'   => 250000,
                'allowances'    => 20000,
                'deductions'    => 20000,
                'net_pay'       => 250000,
                'currency'      => 'NGN',
                'status'        => 'Paid',
                'payment_method'=> 'Direct Bank Transfer',
                'tx_ref'        => 'FAL-PAY-202604-003',
                'processed_at'  => '2026-04-28 14:20:00',
                'processed_by'  => 'Mojisola Emjay'
            ]
        ];
        $settings['payroll_disbursements'] = $items;
        saveSiteSettings($settings);
    }

    if (!empty($username)) {
        $uLower = strtolower(trim($username));
        $items = array_values(array_filter($items, function($it) use ($uLower) {
            return (strtolower($it['username'] ?? '') === $uLower || strtolower($it['email'] ?? '') === $uLower);
        }));
    }

    if (!empty($month)) {
        $mStr = sprintf('%02d', (int)$month);
        $items = array_values(array_filter($items, function($it) use ($mStr) {
            return (($it['period_month'] ?? '') === $mStr);
        }));
    }

    if (!empty($year)) {
        $yStr = (string)$year;
        $items = array_values(array_filter($items, function($it) use ($yStr) {
            return (($it['period_year'] ?? '') === $yStr);
        }));
    }

    return $items;
}

/**
 * Process Employee Payment & Persist Disbursement Record
 */
function processEmployeePayment($data) {
    $settings = getSiteSettings();
    $items = $settings['payroll_disbursements'] ?? getPayrollDisbursements();

    $payPeriod = trim($data['pay_period'] ?? date('F Y'));
    $periodMonth = sprintf('%02d', (int)($data['period_month'] ?? date('m')));
    $periodYear = (string)($data['period_year'] ?? date('Y'));
    $uName = strtolower(trim($data['username'] ?? ''));
    $uEmail = strtolower(trim($data['email'] ?? ''));

    $newId = count($items) + 1;
    $disbursement = [
        'id'            => $newId,
        'username'      => trim($data['username'] ?? ''),
        'full_name'     => trim($data['full_name'] ?? ''),
        'email'         => trim($data['email'] ?? ''),
        'role'          => trim($data['role'] ?? 'Staff'),
        'pay_period'    => $payPeriod,
        'period_month'  => $periodMonth,
        'period_year'   => $periodYear,
        'base_salary'   => (float)($data['base_salary'] ?? 0),
        'allowances'    => (float)($data['allowances'] ?? 0),
        'deductions'    => (float)($data['deductions'] ?? 0),
        'net_pay'       => (float)($data['net_pay'] ?? 0),
        'currency'      => trim($data['currency'] ?? 'NGN'),
        'status'        => trim($data['status'] ?? 'Paid'),
        'payment_method'=> trim($data['payment_method'] ?? 'Direct Bank Transfer'),
        'tx_ref'        => trim($data['tx_ref'] ?? ('FAL-PAY-' . $periodYear . $periodMonth . '-' . sprintf('%03d', $newId))),
        'note'          => trim($data['note'] ?? ''),
        'processed_at'  => date('Y-m-d H:i:s'),
        'processed_by'  => trim($data['processed_by'] ?? 'HR Manager')
    ];

    // Check if an existing disbursement exists for this staff member in this period
    $updatedExisting = false;
    foreach ($items as &$it) {
        $itUser = strtolower($it['username'] ?? '');
        $itEmail = strtolower($it['email'] ?? '');
        $itMonth = sprintf('%02d', (int)($it['period_month'] ?? '0'));
        $itYear = (string)($it['period_year'] ?? '');

        if (($itUser === $uName || $itEmail === $uEmail) && $itMonth === $periodMonth && $itYear === $periodYear) {
            $disbursement['id'] = $it['id'] ?? $newId;
            $it = $disbursement;
            $updatedExisting = true;
            break;
        }
    }
    unset($it);

    if (!$updatedExisting) {
        array_unshift($items, $disbursement);
    }

    $settings['payroll_disbursements'] = array_values($items);
    saveSiteSettings($settings);

    // Audit log
    $auditSym = getCurrencySymbol($disbursement['currency'] ?? 'NGN');
    logDatabaseAudit(
        $disbursement['processed_by'],
        'payroll_disbursement',
        'payroll',
        $disbursement['tx_ref'],
        "Salary payment of {$auditSym}" . number_format($disbursement['net_pay'], 2) . " processed for {$disbursement['full_name']} ({$disbursement['pay_period']}) via {$disbursement['payment_method']}."
    );

    return $disbursement;
}

/**
 * Calculate Employee YTD Metrics (Earnings, Taxes, Pension)
 */
function calculateEmployeeYTDMetrics($username = null, $year = '2026') {
    $disbursements = getPayrollDisbursements($username, null, $year);

    $ytdNetEarnings = 0;
    $ytdTaxes = 0;
    $ytdPension = 0;

    foreach ($disbursements as $d) {
        if (($d['status'] ?? '') === 'Paid') {
            $net = (float)($d['net_pay'] ?? 0);
            $ded = (float)($d['deductions'] ?? 0);

            $ytdNetEarnings += $net;
            // 50% of deductions estimated as tax withholding, 50% pension
            $ytdTaxes += ($ded * 0.5);
            $ytdPension += ($ded * 0.5);
        }
    }

    return [
        'net_earnings' => $ytdNetEarnings,
        'taxes'        => $ytdTaxes,
        'pension'      => $ytdPension
    ];
}

/**
 * ============================================================
 * VENDORS & INDEPENDENT CONTRACTOR ONBOARDING HELPERS
 * ============================================================
 */

/**
 * Generate Unique Vendor Reference Code (e.g., FAL-VND-2026-4821)
 */
function generateVendorReference() {
    return 'FAL-VND-' . date('Y') . '-' . str_pad((string)mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Get All Onboarded Vendors (Database + Settings Fallback)
 */
function getVendorsList() {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM `vendors` WHERE `deleted_at` IS NULL ORDER BY `id` DESC");
            if ($stmt !== false) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            error_log("Failed to fetch vendors from DB: " . $e->getMessage());
        }
    }

    $settings = getSiteSettings();
    $vendors = $settings['vendors'] ?? [];
    if (!is_array($vendors)) {
        $vendors = [];
    }
    return array_values($vendors);
}

/**
 * Get Single Vendor by ID or Reference Code
 */
function getVendorById($idOrRef) {
    if (empty($idOrRef)) return null;

    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `vendors` WHERE (`id` = ? OR `reference_code` = ?) AND `deleted_at` IS NULL LIMIT 1");
            $stmt->execute([$idOrRef, $idOrRef]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row;
        } catch (Exception $e) {}
    }

    $vendors = getVendorsList();
    foreach ($vendors as $v) {
        if ((string)($v['id'] ?? '') === (string)$idOrRef || (string)($v['reference_code'] ?? '') === (string)$idOrRef) {
            return $v;
        }
    }
    return null;
}

/**
 * Save Vendor Record (Onboarding Submission or Admin Edit)
 */
function saveVendorRecord($data) {
    $pdo = getDBConnection();
    $refCode = !empty($data['reference_code']) ? $data['reference_code'] : generateVendorReference();
    $now = date('Y-m-d H:i:s');

    $role = $data['role'] ?? 'photographer_videographer';
    $roleLabels = [
        'photographer_videographer' => 'Photographer / Videographer',
        'contractor'                => 'Photographer / Videographer',
        'intern'                    => 'Intern',
        'volunteer'                 => 'Volunteer'
    ];
    $roleLabel = $roleLabels[$role] ?? ucwords(str_replace('_', ' ', $role));

    $record = [
        'reference_code'          => $refCode,
        'role'                    => $role,
        'role_label'              => $roleLabel,
        'full_name'               => trim($data['full_name'] ?? ''),
        'email'                   => trim(strtolower($data['email'] ?? '')),
        'phone'                   => trim($data['phone'] ?? ''),
        'address'                 => trim($data['address'] ?? ''),
        'street_address'          => trim($data['street_address'] ?? ''),
        'city'                    => trim($data['city'] ?? ''),
        'state'                   => trim($data['state'] ?? ''),
        'zip'                     => trim($data['zip'] ?? ''),
        'id_type'                 => trim($data['id_type'] ?? 'W-9'),
        'tax_form_type'           => trim($data['tax_form_type'] ?? 'W-9'),
        'tax_id'                  => trim($data['tax_id'] ?? ''),
        'additional_notes'        => trim($data['additional_notes'] ?? ''),
        'id_document_url'         => trim($data['id_document_url'] ?? ''),
        'currency'                => trim($data['currency'] ?? 'NGN'),
        'bank_name'               => trim($data['bank_name'] ?? ''),
        'account_number'          => trim($data['account_number'] ?? ''),
        'account_name'            => trim($data['account_name'] ?? ''),
        'emergency_name'          => trim($data['emergency_name'] ?? ''),
        'emergency_relationship'  => trim($data['emergency_relationship'] ?? ''),
        'emergency_phone'         => trim($data['emergency_phone'] ?? ''),
        'workplace_policy_agreed' => !empty($data['workplace_policy_agreed']) ? 1 : 0,
        'nda_agreed'              => !empty($data['nda_agreed']) ? 1 : 0,
        'msa_agreed'              => !empty($data['msa_agreed']) ? 1 : 0,
        'signature_type'          => !empty($data['signature_type']) ? trim($data['signature_type']) : 'typed',
        'signature_data'          => !empty($data['signature_data']) ? trim($data['signature_data']) : '',
        'signature_name'          => trim($data['signature_name'] ?? $data['full_name'] ?? ''),
        'signature_date'          => !empty($data['signature_date']) ? $data['signature_date'] : date('Y-m-d'),
        'status'                  => !empty($data['status']) ? $data['status'] : 'active',
        'password_hash'           => !empty($data['password_hash']) ? $data['password_hash'] : null,
        'created_at'              => !empty($data['created_at']) ? $data['created_at'] : $now,
        'updated_at'              => $now
    ];

    $savedInDB = false;
    $recordId = $data['id'] ?? null;

    if ($pdo) {
        try {
            if ($recordId) {
                $stmt = $pdo->prepare("UPDATE `vendors` SET 
                    `role` = ?, `role_label` = ?, `full_name` = ?, `email` = ?, `phone` = ?, `address` = ?,
                    `id_type` = ?, `tax_id` = ?, `id_document_url` = ?, `currency` = ?, `bank_name` = ?,
                    `account_number` = ?, `account_name` = ?, `emergency_name` = ?, `emergency_relationship` = ?,
                    `emergency_phone` = ?, `status` = ?, `password_hash` = COALESCE(?, `password_hash`), `updated_at` = ?
                    WHERE `id` = ? OR `reference_code` = ?");
                $stmt->execute([
                    $record['role'], $record['role_label'], $record['full_name'], $record['email'], $record['phone'],
                    $record['address'], $record['id_type'], $record['tax_id'], $record['id_document_url'],
                    $record['currency'], $record['bank_name'], $record['account_number'], $record['account_name'],
                    $record['emergency_name'], $record['emergency_relationship'], $record['emergency_phone'],
                    $record['status'], $record['password_hash'], $record['updated_at'], $recordId, $refCode
                ]);
                $savedInDB = true;
            } else {
                $stmt = $pdo->prepare("INSERT INTO `vendors` (
                    `reference_code`, `role`, `role_label`, `full_name`, `email`, `phone`, `address`,
                    `id_type`, `tax_id`, `id_document_url`, `currency`, `bank_name`, `account_number`,
                    `account_name`, `emergency_name`, `emergency_relationship`, `emergency_phone`,
                    `workplace_policy_agreed`, `nda_agreed`, `msa_agreed`, `signature_name`, `signature_date`,
                    `status`, `created_at`, `updated_at`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $record['reference_code'], $record['role'], $record['role_label'], $record['full_name'],
                    $record['email'], $record['phone'], $record['address'], $record['id_type'], $record['tax_id'],
                    $record['id_document_url'], $record['currency'], $record['bank_name'], $record['account_number'],
                    $record['account_name'], $record['emergency_name'], $record['emergency_relationship'],
                    $record['emergency_phone'], $record['workplace_policy_agreed'], $record['nda_agreed'],
                    $record['msa_agreed'], $record['signature_name'], $record['signature_date'], $record['status'],
                    $record['created_at'], $record['updated_at']
                ]);
                $record['id'] = (int)$pdo->lastInsertId();
                $savedInDB = true;
            }
        } catch (Exception $e) {
            error_log("DB Vendor Save Error: " . $e->getMessage());
        }
    }

    // Always mirror to config/settings.json
    $settings = getSiteSettings();
    $vendors = $settings['vendors'] ?? [];
    if (!is_array($vendors)) {
        $vendors = [];
    }

    if (empty($record['id'])) {
        $record['id'] = time() + mt_rand(10, 99);
    }

    $foundIndex = -1;
    foreach ($vendors as $idx => $v) {
        if ((!empty($recordId) && (string)($v['id'] ?? '') === (string)$recordId) || 
            (!empty($refCode) && (string)($v['reference_code'] ?? '') === (string)$refCode)) {
            $foundIndex = $idx;
            break;
        }
    }

    if ($foundIndex >= 0) {
        $vendors[$foundIndex] = array_merge($vendors[$foundIndex], $record);
    } else {
        array_unshift($vendors, $record);
    }

    $settings['vendors'] = $vendors;
    saveSiteSettings($settings);

    // Audit log
    $actor = $_SESSION['admin_username'] ?? $record['full_name'] ?? 'System';
    logDatabaseAudit($actor, 'vendor_onboarded', 'vendor', $refCode, "Vendor onboarded: {$record['full_name']} as {$record['role_label']} ({$refCode}).");

    return $record;
}

/**
 * Delete / Soft-Delete Vendor Record
 */
function deleteVendorRecord($idOrRef) {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE `vendors` SET `deleted_at` = NOW() WHERE `id` = ? OR `reference_code` = ?");
            $stmt->execute([$idOrRef, $idOrRef]);
        } catch (Exception $e) {}
    }

    $settings = getSiteSettings();
    $vendors = $settings['vendors'] ?? [];
    if (is_array($vendors)) {
        $vendors = array_values(array_filter($vendors, function($v) use ($idOrRef) {
            return (string)($v['id'] ?? '') !== (string)$idOrRef && (string)($v['reference_code'] ?? '') !== (string)$idOrRef;
        }));
        $settings['vendors'] = $vendors;
        saveSiteSettings($settings);
    }

    $actor = $_SESSION['admin_username'] ?? 'Admin';
    logDatabaseAudit($actor, 'vendor_deleted', 'vendor', (string)$idOrRef, "Vendor #{$idOrRef} deleted.");
    return true;
}

/**
 * Clear All Vendor / Talent Records
 */
function clearAllVendors() {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $pdo->exec("UPDATE `vendors` SET `deleted_at` = NOW() WHERE `deleted_at` IS NULL");
        } catch (Exception $e) {
            error_log("Failed to soft-delete all vendors in DB: " . $e->getMessage());
        }
    }

    $settings = getSiteSettings();
    $settings['vendors'] = [];
    saveSiteSettings($settings);

    $actor = $_SESSION['admin_username'] ?? 'Admin';
    logDatabaseAudit($actor, 'vendors_cleared_all', 'vendor', 'all', "All vendors / talent cleared.");
    return true;
}

/**
 * Update Vendor Status (active, under_review, suspended)
 */
function updateVendorStatus($idOrRef, $newStatus) {
    $allowed = ['active', 'under_review', 'completed', 'suspended'];
    if (!in_array($newStatus, $allowed, true)) {
        return false;
    }

    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE `vendors` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ? OR `reference_code` = ?");
            $stmt->execute([$newStatus, $idOrRef, $idOrRef]);
        } catch (Exception $e) {}
    }

    $settings = getSiteSettings();
    $vendors = $settings['vendors'] ?? [];
    if (is_array($vendors)) {
        foreach ($vendors as &$v) {
            if ((string)($v['id'] ?? '') === (string)$idOrRef || (string)($v['reference_code'] ?? '') === (string)$idOrRef) {
                $v['status'] = $newStatus;
                $v['updated_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        unset($v);
        $settings['vendors'] = $vendors;
        saveSiteSettings($settings);
    }

    $actor = $_SESSION['admin_username'] ?? 'Admin';
    logDatabaseAudit($actor, 'vendor_status_update', 'vendor', (string)$idOrRef, "Vendor #{$idOrRef} status updated to {$newStatus}.");
    return true;
}

/**
 * Get Authorized Counter-Signer for Company Agreements & Legal Documents
 */
function getAuthorizedCounterSigner() {
    $settings = getSiteSettings();
    if (!empty($settings['authorized_counter_signer']) && is_array($settings['authorized_counter_signer'])) {
        return $settings['authorized_counter_signer'];
    }

    // Check staff accounts for a designated counter-signer
    if (function_exists('getStaffAccountsRepo')) {
        $staffList = getStaffAccountsRepo();
        foreach ($staffList as $staff) {
            if (!empty($staff['is_authorized_counter_signer']) && !empty($staff['signature_data'])) {
                return [
                    'full_name'      => $staff['full_name'] ?? 'Oluwatosin Ligali',
                    'designation'    => $staff['counter_signer_title'] ?? ($staff['designation'] ?? 'Executive Producer'),
                    'signature_data' => $staff['signature_data'] ?? '',
                    'signature_type' => $staff['signature_type'] ?? 'draw',
                    'username'       => $staff['username'] ?? '',
                    'updated_at'     => $staff['signature_updated_at'] ?? date('Y-m-d H:i:s')
                ];
            }
        }
    }

    // Check admin profile
    if (function_exists('getAdminUserProfile')) {
        $admin = getAdminUserProfile();
        if (!empty($admin['signature_data'])) {
            return [
                'full_name'      => $admin['full_name'] ?? 'Oluwatosin Ligali',
                'designation'    => $admin['counter_signer_title'] ?? ($admin['designation'] ?? 'Executive Producer'),
                'signature_data' => $admin['signature_data'] ?? '',
                'signature_type' => $admin['signature_type'] ?? 'draw',
                'username'       => $admin['username'] ?? 'admin',
                'updated_at'     => $admin['signature_updated_at'] ?? date('Y-m-d H:i:s')
            ];
        }
    }

    // Default fallback to company executive producer
    return [
        'full_name'      => 'Oluwatosin Ligali',
        'designation'    => 'Executive Producer',
        'signature_data' => '',
        'signature_type' => 'typed',
        'username'       => 'oluwatosin.ligali',
        'updated_at'     => date('Y-m-d H:i:s')
    ];
}

/**
 * Save Authorized Counter-Signer to Site Settings
 */
function saveAuthorizedCounterSigner(array $signerData) {
    $settings = getSiteSettings();
    $settings['authorized_counter_signer'] = [
        'full_name'      => trim($signerData['full_name'] ?? 'Oluwatosin Ligali'),
        'designation'    => trim($signerData['designation'] ?? 'Executive Producer'),
        'signature_data' => trim($signerData['signature_data'] ?? ''),
        'signature_type' => trim($signerData['signature_type'] ?? 'draw'),
        'username'       => trim($signerData['username'] ?? ''),
        'updated_at'     => date('Y-m-d H:i:s')
    ];
    saveSiteSettings($settings);
    return $settings['authorized_counter_signer'];
}

/**
 * Get Vendor by Email Address
 */
function getVendorByEmail($email) {
    if (empty($email)) return null;
    $email = strtolower(trim($email));
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `vendors` WHERE LOWER(`email`) = ? AND `deleted_at` IS NULL LIMIT 1");
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row;
        } catch (Exception $e) {}
    }

    $vendors = getVendorsList();
    foreach ($vendors as $v) {
        if (strtolower(trim($v['email'] ?? '')) === $email) {
            return $v;
        }
    }
    return null;
}

/**
 * Authenticate Vendor by Email and Reference Code / Password
 */
function authenticateVendor($email, $refOrPassword) {
    if (empty($email) || empty($refOrPassword)) {
        return ['success' => false, 'message' => 'Please provide both email address and reference code / password.'];
    }

    $email = strtolower(trim($email));
    $inputCred = trim($refOrPassword);

    $pdo = getDBConnection();
    $vendor = null;

    if ($pdo) {
        try {
            // Check direct reference code match
            $stmt = $pdo->prepare("SELECT * FROM `vendors` WHERE LOWER(`email`) = ? AND UPPER(`reference_code`) = ? AND `deleted_at` IS NULL ORDER BY `id` DESC LIMIT 1");
            $stmt->execute([$email, strtoupper($inputCred)]);
            $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

            // If not found by ref code, check password
            if (!$vendor) {
                $stmt = $pdo->prepare("SELECT * FROM `vendors` WHERE LOWER(`email`) = ? AND `deleted_at` IS NULL ORDER BY `id` DESC");
                $stmt->execute([$email]);
                $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($candidates as $cand) {
                    if (!empty($cand['password_hash']) && password_verify($inputCred, $cand['password_hash'])) {
                        $vendor = $cand;
                        break;
                    }
                }
            }
        } catch (Exception $e) {}
    }

    // Fallback to JSON if not found via DB
    if (!$vendor) {
        $vendors = getVendorsList();
        foreach ($vendors as $cand) {
            if (strtolower(trim($cand['email'] ?? '')) === $email) {
                $cleanRef = strtoupper(trim($cand['reference_code'] ?? ''));
                if (!empty($cleanRef) && strtoupper($inputCred) === $cleanRef) {
                    $vendor = $cand;
                    break;
                }
                if (!empty($cand['password_hash']) && password_verify($inputCred, $cand['password_hash'])) {
                    $vendor = $cand;
                    break;
                }
            }
        }
    }

    if (!$vendor) {
        $exists = getVendorByEmail($email);
        if (!$exists) {
            return ['success' => false, 'message' => 'No vendor record found with this email address. Please ensure you have completed vendor onboarding.'];
        }
        return ['success' => false, 'message' => 'Invalid reference code or password. Please verify and try again.'];
    }

    // Check status
    if (in_array(strtolower($vendor['status'] ?? ''), ['suspended', 'inactive'], true)) {
        return ['success' => false, 'message' => 'Your vendor account is currently suspended. Please contact Falhen Media management.'];
    }

    // Set vendor session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['vendor_logged_in']  = true;
    $_SESSION['vendor_id']         = $vendor['id'];
    $_SESSION['vendor_ref']        = $vendor['reference_code'];
    $_SESSION['vendor_email']      = $vendor['email'];
    $_SESSION['vendor_name']       = $vendor['full_name'];
    $_SESSION['vendor_role']       = $vendor['role'];
    $_SESSION['vendor_role_label'] = $vendor['role_label'] ?? 'Vendor';

    logDatabaseAudit($vendor['full_name'], 'vendor_login', 'vendor', $vendor['reference_code'], "Vendor logged in: {$vendor['full_name']} ({$vendor['reference_code']}).");

    return ['success' => true, 'vendor' => $vendor];
}

/**
 * Check if a Vendor is Logged In
 */
function isVendorLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['vendor_logged_in']) && (!empty($_SESSION['vendor_id']) || !empty($_SESSION['vendor_ref']));
}

/**
 * Get Current Logged-In Vendor Record
 */
function getLoggedInVendor() {
    if (!isVendorLoggedIn()) return null;
    $ref = $_SESSION['vendor_ref'] ?? $_SESSION['vendor_id'] ?? null;
    if (!$ref) return null;
    return getVendorById($ref);
}

/**
 * Clear Vendor Session (Logout)
 */
function clearVendorSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    unset(
        $_SESSION['vendor_logged_in'],
        $_SESSION['vendor_id'],
        $_SESSION['vendor_ref'],
        $_SESSION['vendor_email'],
        $_SESSION['vendor_name'],
        $_SESSION['vendor_role'],
        $_SESSION['vendor_role_label']
    );
}

/**
 * Update Vendor Profile Info
 */
function updateVendorProfile($idOrRef, $data) {
    $vendor = getVendorById($idOrRef);
    if (!$vendor) return false;

    $fields = ['phone', 'address', 'street_address', 'city', 'state', 'zip', 'emergency_name', 'emergency_relationship', 'emergency_phone', 'bank_name', 'account_number', 'account_name', 'currency'];
    foreach ($fields as $f) {
        if (isset($data[$f])) {
            $vendor[$f] = trim($data[$f]);
        }
    }
    if (isset($data['street_address']) || isset($data['city']) || isset($data['state']) || isset($data['zip'])) {
        $parts = array_filter([$vendor['street_address'] ?? '', $vendor['city'] ?? '', trim(($vendor['state'] ?? '') . ' ' . ($vendor['zip'] ?? ''))]);
        if (!empty($parts)) {
            $vendor['address'] = implode(', ', $parts);
        }
    }

    return saveVendorRecord($vendor);
}

/**
 * Set or Change Vendor Password
 */
function setVendorPassword($idOrRef, $newPassword) {
    if (strlen($newPassword) < 6) return false;
    $vendor = getVendorById($idOrRef);
    if (!$vendor) return false;

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $vendor['password_hash'] = $hash;

    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE `vendors` SET `password_hash` = ?, `updated_at` = NOW() WHERE `id` = ? OR `reference_code` = ?");
            $stmt->execute([$hash, $idOrRef, $idOrRef]);
        } catch (Exception $e) {
            try {
                $pdo->exec("ALTER TABLE `vendors` ADD COLUMN `password_hash` VARCHAR(255) NULL AFTER `signature_date`");
                $stmt = $pdo->prepare("UPDATE `vendors` SET `password_hash` = ?, `updated_at` = NOW() WHERE `id` = ? OR `reference_code` = ?");
                $stmt->execute([$hash, $idOrRef, $idOrRef]);
            } catch (Exception $e2) {}
        }
    }

    return saveVendorRecord($vendor);
}

/**
 * Get Global Vendor Gigs Repository from settings.json
 */
function getVendorGigsRepository() {
    $settings = getSiteSettings();
    if (array_key_exists('vendor_gigs', $settings) && is_array($settings['vendor_gigs'])) {
        return $settings['vendor_gigs'];
    }

    return [];
}

/**
 * Clear All Production Gigs from Repository
 */
function clearAllVendorGigs() {
    saveSiteSettings(['vendor_gigs' => []]);
    $actor = $_SESSION['admin_username'] ?? 'Admin';
    logDatabaseAudit($actor, 'vendor_gigs_cleared_all', 'vendor_gig', 'all', "All production gigs cleared.");
    return true;
}

/**
 * Get Gigs Assigned to a Specific Vendor
 */
function getVendorGigs($vendorRefOrEmail) {
    $allGigs = getVendorGigsRepository();
    $vendorKey = strtolower(trim($vendorRefOrEmail));

    $matched = [];
    $unassigned = [];

    foreach ($allGigs as $g) {
        $gRef = strtolower(trim($g['assigned_to_ref'] ?? ''));
        $gEmail = strtolower(trim($g['assigned_to_email'] ?? ''));

        if ((!empty($gRef) && $gRef === $vendorKey) || (!empty($gEmail) && $gEmail === $vendorKey)) {
            $matched[] = $g;
        } else if (empty($gRef) && empty($gEmail)) {
            $unassigned[] = $g;
        }
    }

    // If no specific gigs were mapped, attach default gigs to this vendor and save
    if (empty($matched) && !empty($unassigned)) {
        foreach ($allGigs as &$g) {
            if (empty($g['assigned_to_ref']) && empty($g['assigned_to_email'])) {
                $g['assigned_to_ref'] = $vendorRefOrEmail;
                $g['assigned_to_email'] = $vendorRefOrEmail;
                $matched[] = $g;
            }
        }
        unset($g);
        saveSiteSettings(['vendor_gigs' => $allGigs]);
    }

    // Sort: upcoming dates first, then done
    usort($matched, function($a, $b) {
        $statusWeight = [
            'in_progress' => 1,
            'assigned'    => 2,
            'confirmed'   => 3,
            'done'        => 4
        ];
        $wA = $statusWeight[$a['status'] ?? 'assigned'] ?? 5;
        $wB = $statusWeight[$b['status'] ?? 'assigned'] ?? 5;
        if ($wA !== $wB) return $wA <=> $wB;
        return strcmp($b['shoot_date'] ?? '', $a['shoot_date'] ?? '');
    });

    return $matched;
}

/**
 * Get Single Gig by ID
 */
function getVendorGigById($gigId) {
    $allGigs = getVendorGigsRepository();
    foreach ($allGigs as $g) {
        if (($g['id'] ?? '') === $gigId) {
            return $g;
        }
    }
    return null;
}

/**
 * Save / Update Single Gig in Repository
 */
function saveVendorGig($gigData) {
    $allGigs = getVendorGigsRepository();
    if (empty($gigData['id'])) {
        $gigData['id'] = 'GIG-' . date('Y') . '-' . str_pad((string)mt_rand(100, 999), 3, '0', STR_PAD_LEFT);
    }
    if (!empty($gigData['assigned_to_ref'])) {
        $vendor = getVendorById($gigData['assigned_to_ref']);
        if ($vendor) {
            $gigData['assigned_to_name'] = $vendor['full_name'];
            $gigData['assigned_to_email'] = $vendor['email'];
        }
    }
    $found = false;
    foreach ($allGigs as $idx => $g) {
        if (($g['id'] ?? '') === ($gigData['id'] ?? '')) {
            $allGigs[$idx] = array_merge($g, $gigData);
            $found = true;
            break;
        }
    }
    if (!$found) {
        array_unshift($allGigs, $gigData);
    }
    saveSiteSettings(['vendor_gigs' => $allGigs]);
    return $gigData;
}

/**
 * Delete Production Gig from Repository
 */
function deleteVendorGig($gigId) {
    $allGigs = getVendorGigsRepository();
    $found = false;
    $filtered = [];
    foreach ($allGigs as $g) {
        if (($g['id'] ?? '') === $gigId) {
            $found = true;
        } else {
            $filtered[] = $g;
        }
    }
    if ($found) {
        saveSiteSettings(['vendor_gigs' => $filtered]);
        $actor = $_SESSION['admin_username'] ?? 'Admin';
        logDatabaseAudit($actor, 'vendor_gig_deleted', 'vendor_gig', (string)$gigId, "Production Gig #{$gigId} deleted.");
        return true;
    }
    return false;
}

/**
 * Update Gig Status (assigned -> confirmed -> done)
 */
function updateVendorGigStatus($gigId, $status, $extraNotes = '') {
    $gig = getVendorGigById($gigId);
    if (!$gig) return false;

    $gig['status'] = $status;
    if (!empty($extraNotes)) {
        $gig['vendor_notes'] = $extraNotes;
    }
    if ($status === 'done') {
        $gig['completed_at'] = date('Y-m-d H:i:s');
        $gig['deliverables_status'] = 'Marked Done by Vendor (Ready for Handover)';
    } else if ($status === 'confirmed') {
        $gig['confirmed_at'] = date('Y-m-d H:i:s');
    }

    return saveVendorGig($gig);
}

/**
 * Save Vendor Uploaded Call Sheet / Wrap Report
 */
function saveVendorCallSheetUpload($gigId, $fileUrl, $fileName) {
    $gig = getVendorGigById($gigId);
    if (!$gig) return false;

    $gig['uploaded_call_sheet'] = $fileUrl;
    $gig['uploaded_call_sheet_name'] = $fileName;
    $gig['uploaded_call_sheet_at'] = date('Y-m-d H:i:s');

    return saveVendorGig($gig);
}

/**
 * Get Vendor Calendar Bookings & Availability
 */
function getVendorAvailability($vendorRef) {
    $settings = getSiteSettings();
    $availRepo = $settings['vendor_availability'] ?? [];
    return $availRepo[$vendorRef] ?? [
        'available_dates'   => [],
        'unavailable_dates' => [],
        'notes'             => ''
    ];
}

/**
 * Save Vendor Calendar Availability
 */
function saveVendorAvailability($vendorRef, $availableDates, $unavailableDates = [], $notes = '') {
    $settings = getSiteSettings();
    $availRepo = $settings['vendor_availability'] ?? [];
    $availRepo[$vendorRef] = [
        'available_dates'   => array_values(array_unique($availableDates)),
        'unavailable_dates' => array_values(array_unique($unavailableDates)),
        'notes'             => trim($notes),
        'updated_at'        => date('Y-m-d H:i:s')
    ];
    saveSiteSettings(['vendor_availability' => $availRepo]);
    return true;
}
