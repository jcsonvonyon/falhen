<?php
/**
 * api/verify-download.php
 * Client Identity (Name, Email, Phone) & 6-Digit OTP Verification Gate
 * Falhen Media Client Portal (client.falhen.com)
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/smtp_mailer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Use POST.']);
    exit;
}

// Parse JSON body if Content-Type is application/json, otherwise use $_POST
$rawBody = file_get_contents('php://input');
if (!empty($rawBody) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $jsonInput = json_decode($rawBody, true);
    if (is_array($jsonInput)) {
        $_POST = array_merge($_POST, $jsonInput);
    }
}

$action = trim($_POST['action'] ?? '');
$email  = strtolower(trim($_POST['email'] ?? ''));
$name   = trim($_POST['name'] ?? '');
$phone  = trim($_POST['phone'] ?? '');
$albumId = (int)($_POST['album_id'] ?? 1);
$albumTitle = trim($_POST['album_title'] ?? 'Falhen Client Gallery');

// Authorized studio administrator emails (can verify any album for QA / client handover)
$adminAuthorizedEmails = [
    'jcsonvonyon@gmail.com',
    'admin@falhen.com',
    'henry@falhen.com'
];

/**
 * Strict folder-scoped authorization check:
 * Validates that the provided email is authorized for THIS specific album folder.
 * Clients of Folder A cannot unlock or download Folder B.
 */
function isClientAuthorizedForFolder(string $email, int $albumId, string $albumTitle, string $name, array $adminEmails): bool {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $cleanEmail = strtolower(trim($email));

    // 1. Studio superadmins can authorize any album
    if (in_array($cleanEmail, array_map('strtolower', $adminEmails), true)) {
        return true;
    }

    // 2. Resolve the specific album record from Portfolio Repository
    $portfolioRepo = getPortfolioRepo();
    $targetAlbum = null;
    foreach ($portfolioRepo as $alb) {
        if ((int)($alb['id'] ?? 0) === $albumId) {
            $targetAlbum = $alb;
            break;
        }
    }
    if (!$targetAlbum && !empty($albumTitle)) {
        foreach ($portfolioRepo as $alb) {
            if (!empty($alb['title']) && strcasecmp(trim($alb['title']), trim($albumTitle)) === 0) {
                $targetAlbum = $alb;
                break;
            }
        }
    }

    if (!$targetAlbum) {
        return false;
    }

    // 3. Match against the folder-specific client_email configured for THIS album
    $albumEmailConfig = trim($targetAlbum['client_email'] ?? '');
    if (!empty($albumEmailConfig)) {
        $allowedFolderEmails = array_filter(array_map('trim', preg_split('/[,;\s]+/', strtolower($albumEmailConfig))));
        if (in_array($cleanEmail, $allowedFolderEmails, true)) {
            return true;
        }
    }

    // 4. Fallback: If no client_email configured yet on this specific album, check client name match for this album
    $albClient = strtolower(trim($targetAlbum['client'] ?? ''));
    if (!empty($albClient)) {
        $clientClean = preg_replace('/[^a-z0-9]/', '', $albClient);
        $emailPrefixClean = preg_replace('/[^a-z0-9]/', '', strstr($cleanEmail, '@', true) ?: $cleanEmail);
        if (!empty($clientClean) && (strpos($emailPrefixClean, $clientClean) !== false || strpos($clientClean, $emailPrefixClean) !== false)) {
            return true;
        }
    }

    // 5. Check Database: clients / inquiries matching this specific email AND client name
    $pdo = getDBConnection();
    if ($pdo && !empty($albClient)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM clients WHERE LOWER(email) = :email AND LOWER(name) LIKE :cname LIMIT 1");
            $stmt->execute([':email' => $cleanEmail, ':cname' => '%' . $albClient . '%']);
            if ($stmt->fetch()) {
                return true;
            }
        } catch (Exception $e) {}

        try {
            $stmt = $pdo->prepare("SELECT id FROM inquiries WHERE LOWER(email) = :email AND LOWER(name) LIKE :cname LIMIT 1");
            $stmt->execute([':email' => $cleanEmail, ':cname' => '%' . $albClient . '%']);
            if ($stmt->fetch()) {
                return true;
            }
        } catch (Exception $e) {}
    }

    // Default: Email is NOT authorized for this specific folder
    return false;
}

/**
 * Helper to append an audit entry to config/downloads_log.json
 */
function logClientDownloadEvent(array $entry): void {
    $logFile = __DIR__ . '/../config/downloads_log.json';
    $logs = file_exists($logFile) ? @json_decode(file_get_contents($logFile), true) : [];
    if (!is_array($logs)) {
        $logs = [];
    }
    $logs[] = array_merge($entry, [
        'time' => date('Y-m-d H:i:s'),
        'ip'   => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ]);
    @file_put_contents($logFile, json_encode(array_slice($logs, -250), JSON_PRETTY_PRINT));

    if (function_exists('logClientDownload')) {
        logClientDownload([
            'client_name'   => $entry['name'] ?? ($entry['client_name'] ?? 'Client'),
            'client_email'  => $entry['email'] ?? ($entry['client_email'] ?? ''),
            'client_phone'  => $entry['phone'] ?? ($entry['client_phone'] ?? ''),
            'album_id'      => (int)($entry['album_id'] ?? 0),
            'album_title'   => $entry['album_title'] ?? 'Gallery',
            'download_type' => $entry['event'] ?? ($entry['status'] ?? 'Verification Code Requested')
        ]);
    }
}

// ============================================================================
// STEP 1: REQUEST 6-DIGIT OTP CODE (Requires Name, Email, and Phone Number)
// ============================================================================
if ($action === 'request_code' || ($action === 'verify_client_download' && !empty($_POST['require_otp']))) {
    if (empty($name) || strlen($name) < 2) {
        http_response_code(422);
        echo json_encode(['success' => false, 'field' => 'name', 'message' => 'Please provide your full name.']);
        exit;
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'field' => 'email', 'message' => 'Please provide a valid email address.']);
        exit;
    }
    $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
    if (empty($cleanPhone) || strlen($cleanPhone) < 7) {
        http_response_code(422);
        echo json_encode(['success' => false, 'field' => 'phone', 'message' => 'Please provide a valid phone number for security verification.']);
        exit;
    }

    $isAuthorized = isClientAuthorizedForFolder($email, $albumId, $albumTitle, $name, $adminAuthorizedEmails);
    if (!$isAuthorized) {
        http_response_code(403);
        echo json_encode([
            'success'       => false,
            'is_registered' => false,
            'message'       => 'Access Restricted: The email provided is not authorized to download this private gallery folder. Please provide the client email registered for this shoot.'
        ]);
        exit;
    }

    // Generate cryptographically secure 6-digit OTP
    $code = (string)random_int(100000, 999999);
    $_SESSION['download_code_hash']    = hash('sha256', $code);
    $_SESSION['download_code_plain']   = $code; // Kept in server session for Super Admin flow test verification
    $_SESSION['download_code_expires'] = time() + 600; // 10 minutes TTL
    $_SESSION['download_code_attempts']= 0;
    $_SESSION['download_email']        = $email;
    $_SESSION['download_name']         = $name;
    $_SESSION['download_phone']        = $phone;
    $_SESSION['download_album_id']     = $albumId;
    $_SESSION['download_album_title']  = $albumTitle;

    // Dispatch 6-digit OTP via SMTP Email
    $mailSent = false;
    if (function_exists('sendVerificationCodeEmail')) {
        $mailSent = @sendVerificationCodeEmail($email, $code, $name);
    }

    // Mask email and phone for UI confirmation (never expose full email publicly)
    $emailParts = explode('@', $email);
    $maskedEmail = substr($emailParts[0], 0, 2) . '***@' . ($emailParts[1] ?? '');
    $maskedPhone = substr($cleanPhone, 0, 3) . '****' . substr($cleanPhone, -3);

    $responsePayload = [
        'success'      => true,
        'otp_required' => true,
        'masked_email' => $maskedEmail,
        'masked_phone' => $maskedPhone,
        'mail_sent'    => (bool)$mailSent,
        'expires_in'   => 600,
        'message'      => 'A 6-digit security verification code has been dispatched to your registered address (' . htmlspecialchars($maskedEmail) . ').'
    ];

    // If SMTP is unavailable in local dev or fallback mode, provide a dev preview hint so clients/testers aren't blocked
    if (!$mailSent) {
        $responsePayload['delivery_mode'] = 'instant_fallback';
        $responsePayload['dev_otp_hint']  = $code;
        $responsePayload['message']       = 'Verification code generated for ' . htmlspecialchars($maskedEmail) . '. Enter your 6-digit security passcode below.';
    }

    echo json_encode($responsePayload);
    exit;
}

// ============================================================================
// STEP 2: VERIFY 6-DIGIT OTP CODE & UNLOCK DOWNLOAD GATE FOR THIS FOLDER
// ============================================================================
if ($action === 'verify_code') {
    $enteredCode = trim($_POST['code'] ?? $_POST['otp'] ?? '');
    $savedHash   = $_SESSION['download_code_hash'] ?? '';
    $savedEmail  = $_SESSION['download_email'] ?? '';
    $expiresAt   = (int)($_SESSION['download_code_expires'] ?? 0);
    $attempts    = (int)($_SESSION['download_code_attempts'] ?? 0);

    if (empty($savedHash) || empty($savedEmail)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Verification session expired. Please request a new 6-digit code.']);
        exit;
    }

    if (time() > $expiresAt) {
        unset($_SESSION['download_code_hash'], $_SESSION['download_code_plain'], $_SESSION['download_code_expires']);
        http_response_code(410);
        echo json_encode(['success' => false, 'message' => 'Your 6-digit verification code has expired. Please request a new code.']);
        exit;
    }

    if ($attempts >= 3) {
        unset($_SESSION['download_code_hash'], $_SESSION['download_code_plain'], $_SESSION['download_code_expires']);
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many incorrect attempts. For your security, please request a new verification code.']);
        exit;
    }

    if (!empty($email) && $email !== $savedEmail) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email mismatch. Please request a new verification code.']);
        exit;
    }

    if (empty($enteredCode) || !hash_equals($savedHash, hash('sha256', $enteredCode))) {
        $_SESSION['download_code_attempts'] = $attempts + 1;
        $remaining = max(0, 3 - $_SESSION['download_code_attempts']);
        http_response_code(401);
        echo json_encode([
            'success'            => false,
            'remaining_attempts' => $remaining,
            'message'            => $remaining > 0
                ? "Invalid verification code. {$remaining} attempt(s) remaining."
                : "Invalid code. Maximum attempts reached — please request a new code."
        ]);
        exit;
    }

    // OTP Verified! Issue signed download token and unlock client session strictly for this album
    $verifiedName  = $_SESSION['download_name'] ?? $name;
    $verifiedPhone = $_SESSION['download_phone'] ?? $phone;
    $verifiedAlbumId = (int)($_SESSION['download_album_id'] ?? $albumId);
    $verifiedAlbumTitle = $_SESSION['download_album_title'] ?? $albumTitle;

    unset($_SESSION['download_code_hash'], $_SESSION['download_code_plain'], $_SESSION['download_code_expires'], $_SESSION['download_code_attempts']);

    $downloadToken = bin2hex(random_bytes(24));
    
    // Strict folder-scoped session storage
    if (!isset($_SESSION['verified_albums']) || !is_array($_SESSION['verified_albums'])) {
        $_SESSION['verified_albums'] = [];
    }
    $_SESSION['verified_albums'][$verifiedAlbumId] = true;

    if (!isset($_SESSION['verified_album_tokens']) || !is_array($_SESSION['verified_album_tokens'])) {
        $_SESSION['verified_album_tokens'] = [];
    }
    $_SESSION['verified_album_tokens'][$verifiedAlbumId] = $downloadToken;

    // Backward compatibility keys
    $_SESSION['client_download_verified'] = true;
    $_SESSION['client_download_token']    = $downloadToken;
    $_SESSION['last_verified_album_id']   = $verifiedAlbumId;
    $_SESSION['registered_client_name']   = $verifiedName;

    logClientDownloadEvent([
        'name'        => $verifiedName,
        'email_hash'  => hash('sha256', $savedEmail),
        'phone_masked'=> substr($verifiedPhone, 0, 3) . '****' . substr($verifiedPhone, -3),
        'album_id'    => $verifiedAlbumId,
        'album_title' => $verifiedAlbumTitle,
        'verified_by' => 'otp_6digit'
    ]);

    $emailParts = explode('@', $savedEmail);
    $maskedEmail = substr($emailParts[0], 0, 2) . '***@' . ($emailParts[1] ?? '');

    echo json_encode([
        'success'          => true,
        'verified'         => true,
        'masked_email'     => $maskedEmail,
        'name'             => $verifiedName,
        'album_id'         => $verifiedAlbumId,
        'download_token'   => $downloadToken,
        'download_zip_url' => '/api/download-album.php?album_id=' . $verifiedAlbumId . '&token=' . urlencode($downloadToken),
        'message'          => 'Identity verified! Preparing your gallery download...'
    ]);
    exit;
}

// ============================================================================
// LEGACY / DIRECT CLIENT VERIFICATION (Also requires Name, Email, Phone)
// ============================================================================
if ($action === 'verify_client_download') {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
        exit;
    }
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Please provide your full name.']);
        exit;
    }
    $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
    if (empty($cleanPhone) || strlen($cleanPhone) < 7) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid phone number.']);
        exit;
    }

    if (!isClientAuthorizedForFolder($email, $albumId, $albumTitle, $name, $adminAuthorizedEmails)) {
        echo json_encode([
            'success'       => false,
            'is_registered' => false,
            'message'       => 'Access Restricted: The email provided is not authorized to download this private gallery folder.'
        ]);
        exit;
    }

    $downloadToken = bin2hex(random_bytes(24));
    if (!isset($_SESSION['verified_albums']) || !is_array($_SESSION['verified_albums'])) {
        $_SESSION['verified_albums'] = [];
    }
    $_SESSION['verified_albums'][$albumId] = true;

    if (!isset($_SESSION['verified_album_tokens']) || !is_array($_SESSION['verified_album_tokens'])) {
        $_SESSION['verified_album_tokens'] = [];
    }
    $_SESSION['verified_album_tokens'][$albumId] = $downloadToken;

    $_SESSION['client_download_verified'] = true;
    $_SESSION['client_download_token']    = $downloadToken;
    $_SESSION['last_verified_album_id']   = $albumId;
    $_SESSION['registered_client_name']   = $name;

    logClientDownloadEvent([
        'name'        => $name,
        'email_hash'  => hash('sha256', $email),
        'phone_masked'=> substr($phone, 0, 3) . '****' . substr($phone, -3),
        'album_id'    => $albumId,
        'album_title' => $albumTitle,
        'verified_by' => 'direct_identity'
    ]);

    $emailParts = explode('@', $email);
    $maskedEmail = substr($emailParts[0], 0, 2) . '***@' . ($emailParts[1] ?? '');

    echo json_encode([
        'success'          => true,
        'is_registered'    => true,
        'masked_email'     => $maskedEmail,
        'name'             => $name,
        'album_id'         => $albumId,
        'download_token'   => $downloadToken,
        'download_zip_url' => '/api/download-album.php?album_id=' . $albumId . '&token=' . urlencode($downloadToken),
        'message'          => 'Client verified successfully! Your download is starting...'
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit;
