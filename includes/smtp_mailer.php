<?php
/**
 * Socket-based SMTP Mailer Class & Helper
 * Falhen Media
 */

require_once __DIR__ . '/../config/smtp.php';

class FalhenSMTPMailer {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $secure;

    public function __construct() {
        $this->host = SMTP_HOST;
        $this->port = SMTP_PORT;
        $this->user = SMTP_USER;
        $this->pass = SMTP_PASS;
        $this->secure = SMTP_SECURE;
    }

    /**
     * Send HTML email using socket SMTP connection with automatic PHP mail() fallback
     */
    public function sendMail($to, $subject, $htmlBody, $toName = '') {
        $smtpSuccess = false;

        try {
            $prefix = ($this->secure === 'ssl') ? 'ssl://' : '';
            $hostWithPrefix = $prefix . $this->host;

            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

            $socket = @stream_socket_client($hostWithPrefix . ':' . $this->port, $errno, $errstr, 6, STREAM_CLIENT_CONNECT, $context);

            if ($socket) {
                $this->getResponse($socket);
                $this->sendCommand($socket, "EHLO " . gethostname());
                
                if ($this->secure === 'tls') {
                    $this->sendCommand($socket, "STARTTLS");
                    @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                    $this->sendCommand($socket, "EHLO " . gethostname());
                }

                // Authenticate
                $this->sendCommand($socket, "AUTH LOGIN");
                $this->sendCommand($socket, base64_encode($this->user));
                $authRes = $this->sendCommand($socket, base64_encode($this->pass));

                // Mail transaction
                $this->sendCommand($socket, "MAIL FROM: <" . $this->user . ">");
                $this->sendCommand($socket, "RCPT TO: <" . $to . ">");
                $this->sendCommand($socket, "DATA");

                // Headers & Content
                $headers  = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: " . SMTP_FROM_NAME . " <" . $this->user . ">\r\n";
                $headers .= "To: " . ($toName ? "$toName <$to>" : $to) . "\r\n";
                $headers .= "Subject: " . $subject . "\r\n";
                $headers .= "Date: " . date("r") . "\r\n";
                $headers .= "X-Mailer: FalhenMediaSMTP/1.0\r\n";

                $content = $headers . "\r\n" . $htmlBody . "\r\n.\r\n";
                fwrite($socket, $content);
                $dataRes = $this->getResponse($socket);

                $this->sendCommand($socket, "QUIT");
                fclose($socket);

                if (strpos($dataRes, '250') !== false || strpos($authRes, '235') !== false) {
                    $smtpSuccess = true;
                }
            }
        } catch (Exception $e) {
            error_log("Socket SMTP failed: " . $e->getMessage());
        }

        if ($smtpSuccess) {
            return true;
        }

        // Fallback to PHP native mail() with full HTML & From headers
        $nativeHeaders  = "MIME-Version: 1.0\r\n";
        $nativeHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
        $nativeHeaders .= "From: " . SMTP_FROM_NAME . " <" . $this->user . ">\r\n";
        $nativeHeaders .= "Reply-To: " . $this->user . "\r\n";
        $nativeHeaders .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        return @mail($to, $subject, $htmlBody, $nativeHeaders, "-f" . $this->user);
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
}

/**
 * Send 6-digit Verification Code Email via SMTP
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
            h2 { color: #ffffff; font-size: 22px; font-weight: 800; margin-top: 0; }
            p { color: #a1a1aa; font-size: 15px; line-height: 1.6; }
            .code-box { display: inline-block; background-color: #18181b; border: 1px solid #dc2626; color: #ffffff; font-size: 32px; font-weight: 800; letter-spacing: 6px; padding: 16px 28px; border-radius: 12px; margin: 24px 0; }
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
        error_log("Failed to send SMTP email: " . $e->getMessage());
        return false;
    }
}

/**
 * Send 6-digit Staff Account Activation Code Email via SMTP
 */
function sendStaffActivationEmail($toEmail, $code, $recipientName = '', $staffEmail = '') {
    if (empty($toEmail)) return false;

    $mailer = new FalhenSMTPMailer();
    $subject = "Falhen Portal — Staff Account Activation Code";
    
    $loginEmailSection = '';
    if (!empty($staffEmail)) {
        $loginEmailSection = '
        <div style="background-color: #0d121d; border: 1px solid #1e293b; border-radius: 12px; padding: 14px 18px; margin: 18px 0; text-align: left;">
            <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #ef4444; letter-spacing: 1px; margin-bottom: 4px;">Official Login Staff Email</div>
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
            h2 { color: #ffffff; font-size: 22px; font-weight: 800; margin-top: 0; margin-bottom: 12px; }
            p { color: #94a3b8; font-size: 15px; line-height: 1.6; margin: 0 0 16px 0; }
            .code-box { display: inline-block; background-color: #0d121d; border: 2px solid #ef4444; color: #ffffff; font-size: 34px; font-weight: 800; letter-spacing: 8px; padding: 18px 32px; border-radius: 14px; margin: 20px 0; box-shadow: 0 4px 20px rgba(239, 68, 68, 0.25); }
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
                <a href="https://falhen.com/admin/login.php?action=activate&user=' . urlencode($staffEmail) . '&code=' . urlencode($code) . '" style="background-color: #ef4444; color: #ffffff; text-decoration: none; padding: 16px 32px; border-radius: 12px; font-weight: 800; font-size: 15px; display: inline-block; box-shadow: 0 4px 16px rgba(239, 68, 68, 0.4); text-transform: uppercase; letter-spacing: 0.5px;">
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
        error_log("Failed to send Staff Activation SMTP email: " . $e->getMessage());
        return false;
    }
}
