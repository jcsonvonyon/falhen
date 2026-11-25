<?php
/**
 * Falhen Productions - MXroute Protocol Authentication & Domain Gating Engine
 *
 * Implements RFC-compliant IMAP SSL protocol authentication against MXroute mail nodes
 * (sunfire.mxrouting.net:993 with failover to mail.falhen.com:993) with zero-dependency
 * SSL stream socket fallback and strict corporate @falhen.com domain & format gating.
 */

if (!defined('FALHEN_ALLOWED_DOMAINS')) {
    define('FALHEN_ALLOWED_DOMAINS', ['falhen.com']);
}

/**
 * Validate that an email address belongs strictly to authorized @falhen.com domain.
 *
 * @param string $email
 * @return bool
 */
if (!function_exists('isFalhenDomainEmail')) {
    function isFalhenDomainEmail($email) {
        $cleanEmail = strtolower(trim((string) $email));
        if ($cleanEmail === '' || !filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $parts = explode('@', $cleanEmail);
        if (count($parts) !== 2) {
            return false;
        }
        $domain = $parts[1];
        return in_array($domain, FALHEN_ALLOWED_DOMAINS, true);
    }
}

/**
 * Check if an email address is one of Henry Falonipe's authorized multi-mail corporate aliases.
 *
 * @param string $email
 * @return bool
 */
if (!function_exists('isHenryMultiMail')) {
    function isHenryMultiMail($email) {
        $cleanEmail = strtolower(trim((string) $email));
        if ($cleanEmail === '') {
            return false;
        }
        static $henryMultiMails = [
            'henry.falonipe@falhen.com',
            'henry@falhen.com',
            'admin@falhen.com',
            'info@falhen.com',
        ];
        return in_array($cleanEmail, $henryMultiMails, true);
    }
}

/**
 * Validate strict corporate Falhen email format:
 * Must be a valid @falhen.com email AND either an authorized Henry Falonipe multi-mail
 * address or strictly formatted as firstname.lastname@falhen.com.
 *
 * @param string $email
 * @return bool
 */
if (!function_exists('isValidCorporateFalhenEmail')) {
    function isValidCorporateFalhenEmail($email) {
        $cleanEmail = strtolower(trim((string) $email));
        if (!isFalhenDomainEmail($cleanEmail)) {
            return false;
        }
        $parts = explode('@', $cleanEmail);
        if (count($parts) !== 2 || $parts[1] !== 'falhen.com') {
            return false;
        }
        if (isHenryMultiMail($cleanEmail)) {
            return true;
        }
        return preg_match('/^[a-z][a-z0-9_-]*\.[a-z][a-z0-9_-]*(?:\.[a-z0-9_-]+)*$/', $parts[0]) === 1;
    }
}

/**
 * Extract email domain.
 *
 * @param string $email
 * @return string
 */
if (!function_exists('getEmailDomain')) {
    function getEmailDomain($email) {
        $parts = explode('@', strtolower(trim((string) $email)));
        return (count($parts) === 2) ? $parts[1] : '';
    }
}

/**
 * Authenticate credentials against MXroute mail infrastructure using IMAP over SSL (Port 993).
 * Supports ordered host failover, native ext-imap (when available), and resilient RFC 3501
 * raw SSL stream socket fallback. Stops immediately on explicit authentication rejection
 * (NO/BAD) without retrying invalid credentials on secondary hosts.
 *
 * @param string $email
 * @param string $password
 * @param string $host
 * @param int $port
 * @param int $timeoutSeconds
 * @return array ['success' => bool, 'error' => string|null, 'protocol' => string|null, 'host' => string|null]
 */
if (!function_exists('authenticateViaMXrouteProtocol')) {
    function authenticateViaMXrouteProtocol($email, $password, $host = 'sunfire.mxrouting.net', $port = 993, $timeoutSeconds = 4) {
        $cleanEmail = strtolower(trim((string) $email));
        $port = (int) $port > 0 ? (int) $port : 993;
        $timeoutSeconds = max(1, (int) $timeoutSeconds);

        // 1. Strict Corporate Domain & Format Gating Check
        if (!isFalhenDomainEmail($cleanEmail)) {
            return [
                'success'  => false,
                'error'    => 'Domain access restricted. Only authorized @falhen.com mail accounts can authenticate via MXroute protocol.',
                'protocol' => null,
                'host'     => null,
            ];
        }

        if (!isValidCorporateFalhenEmail($cleanEmail)) {
            return [
                'success'  => false,
                'error'    => 'Invalid corporate email format. Staff login requires a firstname.lastname@falhen.com email address.',
                'protocol' => null,
                'host'     => null,
            ];
        }

        if ($password === '' || $password === null) {
            return [
                'success'  => false,
                'error'    => 'Password cannot be empty.',
                'protocol' => null,
                'host'     => null,
            ];
        }

        $hostsToTry = array_values(array_unique(array_filter([
            trim((string) $host),
            'sunfire.mxrouting.net',
            'mail.falhen.com',
        ])));

        $lastError = 'Unable to reach MXroute mail server.';
        $lastProtocol = 'socket_connect_error';
        $lastHost = $hostsToTry[0] ?? null;

        foreach ($hostsToTry as $h) {
            $lastHost = $h;
            // Enforce strict CA-signed TLS verification for sunfire.mxrouting.net (and other MXroute nodes);
            // keep novalidate fallback ONLY for mail.falhen.com alias where cert CN may be sunfire.mxrouting.net.
            $verifyTlsCert = (strtolower($h) !== 'mail.falhen.com');

            // 2. Primary: Native PHP IMAP extension (if compiled)
            if (function_exists('imap_open')) {
                if (function_exists('imap_timeout')) {
                    @imap_timeout(IMAP_OPENTIMEOUT, $timeoutSeconds);
                    @imap_timeout(IMAP_READTIMEOUT, $timeoutSeconds);
                    @imap_timeout(IMAP_WRITETIMEOUT, $timeoutSeconds);
                }

                $imapSslFlags = $verifyTlsCert ? '/imap/ssl' : '/imap/ssl/novalidate-cert';
                $mailbox = '{' . $h . ':' . $port . $imapSslFlags . '}INBOX';
                $mbox = @imap_open($mailbox, $cleanEmail, $password, OP_HALFOPEN, 1, [
                    'DISABLE_AUTHENTICATOR' => 'GSSAPI',
                ]);

                if ($mbox) {
                    @imap_close($mbox);
                    if (function_exists('imap_errors')) {
                        @imap_errors();
                    }
                    if (function_exists('imap_alerts')) {
                        @imap_alerts();
                    }
                    return [
                        'success'  => true,
                        'error'    => null,
                        'protocol' => 'imap_extension',
                        'host'     => $h,
                    ];
                }

                $imapErrors = function_exists('imap_errors') ? (array) @imap_errors() : [];
                if (function_exists('imap_alerts')) {
                    @imap_alerts();
                }
                $joinedImapErr = implode(' ', array_filter($imapErrors));
                if ($joinedImapErr !== '' && preg_match('/(AUTHENTICATIONFAILED|LOGIN\s+failed|Invalid\s+credentials|Authentication\s+failed|Too\s+many\s+login\s+failures|NO\b|BAD\b)/i', $joinedImapErr)) {
                    return [
                        'success'  => false,
                        'error'    => 'Invalid MXroute mailbox credentials.',
                        'protocol' => 'imap_auth_rejected',
                        'host'     => $h,
                    ];
                }
            }

            // 3. Resilient Direct RFC 3501 IMAP SSL Stream Socket Client
            $socket = null;
            try {
                $sslOptions = $verifyTlsCert
                    ? [
                        'verify_peer'       => true,
                        'verify_peer_name'  => true,
                        'allow_self_signed' => false,
                        'peer_name'         => $h,
                    ]
                    : [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true,
                    ];
                $context = stream_context_create([
                    'ssl' => $sslOptions,
                ]);

                $errno = 0;
                $errstr = '';
                $remoteSocket = "ssl://{$h}:{$port}";
                $socket = @stream_socket_client(
                    $remoteSocket,
                    $errno,
                    $errstr,
                    $timeoutSeconds,
                    STREAM_CLIENT_CONNECT,
                    $context
                );

                if (!$socket) {
                    $lastError = "MXroute socket connection failed on {$h} ({$errno}): {$errstr}";
                    $lastProtocol = 'socket_connect_error';
                    continue;
                }

                stream_set_timeout($socket, $timeoutSeconds);

                // Read server greeting: "* OK ..."
                $greeting = fgets($socket, 1024);
                if (!$greeting || stripos($greeting, '* OK') === false) {
                    @fclose($socket);
                    $socket = null;
                    $lastError = 'Invalid MXroute IMAP greeting banner on ' . $h . ': ' . trim((string) $greeting);
                    $lastProtocol = 'socket_bad_greeting';
                    continue;
                }

                // Quote and escape user & password for IMAP LOGIN command (strip CR/LF to prevent command injection)
                $safePass = str_replace(["\r", "\n"], '', (string) $password);
                $imapUser = str_replace(['\\', '"'], ['\\\\', '\\"'], $cleanEmail);
                $imapPass = str_replace(['\\', '"'], ['\\\\', '\\"'], $safePass);

                $loginTag = 'A0001';
                $loginCmd = "{$loginTag} LOGIN \"{$imapUser}\" \"{$imapPass}\"\r\n";

                $written = @fwrite($socket, $loginCmd);
                if ($written === false) {
                    @fclose($socket);
                    $socket = null;
                    $lastError = "Failed writing IMAP LOGIN command to {$h}.";
                    $lastProtocol = 'socket_write_error';
                    continue;
                }

                $authSuccess = false;
                $authRejected = false;
                $responseDetails = '';

                // Read lines until tagged response
                while (!feof($socket)) {
                    $line = fgets($socket, 1024);
                    if ($line === false) {
                        break;
                    }

                    $trimmedLine = trim($line);
                    $responseDetails .= $trimmedLine . ' ';

                    if (stripos($trimmedLine, "{$loginTag} OK") === 0) {
                        $authSuccess = true;
                        break;
                    } elseif (stripos($trimmedLine, "{$loginTag} NO") === 0 || stripos($trimmedLine, "{$loginTag} BAD") === 0) {
                        $authRejected = true;
                        break;
                    }

                    $meta = stream_get_meta_data($socket);
                    if (!empty($meta['timed_out'])) {
                        break;
                    }
                }

                // Clean logout
                @fwrite($socket, "A0002 LOGOUT\r\n");
                @fclose($socket);
                $socket = null;

                if ($authSuccess) {
                    return [
                        'success'  => true,
                        'error'    => null,
                        'protocol' => 'ssl_socket',
                        'host'     => $h,
                    ];
                }

                if ($authRejected) {
                    // Explicit auth rejection: stop immediately, do NOT retry password on next host
                    return [
                        'success'  => false,
                        'error'    => 'Invalid MXroute mailbox credentials: ' . trim($responseDetails),
                        'protocol' => 'socket_auth_rejected',
                        'host'     => $h,
                    ];
                }

                // Timed out or dropped connection before tagged response: fail over to next host
                $lastError = "MXroute IMAP response incomplete or timed out on {$h}.";
                $lastProtocol = 'socket_timeout';
            } catch (Exception $e) {
                if (is_resource($socket)) {
                    @fclose($socket);
                }
                $lastError = 'MXroute protocol exception on ' . $h . ': ' . $e->getMessage();
                $lastProtocol = 'socket_exception';
                continue;
            }
        }

        return [
            'success'  => false,
            'error'    => $lastError,
            'protocol' => $lastProtocol,
            'host'     => $lastHost,
        ];
    }
}
