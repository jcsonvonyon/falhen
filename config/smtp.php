<?php
/**
 * SMTP & Resend Mailer Configuration
 * Falhen Media
 */

// Auto-load .env configuration if present
if (!function_exists('falhenLoadEnv')) {
    function falhenLoadEnv($envFile = null) {
        if ($envFile === null) {
            $envFile = dirname(__DIR__) . '/.env';
        }
        if (!file_exists($envFile) || !is_readable($envFile)) {
            return;
        }
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($key, $val) = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);
                // Strip outer double or single quotes
                if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                    (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                    $val = substr($val, 1, -1);
                }
                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV) && getenv($key) === false) {
                    putenv("{$key}={$val}");
                    $_ENV[$key] = $val;
                    $_SERVER[$key] = $val;
                }
            }
        }
    }
}
falhenLoadEnv();

// Resend API Key (used for direct Resend REST API or as Resend SMTP password)
$resendApiKey = getenv('RESEND_API_KEY');
if (empty($resendApiKey) && str_starts_with(getenv('SMTP_PASS') ?: '', 're_')) {
    $resendApiKey = getenv('SMTP_PASS');
}
define('RESEND_API_KEY', $resendApiKey ?: '');

/**
 * Resend SMTP Configuration
 * Resend Host: smtp.resend.com
 * Resend Port: 465 (SSL) or 587 (TLS)
 * Resend User: 'resend' (fixed requirement by Resend)
 * Resend Pass: Your Resend API Key (starts with re_)
 */
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.resend.com');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 465));
define('SMTP_USER', getenv('SMTP_USER') ?: 'resend');
define('SMTP_PASS', getenv('SMTP_PASS') ?: (RESEND_API_KEY ?: ''));
define('SMTP_SECURE', strtolower(getenv('SMTP_SECURE') ?: 'ssl')); // 'ssl' (port 465) or 'tls' (port 587)

// Sender Identity
// Note: Domain must be verified on resend.com, or use onboarding@resend.dev for initial sandbox tests
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'noreply@falhen.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Falhen Media');

// Mail Driver Preference:
// 'resend' = Attempts Resend REST API first (fastest, immune to blocked SMTP ports), falls back to Socket SMTP, then PHP mail()
// 'smtp'   = Directly connects via Socket SMTP (smtp.resend.com) with PHP mail() fallback
define('MAIL_DRIVER', strtolower(getenv('MAIL_DRIVER') ?: 'resend'));
