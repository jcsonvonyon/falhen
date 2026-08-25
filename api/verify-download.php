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
