<?php
// api/verify-download.php - Client Verification & Download Code Handler
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/smtp_mailer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';
$email = strtolower(trim($_POST['email'] ?? ''));

// Authorized client emails list (for demonstration & database fallback)
$authorizedClients = [
    'jcsonvonyon@gmail.com',
    'kingdavid@falhen.com',
    'halima@falhen.com',
    'demola@falhen.com',
    'henry@falhen.com',
    'client@falhen.com',
    'user@example.com',
    'test@gmail.com'
];

if ($action === 'verify_client_download') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $albumId = (int)($_POST['album_id'] ?? 0);
    $albumTitle = trim($_POST['album_title'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
        exit;
    }
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Please provide your full name.']);
        exit;
    }

    $isAuthorized = false;

    // Check Settings for registered clients
    $settings = getSiteSettings();
    $registeredClients = $settings['registered_clients'] ?? [];
    if (is_array($registeredClients)) {
        foreach ($registeredClients as $rc) {
            $rcEmail = is_array($rc) ? strtolower($rc['email'] ?? '') : strtolower((string)$rc);
            if ($rcEmail === $email) {
                $isAuthorized = true;
                break;
            }
        }
    }

    // Check Database if not found in settings
    if (!$isAuthorized) {
        $pdo = getDBConnection();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM clients WHERE LOWER(email) = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                if ($stmt->fetch()) {
                    $isAuthorized = true;
                }
            } catch (Exception $e) {
                // Ignore DB error and fallback
            }

            if (!$isAuthorized) {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM inquiries WHERE LOWER(email) = :email LIMIT 1");
                    $stmt->execute([':email' => $email]);
                    if ($stmt->fetch()) {
                        $isAuthorized = true;
                    }
                } catch (Exception $e) {}
            }
        }
    }

    // Fallback to authorized clients array
    if (!$isAuthorized) {
        $isAuthorized = in_array($email, array_map('strtolower', $authorizedClients));
    }

    // Also match album client name if matches
    if (!$isAuthorized && !empty($albumTitle)) {
        if (strpos(strtolower($albumTitle), 'king david') !== false && (strpos($email, 'kingdavid') !== false || strpos(strtolower($name), 'david') !== false)) {
            $isAuthorized = true;
        }
    }

    if (!$isAuthorized) {
        echo json_encode([
            'success' => false,
            'is_registered' => false,
            'message' => 'Access Restricted: The email (' . htmlspecialchars($email) . ') is not registered as an authorized client in our system. Only registered clients can download album assets. Please use your client email or contact Falhen Media.'
        ]);
        exit;
    }

    // Client is authorized -> register session and set 30-day cookie
    $_SESSION['registered_client_email'] = $email;
    $_SESSION['registered_client_name'] = $name;
    @setcookie('falhen_client_email', $email, time() + (86400 * 30), '/');

    // Log the download activity
    $logFile = __DIR__ . '/../config/downloads_log.json';
    $logs = file_exists($logFile) ? @json_decode(file_get_contents($logFile), true) : [];
    if (!is_array($logs)) $logs = [];
    $logs[] = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'album_id' => $albumId,
        'album_title' => $albumTitle,
        'time' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    @file_put_contents($logFile, json_encode(array_slice($logs, -200), JSON_PRETTY_PRINT));

    echo json_encode([
        'success' => true,
        'is_registered' => true,
        'message' => 'Client verified successfully! Your download is starting...',
        'email' => $email,
        'name' => $name
    ]);
    exit;
}

if ($action === 'request_code') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
        exit;
    }
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Please provide your full name.']);
        exit;
    }

    // Check Database connection first
    $isAuthorized = false;
    $pdo = getDBConnection();

    if ($pdo) {
        try {
            // Check if clients table exists and query email
            $stmt = $pdo->prepare("SELECT id FROM clients WHERE LOWER(email) = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $isAuthorized = true;
            }
        } catch (Exception $e) {
            // Fallback to authorized list if DB table does not exist
            $isAuthorized = in_array($email, array_map('strtolower', $authorizedClients));
        }
    } else {
        // DB not connected -> check authorized clients array
        $isAuthorized = in_array($email, array_map('strtolower', $authorizedClients));
    }

    if (!$isAuthorized) {
        echo json_encode([
            'success' => false,
            'message' => 'Access Denied: The email address (' . htmlspecialchars($email) . ') is not registered as an authorized client in our database. Only registered clients can download album assets.'
        ]);
        exit;
    }

    // Generate 6-digit verification code
    $code = (string)rand(100000, 999999);
    $_SESSION['download_code'] = $code;
    $_SESSION['download_email'] = $email;
    $_SESSION['download_name'] = $name;

    // Send SMTP Email
    $mailSent = sendVerificationCodeEmail($email, $code, $name);

    echo json_encode([
        'success' => true,
        'email' => $email,
        'mail_sent' => $mailSent,
        'message' => 'A 6-digit verification code has been sent to ' . htmlspecialchars($email)
    ]);
    exit;
}

if ($action === 'verify_code') {
    $enteredCode = trim($_POST['code'] ?? '');
    $savedCode = $_SESSION['download_code'] ?? '';
    $savedEmail = $_SESSION['download_email'] ?? '';

    if (empty($savedCode) || empty($savedEmail)) {
        echo json_encode(['success' => false, 'message' => 'Session expired. Please request a new verification code.']);
        exit;
    }

    if ($email !== $savedEmail) {
        echo json_encode(['success' => false, 'message' => 'Email mismatch. Please request a new verification code.']);
        exit;
    }

    if ($enteredCode !== $savedCode) {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please check your inbox and try again.']);
        exit;
    }

    // Clear session code after successful verification
    unset($_SESSION['download_code']);

    echo json_encode([
        'success' => true,
        'message' => 'Verification successful! Your download is starting...',
        'download_url' => '/assets/img/portfolio/portfolio_wedding.png'
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit;
