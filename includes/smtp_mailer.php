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
     * Send HTML email using socket SMTP connection
     */
    public function sendMail($to, $subject, $htmlBody, $toName = '') {
        $prefix = ($this->secure === 'ssl') ? 'ssl://' : '';
        $hostWithPrefix = $prefix . $this->host;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $socket = @stream_socket_client($hostWithPrefix . ':' . $this->port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            error_log("SMTP Connect Error: $errstr ($errno)");
            return false;
        }

        $this->getResponse($socket);

        $this->sendCommand($socket, "EHLO " . gethostname());
        
        if ($this->secure === 'tls') {
            $this->sendCommand($socket, "STARTTLS");
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->sendCommand($socket, "EHLO " . gethostname());
        }

        // Authenticate
        $this->sendCommand($socket, "AUTH LOGIN");
        $this->sendCommand($socket, base64_encode($this->user));
        $this->sendCommand($socket, base64_encode($this->pass));

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
        $this->getResponse($socket);

        $this->sendCommand($socket, "QUIT");
        fclose($socket);

        return true;
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
