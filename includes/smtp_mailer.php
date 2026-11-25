<?php
/**
 * Multi-Provider High-Deliverability SMTP & Resend Mailer Class & Helpers
 * Falhen Productions
 *
 * Transport Priority Order:
 *  1. MXroute Authenticated SMTP (full SPF/DKIM alignment for falhen.com)
 *  2. Resend REST API (HTTPS failover)
 *  3. Configured Socket SMTP (skipped if identical to MXroute host+user)
 *  4. Native PHP mail() fallback (RFC 5322 multipart/alternative)
 */

// Auto-load .env configuration if present
if (function_exists('falhenLoadEnv')) {
    falhenLoadEnv();
} else {
    function falhenLoadEnv($envFile = null) {
        if ($envFile === null) {
            $envFile = dirname(__DIR__) . '/.env';
        }
        if (!file_exists($envFile) || !is_readable($envFile)) {
            return;
        }
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($key, $val) = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);
                if (strlen($val) >= 2 && (
                    ($val[0] === '"' && substr($val, -1) === '"') ||
                    ($val[0] === "'" && substr($val, -1) === "'")
                )) {
                    $val = substr($val, 1, -1);
                }
                if ($key !== '' && !array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV) && getenv($key) === false) {
                    putenv("{$key}={$val}");
                    $_ENV[$key] = $val;
                    $_SERVER[$key] = $val;
                }
            }
        }
    }
    falhenLoadEnv();
}

// Load config/smtp.php seamlessly whether it defines constants OR returns an associative array
$__falhenSmtpConfigPath = __DIR__ . '/../config/smtp.php';
if (file_exists($__falhenSmtpConfigPath)) {
    $__smtpCfg = require_once $__falhenSmtpConfigPath;
    if ($__smtpCfg === true && !defined('SMTP_HOST') && !defined('MXROUTE_SMTP_HOST')) {
        $__smtpCfg = include $__falhenSmtpConfigPath;
    }
    if (is_array($__smtpCfg)) {
        $__smtpConstMap = [
            'smtp_host'           => 'SMTP_HOST',
            'smtp_port'           => 'SMTP_PORT',
            'smtp_user'           => 'SMTP_USER',
            'smtp_pass'           => 'SMTP_PASS',
            'smtp_secure'         => 'SMTP_SECURE',
            'smtp_from_email'     => 'SMTP_FROM_EMAIL',
            'smtp_from_name'      => 'SMTP_FROM_NAME',
            'mail_driver'         => 'MAIL_DRIVER',
            'resend_api_key'      => 'RESEND_API_KEY',
            'mxroute_smtp_host'   => 'MXROUTE_SMTP_HOST',
            'mxroute_smtp_port'   => 'MXROUTE_SMTP_PORT',
            'mxroute_smtp_user'   => 'MXROUTE_SMTP_USER',
            'mxroute_smtp_pass'   => 'MXROUTE_SMTP_PASS',
            'mxroute_smtp_secure' => 'MXROUTE_SMTP_SECURE',
        ];
        foreach ($__smtpConstMap as $__lowerKey => $__constName) {
            if (defined($__constName)) {
                continue;
            }
            $__val = null;
            if (array_key_exists($__constName, $__smtpCfg)) {
                $__val = $__smtpCfg[$__constName];
            } elseif (array_key_exists($__lowerKey, $__smtpCfg)) {
                $__val = $__smtpCfg[$__lowerKey];
            }
            if ($__val !== null && $__val !== '') {
                if ($__constName === 'SMTP_PORT' || $__constName === 'MXROUTE_SMTP_PORT') {
                    define($__constName, (int)$__val);
                } elseif ($__constName === 'SMTP_SECURE' || $__constName === 'MXROUTE_SMTP_SECURE' || $__constName === 'MAIL_DRIVER') {
                    define($__constName, strtolower(trim((string)$__val)));
                } else {
                    define($__constName, (string)$__val);
                }
            }
        }
    }
    unset($__smtpCfg, $__smtpConstMap, $__lowerKey, $__constName, $__val);
}
unset($__falhenSmtpConfigPath);

// Fallback MXroute SMTP constants (SPF/DKIM aligned primary for falhen.com)
if (!defined('MXROUTE_SMTP_HOST'))   define('MXROUTE_SMTP_HOST', getenv('MXROUTE_SMTP_HOST') ?: 'sunfire.mxrouting.net');
if (!defined('MXROUTE_SMTP_PORT'))   define('MXROUTE_SMTP_PORT', (int)(getenv('MXROUTE_SMTP_PORT') ?: 465));
if (!defined('MXROUTE_SMTP_SECURE')) define('MXROUTE_SMTP_SECURE', strtolower(getenv('MXROUTE_SMTP_SECURE') ?: 'ssl'));
if (!defined('MXROUTE_SMTP_USER'))   define('MXROUTE_SMTP_USER', getenv('MXROUTE_SMTP_USER') ?: '');
if (!defined('MXROUTE_SMTP_PASS'))   define('MXROUTE_SMTP_PASS', getenv('MXROUTE_SMTP_PASS') ?: '');

// Fallback generic SMTP / Resend constants if config/smtp.php is omitted or partial
if (!defined('SMTP_HOST'))       define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.resend.com');
if (!defined('SMTP_PORT'))       define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 465));
if (!defined('SMTP_USER'))       define('SMTP_USER', getenv('SMTP_USER') ?: 'resend');
if (!defined('SMTP_PASS'))       define('SMTP_PASS', getenv('SMTP_PASS') ?: (getenv('RESEND_API_KEY') ?: ''));
if (!defined('SMTP_SECURE'))     define('SMTP_SECURE', strtolower(getenv('SMTP_SECURE') ?: 'ssl'));
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'noreply@falhen.com');
if (!defined('SMTP_FROM_NAME'))  define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Falhen Productions');
if (!defined('MAIL_DRIVER'))     define('MAIL_DRIVER', strtolower(getenv('MAIL_DRIVER') ?: 'mxroute'));
if (!defined('RESEND_API_KEY')) {
    $__envPass = getenv('SMTP_PASS') ?: (defined('SMTP_PASS') ? (string)SMTP_PASS : '');
    define('RESEND_API_KEY', getenv('RESEND_API_KEY') ?: (strpos($__envPass, 're_') === 0 ? $__envPass : ''));
    unset($__envPass);
}

class FalhenSMTPMailer {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $secure;
    private $fromEmail;
    private $fromName;
    private $apiKey;
    private $driver;

    private $mxrouteHost;
    private $mxroutePort;
    private $mxrouteUser;
    private $mxroutePass;
    private $mxrouteSecure;

    public function __construct() {
        $this->host          = SMTP_HOST;
        $this->port          = (int)SMTP_PORT;
        $this->user          = SMTP_USER;
        $this->pass          = SMTP_PASS;
        $this->secure        = strtolower(SMTP_SECURE);
        $this->fromEmail     = SMTP_FROM_EMAIL;
        $this->fromName      = SMTP_FROM_NAME;
        $this->apiKey        = RESEND_API_KEY;
        $this->driver        = defined('MAIL_DRIVER') ? strtolower((string)MAIL_DRIVER) : 'mxroute';

        $this->mxrouteHost   = MXROUTE_SMTP_HOST;
        $this->mxroutePort   = (int)MXROUTE_SMTP_PORT;
        $this->mxrouteUser   = MXROUTE_SMTP_USER;
        $this->mxroutePass   = MXROUTE_SMTP_PASS;
        $this->mxrouteSecure = strtolower(MXROUTE_SMTP_SECURE);
    }

    /**
     * Send email with structured delivery diagnostics across fast multi-provider failover.
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML email content
     * @param string $toName Optional recipient display name
     * @param string $textBody Optional plain-text alternative (auto-generated when empty)
     * @param bool   $highPriority Mark transactional/OTP message as high priority
     * @return array ['sent' => bool, 'provider' => string|null, 'error' => string|null, 'duration_ms' => int]
     */
    public function sendMailWithDetails($to, $subject, $htmlBody, $toName = '', $textBody = '', $highPriority = false) {
        $startedAt = microtime(true);
        $to = trim((string)$to);
        $toName = trim(str_replace(["\r", "\n"], '', (string)$toName));
        $subject = trim(str_replace(["\r", "\n"], '', (string)$subject));

        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $err = "Invalid recipient email address: '{$to}'";
            error_log("FalhenSMTPMailer: {$err}");
            $result = [
                'sent'        => false,
                'provider'    => null,
                'error'       => $err,
                'duration_ms' => (int)round((microtime(true) - $startedAt) * 1000)
            ];
            $GLOBALS['falhen_last_mail_result'] = $result;
            return $result;
        }

        if ($textBody === '' || $textBody === null) {
            $textBody = $this->buildPlainTextBody($htmlBody);
        }

        $errors = [];
        $mxrouteAttempted = false;

        // 1. Primary: MXroute Authenticated SMTP (SPF/DKIM aligned for falhen.com = instant external delivery)
        if (!empty($this->mxrouteHost) && !empty($this->mxrouteUser) && !empty($this->mxroutePass)) {
            $mxrouteAttempted = true;
            try {
                $mxConfig = [
                    'host'   => $this->mxrouteHost,
                    'port'   => $this->mxroutePort ?: 465,
                    'user'   => $this->mxrouteUser,
                    'pass'   => $this->mxroutePass,
                    'secure' => $this->mxrouteSecure ?: 'ssl',
                ];
                if ($this->sendViaSocketSmtp($to, $subject, $htmlBody, $toName, $textBody, $highPriority, $mxConfig)) {
                    $result = [
                        'sent'        => true,
                        'provider'    => 'mxroute_smtp',
                        'error'       => null,
                        'duration_ms' => (int)round((microtime(true) - $startedAt) * 1000)
                    ];
                    $GLOBALS['falhen_last_mail_result'] = $result;
                    return $result;
                }
                $errors[] = 'mxroute_smtp: dispatch returned false';
            } catch (Exception $e) {
                $errors[] = 'mxroute_smtp: ' . $e->getMessage();
                error_log("FalhenSMTPMailer MXroute SMTP Error: " . $e->getMessage());
            }
        }

        // 2. Failover: Resend REST API (HTTPS port 443, fast 3s connect / 6s total timeout)
        if (!empty($this->apiKey)) {
            try {
                if ($this->sendViaResendApi($to, $subject, $htmlBody, $toName, $textBody, $highPriority)) {
                    $result = [
                        'sent'        => true,
                        'provider'    => 'resend_api',
                        'error'       => null,
                        'duration_ms' => (int)round((microtime(true) - $startedAt) * 1000)
                    ];
                    $GLOBALS['falhen_last_mail_result'] = $result;
                    return $result;
                }
                $errors[] = 'resend_api: API rejected request';
            } catch (Exception $e) {
                $errors[] = 'resend_api: ' . $e->getMessage();
                error_log("FalhenSMTPMailer Resend API Error: " . $e->getMessage());
            }
        }

        // 3. Secondary: Configured Socket SMTP (skip if identical to already-tried MXroute host+user)
        $isSameAsMxroute = $mxrouteAttempted
            && strtolower(trim((string)$this->host)) === strtolower(trim((string)$this->mxrouteHost))
            && (int)$this->port === (int)$this->mxroutePort
            && strtolower(trim((string)$this->user)) === strtolower(trim((string)$this->mxrouteUser));

        if (!$isSameAsMxroute && !empty($this->host) && !empty($this->port)) {
            // Avoid wasting timeout on Resend SMTP when no password/key is configured
            $isUnconfiguredResendSmtp = (stripos($this->host, 'resend.com') !== false && empty($this->pass));
            if (!$isUnconfiguredResendSmtp) {
                try {
                    if ($this->sendViaSocketSmtp($to, $subject, $htmlBody, $toName, $textBody, $highPriority)) {
                        $result = [
                            'sent'        => true,
                            'provider'    => 'socket_smtp',
                            'error'       => null,
                            'duration_ms' => (int)round((microtime(true) - $startedAt) * 1000)
                        ];
                        $GLOBALS['falhen_last_mail_result'] = $result;
                        return $result;
                    }
                    $errors[] = 'socket_smtp: dispatch returned false';
                } catch (Exception $e) {
                    $errors[] = 'socket_smtp: ' . $e->getMessage();
                    error_log("FalhenSMTPMailer Socket SMTP Error: " . $e->getMessage());
                }
            }
        }

        // 4. Last-resort fallback: Native PHP mail() with RFC 5322 Message-ID & multipart/alternative
        try {
            if ($this->sendViaNativeMail($to, $subject, $htmlBody, $toName, $textBody, $highPriority)) {
                $result = [
                    'sent'        => true,
                    'provider'    => 'native_mail',
                    'error'       => null,
                    'duration_ms' => (int)round((microtime(true) - $startedAt) * 1000)
                ];
                $GLOBALS['falhen_last_mail_result'] = $result;
                return $result;
            }
            $errors[] = 'native_mail: PHP mail() returned false';
        } catch (Exception $e) {
            $errors[] = 'native_mail: ' . $e->getMessage();
            error_log("FalhenSMTPMailer Native mail() Error: " . $e->getMessage());
        }

        $result = [
            'sent'        => false,
            'provider'    => null,
            'error'       => !empty($errors) ? implode(' | ', $errors) : 'All mail transports failed',
            'duration_ms' => (int)round((microtime(true) - $startedAt) * 1000)
        ];
        $GLOBALS['falhen_last_mail_result'] = $result;
        return $result;
    }

    /**
     * Send HTML email using multi-provider transport with automatic failover.
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML content
     * @param string $toName Optional recipient display name
     * @param string $textBody Optional plain-text content
     * @param bool   $highPriority Mark message as high-priority transactional mail
     * @return bool True if mail was accepted by a provider, false otherwise
     */
    public function sendMail($to, $subject, $htmlBody, $toName = '', $textBody = '', $highPriority = false) {
        $res = $this->sendMailWithDetails($to, $subject, $htmlBody, $toName, $textBody, $highPriority);
        $GLOBALS['falhen_last_mail_result'] = $res;
        return (bool)$res['sent'];
    }

    /**
     * Generate a clean plain-text body from HTML (strips style/script blocks first).
     */
    private function buildPlainTextBody($htmlBody) {
        $text = (string)$htmlBody;
        $text = preg_replace('/<(style|script)\b[^>]*>.*?<\/\1>/is', '', $text);
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<\/(p|div|h[1-6]|tr|li|blockquote)>/i', "\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text);
        $text = preg_replace("/\r\n|\r/", "\n", $text);
        $text = preg_replace("/\n[ \t]+/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    /**
     * Generate RFC 5322 compliant unique Message-ID on the sender domain.
     */
    private function generateMessageId() {
        $domain = 'falhen.com';
        if (strpos($this->fromEmail, '@') !== false) {
            $parts = explode('@', $this->fromEmail, 2);
            $candidate = strtolower(trim($parts[1]));
            if ($candidate !== '' && preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $candidate)) {
                $domain = $candidate;
            }
        }
        try {
            $token = bin2hex(random_bytes(12));
        } catch (Exception $e) {
            $token = bin2hex(openssl_random_pseudo_bytes(12) ?: md5(uniqid('', true)));
        }
        return '<' . $token . '.' . time() . '@' . $domain . '>';
    }

    /**
     * Encode RFC 2047 UTF-8 Base64 header value when non-ASCII characters are present.
     */
    private function encodeHeaderValue($value) {
        $clean = str_replace(["\r", "\n"], '', (string)$value);
        if (preg_match('/[^\x20-\x7E]/', $clean)) {
            return '=?UTF-8?B?' . base64_encode($clean) . '?=';
        }
        return $clean;
    }

    /**
     * Format an RFC 5322 email address header with optional display name.
     */
    private function formatAddressHeader($email, $name = '') {
        $cleanEmail = str_replace(["\r", "\n", '<', '>'], '', trim((string)$email));
        $cleanName = str_replace(["\r", "\n"], '', trim((string)$name));
        if ($cleanName === '') {
            return $cleanEmail;
        }
        if (preg_match('/[^\x20-\x7E]/', $cleanName)) {
            return '=?UTF-8?B?' . base64_encode($cleanName) . "?= <{$cleanEmail}>";
        }
        $escapedName = str_replace(['\\', '"'], ['\\\\', '\\"'], $cleanName);
        return "\"{$escapedName}\" <{$cleanEmail}>";
    }

    /**
     * Build RFC 5322 multipart/alternative MIME headers & Base64-encoded parts.
     *
     * @return array ['headers' => string, 'body' => string, 'boundary' => string, 'message_id' => string]
     */
    private function buildMimePayload($to, $subject, $htmlBody, $toName, $textBody, $highPriority, $includeToAndSubject = true) {
        if ($textBody === '' || $textBody === null) {
            $textBody = $this->buildPlainTextBody($htmlBody);
        }

        try {
            $boundary = '=_FalhenAlt_' . bin2hex(random_bytes(12));
        } catch (Exception $e) {
            $boundary = '=_FalhenAlt_' . md5(uniqid('falhen_', true));
        }

        $messageId  = $this->generateMessageId();
        $fromHeader = $this->formatAddressHeader($this->fromEmail, $this->fromName);
        $toHeader   = $this->formatAddressHeader($to, $toName);
        $encodedSub = $this->encodeHeaderValue($subject);

        $headers  = "Date: " . date('r') . "\r\n";
        $headers .= "Message-ID: {$messageId}\r\n";
        $headers .= "From: {$fromHeader}\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        if ($includeToAndSubject) {
            $headers .= "To: {$toHeader}\r\n";
            $headers .= "Subject: {$encodedSub}\r\n";
        }
        $headers .= "MIME-Version: 1.0\r\n";
        if ($highPriority) {
            $headers .= "X-Priority: 1 (Highest)\r\n";
            $headers .= "Importance: high\r\n";
            $headers .= "Auto-Submitted: auto-generated\r\n";
        }
        $headers .= "X-Mailer: Falhen-Productions-Mailer/3.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

        $textB64 = rtrim(chunk_split(base64_encode((string)$textBody), 76, "\r\n"));
        $htmlB64 = rtrim(chunk_split(base64_encode((string)$htmlBody), 76, "\r\n"));

        $body  = "This is a multi-part message in MIME format.\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= $textB64 . "\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= $htmlB64 . "\r\n\r\n";
        $body .= "--{$boundary}--\r\n";

        return [
            'headers'    => $headers,
            'body'       => $body,
            'boundary'   => $boundary,
            'message_id' => $messageId,
            'subject'    => $encodedSub,
        ];
    }

    /**
     * Send email via Resend official REST API (https://api.resend.com/emails)
     */
    public function sendViaResendApi($to, $subject, $htmlBody, $toName = '', $textBody = '', $highPriority = false) {
        $apiKey = $this->apiKey;
        if (empty($apiKey)) {
            return false;
        }

        if ($textBody === '' || $textBody === null) {
            $textBody = $this->buildPlainTextBody($htmlBody);
        }

        $fromAddress = !empty($this->fromName)
            ? "{$this->fromName} <{$this->fromEmail}>"
            : $this->fromEmail;

        $toRecipient = !empty($toName) ? "{$toName} <{$to}>" : $to;

        $customHeaders = [
            'X-Entity-Ref-ID' => uniqid('falhen_', true),
        ];
        if ($highPriority) {
            $customHeaders['X-Priority'] = '1 (Highest)';
            $customHeaders['Importance'] = 'high';
            $customHeaders['Auto-Submitted'] = 'auto-generated';
        }

        $payload = [
            'from'    => $fromAddress,
            'to'      => [$toRecipient],
            'subject' => $subject,
            'html'    => $htmlBody,
            'text'    => $textBody,
            'headers' => $customHeaders,
        ];

        $jsonPayload = json_encode($payload);

        // Prefer cURL with strict TLS verification and fast connect/read timeouts
        if (function_exists('curl_init')) {
            $ch = curl_init('https://api.resend.com/emails');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $jsonPayload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonPayload),
                    'User-Agent: Falhen-Productions-Mailer/3.0'
                ],
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT        => 6,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);

            $rawResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                error_log("FalhenSMTPMailer cURL Error to Resend API: " . $curlErr);
                throw new Exception("cURL error: " . $curlErr);
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                return true;
            }

            error_log("FalhenSMTPMailer Resend API HTTP {$httpCode}: " . $rawResponse);
            throw new Exception("HTTP {$httpCode}: " . substr((string)$rawResponse, 0, 200));
        }

        // Stream context fallback if cURL is disabled
        $opts = [
            'http' => [
                'method'        => 'POST',
                'header'        => "Authorization: Bearer {$apiKey}\r\n" .
                                   "Content-Type: application/json\r\n" .
                                   "Content-Length: " . strlen($jsonPayload) . "\r\n" .
                                   "User-Agent: Falhen-Productions-Mailer/3.0\r\n",
                'content'       => $jsonPayload,
                'timeout'       => 6,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true
            ]
        ];

        $context = stream_context_create($opts);
        $result = @file_get_contents('https://api.resend.com/emails', false, $context);
        $statusLine = $http_response_header[0] ?? '';

        if (preg_match('#HTTP/\S+\s+2\d\d#', $statusLine)) {
            return true;
        }

        error_log("FalhenSMTPMailer file_get_contents Resend API Error: {$statusLine} - {$result}");
        throw new Exception("Stream HTTP error: {$statusLine}");
    }

    /**
     * Send email via direct RFC 5321/5322 Socket SMTP connection (MXroute or configured SMTP host)
     */
    public function sendViaSocketSmtp($to, $subject, $htmlBody, $toName = '', $textBody = '', $highPriority = false, $overrideConfig = null) {
        $host   = is_array($overrideConfig) && !empty($overrideConfig['host']) ? $overrideConfig['host'] : $this->host;
        $port   = is_array($overrideConfig) && !empty($overrideConfig['port']) ? (int)$overrideConfig['port'] : $this->port;
        $user   = is_array($overrideConfig) && array_key_exists('user', $overrideConfig) ? (string)$overrideConfig['user'] : $this->user;
        $pass   = is_array($overrideConfig) && array_key_exists('pass', $overrideConfig) ? (string)$overrideConfig['pass'] : $this->pass;
        $secure = is_array($overrideConfig) && !empty($overrideConfig['secure']) ? strtolower((string)$overrideConfig['secure']) : $this->secure;

        if (empty($host) || empty($port)) {
            return false;
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
                'peer_name'         => $host,
                'SNI_enabled'       => true,
            ]
        ]);

        $isTls = ($secure === 'tls' || $port === 587);
        $hostPrefix = $isTls ? 'tcp://' : 'ssl://';
        $fullHost = $hostPrefix . $host . ':' . $port;

        // Fast 4s connect timeout & 6s stream read timeout
        $socket = @stream_socket_client($fullHost, $errno, $errstr, 4, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            error_log("FalhenSMTPMailer Socket connection failed to {$fullHost}: {$errstr} ({$errno})");
            throw new Exception("Socket connection failed to {$fullHost}: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, 6);

        try {
            // 1. Initial Greeting
            $greeting = $this->getResponse($socket);
            if (strpos($greeting, '220') === false) {
                throw new Exception("Unexpected SMTP banner: " . trim($greeting));
            }

            // 2. EHLO
            $ehloHost = gethostname() ?: 'falhen.com';
            $ehloRes = $this->sendCommand($socket, "EHLO " . $ehloHost);
            if (strpos($ehloRes, '250') === false) {
                throw new Exception("EHLO rejected: " . trim($ehloRes));
            }

            // 3. STARTTLS if TLS mode (port 587)
            if ($isTls) {
                $starttlsRes = $this->sendCommand($socket, "STARTTLS");
                if (strpos($starttlsRes, '220') === false) {
                    throw new Exception("STARTTLS failed: " . trim($starttlsRes));
                }
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                }
                $crypto = @stream_socket_enable_crypto($socket, true, $cryptoMethod);
                if (!$crypto) {
                    throw new Exception("TLS negotiation failed with {$host}");
                }
                $ehloRes = $this->sendCommand($socket, "EHLO " . $ehloHost);
                if (strpos($ehloRes, '250') === false) {
                    throw new Exception("Post-TLS EHLO rejected: " . trim($ehloRes));
                }
            }

            // 4. SMTP AUTH (supports both AUTH LOGIN and AUTH PLAIN cleanly)
            if (!empty($user) && !empty($pass)) {
                $this->authenticateSmtp($socket, $user, $pass, $ehloRes);
            }

            // 5. Envelope Sender (Must be a valid email address, e.g. noreply@falhen.com)
            $envelopeSender = !empty($this->fromEmail) ? $this->fromEmail : 'noreply@falhen.com';
            $mailFromRes = $this->sendCommand($socket, "MAIL FROM:<" . $envelopeSender . ">");
            if (strpos($mailFromRes, '250') === false) {
                throw new Exception("MAIL FROM rejected: " . trim($mailFromRes));
            }

            // 6. Recipient
            $rcptRes = $this->sendCommand($socket, "RCPT TO:<" . $to . ">");
            if (strpos($rcptRes, '250') === false && strpos($rcptRes, '251') === false) {
                throw new Exception("RCPT TO rejected: " . trim($rcptRes));
            }

            // 7. DATA Command
            $dataRes = $this->sendCommand($socket, "DATA");
            if (strpos($dataRes, '354') === false) {
                throw new Exception("DATA command rejected: " . trim($dataRes));
            }

            // 8. RFC 5322 Headers & Base64 multipart/alternative Body Payload
            $mime = $this->buildMimePayload($to, $subject, $htmlBody, $toName, $textBody, $highPriority, true);
            $content = $mime['headers'] . "\r\n" . $mime['body'] . "\r\n.\r\n";

            $written = @fwrite($socket, $content);
            if ($written === false) {
                throw new Exception("Failed writing DATA payload to {$host}");
            }

            $deliveryRes = $this->getResponse($socket);
            $this->sendCommand($socket, "QUIT");
            fclose($socket);
            $socket = null;

            if (strpos($deliveryRes, '250') !== false) {
                return true;
            }

            throw new Exception("SMTP DATA delivery failed: " . trim($deliveryRes));
        } catch (Exception $e) {
            if (is_resource($socket)) {
                @fclose($socket);
            }
            throw $e;
        }
    }

    /**
     * Authenticate over SMTP supporting both AUTH LOGIN and AUTH PLAIN.
     */
    private function authenticateSmtp($socket, $user, $pass, $ehloRes) {
        $supportsPlain = (stripos($ehloRes, 'AUTH') !== false && stripos($ehloRes, 'PLAIN') !== false);
        $supportsLogin = (stripos($ehloRes, 'AUTH') !== false && stripos($ehloRes, 'LOGIN') !== false);

        // If server advertises PLAIN exclusively, use AUTH PLAIN directly
        if ($supportsPlain && !$supportsLogin) {
            $this->authenticatePlain($socket, $user, $pass);
            return;
        }

        // Try AUTH LOGIN first
        $authInit = $this->sendCommand($socket, "AUTH LOGIN");
        if (strpos($authInit, '334') !== false) {
            $userRes = $this->sendCommand($socket, base64_encode($user));
            if (strpos($userRes, '334') === false) {
                throw new Exception("SMTP AUTH LOGIN username rejected: " . trim($userRes));
            }
            $authRes = $this->sendCommand($socket, base64_encode($pass));
            if (strpos($authRes, '235') !== false) {
                return;
            }
            throw new Exception("SMTP AUTH LOGIN failed: " . trim($authRes));
        }

        // Fallback cleanly to AUTH PLAIN if AUTH LOGIN was not accepted
        if ($supportsPlain || strpos($authInit, '504') !== false || strpos($authInit, '500') !== false) {
            $this->authenticatePlain($socket, $user, $pass);
            return;
        }

        throw new Exception("SMTP AUTH rejected: " . trim($authInit));
    }

    /**
     * Perform RFC 4616 SMTP AUTH PLAIN handshake.
     */
    private function authenticatePlain($socket, $user, $pass) {
        $token = base64_encode("\0{$user}\0{$pass}");
        $res = $this->sendCommand($socket, "AUTH PLAIN {$token}");
        if (strpos($res, '334') !== false) {
            $res = $this->sendCommand($socket, $token);
        }
        if (strpos($res, '235') === false) {
            throw new Exception("SMTP AUTH PLAIN failed: " . trim($res));
        }
    }

    /**
     * Fallback to native PHP mail() function with RFC 5322 Message-ID & multipart/alternative
     */
    private function sendViaNativeMail($to, $subject, $htmlBody, $toName = '', $textBody = '', $highPriority = false) {
        $mime = $this->buildMimePayload($to, $subject, $htmlBody, $toName, $textBody, $highPriority, false);
        $safeFrom = preg_replace('/[^a-zA-Z0-9._@+-]/', '', (string)$this->fromEmail);
        $extraParam = !empty($safeFrom) ? "-f" . $safeFrom : '';
        return @mail($to, $mime['subject'], $mime['body'], rtrim($mime['headers'], "\r\n"), $extraParam);
    }

    private function sendCommand($socket, $cmd) {
        @fwrite($socket, $cmd . "\r\n");
        return $this->getResponse($socket);
    }

    private function getResponse($socket) {
        $response = "";
        while (($str = fgets($socket, 512)) !== false) {
            $response .= $str;
            if (isset($str[3]) && $str[3] === " ") {
                break;
            }
        }
        $meta = stream_get_meta_data($socket);
        if (!empty($meta['timed_out'])) {
            throw new Exception("SMTP read timed out");
        }
        return $response;
    }

    /**
     * Diagnostic helper to test the mail configuration
     */
    public function testConnection($testRecipient = null) {
        $results = [
            'driver'            => $this->driver,
            'mxroute_host'      => $this->mxrouteHost,
            'mxroute_port'      => $this->mxroutePort,
            'mxroute_secure'    => $this->mxrouteSecure,
            'has_mxroute_creds' => (!empty($this->mxrouteUser) && !empty($this->mxroutePass)),
            'host'              => $this->host,
            'port'              => $this->port,
            'secure'            => $this->secure,
            'user'              => $this->user,
            'has_api_key'       => !empty($this->apiKey),
            'from'              => "{$this->fromName} <{$this->fromEmail}>",
            'last_result'       => null,
            'overall'           => false
        ];

        if (!empty($testRecipient)) {
            $subject = "Falhen Productions — Mail Transport Connection Test";
            $html = "<h3>Mail Transport Test Successful</h3><p>This is a test notification dispatched from Falhen Productions on " . date('Y-m-d H:i:s') . ".</p>";
            $res = $this->sendMailWithDetails($testRecipient, $subject, $html, "Test Recipient", '', true);
            $results['last_result'] = $res;
            $results['overall'] = (bool)$res['sent'];
        }

        return $results;
    }
}

/**
 * Helper: General Email Dispatcher
 */
function sendFalhenEmail($toEmail, $subject, $htmlBody, $recipientName = '', $textBody = '', $highPriority = false) {
    $mailer = new FalhenSMTPMailer();
    return $mailer->sendMail($toEmail, $subject, $htmlBody, $recipientName, $textBody, $highPriority);
}

/**
 * Send 6-digit Verification Code Email via SMTP / Resend
 * Daylight & Nighttime High-Contrast Editorial Email Template with Falhen Wordmark
 */
function sendVerificationCodeEmail($toEmail, $code, $recipientName = '') {
    $mailer = new FalhenSMTPMailer();
    
    $cleanName = htmlspecialchars(trim($recipientName ?: 'Client'));
    $cleanCode = htmlspecialchars(trim((string)$code));
    $subject = "Your Access Code: {$cleanCode} — Falhen Productions";
    
    $html = '
    <!DOCTYPE html>
    <html lang="en" xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="color-scheme" content="light dark">
        <meta name="supported-color-schemes" content="light dark">
        <title>Your Access Code — Falhen Productions</title>
        <style>
            :root { color-scheme: light dark; supported-color-schemes: light dark; }
            body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
            table, td { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
            img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
            
            /* Nighttime (Dark Mode) Contrast Engine */
            @media (prefers-color-scheme: dark) {
                body, .email-body { background-color: #08090d !important; }
                .email-card { background-color: #11131a !important; border-color: #232736 !important; box-shadow: 0 10px 30px rgba(0,0,0,0.6) !important; }
                .text-headline { color: #ffffff !important; }
                .text-body { color: #cbd5e1 !important; }
                .text-secondary { color: #94a3b8 !important; }
                .code-display { background-color: #171922 !important; border-color: #2e3344 !important; color: #ffffff !important; }
                .wordmark-light { display: none !important; }
                .wordmark-dark { display: block !important; }
                .pill-tag { background-color: rgba(220, 38, 38, 0.16) !important; border-color: rgba(220, 38, 38, 0.35) !important; color: #f87171 !important; }
                .divider { border-top-color: #1e2333 !important; }
            }
            @media only screen and (max-width: 600px) {
                .email-wrapper { padding: 20px 12px !important; }
                .email-card { padding: 28px 20px !important; border-radius: 14px !important; }
                .code-num { font-size: 28px !important; letter-spacing: 6px !important; }
            }
        </style>
    </head>
    <body class="email-body" style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="email-body" style="background-color: #f4f4f5;">
            <tr>
                <td align="center" class="email-wrapper" style="padding: 40px 16px;">
                    <!-- Centered Responsive Card Container -->
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="email-card" style="max-width: 480px; background-color: #ffffff; border: 1px solid #e4e4e7; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,0.05); text-align: center; overflow: hidden;">
                        <tr>
                            <td style="padding: 38px 32px 30px 32px;">
                                <!-- Falhen Responsive Wordmark -->
                                <div style="margin-bottom: 24px;">
                                    <a href="https://falhen.com" target="_blank" style="text-decoration: none; display: inline-block;">
                                        <img src="https://falhen.com/assets/img/icons/wordmark-daylight.png" alt="Falhen Productions" width="135" class="wordmark-light" style="display: block; margin: 0 auto; height: 36px; width: auto; border: 0;">
                                        <!--[if !mso]><!-->
                                        <img src="https://falhen.com/assets/img/icons/wordmark-nighttime.png" alt="Falhen Productions" width="135" class="wordmark-dark" style="display: none; margin: 0 auto; height: 36px; width: auto; border: 0;">
                                        <!--<![endif]-->
                                    </a>
                                </div>

                                <!-- Editorial Category Pill -->
                                <div style="margin-bottom: 14px;">
                                    <span class="pill-tag" style="display: inline-block; font-size: 10.5px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #b91c1c; background-color: #fee2e2; border: 1px solid #fecaca; padding: 3px 12px; border-radius: 20px;">SECURITY CODE</span>
                                </div>

                                <!-- Headline -->
                                <h1 class="text-headline" style="margin: 0 0 10px 0; font-size: 22px; font-weight: 700; color: #09090b; letter-spacing: -0.02em;">Access Verification</h1>

                                <!-- Clear, Focused Prompt -->
                                <p class="text-body" style="margin: 0 0 22px 0; font-size: 14.5px; line-height: 1.6; color: #52525b;">
                                    Hello ' . $cleanName . ', enter the 6-digit access code below on the client portal to authorize your gallery download:
                                </p>

                                <!-- High-Contrast OTP Code Box -->
                                <div class="code-display" style="display: inline-block; background-color: #fafafa; border: 1.5px solid #dc2626; border-radius: 12px; padding: 14px 24px; margin: 0 0 20px 0;">
                                    <span class="code-num" style="font-size: 32px; font-weight: 800; letter-spacing: 8px; color: #09090b; font-family: \'SF Mono\', Monaco, Menlo, Consolas, monospace; display: inline-block; padding-left: 8px;">' . $cleanCode . '</span>
                                </div>

                                <!-- Minimalist Expiry Note -->
                                <p class="text-secondary" style="margin: 0; font-size: 12.5px; line-height: 1.5; color: #71717a;">
                                    Valid for 10 minutes. If you did not request access, no action is needed.
                                </p>

                                <!-- Quiet Editorial Footer -->
                                <div class="divider" style="margin-top: 28px; padding-top: 18px; border-top: 1px solid #f4f4f5;">
                                    <p style="margin: 0; font-size: 11.5px; color: #a1a1aa; line-height: 1.5;">
                                        &copy; ' . date('Y') . ' Falhen Productions &bull; Editorial &amp; Commercial Photography
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';

    $textBody = "Falhen Productions — Gallery Access Code\n\n"
        . "Hello " . ($recipientName ?: 'Client') . ",\n\n"
        . "Enter this 6-digit code to authorize your download:\n\n"
        . "Code: {$code}\n\n"
        . "Valid for 10 minutes. If you did not request access, please disregard.\n\n"
        . "Falhen Productions";

    try {
        $res = $mailer->sendMailWithDetails($toEmail, $subject, $html, $recipientName, $textBody, true);
        $GLOBALS['falhen_last_mail_result'] = $res;
        return (bool)$res['sent'];
    } catch (Exception $e) {
        error_log("Failed to send Verification SMTP/Resend email: " . $e->getMessage());
        $GLOBALS['falhen_last_mail_result'] = ['sent' => false, 'provider' => null, 'error' => $e->getMessage(), 'duration_ms' => 0];
        return false;
    }
}

/**
 * Send 6-digit Staff Account Activation Code Email via SMTP / Resend
 */
function sendStaffActivationEmail($toEmail, $code, $recipientName = '', $staffEmail = '') {
    if (empty($toEmail)) return false;

    $mailer = new FalhenSMTPMailer();
    $subject = "Falhen Portal — Staff Account Activation Code";
    
    $loginEmailSection = '';
    if (!empty($staffEmail)) {
        $loginEmailSection = '
        <div style="background-color: #0d121d; border: 1px solid #1e293b; border-radius: 12px; padding: 14px 18px; margin: 18px 0; text-align: left;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #ef4444; letter-spacing: 1px; margin-bottom: 4px;">Official Login Staff Email</div>
            <div style="font-size: 15px; font-weight: 700; color: #ffffff; font-family: monospace; word-break: break-all;">' . htmlspecialchars($staffEmail) . '</div>
        </div>';
    }

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #080b11; color: #f8fafc; margin: 0; padding: 40px 20px; }
            .email-card { max-width: 520px; margin: 0 auto; background-color: #111723; border: 1px solid #1e293b; border-radius: 16px; padding: 36px 32px; text-align: center; }
            .brand-logo { margin-bottom: 24px; }
            h2 { color: #ffffff; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 12px; }
            p { color: #94a3b8; font-size: 15px; line-height: 1.6; margin: 0 0 16px 0; }
            .code-box { display: inline-block; background-color: #0d121d; border: 2px solid #ef4444; color: #ffffff; font-size: 34px; font-weight: 700; letter-spacing: 8px; padding: 18px 32px; border-radius: 14px; margin: 20px 0; box-shadow: 0 4px 20px rgba(239, 68, 68, 0.25); }
            .notice-box { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 10px; padding: 12px; font-size: 13px; color: #fca5a5; margin-top: 20px; }
            .footer { font-size: 12px; color: #64748b; margin-top: 32px; border-top: 1px solid #1e293b; padding-top: 16px; }
        </style>
    </head>
    <body>
        <div class="email-card">
            <div class="brand-logo">
                <img src="https://falhen.com/assets/img/icons/logo.png" alt="Falhen Productions" style="height: 36px;">
            </div>
            <h2>Account Activation Code</h2>
            <p>Hello ' . htmlspecialchars($recipientName ?: 'Team Member') . ',</p>
            <p>Your Falhen Staff Portal account has been created. Use your assigned staff email address and the 6-digit activation code below to activate your account:</p>
            ' . $loginEmailSection . '
            <div class="code-box">' . htmlspecialchars($code) . '</div>
            <div style="margin: 24px 0 20px 0;">
                <a href="https://falhen.com/admin/login.php?action=activate&user=' . urlencode($staffEmail) . '&code=' . urlencode($code) . '" style="background-color: #ef4444; color: #ffffff; text-decoration: none; padding: 16px 32px; border-radius: 12px; font-weight: 700; font-size: 15px; display: inline-block; box-shadow: 0 4px 16px rgba(239, 68, 68, 0.4); text-transform: uppercase; letter-spacing: 0.5px;">
                    🚀 Click Here to Activate Your Account &rarr;
                </a>
            </div>
            <p style="font-size: 13px; color: #94a3b8; margin-top: 10px;">Or visit <a href="https://falhen.com/admin/login.php?action=activate&user=' . urlencode($staffEmail) . '&code=' . urlencode($code) . '" style="color: #ef4444; font-weight: 700; text-decoration: underline;">falhen.com/admin/login.php</a> to verify your 6-digit code.</p>
            <div class="notice-box">
                <strong>Security Notice:</strong> Keep this activation code confidential. Do not share it with anyone outside Falhen Productions.
            </div>
            <div class="footer">
                &copy; ' . date('Y') . ' Falhen Productions Ltd. All rights reserved.
            </div>
        </div>
    </body>
    </html>';

    $activateUrl = "https://falhen.com/admin/login.php?action=activate&user=" . urlencode($staffEmail) . "&code=" . urlencode($code);
    $textBody = "Falhen Portal — Staff Account Activation Code\n\n"
        . "Hello " . ($recipientName ?: 'Team Member') . ",\n\n"
        . "Your Falhen Staff Portal account has been created.\n"
        . (!empty($staffEmail) ? "Official Login Staff Email: {$staffEmail}\n" : "")
        . "6-Digit Activation Code: {$code}\n\n"
        . "Activate your account here:\n{$activateUrl}\n\n"
        . "Security Notice: Keep this activation code confidential. Do not share it with anyone outside Falhen Productions.\n\n"
        . "Falhen Productions Ltd.";

    try {
        $res = $mailer->sendMailWithDetails($toEmail, $subject, $html, $recipientName, $textBody, true);
        $GLOBALS['falhen_last_mail_result'] = $res;
        return (bool)$res['sent'];
    } catch (Exception $e) {
        error_log("Failed to send Staff Activation SMTP/Resend email: " . $e->getMessage());
        $GLOBALS['falhen_last_mail_result'] = ['sent' => false, 'provider' => null, 'error' => $e->getMessage(), 'duration_ms' => 0];
        return false;
    }
}

/**
 * Send 6-digit Staff Password / Username Reset OTP Email via SMTP / Resend
 * Sent to the registered alternative email domain (alt_email) provided strictly for OTP verification.
 */
function sendStaffPasswordResetOtpEmail($toAltEmail, $code, $recipientName = '', $staffUsername = '', $staffEmail = '') {
    if (empty($toAltEmail)) return false;

    $mailer = new FalhenSMTPMailer();
    $subject = "Falhen Staff Security — Password & Username Reset Code (OTP)";

    $accountSection = '';
    if (!empty($staffEmail) || !empty($staffUsername)) {
        $accountSection = '
        <div style="background-color: #0d121d; border: 1px solid #1e293b; border-radius: 12px; padding: 14px 18px; margin: 18px 0; text-align: left;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #ef4444; letter-spacing: 1px; margin-bottom: 6px;">Target Staff Account Credentials</div>
            ' . (!empty($staffUsername) ? '<div style="font-size: 14px; color: #94a3b8; margin-bottom: 4px;">Staff Username: <strong style="color: #ffffff; font-family: monospace;">' . htmlspecialchars($staffUsername) . '</strong></div>' : '') . '
            ' . (!empty($staffEmail) ? '<div style="font-size: 14px; color: #94a3b8;">Official Falhen Domain: <strong style="color: #ffffff; font-family: monospace;">' . htmlspecialchars($staffEmail) . '</strong></div>' : '') . '
        </div>';
    }

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #080b11; color: #f8fafc; margin: 0; padding: 40px 20px; }
            .email-card { max-width: 520px; margin: 0 auto; background-color: #111723; border: 1px solid #1e293b; border-radius: 16px; padding: 36px 32px; text-align: center; }
            .brand-logo { margin-bottom: 24px; }
            h2 { color: #ffffff; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 12px; }
            p { color: #94a3b8; font-size: 15px; line-height: 1.6; margin: 0 0 16px 0; }
            .code-box { display: inline-block; background-color: #0d121d; border: 2px solid #ef4444; color: #ffffff; font-size: 34px; font-weight: 700; letter-spacing: 8px; padding: 18px 32px; border-radius: 14px; margin: 20px 0; box-shadow: 0 4px 20px rgba(239, 68, 68, 0.25); }
            .notice-box { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 10px; padding: 12px; font-size: 13px; color: #fca5a5; margin-top: 20px; text-align: left; }
            .footer { font-size: 12px; color: #64748b; margin-top: 32px; border-top: 1px solid #1e293b; padding-top: 16px; }
        </style>
    </head>
    <body>
        <div class="email-card">
            <div class="brand-logo">
                <img src="https://falhen.com/assets/img/icons/logo.png" alt="Falhen Productions" style="height: 36px;">
            </div>
            <h2>Password &amp; Username Reset OTP</h2>
            <p>Hello ' . htmlspecialchars($recipientName ?: 'Staff Member') . ',</p>
            <p>A request was received to reset the username and password for your Falhen Staff Portal account. This one-time password has been dispatched to your designated recovery email address:</p>
            ' . $accountSection . '
            <div class="code-box">' . htmlspecialchars($code) . '</div>
            <p style="font-size: 14px; color: #94a3b8; margin-top: 10px;">
                Enter this 6-digit code on the reset page to authorize and update your username and password. This code expires in <strong>15 minutes</strong>.
            </p>
            <div class="notice-box">
                <strong>Important Security Notice:</strong> This alternative email address is designated strictly for security OTP verifications. If you did not initiate this request, your account credentials remain unchanged. Notify the system administrator immediately.
            </div>
            <div class="footer">
                &copy; ' . date('Y') . ' Falhen Productions Ltd. All rights reserved.
            </div>
        </div>
    </body>
    </html>';

    $textBody = "Falhen Staff Security — Password & Username Reset Code (OTP)\n\n"
        . "Hello " . ($recipientName ?: 'Staff Member') . ",\n\n"
        . "A request was received to reset the username and password for your Falhen Staff Portal account.\n"
        . (!empty($staffUsername) ? "Staff Username: {$staffUsername}\n" : "")
        . (!empty($staffEmail) ? "Official Falhen Email: {$staffEmail}\n" : "")
        . "\nYour 6-Digit Reset OTP Code: {$code}\n\n"
        . "Enter this code on the reset page to authorize and update your username and password. This code expires in 15 minutes.\n\n"
        . "If you did not initiate this request, your credentials remain unchanged. Notify the system administrator immediately.\n\n"
        . "Falhen Productions Ltd.";

    try {
        $res = $mailer->sendMailWithDetails($toAltEmail, $subject, $html, $recipientName, $textBody, true);
        $GLOBALS['falhen_last_mail_result'] = $res;
        return (bool)$res['sent'];
    } catch (Exception $e) {
        error_log("Failed to send Staff Password Reset OTP SMTP/Resend email: " . $e->getMessage());
        $GLOBALS['falhen_last_mail_result'] = ['sent' => false, 'provider' => null, 'error' => $e->getMessage(), 'duration_ms' => 0];
        return false;
    }
}

/**
 * Dispatch Salary Payout Notification Email to Employee
 */
function sendSalaryPayoutNotificationEmail($toEmail, $recipientName, $payPeriod, $netPay, $txRef = '') {
    if (empty($toEmail)) return false;

    $mailer = new FalhenSMTPMailer();
    $subject = "Falhen Productions — Salary Payout Advice (" . htmlspecialchars($payPeriod) . ")";

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #080b11; color: #f8fafc; margin: 0; padding: 40px 20px; }
            .email-card { max-width: 540px; margin: 0 auto; background-color: #111723; border: 1px solid #1e293b; border-radius: 16px; padding: 36px 32px; text-align: center; }
            .brand-logo { margin-bottom: 24px; }
            h2 { color: #ffffff; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 12px; }
            p { color: #94a3b8; font-size: 15px; line-height: 1.6; margin: 0 0 16px 0; }
            .payout-box { background-color: #0d121d; border: 2px solid #22c55e; color: #22c55e; font-size: 32px; font-weight: 700; padding: 18px 24px; border-radius: 14px; margin: 20px 0; box-shadow: 0 4px 20px rgba(34, 197, 94, 0.2); }
            .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; text-align: left; }
            .details-table td { padding: 10px 14px; border-bottom: 1px solid #1e293b; color: #cbd5e1; }
            .details-table td strong { color: #ffffff; }
            .footer { font-size: 12px; color: #64748b; margin-top: 32px; border-top: 1px solid #1e293b; padding-top: 16px; }
        </style>
    </head>
    <body>
        <div class="email-card">
            <div class="brand-logo">
                <img src="https://falhen.com/assets/img/icons/logo.png" alt="Falhen Productions" style="height: 36px;">
            </div>
            <h2>Salary Payment Advice</h2>
            <p>Dear ' . htmlspecialchars($recipientName ?: 'Team Member') . ',</p>
            <p>Your monthly remuneration for <strong>' . htmlspecialchars($payPeriod) . '</strong> has been processed successfully. Below is your payment disbursement advice:</p>
            
            <div class="payout-box">' . htmlspecialchars($netPay) . '</div>

            <table class="details-table">
                <tr>
                    <td>Pay Period:</td>
                    <td style="text-align: right;"><strong>' . htmlspecialchars($payPeriod) . '</strong></td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td style="text-align: right;"><strong style="color: #22c55e;">Paid / Processed</strong></td>
                </tr>
                ' . (!empty($txRef) ? '<tr><td>Transaction Ref:</td><td style="text-align: right;"><strong>' . htmlspecialchars($txRef) . '</strong></td></tr>' : '') . '
            </table>

            <div style="margin: 24px 0 16px 0;">
                <a href="https://falhen.com/admin/index.php?section=payslips" style="background-color: #dc2626; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 14px; display: inline-block; box-shadow: 0 4px 16px rgba(220, 38, 38, 0.35);">
                    📄 View & Download Payslip Stub &rarr;
                </a>
            </div>

            <div class="footer">
                &copy; ' . date('Y') . ' Falhen Productions Ltd. All rights reserved.
            </div>
        </div>
    </body>
    </html>';

    try {
        return $mailer->sendMail($toEmail, $subject, $html, $recipientName);
    } catch (Exception $e) {
        error_log("Failed to send Salary Payout SMTP/Resend email: " . $e->getMessage());
        return false;
    }
}

/**
 * Dispatch Vendor / Contractor Onboarding Confirmation Email via SMTP / Resend
 */
function sendVendorOnboardingConfirmationEmail($toEmail, $recipientName, $roleLabel, $refCode, $downloadUrl = '') {
    if (empty($toEmail)) return false;

    $mailer = new FalhenSMTPMailer();
    $subject = "Welcome to Falhen Productions — Onboarding Recorded (Ref: {$refCode})";

    if (empty($downloadUrl)) {
        $downloadUrl = "https://falhen.com/download_vendor_agreement.php?ref=" . urlencode($refCode);
    } elseif (strpos($downloadUrl, 'http') !== 0) {
        $downloadUrl = 'https://falhen.com' . (strpos($downloadUrl, '/') === 0 ? '' : '/') . $downloadUrl;
    }

    $portalUrl = "https://falhen.com/admin/login.php?view=vendor";

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #080b11; color: #f8fafc; margin: 0; padding: 40px 20px; }
            .email-card { max-width: 540px; margin: 0 auto; background-color: #111723; border: 1px solid #1e293b; border-radius: 16px; padding: 36px 32px; text-align: center; }
            .brand-logo { margin-bottom: 24px; }
            h2 { color: #ffffff; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 12px; }
            p { color: #94a3b8; font-size: 15px; line-height: 1.6; margin: 0 0 16px 0; }
            .ref-box { background-color: #0d121d; border: 2px solid #10b981; color: #ffffff; font-size: 26px; font-weight: 700; letter-spacing: 4px; padding: 16px 24px; border-radius: 14px; margin: 20px 0; box-shadow: 0 4px 20px rgba(16, 185, 129, 0.2); font-family: monospace; }
            .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; text-align: left; }
            .details-table td { padding: 10px 14px; border-bottom: 1px solid #1e293b; color: #cbd5e1; }
            .details-table td strong { color: #ffffff; }
            .btn-primary { background-color: #dc2626; color: #ffffff !important; text-decoration: none; padding: 14px 28px; border-radius: 10px; font-weight: 700; font-size: 14px; display: inline-block; box-shadow: 0 4px 16px rgba(220, 38, 38, 0.35); margin: 6px; }
            .btn-secondary { background-color: #0f172a; color: #10b981 !important; border: 1px solid #10b981; text-decoration: none; padding: 13px 26px; border-radius: 10px; font-weight: 700; font-size: 14px; display: inline-block; margin: 6px; }
            .notice-box { background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 10px; padding: 14px; font-size: 13px; color: #a7f3d0; margin-top: 24px; text-align: left; line-height: 1.5; }
            .footer { font-size: 12px; color: #64748b; margin-top: 32px; border-top: 1px solid #1e293b; padding-top: 16px; }
        </style>
    </head>
    <body>
        <div class="email-card">
            <div class="brand-logo">
                <img src="https://falhen.com/assets/img/icons/logo.png" alt="Falhen Productions" style="height: 38px;">
            </div>
            <h2>Onboarding Complete!</h2>
            <p>Hello <strong>' . htmlspecialchars($recipientName ?: 'Team Member') . '</strong>,</p>
            <p>Welcome to the Falhen Productions team! Your contractor/talent profile, workplace policy acceptance, and signed legal agreements (Master Services Agreement &amp; Non-Disclosure Agreement) have been officially registered.</p>
            
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #10b981; letter-spacing: 1px; margin-top: 16px;">Your Official Reference Code</div>
            <div class="ref-box">' . htmlspecialchars($refCode) . '</div>

            <table class="details-table">
                <tr>
                    <td>Vendor / Talent Name:</td>
                    <td style="text-align: right;"><strong>' . htmlspecialchars($recipientName) . '</strong></td>
                </tr>
                <tr>
                    <td>Engagement Role:</td>
                    <td style="text-align: right;"><strong>' . htmlspecialchars($roleLabel) . '</strong></td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td style="text-align: right;"><strong style="color: #10b981;">&bull; Verified &amp; Active</strong></td>
                </tr>
            </table>

            <div style="margin: 24px 0 16px 0;">
                <a href="' . htmlspecialchars($downloadUrl) . '" class="btn-primary">
                    📄 Download Signed Agreement (PDF) &rarr;
                </a>
                <br>
                <a href="' . htmlspecialchars($portalUrl) . '" class="btn-secondary">
                    🔐 Sign In to Vendor Portal
                </a>
            </div>

            <div class="notice-box">
                <strong>💡 Portal Sign-In Information:</strong><br>
                You can access the Vendor Portal anytime at <a href="' . htmlspecialchars($portalUrl) . '" style="color: #34d399; font-weight: 700;">falhen.com/admin/login.php?view=vendor</a> using your email address and your Reference Code (<strong>' . htmlspecialchars($refCode) . '</strong>) to view upcoming shoot schedules, call sheets, and engagement updates.
            </div>

            <div class="footer">
                &copy; ' . date('Y') . ' Falhen Productions Ltd. All rights reserved.
            </div>
        </div>
    </body>
    </html>';

    try {
        return $mailer->sendMail($toEmail, $subject, $html, $recipientName);
    } catch (Exception $e) {
        error_log("Failed to send Vendor Onboarding Confirmation email: " . $e->getMessage());
        return false;
    }
}

/**
 * Send High-Resolution Client Gallery Download Delivery Email
 * Daylight & Nighttime High-Contrast Editorial Email Template with Falhen Wordmark
 */
function sendClientGalleryDownloadDeliveryEmail($toEmail, $recipientName, $albumTitle, $downloadUrl, $photoCount = 0, $coverUrl = '', $accessTier = 'authorized_client') {
    if (empty($toEmail)) return false;

    $mailer = new FalhenSMTPMailer();
    $cleanName = htmlspecialchars(trim($recipientName ?: 'Client'));
    $cleanTitle = htmlspecialchars(trim($albumTitle ?: 'Client Gallery'));
    $fullDlUrl = htmlspecialchars($downloadUrl);
    $subject = "Your Photos: " . strip_tags($albumTitle) . " — Falhen Productions";

    $coverMarkup = '';
    if (!empty($coverUrl)) {
        $coverMarkup = '<div class="cover-card" style="margin: 18px 0 16px 0; border-radius: 12px; overflow: hidden; max-height: 280px; border: 1px solid #e4e4e7; box-shadow: 0 4px 16px rgba(0,0,0,0.04);"><img src="' . htmlspecialchars($coverUrl) . '" alt="' . $cleanTitle . '" style="width: 100%; height: auto; display: block; object-fit: cover;"></div>';
    }

    $countBadge = ($photoCount > 0) ? (int)$photoCount . ' High-Resolution Photos' : 'High-Resolution Collection';

    $html = '
    <!DOCTYPE html>
    <html lang="en" xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="color-scheme" content="light dark">
        <meta name="supported-color-schemes" content="light dark">
        <title>Your Photo Delivery — Falhen Productions</title>
        <style>
            :root { color-scheme: light dark; supported-color-schemes: light dark; }
            body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
            table, td { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
            img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }

            /* Nighttime (Dark Mode) Contrast Engine */
            @media (prefers-color-scheme: dark) {
                body, .email-body { background-color: #08090d !important; }
                .email-card { background-color: #11131a !important; border-color: #232736 !important; box-shadow: 0 10px 30px rgba(0,0,0,0.6) !important; }
                .text-headline { color: #ffffff !important; }
                .text-body { color: #cbd5e1 !important; }
                .text-secondary { color: #94a3b8 !important; }
                .cover-card { border-color: #2e3344 !important; }
                .wordmark-light { display: none !important; }
                .wordmark-dark { display: block !important; }
                .pill-tag { background-color: rgba(220, 38, 38, 0.16) !important; border-color: rgba(220, 38, 38, 0.35) !important; color: #f87171 !important; }
                .divider { border-top-color: #1e2333 !important; }
                .link-subtle { color: #f87171 !important; }
            }
            @media only screen and (max-width: 600px) {
                .email-wrapper { padding: 20px 12px !important; }
                .email-card { padding: 28px 20px !important; border-radius: 14px !important; }
                .btn-action { display: block !important; width: 100% !important; box-sizing: border-box !important; padding: 16px 20px !important; }
            }
        </style>
    </head>
    <body class="email-body" style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="email-body" style="background-color: #f4f4f5;">
            <tr>
                <td align="center" class="email-wrapper" style="padding: 40px 16px;">
                    <!-- Centered Responsive Card Container -->
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="email-card" style="max-width: 520px; background-color: #ffffff; border: 1px solid #e4e4e7; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,0.05); text-align: center; overflow: hidden;">
                        <tr>
                            <td style="padding: 38px 32px 30px 32px;">
                                <!-- Falhen Responsive Wordmark -->
                                <div style="margin-bottom: 24px;">
                                    <a href="https://falhen.com" target="_blank" style="text-decoration: none; display: inline-block;">
                                        <img src="https://falhen.com/assets/img/icons/wordmark-daylight.png" alt="Falhen Productions" width="135" class="wordmark-light" style="display: block; margin: 0 auto; height: 36px; width: auto; border: 0;">
                                        <!--[if !mso]><!-->
                                        <img src="https://falhen.com/assets/img/icons/wordmark-nighttime.png" alt="Falhen Productions" width="135" class="wordmark-dark" style="display: none; margin: 0 auto; height: 36px; width: auto; border: 0;">
                                        <!--<![endif]-->
                                    </a>
                                </div>

                                <!-- Category Pill -->
                                <div style="margin-bottom: 14px;">
                                    <span class="pill-tag" style="display: inline-block; font-size: 10.5px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #b91c1c; background-color: #fee2e2; border: 1px solid #fecaca; padding: 3px 12px; border-radius: 20px;">DIGITAL DELIVERY</span>
                                </div>

                                <!-- Headline -->
                                <h1 class="text-headline" style="margin: 0 0 10px 0; font-size: 22px; font-weight: 700; color: #09090b; letter-spacing: -0.02em;">Your Gallery is Ready</h1>

                                <!-- Intro Copy -->
                                <p class="text-body" style="margin: 0 0 18px 0; font-size: 14.5px; line-height: 1.6; color: #52525b;">
                                    Hello ' . $cleanName . ', your high-resolution collection for <strong>' . $cleanTitle . '</strong> is ready for download:
                                </p>

                                <!-- Gallery Hero Image -->
                                ' . $coverMarkup . '

                                <!-- Metadata Bar -->
                                <div style="margin: 12px 0 22px 0;">
                                    <span class="text-headline" style="font-size: 14px; font-weight: 700; color: #09090b;">' . $cleanTitle . '</span>
                                    <span class="text-secondary" style="font-size: 13px; color: #71717a; margin-left: 6px;">&bull; ' . $countBadge . '</span>
                                </div>

                                <!-- Primary Download CTA Button -->
                                <div style="margin: 0 0 18px 0;">
                                    <a href="' . $fullDlUrl . '" class="btn-action" style="background-color: #dc2626; color: #ffffff !important; text-decoration: none; padding: 15px 36px; border-radius: 12px; font-weight: 700; font-size: 15px; display: inline-block; letter-spacing: 0.3px; box-shadow: 0 4px 16px rgba(220, 38, 38, 0.28);">
                                        &darr; Download Full Gallery (.ZIP)
                                    </a>
                                </div>

                                <!-- Clean, Discreet Fallback Link -->
                                <p class="text-secondary" style="margin: 0; font-size: 12px; color: #71717a;">
                                    Having trouble with the button? <a href="' . $fullDlUrl . '" class="link-subtle" style="color: #dc2626; text-decoration: underline;">Open direct link</a>
                                </p>

                                <!-- Divider & Quiet Studio Footer -->
                                <div class="divider" style="margin-top: 28px; padding-top: 18px; border-top: 1px solid #f4f4f5;">
                                    <p style="margin: 0; font-size: 11.5px; color: #a1a1aa; line-height: 1.5;">
                                        &copy; ' . date('Y') . ' Falhen Productions &bull; Editorial &amp; Commercial Photography
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';

    $textBody = "Falhen Productions — Photo Delivery\n\n"
        . "Hello " . ($recipientName ?: 'Client') . ",\n\n"
        . "Your full-resolution collection for '{$albumTitle}' is ready for download:\n\n"
        . "Download Link: {$downloadUrl}\n\n"
        . "Falhen Productions · Editorial & Commercial Photography";

    try {
        return $mailer->sendMail($toEmail, $subject, $html, $recipientName, $textBody);
    } catch (Exception $e) {
        error_log("Failed to send Client Download Delivery email: " . $e->getMessage());
        return false;
    }
}


