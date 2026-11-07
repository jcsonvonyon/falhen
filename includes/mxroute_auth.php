<?php
/**
 * Falhen Media - MXroute Protocol Authentication & Domain Gating Engine
 * 
 * Implements RFC-compliant IMAP SSL protocol authentication against MXroute mail nodes
 * (e.g. sunfire.mxrouting.net:993) with zero-dependency socket fallback and strict
 * corporate domain gating for @falhen.com and @falhenmedia.com accounts.
 */

if (!defined('FALHEN_ALLOWED_DOMAINS')) {
    define('FALHEN_ALLOWED_DOMAINS', ['falhen.com', 'falhenmedia.com']);
}

/**
 * Validate that an email address belongs strictly to authorized Falhen corporate domains.
 *
 * @param string $email
 * @return bool
 */
function isFalhenDomainEmail($email) {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $parts = explode('@', strtolower(trim($email)));
    if (count($parts) !== 2) {
        return false;
    }
    $domain = $parts[1];
    return in_array($domain, FALHEN_ALLOWED_DOMAINS, true);
}

/**
 * Extract email domain
 *
 * @param string $email
 * @return string
 */
function getEmailDomain($email) {
    $parts = explode('@', strtolower(trim($email)));
    return (count($parts) === 2) ? $parts[1] : '';
}

/**
 * Authenticate credentials against MXroute mail infrastructure using IMAP over SSL (Port 993).
 * Supports native ext-imap if available, with resilient fallback to raw SSL stream sockets.
 *
 * @param string $email
 * @param string $password
 * @param string $host
 * @param int $port
 * @param int $timeoutSeconds
 * @return array ['success' => bool, 'error' => string|null, 'protocol' => string|null]
 */
function authenticateViaMXrouteProtocol($email, $password, $host = 'sunfire.mxrouting.net', $port = 993, $timeoutSeconds = 5) {
    $cleanEmail = strtolower(trim($email));
    
    // 1. Strict Domain Gating Check
    if (!isFalhenDomainEmail($cleanEmail)) {
        return [
            'success' => false,
            'error' => 'Domain access restricted. Only authorized @falhen.com and @falhenmedia.com mail accounts can authenticate via MXroute protocol.',
            'protocol' => null
        ];
    }

    if (empty($password)) {
        return [
            'success' => false,
            'error' => 'Password cannot be empty.',
            'protocol' => null
        ];
    }

    // 2. Primary: Native PHP IMAP extension (if compiled)
    if (function_exists('imap_open')) {
        $mailbox = "{" . $host . ":" . $port . "/imap/ssl/novalidate-cert}INBOX";
        // Suppress warnings as bad passwords trigger PHP warnings
        $mbox = @imap_open($mailbox, $cleanEmail, $password, OP_HALFOPEN, 1, [
            'DISABLE_AUTHENTICATOR' => 'GSSAPI'
        ]);

        if ($mbox) {
            @imap_close($mbox);
            return [
                'success' => true,
                'error' => null,
                'protocol' => 'imap_extension'
            ];
        }
    }

    // 3. Resilient Secondary: Direct RFC 3501 IMAP SSL stream socket client
    try {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $remoteSocket = "ssl://{$host}:{$port}";
        $socket = @stream_socket_client(
            $remoteSocket,
            $errno,
            $errstr,
            $timeoutSeconds,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            return [
                'success' => false,
                'error' => "MXroute socket connection failed ({$errno}): {$errstr}",
                'protocol' => 'socket_connect_error'
            ];
        }

        stream_set_timeout($socket, $timeoutSeconds);

        // Read server greeting: "* OK ..."
        $greeting = fgets($socket, 1024);
        if (!$greeting || stripos($greeting, '* OK') === false) {
            @fclose($socket);
            return [
                'success' => false,
                'error' => 'Invalid MXroute IMAP greeting banner: ' . trim((string)$greeting),
                'protocol' => 'socket_bad_greeting'
            ];
        }

        // Quote and escape user & password for IMAP command
        $imapUser = str_replace(['\\', '"'], ['\\\\', '\\"'], $cleanEmail);
        $imapPass = str_replace(['\\', '"'], ['\\\\', '\\"'], $password);
        
        $loginTag = 'A0001';
        $loginCmd = "{$loginTag} LOGIN \"{$imapUser}\" \"{$imapPass}\"\r\n";
        
        fwrite($socket, $loginCmd);

        $authSuccess = false;
        $responseDetails = '';

        // Read lines until tag response
        while (!feof($socket)) {
            $line = fgets($socket, 1024);
            if ($line === false) break;
            
            $trimmedLine = trim($line);
            $responseDetails .= $trimmedLine . ' ';

            if (strpos($trimmedLine, "{$loginTag} OK") === 0) {
                $authSuccess = true;
                break;
            } elseif (strpos($trimmedLine, "{$loginTag} NO") === 0 || strpos($trimmedLine, "{$loginTag} BAD") === 0) {
                $authSuccess = false;
                break;
            }
        }

        // Clean logout
        @fwrite($socket, "A0002 LOGOUT\r\n");
        @fclose($socket);

        if ($authSuccess) {
            return [
                'success' => true,
                'error' => null,
                'protocol' => 'ssl_socket'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Invalid MXroute mailbox credentials: ' . trim($responseDetails),
                'protocol' => 'socket_auth_rejected'
            ];
        }

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'MXroute protocol exception: ' . $e->getMessage(),
            'protocol' => 'socket_exception'
        ];
    }
}
