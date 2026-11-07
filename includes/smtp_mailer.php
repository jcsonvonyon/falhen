<?php
/**
 * Resend & Socket-based SMTP Mailer Class & Helpers
 * Falhen Media
 */

require_once __DIR__ . '/../config/smtp.php';

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

    public function __construct() {
        $this->host      = SMTP_HOST;
        $this->port      = (int)SMTP_PORT;
        $this->user      = SMTP_USER;
        $this->pass      = SMTP_PASS;
        $this->secure    = strtolower(SMTP_SECURE);
        $this->fromEmail = SMTP_FROM_EMAIL;
        $this->fromName  = SMTP_FROM_NAME;
        $this->apiKey    = RESEND_API_KEY;
        $this->driver    = defined('MAIL_DRIVER') ? MAIL_DRIVER : 'resend';
    }

    /**
     * Send HTML email using Resend API / Socket SMTP with automatic fallback
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML content
     * @param string $toName Optional recipient display name
     * @return bool True if mail was accepted by provider, false otherwise
     */
    public function sendMail($to, $subject, $htmlBody, $toName = '') {
        $to = trim($to);
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("FalhenSMTPMailer: Invalid recipient email address: '{$to}'");
            return false;
        }

        // 1. If Resend Driver and API Key available, attempt Resend REST API (fastest & most reliable)
        if ($this->driver === 'resend' && !empty($this->apiKey)) {
            try {
                if ($this->sendViaResendApi($to, $subject, $htmlBody, $toName)) {
                    return true;
                }
            } catch (Exception $e) {
                error_log("FalhenSMTPMailer Resend API Error: " . $e->getMessage());
            }
        }

        // 2. Attempt Socket-based SMTP (Resend SMTP or custom host)
        try {
            if ($this->sendViaSocketSmtp($to, $subject, $htmlBody, $toName)) {
                return true;
            }
        } catch (Exception $e) {
            error_log("FalhenSMTPMailer Socket SMTP Error: " . $e->getMessage());
        }

        // 3. Fallback to PHP native mail() with full HTML & From headers
        return $this->sendViaNativeMail($to, $subject, $htmlBody, $toName);
    }

    /**
     * Send email via Resend official REST API (https://api.resend.com/emails)
     */
    public function sendViaResendApi($to, $subject, $htmlBody, $toName = '') {
        $apiKey = $this->apiKey;
        if (empty($apiKey)) {
            return false;
        }

        $fromAddress = !empty($this->fromName) 
            ? "{$this->fromName} <{$this->fromEmail}>" 
            : $this->fromEmail;

        $toRecipient = !empty($toName) ? "{$toName} <{$to}>" : $to;

        $payload = [
            'from'    => $fromAddress,
            'to'      => [$toRecipient],
            'subject' => $subject,
            'html'    => $htmlBody,
            'headers' => [
                'X-Entity-Ref-ID' => uniqid('falhen_', true)
            ]
        ];

        $jsonPayload = json_encode($payload);

        // Prefer cURL for performance and SSL management
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
                    'User-Agent: Falhen-Media-Resend/2.0'
                ],
                CURLOPT_TIMEOUT        => 12,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);

            $rawResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                error_log("FalhenSMTPMailer cURL Error to Resend API: " . $curlErr);
                return false;
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                return true;
            }

            error_log("FalhenSMTPMailer Resend API HTTP {$httpCode}: " . $rawResponse);
            return false;
        }

        // Stream context fallback if cURL is disabled
        $opts = [
            'http' => [
                'method'        => 'POST',
                'header'        => "Authorization: Bearer {$apiKey}\r\n" .
                                   "Content-Type: application/json\r\n" .
                                   "Content-Length: " . strlen($jsonPayload) . "\r\n" .
                                   "User-Agent: Falhen-Media-Resend/2.0\r\n",
                'content'       => $jsonPayload,
                'timeout'       => 12,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false
            ]
        ];

        $context = stream_context_create($opts);
        $result = @file_get_contents('https://api.resend.com/emails', false, $context);
        $statusLine = $http_response_header[0] ?? '';

        if (preg_match('#HTTP/\S+\s+2\d\d#', $statusLine)) {
            return true;
        }

        error_log("FalhenSMTPMailer file_get_contents Resend API Error: {$statusLine} - {$result}");
        return false;
    }

    /**
     * Send email via direct Socket SMTP connection (Resend SMTP: smtp.resend.com)
     */
    public function sendViaSocketSmtp($to, $subject, $htmlBody, $toName = '') {
        if (empty($this->host) || empty($this->port)) {
            return false;
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ]);

        $isTls = ($this->secure === 'tls' || $this->port === 587);
        $hostPrefix = $isTls ? 'tcp://' : 'ssl://';
        $fullHost = $hostPrefix . $this->host . ':' . $this->port;

        $socket = @stream_socket_client($fullHost, $errno, $errstr, 8, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            error_log("FalhenSMTPMailer Socket connection failed to {$fullHost}: {$errstr} ({$errno})");
            return false;
        }

        stream_set_timeout($socket, 10);

        // 1. Initial Greeting
        $greeting = $this->getResponse($socket);
        if (strpos($greeting, '220') === false) {
            fclose($socket);
            throw new Exception("Unexpected SMTP banner: " . trim($greeting));
        }

        // 2. EHLO
        $ehloHost = gethostname() ?: 'falhen.com';
        $this->sendCommand($socket, "EHLO " . $ehloHost);

        // 3. STARTTLS if TLS mode
        if ($isTls) {
            $starttlsRes = $this->sendCommand($socket, "STARTTLS");
            if (strpos($starttlsRes, '220') === false) {
                fclose($socket);
                throw new Exception("STARTTLS failed: " . trim($starttlsRes));
            }
            $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$crypto) {
                fclose($socket);
                throw new Exception("TLS negotiation failed with {$this->host}");
            }
            $this->sendCommand($socket, "EHLO " . $ehloHost);
        }

        // 4. AUTH LOGIN (Username = 'resend', Password = Resend API Key)
        if (!empty($this->user) && !empty($this->pass)) {
            $authInit = $this->sendCommand($socket, "AUTH LOGIN");
            if (strpos($authInit, '334') === false) {
                fclose($socket);
                throw new Exception("AUTH LOGIN rejected: " . trim($authInit));
            }

            $this->sendCommand($socket, base64_encode($this->user));
            $authRes = $this->sendCommand($socket, base64_encode($this->pass));
            if (strpos($authRes, '235') === false) {
                fclose($socket);
                throw new Exception("SMTP Authentication failed: " . trim($authRes));
            }
        }

        // 5. Envelope Sender (Must be an email address, e.g. noreply@falhen.com, NOT 'resend')
        $envelopeSender = !empty($this->fromEmail) ? $this->fromEmail : 'noreply@falhen.com';
        $mailFromRes = $this->sendCommand($socket, "MAIL FROM: <" . $envelopeSender . ">");
        if (strpos($mailFromRes, '250') === false) {
            fclose($socket);
            throw new Exception("MAIL FROM rejected: " . trim($mailFromRes));
        }

        // 6. Recipient
        $rcptRes = $this->sendCommand($socket, "RCPT TO: <" . $to . ">");
        if (strpos($rcptRes, '250') === false && strpos($rcptRes, '251') === false) {
            fclose($socket);
            throw new Exception("RCPT TO rejected: " . trim($rcptRes));
        }

        // 7. DATA Command
        $dataRes = $this->sendCommand($socket, "DATA");
        if (strpos($dataRes, '354') === false) {
            fclose($socket);
            throw new Exception("DATA command rejected: " . trim($dataRes));
        }

        // 8. Headers & Body Payload
        $fromHeader = !empty($this->fromName) 
            ? "{$this->fromName} <{$this->fromEmail}>" 
            : $this->fromEmail;
        $toHeader = !empty($toName) ? "{$toName} <{$to}>" : $to;

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromHeader}\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "To: {$toHeader}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "Date: " . date("r") . "\r\n";
        $headers .= "X-Mailer: FalhenMedia-Resend/2.0\r\n";

        $content = $headers . "\r\n" . $htmlBody . "\r\n.\r\n";
        fwrite($socket, $content);
        $deliveryRes = $this->getResponse($socket);

        $this->sendCommand($socket, "QUIT");
        fclose($socket);

        if (strpos($deliveryRes, '250') !== false) {
            return true;
        }

        throw new Exception("SMTP DATA delivery failed: " . trim($deliveryRes));
    }

    /**
     * Fallback to native PHP mail() function
     */
    private function sendViaNativeMail($to, $subject, $htmlBody, $toName = '') {
        $fromHeader = !empty($this->fromName) 
            ? "{$this->fromName} <{$this->fromEmail}>" 
            : $this->fromEmail;

        $nativeHeaders  = "MIME-Version: 1.0\r\n";
        $nativeHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
        $nativeHeaders .= "From: {$fromHeader}\r\n";
        $nativeHeaders .= "Reply-To: {$this->fromEmail}\r\n";
        $nativeHeaders .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        $extraParam = !empty($this->fromEmail) ? "-f" . $this->fromEmail : '';
        return @mail($to, $subject, $htmlBody, $nativeHeaders, $extraParam);
    }

    private function sendCommand($socket, $cmd) {
        fwrite($socket, $cmd . "\r\n");
        return $this->getResponse($socket);
    }

    private function getResponse($socket) {
        $response = "";
        while ($str = fgets($socket, 512)) {
            $response .= $str;
            if (isset($str[3]) && $str[3] === " ") break;
        }
        return $response;
    }

    /**
     * Diagnostic helper to test the mail configuration
     */
    public function testConnection($testRecipient = null) {
        $results = [
            'driver'     => $this->driver,
            'host'       => $this->host,
            'port'       => $this->port,
            'secure'     => $this->secure,
            'user'       => $this->user,
            'has_api_key'=> !empty($this->apiKey),
            'from'       => "{$this->fromName} <{$this->fromEmail}>",
            'api_test'   => null,
            'smtp_test'  => null,
            'overall'    => false
        ];

        if (!empty($testRecipient)) {
            $subject = "Falhen Media — Resend SMTP Connection Test";
            $html = "<h3>Resend Mail Test Successful</h3><p>This is a test notification dispatched from Falhen Media on " . date('Y-m-d H:i:s') . ".</p>";
            $results['overall'] = $this->sendMail($testRecipient, $subject, $html, "Test Recipient");
        }

        return $results;
    }
}

/**
 * Helper: General Email Dispatcher
 */
function sendFalhenEmail($toEmail, $subject, $htmlBody, $recipientName = '') {
    $mailer = new FalhenSMTPMailer();
    return $mailer->sendMail($toEmail, $subject, $htmlBody, $recipientName);
}

/**
 * Send 6-digit Verification Code Email via SMTP / Resend
 */
function sendVerificationCodeEmail($toEmail, $code, $recipientName = '') {
    $mailer = new FalhenSMTPMailer();
    
    $subject = "Your Download Verification Code — Falhen Media";
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background-color: #030305; color: #d4d4d8; margin: 0; padding: 40px 20px; }
            .email-card { max-width: 520px; margin: 0 auto; background-color: #0e0e12; border: 1px solid #27272a; border-radius: 16px; padding: 32px; text-align: center; }
            h2 { color: #ffffff; font-size: 22px; font-weight: 700; margin-top: 0; }
            p { color: #a1a1aa; font-size: 15px; line-height: 1.6; }
            .code-box { display: inline-block; background-color: #18181b; border: 1px solid #dc2626; color: #ffffff; font-size: 32px; font-weight: 700; letter-spacing: 6px; padding: 16px 28px; border-radius: 12px; margin: 24px 0; }
            .footer { font-size: 12px; color: #71717a; margin-top: 32px; border-top: 1px solid #27272a; padding-top: 16px; }
        </style>
    </head>
    <body>
        <div class="email-card">
            <h2>Download Verification Code</h2>
            <p>Hello ' . htmlspecialchars($recipientName ?: 'Valued Client') . ',</p>
            <p>Use the verification code below to authorize your album asset download:</p>
            <div class="code-box">' . htmlspecialchars($code) . '</div>
            <p>This code will expire in 10 minutes. If you did not request this download, please ignore this email.</p>
            <div class="footer">
                &copy; ' . date('Y') . ' Falhen Media. All rights reserved.
            </div>
        </div>
    </body>
    </html>';

    try {
        return $mailer->sendMail($toEmail, $subject, $html, $recipientName);
    } catch (Exception $e) {
        error_log("Failed to send Verification SMTP/Resend email: " . $e->getMessage());
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
                <img src="https://falhen.com/assets/img/icons/logo.png" alt="Falhen Media" style="height: 36px;">
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
                <strong>Security Notice:</strong> Keep this activation code confidential. Do not share it with anyone outside Falhen Media.
            </div>
            <div class="footer">
                &copy; ' . date('Y') . ' Falhen Media Ltd. All rights reserved.
            </div>
        </div>
    </body>
    </html>';

    try {
        return $mailer->sendMail($toEmail, $subject, $html, $recipientName);
    } catch (Exception $e) {
        error_log("Failed to send Staff Activation SMTP/Resend email: " . $e->getMessage());
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
                <img src="https://falhen.com/assets/img/icons/logo.png" alt="Falhen Media" style="height: 36px;">
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
                &copy; ' . date('Y') . ' Falhen Media Ltd. All rights reserved.
            </div>
        </div>
    </body>
    </html>';

    try {
        return $mailer->sendMail($toAltEmail, $subject, $html, $recipientName);
    } catch (Exception $e) {
        error_log("Failed to send Staff Password Reset OTP SMTP/Resend email: " . $e->getMessage());
        return false;
    }
}

/**
 * Dispatch Salary Payout Notification Email to Employee
 */
function sendSalaryPayoutNotificationEmail($toEmail, $recipientName, $payPeriod, $netPay, $txRef = '') {
    if (empty($toEmail)) return false;

    $mailer = new FalhenSMTPMailer();
    $subject = "Falhen Media — Salary Payout Advice (" . htmlspecialchars($payPeriod) . ")";

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
                <img src="https://falhen.com/assets/img/icons/logo.png" alt="Falhen Media" style="height: 36px;">
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
                &copy; ' . date('Y') . ' Falhen Media Ltd. All rights reserved.
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
    $subject = "Welcome to Falhen Media — Onboarding Recorded (Ref: {$refCode})";

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
                <img src="https://falhen.com/assets/img/icons/logo.png" alt="Falhen Media" style="height: 38px;">
            </div>
            <h2>Onboarding Complete!</h2>
            <p>Hello <strong>' . htmlspecialchars($recipientName ?: 'Team Member') . '</strong>,</p>
            <p>Welcome to the Falhen Media team! Your contractor/talent profile, workplace policy acceptance, and signed legal agreements (Master Services Agreement &amp; Non-Disclosure Agreement) have been officially registered.</p>
            
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
                &copy; ' . date('Y') . ' Falhen Media Ltd. All rights reserved.
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

