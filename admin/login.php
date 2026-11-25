<?php
/**
 * Staff Login & Multi-Portal Access
 * Falhen Productions Administration
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/smtp_mailer.php';
require_once __DIR__ . '/../includes/mxroute_auth.php';

// Handle logout action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    clearAdminSession();
    header('Location: /dashboard?logged_out=1');
    exit;
}

// Redirect logged-in users directly to their first allowed dashboard section
if (isAdminLoggedIn() && empty($_POST) && empty($_GET['view'])) {
    $firstSection = function_exists('getUserFirstAllowedSection')
        ? getUserFirstAllowedSection($_SESSION['admin_role'] ?? null, $_SESSION['admin_email'] ?? null, $_SESSION['admin_username'] ?? null)
        : 'dashboard';
    header('Location: /admin/' . ltrim((string) $firstSection, '/'));
    exit;
}

/**
 * Resolve cross-subdomain cookie domain (.falhen.com on falhen hosts, '' on localhost/IP).
 */
if (!function_exists('falhenGetCookieDomain')) {
    function falhenGetCookieDomain() {
        $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
        $host = preg_replace('/:\d+$/', '', $host);
        if ($host === 'falhen.com' || substr($host, -11) === '.falhen.com') {
            return '.falhen.com';
        }
        return '';
    }
}

/**
 * Fallback canonical staff account lookup across MySQL admin_users, settings.json staff_accounts,
 * and admin_profile (used if Agent 3's functions.php version is not loaded yet).
 */
if (!function_exists('findStaffAccountByIdentifier')) {
    function findStaffAccountByIdentifier($identifier, $allowAltEmail = false) {
        $needle = strtolower(trim((string) $identifier));
        if ($needle === '') {
            return null;
        }

        $dbMatch = null;
        $pdo = function_exists('getDBConnection') ? getDBConnection() : null;
        if ($pdo) {
            try {
                if ($allowAltEmail) {
                    $stmt = $pdo->prepare("SELECT * FROM `admin_users` WHERE LOWER(email) = ? OR LOWER(username) = ? OR LOWER(alt_email) = ? LIMIT 1");
                    $stmt->execute([$needle, $needle, $needle]);
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM `admin_users` WHERE LOWER(email) = ? OR LOWER(username) = ? LIMIT 1");
                    $stmt->execute([$needle, $needle]);
                }
                $row = $stmt->fetch();
                if ($row) {
                    $dbMatch = $row;
                }
            } catch (Exception $e) {}
        }

        $repoMatch = null;
        if (function_exists('getStaffAccountsRepo')) {
            $staffAccounts = getStaffAccountsRepo();
            foreach ($staffAccounts as $st) {
                $stUser  = strtolower(trim((string) ($st['username'] ?? '')));
                $stEmail = strtolower(trim((string) ($st['email'] ?? '')));
                $stAlt   = strtolower(trim((string) ($st['alt_email'] ?? '')));
                $matched = ($needle === $stUser || $needle === $stEmail || ($allowAltEmail && $stAlt !== '' && $needle === $stAlt));
                if (!$matched && function_exists('isHenryMultiMail') && isHenryMultiMail($needle) && ($stEmail === 'henry.falonipe@falhen.com' || $stUser === 'henry.falonipe' || $stUser === 'admin')) {
                    $matched = true;
                }
                if ($matched) {
                    $repoMatch = $st;
                    break;
                }
            }
        }

        if ($dbMatch && $repoMatch) {
            $merged = array_merge($repoMatch, $dbMatch);
            if (empty($merged['alt_email']) && !empty($repoMatch['alt_email'])) {
                $merged['alt_email'] = $repoMatch['alt_email'];
            }
            if (empty($merged['avatar']) && !empty($repoMatch['avatar'])) {
                $merged['avatar'] = $repoMatch['avatar'];
            }
            if ((empty($merged['role']) || strtolower($merged['role']) === 'staff') && !empty($repoMatch['role'])) {
                $merged['role'] = $repoMatch['role'];
            }
            if (empty($merged['password_hash']) && !empty($repoMatch['password_hash'])) {
                $merged['password_hash'] = $repoMatch['password_hash'];
            }
            return $merged;
        }

        if ($dbMatch) {
            return $dbMatch;
        }
        if ($repoMatch) {
            return $repoMatch;
        }

        if (function_exists('getAdminUserProfile')) {
            $adminProfile = getAdminUserProfile();
            $pUser  = strtolower(trim((string) ($adminProfile['username'] ?? 'admin')));
            $pEmail = strtolower(trim((string) ($adminProfile['email'] ?? 'admin@falhen.com')));
            $pAlt   = strtolower(trim((string) ($adminProfile['alt_email'] ?? '')));
            $ownerAliases = ['henry.falonipe', 'henry', 'admin', 'henry.falonipe@falhen.com', 'henry@falhen.com', 'admin@falhen.com', 'info@falhen.com'];
            if (
                $needle === $pUser ||
                $needle === $pEmail ||
                in_array($needle, $ownerAliases, true) ||
                ($allowAltEmail && $pAlt !== '' && $needle === $pAlt)
            ) {
                return [
                    'id'            => 1,
                    'username'      => $adminProfile['username'] ?? 'henry.falonipe',
                    'email'         => $adminProfile['email'] ?? 'henry.falonipe@falhen.com',
                    'alt_email'     => $adminProfile['alt_email'] ?? '',
                    'password_hash' => $adminProfile['password_hash'] ?? '',
                    'role'          => 'Owner / Super Admin',
                    'avatar'        => $adminProfile['avatar'] ?? '',
                    'full_name'     => $adminProfile['full_name'] ?? 'Henry Falonipe',
                    'is_owner'      => 1,
                    'status'        => 'active',
                ];
            }
        }

        return null;
    }
}

/**
 * Fallback helper to synchronize a staff password hash across both MySQL admin_users and settings.json.
 */
if (!function_exists('syncStaffPasswordAcrossStores')) {
    function syncStaffPasswordAcrossStores($email, $username, $passwordHash) {
        $cleanEmail = strtolower(trim((string) $email));
        $cleanUser  = trim((string) $username);
        if ($passwordHash === '') {
            return false;
        }

        $pdo = function_exists('getDBConnection') ? getDBConnection() : null;
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE `admin_users` SET `password_hash` = ? WHERE LOWER(email) = ? OR LOWER(username) = ?");
                $stmt->execute([$passwordHash, $cleanEmail, strtolower($cleanUser)]);
            } catch (Exception $e) {
                error_log("syncStaffPasswordAcrossStores DB error: " . $e->getMessage());
            }
        }

        if (function_exists('saveStaffAccountByUsername')) {
            $payload = [
                'password_hash' => $passwordHash,
                'status'        => 'active',
            ];
            if ($cleanUser !== '') {
                saveStaffAccountByUsername($cleanUser, $payload);
            }
            if ($cleanEmail !== '') {
                saveStaffAccountByUsername($cleanEmail, $payload);
            }
        }

        if (function_exists('isOwnerUser') && isOwnerUser($cleanEmail, $cleanUser) && function_exists('saveAdminUserProfile')) {
            saveAdminUserProfile(['password_hash' => $passwordHash]);
        }

        return true;
    }
}

/**
 * File-backed + session-backed brute-force / OTP rate limiter with LOCK_EX.
 */
if (!function_exists('falhenRateLimitLedgerOp')) {
    function falhenRateLimitLedgerOp($scope, $identifier, $mode = 'check', $maxAttempts = 5, $windowSeconds = 900) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = hash('sha256', $scope . '|' . $ip . '|' . strtolower(trim((string) $identifier)));
        $now = time();
        $ledgerFile = rtrim(sys_get_temp_dir(), '/\\') . '/falhen_rate_limits.json';

        $timestamps = [];
        $usedFile = false;

        $fp = @fopen($ledgerFile, 'c+');
        if ($fp) {
            if (@flock($fp, LOCK_EX)) {
                $usedFile = true;
                $raw = stream_get_contents($fp);
                $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
                if (!is_array($data)) {
                    $data = [];
                }

                // Prune stale buckets (> 24h) to keep ledger tiny
                foreach ($data as $k => $bucket) {
                    if (!is_array($bucket)) {
                        unset($data[$k]);
                        continue;
                    }
                    $data[$k] = array_values(array_filter($bucket, function ($ts) use ($now) {
                        return is_int($ts) && ($now - $ts) < 86400;
                    }));
                    if (empty($data[$k])) {
                        unset($data[$k]);
                    }
                }

                $bucket = isset($data[$key]) && is_array($data[$key]) ? $data[$key] : [];
                $bucket = array_values(array_filter($bucket, function ($ts) use ($now, $windowSeconds) {
                    return is_int($ts) && ($now - $ts) < $windowSeconds;
                }));

                if ($mode === 'record') {
                    $bucket[] = $now;
                    $data[$key] = $bucket;
                    rewind($fp);
                    ftruncate($fp, 0);
                    fwrite($fp, json_encode($data));
                    fflush($fp);
                } elseif ($mode === 'clear') {
                    unset($data[$key]);
                    $bucket = [];
                    rewind($fp);
                    ftruncate($fp, 0);
                    fwrite($fp, json_encode($data));
                    fflush($fp);
                }

                $timestamps = $bucket;
                @flock($fp, LOCK_UN);
            }
            @fclose($fp);
        }

        // Session mirror fallback (ensures enforcement even if temp storage is unavailable)
        if (!isset($_SESSION['falhen_rl']) || !is_array($_SESSION['falhen_rl'])) {
            $_SESSION['falhen_rl'] = [];
        }
        $sessBucket = isset($_SESSION['falhen_rl'][$key]) && is_array($_SESSION['falhen_rl'][$key]) ? $_SESSION['falhen_rl'][$key] : [];
        $sessBucket = array_values(array_filter($sessBucket, function ($ts) use ($now, $windowSeconds) {
            return is_int($ts) && ($now - $ts) < $windowSeconds;
        }));

        if ($mode === 'record') {
            $sessBucket[] = $now;
            $_SESSION['falhen_rl'][$key] = $sessBucket;
        } elseif ($mode === 'clear') {
            unset($_SESSION['falhen_rl'][$key]);
            $sessBucket = [];
        }

        if (!$usedFile || count($sessBucket) > count($timestamps)) {
            $timestamps = $sessBucket;
        }

        $count = count($timestamps);
        $allowed = ($count < $maxAttempts);
        $remainingSeconds = 0;
        if (!$allowed && !empty($timestamps)) {
            $oldest = min($timestamps);
            $remainingSeconds = max(1, ($oldest + $windowSeconds) - $now);
        }

        return [
            'allowed'           => $allowed,
            'attempts'          => $count,
            'remaining_seconds' => $remainingSeconds,
            'remaining_minutes' => (int) max(1, ceil($remainingSeconds / 60)),
        ];
    }
}

/**
 * Match a newly authenticating firstname.lastname@falhen.com email against website team members
 * so their official role, full name, and avatar are assigned automatically.
 */
if (!function_exists('falhenResolveTeamMemberByEmail')) {
    function falhenResolveTeamMemberByEmail($corpEmail) {
        $cleanEmail = strtolower(trim((string) $corpEmail));
        $localPart = explode('@', $cleanEmail)[0] ?? '';
        $nameTokens = array_values(array_filter(explode('.', $localPart)));
        $normalizedForward = implode(' ', $nameTokens);
        $normalizedReverse = implode(' ', array_reverse($nameTokens));
        $compactForward = preg_replace('/[^a-z0-9]/', '', $normalizedForward);
        $compactReverse = preg_replace('/[^a-z0-9]/', '', $normalizedReverse);

        if (function_exists('getTeamMembers')) {
            $teamMembers = getTeamMembers();
            if (is_array($teamMembers)) {
                foreach ($teamMembers as $tm) {
                    $tmEmail = strtolower(trim((string) ($tm['email'] ?? '')));
                    $tmName  = trim((string) ($tm['name'] ?? ''));
                    $tmCompact = preg_replace('/[^a-z0-9]/', '', strtolower($tmName));

                    $isMatch = ($tmEmail !== '' && $tmEmail === $cleanEmail)
                        || ($tmCompact !== '' && ($tmCompact === $compactForward || $tmCompact === $compactReverse));

                    if ($isMatch) {
                        return [
                            'full_name' => $tmName !== '' ? $tmName : ucwords(str_replace('.', ' ', $localPart)),
                            'role'      => !empty($tm['role']) ? $tm['role'] : 'Staff',
                            'avatar'    => $tm['image'] ?? ($tm['avatar'] ?? ''),
                        ];
                    }
                }
            }
        }

        return [
            'full_name' => ucwords(str_replace('.', ' ', $localPart)),
            'role'      => 'Staff',
            'avatar'    => '',
        ];
    }
}

/**
 * Dispatch password reset OTP with delivery verification and automatic failover
 * from external recovery email to corporate @falhen.com email if primary fails.
 */
if (!function_exists('falhenDispatchVerifiedOtp')) {
    function falhenDispatchVerifiedOtp($primaryEmail, $fallbackCorpEmail, $code, $fullName, $username, $corpEmail) {
        $primaryEmail = trim((string) $primaryEmail);
        $fallbackCorpEmail = trim((string) $fallbackCorpEmail);

        $trySend = function ($recipient) use ($code, $fullName, $username, $corpEmail) {
            if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                return ['ok' => false, 'result' => ['error' => 'Invalid recipient email']];
            }
            $GLOBALS['falhen_last_mail_result'] = null;
            $sent = (bool) sendStaffPasswordResetOtpEmail($recipient, $code, $fullName, $username, $corpEmail);
            $meta = is_array($GLOBALS['falhen_last_mail_result'] ?? null) ? $GLOBALS['falhen_last_mail_result'] : [];
            $ok = $sent && (!array_key_exists('sent', $meta) || !empty($meta['sent']));
            return ['ok' => $ok, 'result' => $meta];
        };

        $primaryAttempt = $trySend($primaryEmail);
        if ($primaryAttempt['ok']) {
            return [
                'success'      => true,
                'delivered_to' => $primaryEmail,
                'failover'     => false,
                'meta'         => $primaryAttempt['result'],
            ];
        }

        error_log("OTP primary delivery failed for {$username} -> {$primaryEmail}: " . json_encode($primaryAttempt['result']));

        if ($fallbackCorpEmail !== '' && strtolower($fallbackCorpEmail) !== strtolower($primaryEmail)) {
            $fallbackAttempt = $trySend($fallbackCorpEmail);
            if ($fallbackAttempt['ok']) {
                return [
                    'success'      => true,
                    'delivered_to' => $fallbackCorpEmail,
                    'failover'     => true,
                    'meta'         => $fallbackAttempt['result'],
                ];
            }
            error_log("OTP failover delivery also failed for {$username} -> {$fallbackCorpEmail}: " . json_encode($fallbackAttempt['result']));
            return [
                'success'      => false,
                'delivered_to' => null,
                'failover'     => true,
                'meta'         => $fallbackAttempt['result'],
            ];
        }

        return [
            'success'      => false,
            'delivered_to' => null,
            'failover'     => false,
            'meta'         => $primaryAttempt['result'],
        ];
    }
}

$error = '';
$success = '';
if (isset($_GET['logged_out'])) {
    $success = 'You have successfully signed out.';
}
if (isset($_GET['pwd_updated'])) {
    $success = 'Your password has been successfully updated! Please sign in with your updated credentials.';
    $view = 'staff';
}
if (isset($_GET['pwd_set'])) {
    $success = 'Password created successfully! Please sign in with your credentials.';
    $view = 'staff';
}
$prefillUser = sanitizeInput($_GET['u'] ?? '');

// Default view is 'choice' landing screen when clicking dashboard from website
if (empty($view)) {
    $view = $_GET['view'] ?? 'choice';
} 
if (isset($_GET['action']) && $_GET['action'] === 'activate') {
    $view = 'activation';
}

$pendingActivationUser = sanitizeInput($_GET['user'] ?? $_GET['activation_user'] ?? $_GET['email'] ?? '');
$pendingCode = sanitizeInput($_GET['code'] ?? $_GET['activation_code'] ?? '');
$verifiedEmail = $_SESSION['verified_activation_user'] ?? '';

if (($view === 'activation' || isset($_GET['action']) && $_GET['action'] === 'activate') && !empty($pendingActivationUser) && !empty($pendingCode)) {
    if (activateStaffAccountWithCode($pendingActivationUser, $pendingCode)) {
        // Activation code verified successfully!
        // Instead of auto-logging into dashboard, require user to set their password first
        $view = 'set_password';
        $verifiedEmail = $pendingActivationUser;
        $_SESSION['verified_activation_user'] = $pendingActivationUser;
        $success = 'Account code verified! Please create your password below to complete setup.';
    } else {
        $error = 'Invalid or expired activation code. Please enter the 6-digit code or request a new code.';
        $view = 'activation';
    }
}

$csrfToken = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? 'login';
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!validateCSRFToken($submittedToken)) {
        $error = 'Invalid security token. Please refresh the page and try again.';
        $view = 'staff';
    } else if ($postAction === 'login') {
        $emailOrUser = sanitizeInput($_POST['email'] ?? $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = !empty($_POST['remember_me']);
        $portalType = $_POST['portal_type'] ?? 'staff';

        if ($portalType === 'client') {
            $clientEmail = sanitizeInput($_POST['client_email'] ?? '');
            if ($clientEmail && filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['portal_lookup_email'] = strtolower($clientEmail);
                header('Location: /clients');
                exit;
            } else {
                $error = 'Please enter a valid client email address.';
                $view = 'client';
            }
        } else if ($portalType === 'vendor') {
            $view = 'vendor';
            $vendorEmail = sanitizeInput($_POST['email'] ?? '');
            $vendorCred = trim($_POST['vendor_cred'] ?? $_POST['password'] ?? '');

            if (empty($vendorEmail) || empty($vendorCred)) {
                $error = 'Please enter your email address and reference code or password.';
            } else {
                $authRes = authenticateVendor($vendorEmail, $vendorCred);
                if ($authRes['success']) {
                    header('Location: /vendor/index.php');
                    exit;
                } else {
                    $error = $authRes['message'];
                }
            }
        } else {
            // Staff portal authentication logic
            $view = 'staff';
            if (empty($emailOrUser) || empty($password)) {
                $error = 'Please enter your staff username or firstname.lastname@falhen.com email address and password.';
            } else {
                $cleanInput = trim($emailOrUser);
                $isEmailInput = strpos($cleanInput, '@') !== false;

                // Brute-force rate limiting: max 5 failed login attempts per IP+identifier within 15 minutes
                $loginRate = falhenRateLimitLedgerOp('staff_login', $cleanInput, 'check', 5, 900);
                if (!$loginRate['allowed']) {
                    $mins = $loginRate['remaining_minutes'];
                    $error = "Too many failed sign-in attempts. Your access is temporarily locked for {$mins} minute(s). Please try again later or reset your password.";
                    logDatabaseAudit($cleanInput, 'LOGIN_LOCKOUT', 'auth', null, "Brute-force lockout triggered ({$loginRate['attempts']} attempts in 15m)");
                } else if ($isEmailInput && !isValidCorporateFalhenEmail($cleanInput)) {
                    // Strict Corporate Email Enforcement: firstname.lastname@falhen.com ONLY (except Henry multi-mail)
                    if (isFalhenDomainEmail($cleanInput)) {
                        $error = 'Invalid corporate email format. Corporate staff login requires a firstname.lastname@falhen.com address.';
                    } else {
                        $error = 'Access Restricted: Corporate staff login strictly requires a firstname.lastname@falhen.com email address. External recovery emails are only used on the Forgot Password / OTP Reset screen.';
                    }
                    logDatabaseAudit($cleanInput, 'LOGIN_DOMAIN_REJECTED', 'auth', null, 'Blocked non-corporate or invalid @falhen.com email format');
                } else {
                    $pdo = getDBConnection();
                    $authenticated = false;
                    $authMethod = '';
                    $userData = null;

                    // Resolve canonical registered staff account across MySQL admin_users & settings.json
                    $account = findStaffAccountByIdentifier($cleanInput, false);

                    // Reject suspended accounts immediately
                    if ($account && strtolower(trim((string) ($account['status'] ?? 'active'))) === 'suspended') {
                        $error = 'This staff account has been suspended. Please contact the administrator.';
                        logDatabaseAudit($account['username'] ?? $cleanInput, 'LOGIN_BLOCKED_SUSPENDED', 'auth', $account['id'] ?? null, 'Suspended account attempted staff portal sign-in');
                    } else {
                        // Preserve 6-digit activation code shortcut if user entered code in password field
                        if ($account) {
                            $stStatus = strtolower(trim((string) ($account['status'] ?? 'active')));
                            $actStatus = strtolower(trim((string) ($account['activation_status'] ?? 'activated')));
                            $stCode = trim((string) ($account['activation_code'] ?? ''));
                            $is6DigitCode = preg_match('/^\d{6}$/', trim($password)) === 1;
                            $matchesAccountCode = ($stCode !== '' && hash_equals($stCode, trim($password)));
                            $tier1Preview = !empty($account['password_hash']) && password_verify($password, $account['password_hash']);

                            if (!$tier1Preview && ($matchesAccountCode || ($is6DigitCode && ($stStatus === 'pending_activation' || $actStatus === 'pending' || empty($account['password_hash']))))) {
                                if (activateStaffAccountWithCode($account['email'] ?? $cleanInput, trim($password))) {
                                    $view = 'set_password';
                                    $verifiedEmail = $account['email'] ?? $cleanInput;
                                    $_SESSION['verified_activation_user'] = $verifiedEmail;
                                    $success = 'Activation code accepted! Please set your new password below.';
                                }
                            }
                        }

                        if ($view === 'staff' && empty($error)) {
                            // Tier 1: Local Password Hash Verification (MySQL admin_users / settings.json with split-brain auto-heal)
                            $localHashMatched = false;
                            $matchedAltHash = null;
                            if ($account) {
                                if (!empty($account['password_hash']) && password_verify($password, $account['password_hash'])) {
                                    $localHashMatched = true;
                                } elseif (!empty($account['_alt_password_hashes']) && is_array($account['_alt_password_hashes'])) {
                                    foreach ($account['_alt_password_hashes'] as $altH) {
                                        if (!empty($altH) && password_verify($password, $altH)) {
                                            $localHashMatched = true;
                                            $matchedAltHash = $altH;
                                            break;
                                        }
                                    }
                                }
                            }
                            if ($account && $localHashMatched) {
                                if ($matchedAltHash !== null) {
                                    // Auto-heal divergent hash across DB and settings.json immediately
                                    syncStaffPasswordAcrossStores($account['email'] ?? $cleanInput, $account['username'] ?? '', $matchedAltHash);
                                }
                                $stStatus = strtolower(trim((string) ($account['status'] ?? 'active')));
                                $actStatus = strtolower(trim((string) ($account['activation_status'] ?? 'activated')));
                                if ($stStatus === 'pending_activation' || $actStatus === 'pending') {
                                    $view = 'activation';
                                    $pendingActivationUser = $account['email'] ?? $cleanInput;
                                    $pendingAltEmail = !empty($account['alt_email']) ? $account['alt_email'] : $pendingActivationUser;
                                    $infoMessage = 'Account activation code was sent to your alternative email (' . maskEmailForDisplay($pendingAltEmail) . ').';
                                } else {
                                    $authenticated = true;
                                    $authMethod = 'local_hash';
                                    $userData = [
                                        'id'        => $account['id'] ?? 0,
                                        'username'  => $account['username'] ?? explode('@', strtolower($account['email'] ?? $cleanInput))[0],
                                        'email'     => $account['email'] ?? ($isEmailInput ? strtolower($cleanInput) : ''),
                                        'role'      => $account['role'] ?? 'Staff',
                                        'avatar'    => $account['avatar'] ?? '',
                                        'full_name' => $account['full_name'] ?? ($account['username'] ?? $cleanInput),
                                        'is_owner'  => $account['is_owner'] ?? 0,
                                    ];
                                }
                            }

                            // Tier 2: Live MXroute IMAP SSL Protocol Fallback + Seamless Password Sync
                            if (!$authenticated && $view === 'staff' && empty($error)) {
                                $targetCorpEmail = $isEmailInput
                                    ? strtolower($cleanInput)
                                    : strtolower(trim((string) ($account['email'] ?? '')));

                                if ($targetCorpEmail !== '' && isValidCorporateFalhenEmail($targetCorpEmail)) {
                                    $mxRes = authenticateViaMXrouteProtocol($targetCorpEmail, $password);
                                    if (!empty($mxRes['success'])) {
                                        $authenticated = true;
                                        $authMethod = 'mxroute_imap:' . ($mxRes['host'] ?? 'mxroute');
                                        $newHash = password_hash($password, PASSWORD_DEFAULT);

                                        if ($account) {
                                            $resolvedUsername = !empty($account['username']) ? $account['username'] : explode('@', $targetCorpEmail)[0];
                                            $userData = [
                                                'id'        => $account['id'] ?? 0,
                                                'username'  => $resolvedUsername,
                                                'email'     => !empty($account['email']) ? $account['email'] : $targetCorpEmail,
                                                'role'      => $account['role'] ?? 'Staff',
                                                'avatar'    => $account['avatar'] ?? '',
                                                'full_name' => $account['full_name'] ?? ucwords(str_replace('.', ' ', $resolvedUsername)),
                                                'is_owner'  => $account['is_owner'] ?? 0,
                                            ];
                                            syncStaffPasswordAcrossStores($targetCorpEmail, $resolvedUsername, $newHash);
                                        } else {
                                            // Not in local roster yet: match against website team members for official role
                                            $extractedUser = explode('@', $targetCorpEmail)[0];
                                            $teamMatch = falhenResolveTeamMemberByEmail($targetCorpEmail);
                                            $userData = [
                                                'id'        => 0,
                                                'username'  => $extractedUser,
                                                'email'     => $targetCorpEmail,
                                                'role'      => $teamMatch['role'],
                                                'avatar'    => $teamMatch['avatar'],
                                                'full_name' => $teamMatch['full_name'],
                                                'is_owner'  => isOwnerUser($targetCorpEmail, $extractedUser) ? 1 : 0,
                                            ];

                                            // Provision into MySQL admin_users if available
                                            if ($pdo) {
                                                try {
                                                    $insStmt = $pdo->prepare("INSERT INTO `admin_users` (`username`, `email`, `password_hash`, `role`, `full_name`, `is_owner`, `status`)
                                                        VALUES (?, ?, ?, ?, ?, ?, 'active')
                                                        ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `role` = IF(`role` = 'Staff' OR `role` IS NULL OR `role` = '', VALUES(`role`), `role`)");
                                                    $insStmt->execute([
                                                        $userData['username'],
                                                        $userData['email'],
                                                        $newHash,
                                                        $userData['role'],
                                                        $userData['full_name'],
                                                        $userData['is_owner'],
                                                    ]);
                                                    $insertedId = (int) $pdo->lastInsertId();
                                                    if ($insertedId > 0) {
                                                        $userData['id'] = $insertedId;
                                                    } else {
                                                        $idStmt = $pdo->prepare("SELECT `id` FROM `admin_users` WHERE LOWER(`email`) = ? LIMIT 1");
                                                        $idStmt->execute([$targetCorpEmail]);
                                                        $rowId = $idStmt->fetchColumn();
                                                        if ($rowId) {
                                                            $userData['id'] = (int) $rowId;
                                                        }
                                                    }
                                                } catch (Exception $e) {
                                                    error_log("MXroute auto-provision DB error: " . $e->getMessage());
                                                }
                                            }
                                            syncStaffPasswordAcrossStores($targetCorpEmail, $extractedUser, $newHash);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($authenticated && $userData) {
                        // Clear login rate-limit counter on successful authentication
                        falhenRateLimitLedgerOp('staff_login', $cleanInput, 'clear', 5, 900);
                        if (!empty($userData['email']) && strtolower($userData['email']) !== strtolower($cleanInput)) {
                            falhenRateLimitLedgerOp('staff_login', $userData['email'], 'clear', 5, 900);
                        }

                        // Preserve Owner / Super Admin primacy for Henry Falonipe
                        $isOwner = isOwnerUser($userData['email'] ?? '', $userData['username'] ?? '');
                        if ($isOwner) {
                            $userData['role'] = 'Owner / Super Admin';
                            $userData['is_owner'] = 1;
                            $userData['full_name'] = 'Henry Falonipe';
                            if (empty($userData['email']) || !isValidCorporateFalhenEmail($userData['email'])) {
                                $userData['email'] = 'henry.falonipe@falhen.com';
                            }
                        }

                        // Preserve IT Administrator primacy for Ligali Oluwatosin & IT staff
                        $uEmailLow = strtolower(trim((string) ($userData['email'] ?? '')));
                        $uUserLow  = strtolower(trim((string) ($userData['username'] ?? '')));
                        $uNameLow  = strtolower(trim((string) ($userData['full_name'] ?? '')));
                        $isLigali  = (strpos($uEmailLow, 'ligali') !== false || strpos($uUserLow, 'ligali') !== false || strpos($uNameLow, 'ligali') !== false || strpos($uNameLow, 'oluwatosin') !== false);
                        $isIT      = !$isOwner && ($isLigali || (function_exists('isITUser') && isITUser($userData['role'] ?? '', $userData['email'] ?? '', $userData['username'] ?? '')));
                        if ($isIT) {
                            if (empty($userData['role']) || strtolower(trim((string) $userData['role'])) === 'staff') {
                                $userData['role'] = 'IT Administrator';
                            }
                            if ($isLigali && (empty($userData['full_name']) || strtolower($userData['full_name']) === $uUserLow)) {
                                $userData['full_name'] = 'Ligali Oluwatosin';
                            }
                        }

                        // Ensure non-empty session user ID even if DB was unavailable
                        if (empty($userData['id'])) {
                            $userData['id'] = $isOwner ? 1 : (abs(crc32($uEmailLow ?: $uUserLow)) ?: 1);
                        }

                        // Update last login timestamp & IP in MySQL
                        if ($pdo) {
                            try {
                                $loginStmt = $pdo->prepare("UPDATE `admin_users` SET `last_login_at` = NOW(), `last_login_ip` = ? WHERE `id` = ? OR LOWER(`email`) = ?");
                                $loginStmt->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $userData['id'], $uEmailLow]);
                            } catch (Exception $e) {}
                        }

                        // Prevent session fixation
                        session_regenerate_id(true);

                        $finalRole = $isOwner
                            ? 'Owner / Super Admin'
                            : (($userData['role'] ?? 'Staff') === 'Talent Manager' ? 'HR Manager' : ($userData['role'] ?? 'Staff'));

                        $_SESSION['admin_user_id']       = $userData['id'];
                        $_SESSION['admin_username']      = $userData['username'];
                        $_SESSION['admin_email']         = $userData['email'];
                        $_SESSION['admin_role']          = $finalRole;
                        $_SESSION['is_owner']            = $isOwner ? 1 : ($userData['is_owner'] ?? 0);
                        $_SESSION['is_it_admin']         = $isIT ? 1 : 0;
                        $_SESSION['admin_avatar']        = $userData['avatar'] ?? '';
                        $_SESSION['admin_full_name']     = $userData['full_name'] ?? $userData['username'];
                        $_SESSION['admin_ua_hash']       = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
                        $_SESSION['admin_last_activity'] = time();

                        // Process 30-day "Remember Me" cross-subdomain cookie
                        if ($rememberMe) {
                            $token = bin2hex(random_bytes(32));
                            if ($pdo) {
                                try {
                                    $uStmt = $pdo->prepare("UPDATE `admin_users` SET `remember_token` = ? WHERE `id` = ? OR LOWER(`email`) = ?");
                                    $uStmt->execute([$token, $userData['id'], $uEmailLow]);
                                } catch (Exception $e) {}
                            }
                            $cookieDomain = falhenGetCookieDomain();
                            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                                || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
                                || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');

                            $cookieOptions = [
                                'expires'  => time() + 30 * 86400,
                                'path'     => '/',
                                'secure'   => $isSecure,
                                'httponly' => true,
                                'samesite' => 'Lax',
                            ];
                            if ($cookieDomain !== '') {
                                $cookieOptions['domain'] = $cookieDomain;
                            }
                            setcookie('falhen_remember_token', $token, $cookieOptions);
                        }

                        logDatabaseAudit(
                            $userData['username'],
                            'STAFF_LOGIN_SUCCESS',
                            'auth',
                            $userData['id'],
                            'Authenticated via ' . $authMethod . ' (' . $userData['email'] . ')'
                        );

                        $firstSection = function_exists('getUserFirstAllowedSection')
                            ? getUserFirstAllowedSection($_SESSION['admin_role'], $_SESSION['admin_email'], $_SESSION['admin_username'])
                            : 'dashboard';
                        header('Location: /admin/' . ltrim((string) $firstSection, '/'));
                        exit;
                    } else if ($view === 'staff') {
                        // Record failed login attempt in brute-force limiter
                        $failState = falhenRateLimitLedgerOp('staff_login', $cleanInput, 'record', 5, 900);
                        logDatabaseAudit(
                            $cleanInput,
                            'STAFF_LOGIN_FAILED',
                            'auth',
                            $account['id'] ?? null,
                            'Failed staff portal sign-in attempt (' . $failState['attempts'] . '/5)'
                        );
                        if (empty($error)) {
                            if (!$failState['allowed']) {
                                $error = "Too many failed sign-in attempts. Your access is temporarily locked for {$failState['remaining_minutes']} minute(s).";
                                logDatabaseAudit($cleanInput, 'LOGIN_LOCKOUT', 'auth', null, 'Brute-force lockout reached on failed login');
                            } else {
                                $error = 'Invalid credentials. Please verify your staff username or firstname.lastname@falhen.com email address and password.';
                            }
                        }
                    }
                }
            }
        }
    } else if ($postAction === 'forgot_password') {
        $view = 'forgot';
        $resetTarget = sanitizeInput(trim($_POST['reset_target'] ?? $_POST['reset_email'] ?? ''));

        if (empty($resetTarget)) {
            $error = 'Please enter your firstname.lastname@falhen.com email, staff username, or registered external recovery email.';
        } else {
            // IP-only guard for unknown-account lookup spam
            $unknownIpRate = falhenRateLimitLedgerOp('otp_dispatch_unknown', 'ip_only', 'check', 5, 600);
            if (!$unknownIpRate['allowed']) {
                $mins = $unknownIpRate['remaining_minutes'];
                $error = "Too many OTP requests. Please wait {$mins} minute(s) before requesting another verification code.";
                logDatabaseAudit($resetTarget, 'OTP_RATE_LIMITED', 'auth', null, 'Exceeded unknown-account OTP lookup limit for IP');
            } else {
                // Lookup by corporate email, username, OR registered external alt_email
                $targetUser = findStaffAccountByIdentifier($resetTarget, true);

                if ($targetUser) {
                    $targetEmail = trim((string) ($targetUser['email'] ?? ''));
                    $targetUsername = trim((string) ($targetUser['username'] ?? explode('@', $targetEmail)[0]));
                    $rateTarget = $targetEmail !== '' ? $targetEmail : ($targetUsername !== '' ? $targetUsername : $resetTarget);

                    // OTP Rate Limiting: max 3 OTP dispatches per 10 minutes per IP + canonical account email (same key as resend_reset_otp)
                    $otpRate = falhenRateLimitLedgerOp('otp_dispatch', $rateTarget, 'check', 3, 600);
                    if (!$otpRate['allowed']) {
                        $mins = $otpRate['remaining_minutes'];
                        $error = "Too many OTP requests. Please wait {$mins} minute(s) before requesting another verification code.";
                        logDatabaseAudit($targetUsername ?: $rateTarget, 'OTP_RATE_LIMITED', 'auth', $targetUser['id'] ?? null, 'Exceeded 3 OTP requests in 10 minutes');
                    } else if (strtolower(trim((string) ($targetUser['status'] ?? 'active'))) === 'suspended') {
                        $error = 'This staff account has been suspended. Please contact the administrator.';
                        $view = 'forgot';
                    } else {
                        falhenRateLimitLedgerOp('otp_dispatch', $rateTarget, 'record', 3, 600);

                        $targetFullName = trim((string) ($targetUser['full_name'] ?? 'Staff Member'));
                        $altEmail = (!empty($targetUser['alt_email']) && filter_var(trim((string) $targetUser['alt_email']), FILTER_VALIDATE_EMAIL))
                            ? trim((string) $targetUser['alt_email'])
                            : $targetEmail;

                        $code = sprintf('%06d', random_int(100000, 999999));
                        $codeHash = hash('sha256', $code);
                        $expiresTs = time() + 900;
                        $expiresSql = date('Y-m-d H:i:s', $expiresTs);

                        // Verified OTP delivery with automatic failover to corporate @falhen.com email if alt_email fails
                        $dispatch = falhenDispatchVerifiedOtp($altEmail, $targetEmail, $code, $targetFullName, $targetUsername, $targetEmail);

                        if (empty($dispatch['success'])) {
                            error_log("OTP delivery failed for {$targetUsername} ({$targetEmail}): " . json_encode($dispatch['meta'] ?? []));
                            logDatabaseAudit($targetUsername, 'OTP_DELIVERY_FAILED', 'auth', $targetUser['id'] ?? null, 'Unable to deliver reset OTP email');
                            $error = 'Unable to deliver verification email at this moment. Please try again shortly or contact your IT Administrator.';
                            $view = 'forgot';
                        } else {
                            $deliveredTo = $dispatch['delivered_to'];
                            $pdo = getDBConnection();
                            if ($pdo) {
                                try {
                                    if ($targetEmail !== '') {
                                        $uStmt = $pdo->prepare("UPDATE `admin_users` SET `reset_otp` = ?, `reset_expires` = ? WHERE LOWER(`email`) = ?");
                                        $uStmt->execute([$codeHash, $expiresSql, strtolower($targetEmail)]);
                                    }
                                    if ($targetEmail === '' || (isset($uStmt) && $uStmt->rowCount() === 0 && !empty($targetUser['id']) && is_numeric($targetUser['id']))) {
                                        $uStmt = $pdo->prepare("UPDATE `admin_users` SET `reset_otp` = ?, `reset_expires` = ? WHERE `id` = ?");
                                        $uStmt->execute([$codeHash, $expiresSql, $targetUser['id']]);
                                    }
                                } catch (Exception $e) {
                                    error_log("Failed persisting OTP hash in DB: " . $e->getMessage());
                                }
                            }

                            // Never store plaintext OTP in session — hash + expiry + attempt counter ONLY
                            unset($_SESSION['reset_pending_code_plain']);
                            $_SESSION['reset_pending_user_id']   = $targetUser['id'] ?? null;
                            $_SESSION['reset_pending_username']  = $targetUsername;
                            $_SESSION['reset_pending_email']     = $targetEmail;
                            $_SESSION['reset_pending_full_name'] = $targetFullName;
                            $_SESSION['reset_pending_alt_email'] = $deliveredTo;
                            $_SESSION['reset_pending_code_hash'] = $codeHash;
                            $_SESSION['reset_pending_expires']   = $expiresTs;
                            $_SESSION['reset_pending_attempts']  = 0;

                            logDatabaseAudit(
                                $targetUsername,
                                'OTP_DISPATCHED',
                                'auth',
                                $targetUser['id'] ?? null,
                                'Password reset OTP delivered to ' . maskEmailForDisplay($deliveredTo) . (!empty($dispatch['failover']) ? ' (failover)' : '')
                            );

                            $success = 'A 6-digit security verification code (OTP) was dispatched to your verified recovery email (' . maskEmailForDisplay($deliveredTo) . '). Enter it below to update your username and password.';
                            $view = 'reset_otp_verify';
                        }
                    }
                } else {
                    falhenRateLimitLedgerOp('otp_dispatch_unknown', 'ip_only', 'record', 5, 600);
                    $error = 'No registered staff account found matching "' . $resetTarget . '". Please check your firstname.lastname@falhen.com email, username, or registered recovery email.';
                    $view = 'forgot';
                }
            }
        }
    } else if ($postAction === 'resend_reset_otp') {
        $userId   = $_SESSION['reset_pending_user_id'] ?? null;
        $username = $_SESSION['reset_pending_username'] ?? '';
        $email    = $_SESSION['reset_pending_email'] ?? '';
        $altEmail = $_SESSION['reset_pending_alt_email'] ?? '';
        $fullName = $_SESSION['reset_pending_full_name'] ?? 'Staff Member';

        if (!empty($userId) || !empty($username) || !empty($email)) {
            $rateTarget = $email !== '' ? $email : $username;
            $otpRate = falhenRateLimitLedgerOp('otp_dispatch', $rateTarget, 'check', 3, 600);
            if (!$otpRate['allowed']) {
                $mins = $otpRate['remaining_minutes'];
                $error = "Too many OTP requests. Please wait {$mins} minute(s) before requesting another verification code.";
                $view = 'reset_otp_verify';
                logDatabaseAudit($username ?: $rateTarget, 'OTP_RATE_LIMITED', 'auth', $userId, 'Resend OTP rate limit hit');
            } else {
                falhenRateLimitLedgerOp('otp_dispatch', $rateTarget, 'record', 3, 600);

                $code = sprintf('%06d', random_int(100000, 999999));
                $codeHash = hash('sha256', $code);
                $expiresTs = time() + 900;
                $expiresSql = date('Y-m-d H:i:s', $expiresTs);

                $primaryRecipient = !empty($altEmail) ? $altEmail : $email;
                $dispatch = falhenDispatchVerifiedOtp($primaryRecipient, $email, $code, $fullName, $username, $email);

                if (empty($dispatch['success'])) {
                    error_log("Resend OTP delivery failed for {$username} ({$email}): " . json_encode($dispatch['meta'] ?? []));
                    logDatabaseAudit($username ?: $rateTarget, 'OTP_DELIVERY_FAILED', 'auth', $userId, 'Unable to re-deliver reset OTP email');
                    $error = 'Unable to deliver verification email at this moment. Please try again shortly or contact your IT Administrator.';
                    $view = 'reset_otp_verify';
                } else {
                    $deliveredTo = $dispatch['delivered_to'];
                    $pdo = getDBConnection();
                    if ($pdo) {
                        try {
                            if ($userId && is_numeric($userId)) {
                                $uStmt = $pdo->prepare("UPDATE `admin_users` SET `reset_otp` = ?, `reset_expires` = ? WHERE `id` = ?");
                                $uStmt->execute([$codeHash, $expiresSql, $userId]);
                            } elseif ($email !== '') {
                                $uStmt = $pdo->prepare("UPDATE `admin_users` SET `reset_otp` = ?, `reset_expires` = ? WHERE LOWER(`email`) = ?");
                                $uStmt->execute([$codeHash, $expiresSql, strtolower($email)]);
                            }
                        } catch (Exception $e) {}
                    }

                    unset($_SESSION['reset_pending_code_plain']);
                    $_SESSION['reset_pending_alt_email'] = $deliveredTo;
                    $_SESSION['reset_pending_code_hash'] = $codeHash;
                    $_SESSION['reset_pending_expires']   = $expiresTs;
                    $_SESSION['reset_pending_attempts']  = 0;

                    logDatabaseAudit($username ?: $rateTarget, 'OTP_RESENT', 'auth', $userId, 'Fresh reset OTP delivered to ' . maskEmailForDisplay($deliveredTo));
                    $success = 'A fresh 6-digit OTP code has been dispatched to ' . maskEmailForDisplay($deliveredTo);
                    $view = 'reset_otp_verify';
                }
            }
        } else {
            $error = 'Reset session expired. Please enter your username or email address again.';
            $view = 'forgot';
        }
    } else if ($postAction === 'verify_reset_otp') {
        $userId          = $_SESSION['reset_pending_user_id'] ?? null;
        $origUsername    = $_SESSION['reset_pending_username'] ?? '';
        $origEmail       = $_SESSION['reset_pending_email'] ?? '';
        $submittedOtp    = trim($_POST['otp_code'] ?? '');
        $newUsername     = sanitizeInput(trim($_POST['new_username'] ?? ''));
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $otpAttempts     = (int) ($_SESSION['reset_pending_attempts'] ?? 0);

        if (empty($userId) && empty($origUsername) && empty($origEmail)) {
            $error = 'Reset session expired. Please start over.';
            $view = 'forgot';
        } else if ($otpAttempts >= 5) {
            // Invalidate OTP once max attempts reached
            $pdo = getDBConnection();
            if ($pdo) {
                try {
                    if ($userId && is_numeric($userId)) {
                        $pdo->prepare("UPDATE `admin_users` SET `reset_otp` = NULL, `reset_expires` = NULL WHERE `id` = ?")->execute([$userId]);
                    } elseif ($origEmail !== '') {
                        $pdo->prepare("UPDATE `admin_users` SET `reset_otp` = NULL, `reset_expires` = NULL WHERE LOWER(`email`) = ?")->execute([strtolower($origEmail)]);
                    }
                } catch (Exception $e) {}
            }
            unset(
                $_SESSION['reset_pending_user_id'],
                $_SESSION['reset_pending_username'],
                $_SESSION['reset_pending_email'],
                $_SESSION['reset_pending_full_name'],
                $_SESSION['reset_pending_alt_email'],
                $_SESSION['reset_pending_code_hash'],
                $_SESSION['reset_pending_expires'],
                $_SESSION['reset_pending_attempts'],
                $_SESSION['reset_pending_code_plain']
            );
            logDatabaseAudit($origUsername ?: $origEmail, 'OTP_MAX_ATTEMPTS_EXCEEDED', 'auth', $userId, 'OTP invalidated after 5 failed verification attempts');
            $error = 'Too many failed OTP attempts (5/5). This verification code has been invalidated — please request a new code.';
            $view = 'forgot';
        } else if (!preg_match('/^\d{6}$/', $submittedOtp)) {
            $error = 'Please enter a valid 6-digit numeric OTP code.';
            $view = 'reset_otp_verify';
        } else if (empty($newUsername) || !preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $newUsername)) {
            $error = 'Username must be between 3 and 50 characters (letters, numbers, dots, dashes, underscores).';
            $view = 'reset_otp_verify';
        } else if (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters in length.';
            $view = 'reset_otp_verify';
        } else if ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match. Please re-enter.';
            $view = 'reset_otp_verify';
        } else {
            $pdo = getDBConnection();
            $dbUser = null;
            if ($pdo) {
                try {
                    if ($origEmail !== '') {
                        $stmt = $pdo->prepare("SELECT * FROM `admin_users` WHERE LOWER(`email`) = ? LIMIT 1");
                        $stmt->execute([strtolower($origEmail)]);
                        $dbUser = $stmt->fetch();
                    }
                    if (!$dbUser && $origUsername !== '') {
                        $stmt = $pdo->prepare("SELECT * FROM `admin_users` WHERE LOWER(`username`) = ? LIMIT 1");
                        $stmt->execute([strtolower($origUsername)]);
                        $dbUser = $stmt->fetch();
                    }
                    if (!$dbUser && $userId && is_numeric($userId)) {
                        $stmt = $pdo->prepare("SELECT * FROM `admin_users` WHERE `id` = ? LIMIT 1");
                        $stmt->execute([$userId]);
                        $dbUser = $stmt->fetch();
                    }
                } catch (Exception $e) {}
            }

            // Constant-time OTP verification against DB hash or session hash (strict expiration check)
            $otpValid = false;
            $hashInput = hash('sha256', $submittedOtp);
            $now = time();

            if ($dbUser && !empty($dbUser['reset_otp'])) {
                $dbExpiresTs = !empty($dbUser['reset_expires']) ? strtotime((string) $dbUser['reset_expires']) : 0;
                if ($dbExpiresTs >= $now && hash_equals((string) $dbUser['reset_otp'], $hashInput)) {
                    $otpValid = true;
                }
            }
            if (!$otpValid && !empty($_SESSION['reset_pending_code_hash']) && !empty($_SESSION['reset_pending_expires'])) {
                if ((int) $_SESSION['reset_pending_expires'] >= $now && hash_equals((string) $_SESSION['reset_pending_code_hash'], $hashInput)) {
                    $otpValid = true;
                }
            }

            if (!$otpValid) {
                $otpAttempts++;
                $_SESSION['reset_pending_attempts'] = $otpAttempts;

                if ($otpAttempts >= 5) {
                    if ($pdo && $dbUser) {
                        try {
                            $pdo->prepare("UPDATE `admin_users` SET `reset_otp` = NULL, `reset_expires` = NULL WHERE `id` = ?")->execute([$dbUser['id']]);
                        } catch (Exception $e) {}
                    }
                    unset(
                        $_SESSION['reset_pending_user_id'],
                        $_SESSION['reset_pending_username'],
                        $_SESSION['reset_pending_email'],
                        $_SESSION['reset_pending_full_name'],
                        $_SESSION['reset_pending_alt_email'],
                        $_SESSION['reset_pending_code_hash'],
                        $_SESSION['reset_pending_expires'],
                        $_SESSION['reset_pending_attempts'],
                        $_SESSION['reset_pending_code_plain']
                    );
                    logDatabaseAudit($origUsername ?: $origEmail, 'OTP_INVALIDATED_MAX_ATTEMPTS', 'auth', $dbUser['id'] ?? $userId, 'OTP invalidated after 5 failed attempts');
                    $error = 'Maximum verification attempts reached (5/5). Your OTP has been invalidated for security — please request a new code.';
                    $view = 'forgot';
                } else {
                    $remaining = 5 - $otpAttempts;
                    logDatabaseAudit($origUsername ?: $origEmail, 'OTP_VERIFY_FAILED', 'auth', $dbUser['id'] ?? $userId, "Invalid/expired OTP attempt ({$otpAttempts}/5)");
                    $error = "Invalid or expired 6-digit OTP code. You have {$remaining} attempt(s) remaining before this code is invalidated.";
                    $view = 'reset_otp_verify';
                }
            } else {
                // If username is changing, ensure it's not taken by another user
                if ($pdo && strtolower($newUsername) !== strtolower($origUsername)) {
                    try {
                        $checkStmt = $pdo->prepare("SELECT id FROM `admin_users` WHERE LOWER(username) = ? AND id != ? LIMIT 1");
                        $checkStmt->execute([strtolower($newUsername), $dbUser ? $dbUser['id'] : 0]);
                        if ($checkStmt->fetch()) {
                            $error = 'The requested username "' . $newUsername . '" is already taken. Please choose another username.';
                            $view = 'reset_otp_verify';
                            $otpValid = false;
                        }
                    } catch (Exception $e) {}
                }

                if ($otpValid) {
                    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    if ($pdo && $dbUser) {
                        try {
                            $uStmt = $pdo->prepare("UPDATE `admin_users` SET `username` = ?, `password_hash` = ?, `reset_otp` = NULL, `reset_expires` = NULL WHERE `id` = ?");
                            $uStmt->execute([$newUsername, $newHash, $dbUser['id']]);
                        } catch (Exception $e) {
                            error_log("Failed to update credentials in DB: " . $e->getMessage());
                        }
                    }

                    // Sync password & username across both MySQL admin_users and settings.json
                    $staffData = [
                        'username'      => $newUsername,
                        'password_hash' => $newHash,
                        'status'        => 'active',
                    ];
                    saveStaffAccountByUsername($origUsername ?: $origEmail, $staffData);
                    syncStaffPasswordAcrossStores($origEmail, $newUsername, $newHash);

                    if (isOwnerUser($origEmail, $origUsername)) {
                        saveAdminUserProfile([
                            'username'      => $newUsername,
                            'password_hash' => $newHash,
                        ]);
                    }

                    // Clear any login lockouts for this account (and all Owner aliases) after verified reset
                    falhenRateLimitLedgerOp('staff_login', $origEmail, 'clear', 5, 900);
                    falhenRateLimitLedgerOp('staff_login', $origUsername, 'clear', 5, 900);
                    falhenRateLimitLedgerOp('staff_login', $newUsername, 'clear', 5, 900);
                    if (isOwnerUser($origEmail, $origUsername)) {
                        foreach (['henry.falonipe@falhen.com', 'henry@falhen.com', 'admin@falhen.com', 'info@falhen.com', 'henry.falonipe', 'henry', 'admin'] as $ownerAlias) {
                            falhenRateLimitLedgerOp('staff_login', $ownerAlias, 'clear', 5, 900);
                        }
                    }

                    unset(
                        $_SESSION['reset_pending_user_id'],
                        $_SESSION['reset_pending_username'],
                        $_SESSION['reset_pending_email'],
                        $_SESSION['reset_pending_full_name'],
                        $_SESSION['reset_pending_alt_email'],
                        $_SESSION['reset_pending_code_hash'],
                        $_SESSION['reset_pending_expires'],
                        $_SESSION['reset_pending_attempts'],
                        $_SESSION['reset_pending_code_plain']
                    );

                    logDatabaseAudit(
                        $newUsername,
                        'PASSWORD_RESET_OTP_SUCCESS',
                        'auth',
                        $dbUser['id'] ?? $userId,
                        'Staff credentials updated via verified OTP (' . $origEmail . ')'
                    );

                    header('Location: /admin/login.php?view=staff&pwd_updated=1&u=' . urlencode($newUsername));
                    exit;
                }
            }
        }
    } else if ($postAction === 'setup_account') {
        $inviteCode = sanitizeInput($_POST['invite_code'] ?? '');
        $setupEmail = sanitizeInput($_POST['setup_email'] ?? '');
        if ($setupEmail && $inviteCode) {
            if (activateStaffAccountWithCode($setupEmail, $inviteCode)) {
                $view = 'set_password';
                $verifiedEmail = $setupEmail;
                $_SESSION['verified_activation_user'] = $setupEmail;
                $success = 'Account code verified! Please set your new password below to complete setup.';
            } else {
                $error = 'Invalid email address or 6-digit activation code. Please verify details.';
                $view = 'setup';
            }
        } else {
            $view = 'setup';
            $error = 'Please provide both staff email and invitation/activation code.';
        }
    } else if ($postAction === 'verify_activation_code') {
        $activationUser = sanitizeInput($_POST['activation_user'] ?? $_POST['email'] ?? '');
        $submittedCode  = sanitizeInput($_POST['activation_code'] ?? '');
        
        if (!empty($activationUser) && !empty($submittedCode)) {
            if (activateStaffAccountWithCode($activationUser, $submittedCode)) {
                $view = 'set_password';
                $verifiedEmail = $activationUser;
                $_SESSION['verified_activation_user'] = $activationUser;
                $success = 'Account activation verified! Please create your password below.';
            } else {
                $error = 'Invalid 6-digit activation code. Please check your alternative email or try again.';
                $view = 'activation';
                $pendingActivationUser = $activationUser;
            }
        } else {
            $error = 'Please enter the 6-digit activation code.';
            $view = 'activation';
            $pendingActivationUser = $activationUser;
        }
    } else if ($postAction === 'set_account_password') {
        // Strictly trust ONLY server-side verified activation session — NEVER trust POST user/email fields
        $setUser = trim((string) ($_SESSION['verified_activation_user'] ?? ''));
        $submittedSetUser = trim((string) sanitizeInput($_POST['set_user'] ?? ''));
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($setUser === '' || ($submittedSetUser !== '' && strcasecmp($submittedSetUser, $setUser) !== 0)) {
            $error = 'Activation session expired or unauthorized. Please verify your activation code first.';
            $view = 'activation';
            logDatabaseAudit($submittedSetUser ?: 'anonymous', 'SET_PASSWORD_UNAUTHORIZED', 'auth', null, 'Blocked set_account_password without valid verified_activation_user session');
        } else if (strlen($newPassword) < 6) {
            $error = 'Password must be at least 6 characters in length.';
            $view = 'set_password';
            $verifiedEmail = $setUser;
        } else if ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match. Please re-enter.';
            $view = 'set_password';
            $verifiedEmail = $setUser;
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pwdData = [
                'password_hash' => $newHash,
                'status' => 'active',
                'activation_status' => 'activated',
                'activation_code' => ''
            ];

            saveStaffAccountByUsername($setUser, $pwdData);

            $uLower = strtolower($setUser);
            if ($uLower === 'admin' || $uLower === 'admin@falhen.com' || strpos($uLower, 'henry') !== false) {
                saveAdminUserProfile(['password_hash' => $newHash]);
                saveStaffAccountByUsername('admin', $pwdData);
                saveStaffAccountByUsername('admin@falhen.com', $pwdData);
            }

            $pdo = getDBConnection();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("UPDATE `admin_users` SET `password_hash` = ? WHERE `username` = ? OR `email` = ?");
                    $stmt->execute([$newHash, $setUser, $setUser]);
                    if ($uLower === 'admin' || $uLower === 'admin@falhen.com') {
                        $stmt = $pdo->prepare("UPDATE `admin_users` SET `password_hash` = ? WHERE `username` = 'admin' OR `email` = 'admin@falhen.com'");
                        $stmt->execute([$newHash]);
                    }
                } catch (Exception $e) {}
            }

            unset($_SESSION['verified_activation_user']);
            $success = 'Password successfully created! You may now sign in with your credentials.';
            $view = 'staff';
            $_POST['email'] = $setUser;
        }
    } else if ($postAction === 'resend_activation_code') {
        $activationUser = sanitizeInput($_POST['activation_user'] ?? $_POST['email'] ?? '');
        $staffAccounts = getStaffAccountsRepo();
        $found = false;
        foreach ($staffAccounts as &$st) {
            $uName = strtolower($st['username'] ?? '');
            $uEmail = strtolower($st['email'] ?? '');
            if (strtolower($activationUser) === $uName || strtolower($activationUser) === $uEmail) {
                $st['activation_code'] = generateStaffActivationCode();
                $st['status'] = 'pending_activation';
                $st['activation_status'] = 'pending';
                $newCode = $st['activation_code'];
                $altEm = $st['alt_email'] ?? $st['email'];
                $targetFullName = $st['full_name'] ?? '';
                $targetStaffEmail = $st['email'] ?? '';
                $found = true;
                break;
            }
        }
        unset($st);
        if ($found) {
            saveSiteSettings(['staff_accounts' => $staffAccounts]);

            // Send SMTP email to alternative email
            if (!empty($altEm) && !empty($newCode)) {
                sendStaffActivationEmail($altEm, $newCode, $targetFullName, $targetStaffEmail);
            }

            $success = 'A new 6-digit activation code has been dispatched to your recovery email address.';
            $view = 'activation';
            $pendingActivationUser = $activationUser;
            $pendingAltEmail = $altEm;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-LJ91C0YXCH"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-LJ91C0YXCH');
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &mdash; Falhen Productions</title>
    <link rel="icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="shortcut icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="apple-touch-icon" href="/assets/img/icons/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg-dark: #080b11;
            --card-bg: #111723;
            --card-hover-bg: #151d2c;
            --card-border: #1e293b;
            --card-border-hover: rgba(239, 68, 68, 0.45);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent-red: #ef4444;
            --accent-red-dark: #dc2626;
            --accent-red-bg: rgba(239, 68, 68, 0.12);
            --accent-red-border: rgba(239, 68, 68, 0.35);
            --input-bg: #0d121d;
            --focus-ring: rgba(239, 68, 68, 0.25);
        }

        html, body {
            height: 100%;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow: hidden; /* Guarantees no scrollbars */
            background-color: var(--bg-dark);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .viewport-wrapper {
            height: 100vh;
            width: 100vw;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 24px 20px 16px 20px;
            box-sizing: border-box;
        }

        /* Top Brand Logo */
        .top-logo-bar {
            width: 100%;
            text-align: center;
            flex-shrink: 0;
            padding-top: 8px;
        }

        .top-logo-bar img {
            height: 36px;
            width: auto;
            object-fit: contain;
        }

        /* Center Content Area */
        .center-content {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-grow: 1;
        }

        .main-card-container {
            width: 100%;
            margin: 0 auto;
            transition: max-width 0.25s ease;
        }

        .main-card-container.wide {
            max-width: 640px;
        }

        .main-card-container.narrow {
            max-width: 400px;
        }

        /* View Switching */
        .view-section {
            display: none;
            width: 100%;
            text-align: center;
        }

        .view-section.active {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Professional Modern Back Link Styling */
        .back-link-left, .back-link-centered {
            color: #94a3b8 !important;
            text-decoration: none !important;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 22px;
            padding: 6px 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .back-link-left {
            align-self: flex-start;
        }

        .back-link-centered {
            align-self: center;
            margin-left: auto;
            margin-right: auto;
        }

        .back-link-left:hover, .back-link-centered:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25);
        }

        /* Shield Badge Icon */
        .shield-icon-box {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: var(--accent-red-bg);
            border: 1px solid var(--accent-red-border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-red);
            font-size: 1.25rem;
            margin-bottom: 16px;
        }

        /* Headings */
        .section-title {
            font-size: 1.7rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.5px;
            margin: 0 0 6px 0;
            text-align: center;
        }

        .section-subtitle {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin: 0 0 24px 0;
            text-align: center;
        }

        /* Portal Selection Grid */
        .portal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 18px;
            width: 100%;
        }

        .portal-card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 24px 20px;
            text-align: left;
            cursor: pointer;
            transition: all 0.25s ease;
            text-decoration: none;
            display: block;
        }

        .portal-card:hover {
            border-color: var(--card-border-hover);
            background-color: var(--card-hover-bg);
            transform: translateY(-2px);
        }

        .portal-card-client:hover {
            border-color: rgba(59, 130, 246, 0.6) !important;
            background-color: rgba(37, 99, 235, 0.05) !important;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.18) !important;
        }

        .portal-card-vendor:hover {
            border-color: rgba(16, 185, 129, 0.6) !important;
            background-color: rgba(16, 185, 129, 0.05) !important;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.18) !important;
        }

        .portal-card-featured:hover {
            border-color: rgba(239, 68, 68, 0.6) !important;
            background-color: rgba(239, 68, 68, 0.05) !important;
            box-shadow: 0 8px 24px rgba(239, 68, 68, 0.18) !important;
        }

        .portal-card-featured {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 22px 24px;
        }

        .portal-card-featured .portal-icon-box {
            margin-bottom: 0;
            flex-shrink: 0;
        }

        .portal-card-featured .portal-card-content {
            flex: 1;
        }

        .portal-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            border: 1px solid var(--accent-red-border);
            background-color: var(--accent-red-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-red);
            font-size: 1.15rem;
            margin-bottom: 16px;
        }

        .portal-card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 6px 0;
        }

        .portal-card-desc {
            font-size: 0.84rem;
            color: var(--text-secondary);
            line-height: 1.45;
            margin: 0;
        }

        .vendor-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 22px;
            width: 100%;
            box-sizing: border-box;
        }

        .badge-new {
            background-color: var(--accent-red-dark);
            color: #ffffff;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 16px;
            text-align: left;
            width: 100%;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .input-relative {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .form-input {
            width: 100%;
            padding: 11px 14px;
            background-color: var(--input-bg);
            border: 1px solid var(--card-border);
            border-radius: 8px;
            color: #ffffff;
            font-size: 0.94rem;
            outline: none;
            transition: border-color 0.2s;
            box-sizing: border-box;
            font-family: inherit;
        }

        .form-input:focus {
            border-color: var(--accent-red);
        }

        .toggle-password-btn {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 0.95rem;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }

        .toggle-password-btn:hover {
            color: var(--text-secondary);
        }

        .helper-text {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 6px;
        }

        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 16px;
            margin-bottom: 20px;
            user-select: none;
            width: 100%;
        }

        .checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.86rem;
            color: var(--text-secondary);
        }

        .custom-checkbox {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 1px solid var(--card-border);
            background-color: var(--input-bg);
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            display: grid;
            place-content: center;
        }

        .custom-checkbox:checked {
            background-color: var(--accent-red-dark);
            border-color: var(--accent-red);
        }

        .custom-checkbox:checked::before {
            content: "\f00c";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: #ffffff;
            font-size: 0.65rem;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 12px 16px;
            background-color: var(--accent-red-dark);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 0.94rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-submit:hover {
            background-color: #ef4444;
        }

        /* Secondary Links */
        .forgot-link-wrapper {
            text-align: center;
            margin-top: 16px;
            width: 100%;
        }

        .forgot-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #ffffff;
        }

        .form-divider-line {
            height: 1px;
            background-color: var(--card-border);
            margin: 22px 0 18px 0;
            width: 100%;
        }

        .account-setup-container {
            text-align: center;
            width: 100%;
        }

        .setup-prompt-text {
            font-size: 0.84rem;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .setup-action-link {
            color: var(--accent-red);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .setup-action-link:hover {
            color: #f87171;
        }

        /* Bottom Fixed Footer Exactly Matching Mockup */
        .bottom-footer {
            width: 100%;
            text-align: center;
            font-size: 0.78rem;
            color: #475569;
            flex-shrink: 0;
            padding-bottom: 8px;
        }

        .bottom-footer a {
            color: #475569;
            text-decoration: none;
            transition: color 0.2s;
        }

        .bottom-footer a:hover {
            color: var(--text-secondary);
        }

        .alert-box {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.84rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            box-sizing: border-box;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #f87171;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.35);
            color: #4ade80;
        }

        /* Mobile Typography & Layout Optimization */
        @media (max-width: 640px) {
            .portal-grid {
                gap: 12px;
                margin-bottom: 14px;
            }
            .portal-card {
                padding: 16px 14px;
                border-radius: 12px;
            }
            .portal-icon-box {
                width: 36px;
                height: 36px;
                font-size: 1rem;
                margin-bottom: 12px;
                border-radius: 8px;
            }
            .portal-card-title {
                font-size: 0.92rem;
                line-height: 1.25;
                margin-bottom: 4px;
            }
            .portal-card-desc {
                font-size: 0.74rem;
                line-height: 1.35;
            }
        }
    </style>
</head>
<body>

    <div class="viewport-wrapper">

        <!-- Top Brand Logo -->
        <div class="top-logo-bar">
            <a href="/">
                <img src="/assets/img/icons/logo.png" alt="Falhen Logo">
            </a>
        </div>

        <!-- Center Main Content -->
        <div class="center-content">
            <div id="loginContainer" class="main-card-container <?php echo ($view === 'choice') ? 'wide' : 'narrow'; ?>">

                <!-- Alert Messages -->
                <?php if ($error): ?>
                    <div class="alert-box alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert-box alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <div><?php echo htmlspecialchars($success); ?></div>
                    </div>
                <?php endif; ?>

                <!-- VIEW 0: WELCOME BACK PORTAL CHOICE LANDING SCREEN -->
                <div id="choiceView" class="view-section <?php echo ($view === 'choice') ? 'active' : ''; ?>">
                    <h1 class="section-title">Welcome Back</h1>
                    <p class="section-subtitle">Choose how you'd like to sign in</p>

                    <div class="portal-grid">
                        <!-- Staff Portal Card (Top Full-Width Card) -->
                        <div class="portal-card portal-card-featured" onclick="switchView('staff')">
                            <div class="portal-icon-box">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div class="portal-card-content">
                                <h2 class="portal-card-title">Staff Portal</h2>
                                <p class="portal-card-desc">Admin &amp; team members &mdash; manage content, galleries, and settings</p>
                            </div>
                        </div>

                        <!-- Client Gallery Card -->
                        <div class="portal-card portal-card-client" onclick="switchView('client')">
                            <div class="portal-icon-box" style="background: rgba(37, 99, 235, 0.12); border-color: rgba(37, 99, 235, 0.35); color: #3b82f6;">
                                <i class="fa-regular fa-image"></i>
                            </div>
                            <h2 class="portal-card-title">Client Gallery</h2>
                            <p class="portal-card-desc">View &amp; download your private photo gallery with your email</p>
                        </div>

                        <!-- Vendor Portal Card -->
                        <div class="portal-card portal-card-vendor" onclick="switchView('vendor')">
                            <div class="portal-icon-box" style="background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.35); color: #10b981;">
                                <i class="fa-solid fa-id-card-clip"></i>
                            </div>
                            <h2 class="portal-card-title">Vendor Portal</h2>
                            <p class="portal-card-desc">Contractors, interns &amp; volunteers &mdash; sign in to dashboard, view agreements &amp; payouts</p>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 24px;">
                        <a href="/" class="back-link-left" style="margin-bottom: 0; align-self: center;">
                            <i class="fa-solid fa-arrow-left"></i> Back to website
                        </a>
                    </div>
                </div>

                <!-- VIEW 1: STAFF SIGN IN FORM (EXACT SCREENSHOT MATCH) -->
                <div id="staffView" class="view-section <?php echo ($view === 'staff') ? 'active' : ''; ?>">
                    
                    <!-- Back Link Left-Aligned Above Shield Icon -->
                    <a href="#" onclick="switchView('choice'); return false;" class="back-link-left">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>

                    <!-- Shield Badge Icon -->
                    <div class="shield-icon-box">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>

                    <h1 class="section-title">Staff Sign In</h1>
                    <p class="section-subtitle">Access the admin dashboard</p>

                    <form method="POST" action="?view=staff" style="width: 100%;">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="portal_type" value="staff">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <!-- Email Address -->
                        <div class="form-group">
                            <label for="email" class="form-label">Email address</label>
                            <input 
                                type="text" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                placeholder="firstname.lastname@falhen.com"
                                value="<?php echo htmlspecialchars(!empty($prefillUser) ? $prefillUser : ($_POST['email'] ?? $_POST['username'] ?? '')); ?>"
                                required 
                                autocomplete="username"
                            >
                            <div class="helper-text">
                                <i class="fa-solid fa-lock"></i> Restricted to firstname.lastname@falhen.com accounts
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-relative">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="form-input" 
                                    placeholder="••••••••••••"
                                    required 
                                    autocomplete="current-password"
                                >
                                <button type="button" class="toggle-password-btn" id="togglePasswordBtn" title="Toggle password visibility">
                                    <i class="fa-regular fa-eye" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me Checkbox -->
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remember_me" value="1" class="custom-checkbox" checked>
                                <span>Remember me for 30 days</span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-submit">
                            Sign In
                        </button>

                        <!-- Forgot Password Link -->
                        <div class="forgot-link-wrapper">
                            <a href="#" onclick="switchView('forgot'); return false;" class="forgot-link">
                                <i class="fa-solid fa-lock"></i> Forgot password?
                            </a>
                        </div>
                    </form>

                    <div class="form-divider-line"></div>

                    <!-- Footer Callout -->
                    <div class="account-setup-container">
                        <p class="setup-prompt-text">New to the portal? Have an invite?</p>
                        <a href="#" onclick="switchView('setup'); return false;" class="setup-action-link">
                            <i class="fa-solid fa-user"></i> Set up your account
                        </a>
                    </div>
                </div>

                <!-- VIEW 2: FORGOT PASSWORD FORM (OTP TO ALT_EMAIL) -->
                <div id="forgotView" class="view-section <?php echo ($view === 'forgot') ? 'active' : ''; ?>">
                    <a href="#" onclick="switchView('staff'); return false;" class="back-link-left">
                        <i class="fa-solid fa-arrow-left"></i> Back to Staff Sign In
                    </a>

                    <div style="width: 52px; height: 52px; border-radius: 50%; background: rgba(239, 68, 68, 0.15); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin: 0 auto 12px auto; border: 1px solid rgba(239, 68, 68, 0.35);">
                        <i class="fa-solid fa-key"></i>
                    </div>

                    <h1 class="section-title" style="font-size: 1.4rem;">Reset Credentials</h1>
                    <p class="section-subtitle">Enter your firstname.lastname@falhen.com email, staff username, or registered recovery email to receive a 6-digit security OTP.</p>

                    <form method="POST" action="?view=forgot" style="width: 100%;">
                        <input type="hidden" name="action" value="forgot_password">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <div class="form-group">
                            <label for="reset_target" class="form-label">Staff Email, Username, or Recovery Email</label>
                            <input type="text" id="reset_target" name="reset_target" class="form-input" placeholder="firstname.lastname@falhen.com, username, or recovery email" required autofocus>
                            <div class="helper-text" style="margin-top: 6px;">
                                <i class="fa-solid fa-shield-halved" style="color: #ef4444;"></i> OTP is dispatched to your registered external recovery email (with automatic @falhen.com failover).
                            </div>
                        </div>

                        <button type="submit" class="btn-submit" style="margin-top: 10px; background: #dc2626;">
                            <i class="fa-solid fa-paper-plane"></i> Send 6-Digit Security OTP
                        </button>
                    </form>
                </div>

                <!-- VIEW 2B: VERIFY OTP & RESET USERNAME / PASSWORD -->
                <div id="reset_otp_verifyView" class="view-section <?php echo ($view === 'reset_otp_verify') ? 'active' : ''; ?>">
                    <a href="#" onclick="switchView('staff'); return false;" class="back-link-left">
                        <i class="fa-solid fa-arrow-left"></i> Back to Sign In
                    </a>

                    <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(239, 68, 68, 0.15); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin: 0 auto 14px auto; border: 1px solid rgba(239, 68, 68, 0.35);">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>

                    <h1 class="section-title" style="font-size: 1.35rem;">Security OTP Verification</h1>
                    <p class="section-subtitle" style="margin-bottom: 14px;">
                        Enter the 6-digit OTP code sent to your registered recovery email to update your username and password.
                    </p>

                    <div style="background: #0d121d; border: 1px solid #1e293b; padding: 12px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; color: #94a3b8; width: 100%; box-sizing: border-box; text-align: left;">
                        <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; color: #ef4444; letter-spacing: 0.8px; margin-bottom: 4px;">Target Staff Account</div>
                        <div style="color: #ffffff; font-weight: 600; font-family: monospace;">
                            <?php echo htmlspecialchars($_SESSION['reset_pending_username'] ?? 'Staff Member'); ?>
                            <?php if (!empty($_SESSION['reset_pending_email'])): ?>
                                <span style="color: #64748b; font-weight: 400;">(<?php echo htmlspecialchars($_SESSION['reset_pending_email']); ?>)</span>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 6px; font-size: 0.82rem; color: #94a3b8;">
                            Recovery Mail (OTP): <strong style="color: #38bdf8;"><?php echo htmlspecialchars(maskEmailForDisplay($_SESSION['reset_pending_alt_email'] ?? 'Recovery Address')); ?></strong>
                        </div>
                    </div>

                    <form method="POST" action="?view=reset_otp_verify" style="width: 100%;">
                        <input type="hidden" name="action" value="verify_reset_otp">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <!-- OTP Code -->
                        <div class="form-group">
                            <label for="otp_code" class="form-label" style="text-align: center; width: 100%; font-weight: 700;">6-Digit Security OTP Code</label>
                            <input type="text" id="otp_code" name="otp_code" class="form-input" placeholder="e.g. 123456" maxlength="6" pattern="[0-9]{6}" required style="text-align: center; letter-spacing: 8px; font-size: 1.4rem; font-weight: 700; font-family: monospace;" autofocus>
                        </div>

                        <!-- Username Reset -->
                        <div class="form-group" style="margin-top: 14px;">
                            <label for="new_username" class="form-label">Staff Username (Update or Keep)</label>
                            <input type="text" id="new_username" name="new_username" class="form-input" value="<?php echo htmlspecialchars($_SESSION['reset_pending_username'] ?? ''); ?>" required autocomplete="username">
                            <div class="helper-text">
                                <i class="fa-solid fa-user-tag"></i> 3-50 alphanumeric characters, dots, dashes, or underscores.
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="form-group" style="margin-top: 14px;">
                            <label for="reset_new_password" class="form-label">New Password</label>
                            <div class="input-relative">
                                <input type="password" id="reset_new_password" name="new_password" class="form-input" placeholder="At least 6 characters" minlength="6" required autocomplete="new-password">
                                <button type="button" class="toggle-password-btn" onclick="togglePassVisibility('reset_new_password', 'toggleResetNewPassIcon')" title="Toggle password visibility">
                                    <i class="fa-regular fa-eye" id="toggleResetNewPassIcon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="form-group" style="margin-top: 14px;">
                            <label for="reset_confirm_password" class="form-label">Confirm New Password</label>
                            <div class="input-relative">
                                <input type="password" id="reset_confirm_password" name="confirm_password" class="form-input" placeholder="Re-enter new password" minlength="6" required autocomplete="new-password">
                                <button type="button" class="toggle-password-btn" onclick="togglePassVisibility('reset_confirm_password', 'toggleResetConfPassIcon')" title="Toggle password visibility">
                                    <i class="fa-regular fa-eye" id="toggleResetConfPassIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit" style="margin-top: 16px; background: #dc2626;">
                            <i class="fa-solid fa-lock-open"></i> Verify OTP &amp; Update Credentials
                        </button>
                    </form>

                    <form method="POST" action="?view=reset_otp_verify" style="width: 100%; margin-top: 14px;">
                        <input type="hidden" name="action" value="resend_reset_otp">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <button type="submit" style="background: none; border: none; color: #94a3b8; font-size: 0.82rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">
                            <i class="fa-solid fa-rotate"></i> Didn't receive OTP? Resend code
                        </button>
                    </form>
                </div>

                <!-- VIEW 3: ACCOUNT SETUP FORM -->
                <div id="setupView" class="view-section <?php echo ($view === 'setup') ? 'active' : ''; ?>">
                    <a href="#" onclick="switchView('staff'); return false;" class="back-link-centered">
                        <i class="fa-solid fa-arrow-left"></i> Back to Staff Sign In
                    </a>

                    <h1 class="section-title" style="font-size: 1.4rem;">Set Up Your Account</h1>
                    <p class="section-subtitle">Enter your details and invitation code to activate portal access.</p>

                    <form method="POST" action="?view=setup" style="width: 100%;">
                        <input type="hidden" name="action" value="setup_account">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <div class="form-group">
                            <label for="setup_email" class="form-label">Staff Email address</label>
                            <input type="email" id="setup_email" name="setup_email" class="form-input" placeholder="firstname.lastname@falhen.com" required>
                        </div>

                        <div class="form-group">
                            <label for="invite_code" class="form-label">Invitation Code</label>
                            <input type="text" id="invite_code" name="invite_code" class="form-input" placeholder="FLH-XXXX-XXXX" required>
                        </div>

                        <button type="submit" class="btn-submit" style="margin-top: 8px;">
                            Verify &amp; Activate Account
                        </button>
                    </form>
                </div>

                <!-- VIEW 4: CLIENT GALLERY PORTAL -->
                <div id="clientView" class="view-section <?php echo ($view === 'client') ? 'active' : ''; ?>">
                    <a href="#" onclick="switchView('choice'); return false;" class="back-link-centered">
                        <i class="fa-solid fa-arrow-left"></i> Back to Portal Selection
                    </a>

                    <!-- Client Badge Icon in Blue -->
                    <div class="shield-icon-box" style="background: rgba(37, 99, 235, 0.15); border-color: rgba(37, 99, 235, 0.35); color: #3b82f6;">
                        <i class="fa-regular fa-image"></i>
                    </div>

                    <h1 class="section-title" style="font-size: 1.4rem;">Client Gallery Portal</h1>
                    <p class="section-subtitle">Enter your client email address to receive your gallery access link.</p>

                    <form method="POST" action="?view=client" style="width: 100%;">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="portal_type" value="client">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <div class="form-group">
                            <label for="client_email" class="form-label">Client Email address</label>
                            <input type="email" id="client_email" name="client_email" class="form-input" placeholder="client@example.com" required>
                        </div>

                        <button type="submit" class="btn-submit" style="margin-top: 8px; background-color: #2563eb; border-color: #2563eb;">
                            Access Private Gallery
                        </button>
                    </form>

                    <div class="form-divider-line"></div>
                    <div class="account-setup-container">
                        <p class="setup-prompt-text">Or browse directly on the Client Portal subdomain:</p>
                        <a href="/client-portal.php" class="setup-action-link" style="color: #ef4444;">
                            <i class="fa-solid fa-table-cells-large"></i> Launch Client Portal (client.falhen.com)
                        </a>
                    </div>
                </div>

                <!-- VIEW 6: VENDOR PORTAL SIGN IN FORM -->
                <div id="vendorView" class="view-section <?php echo ($view === 'vendor') ? 'active' : ''; ?>">
                    <!-- Back Link Left-Aligned -->
                    <a href="#" onclick="switchView('choice'); return false;" class="back-link-left">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>

                    <!-- Vendor Badge Icon -->
                    <div class="shield-icon-box" style="background: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.35); color: #10b981;">
                        <i class="fa-solid fa-id-card-clip"></i>
                    </div>

                    <h1 class="section-title">Vendor Portal</h1>
                    <p class="section-subtitle">Sign in to your contractor dashboard</p>

                    <form method="POST" action="?view=vendor" style="width: 100%;">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="portal_type" value="vendor">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <!-- Email Address -->
                        <div class="form-group">
                            <label for="vendor_email" class="form-label">Email address</label>
                            <input 
                                type="email" 
                                id="vendor_email" 
                                name="email" 
                                class="form-input" 
                                placeholder="you@email.com"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                required 
                            >
                        </div>

                        <!-- Reference Code or Password -->
                        <div class="form-group">
                            <label for="vendor_cred" class="form-label">Reference Code or Password</label>
                            <div class="password-field-wrapper">
                                <input 
                                    type="text" 
                                    id="vendor_cred" 
                                    name="vendor_cred" 
                                    class="form-input" 
                                    placeholder="e.g. FAL-VND-2026-XXXX or password" 
                                    required
                                >
                            </div>
                            <div style="font-size: 0.76rem; color: #64748b; margin-top: 6px;">
                                Use the Reference Code received upon onboarding, or your personal account password.
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-submit" style="background: #10b981;">
                            Sign In to Portal <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
                        </button>
                    </form>

                    <div class="form-divider-line"></div>

                    <!-- Footer Callout -->
                    <div class="account-setup-container">
                        <p class="setup-prompt-text">Not onboarded yet?</p>
                        <a href="/vendor-onboarding.php" class="setup-action-link" style="color: #10b981;">
                            <i class="fa-solid fa-user-plus"></i> Complete Vendor Onboarding
                        </a>
                    </div>
                </div>

                <!-- VIEW 5: ACCOUNT ACTIVATION CODE ENTRY -->
                <div id="activationView" class="view-section <?php echo ($view === 'activation') ? 'active' : ''; ?>">
                    <a href="#" onclick="switchView('staff'); return false;" class="back-link-left">
                        <i class="fa-solid fa-arrow-left"></i> Back to Sign In
                    </a>

                    <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(239, 68, 68, 0.15); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin: 0 auto 14px auto; border: 1px solid rgba(239, 68, 68, 0.35);">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>

                    <h1 class="section-title" style="font-size: 1.35rem;">Account Activation Required</h1>
                    <p class="section-subtitle" style="margin-bottom: 16px;">
                        A 6-digit activation code was sent to your alternative email address:
                    </p>

                    <div style="background: #0d121d; border: 1px solid #1e293b; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.86rem; color: #94a3b8; width: 100%; box-sizing: border-box; text-align: center;">
                        <i class="fa-solid fa-envelope-open-text" style="color: #3b82f6; margin-right: 6px;"></i>
                        <strong><?php echo htmlspecialchars(maskEmailForDisplay($pendingAltEmail ?? 'Alternative Email')); ?></strong>
                    </div>

                    <form method="POST" action="?view=activation" style="width: 100%;">
                        <input type="hidden" name="action" value="verify_activation_code">
                        <input type="hidden" name="activation_user" value="<?php echo htmlspecialchars($pendingActivationUser ?? ''); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <div class="form-group">
                            <label for="activation_code" class="form-label" style="text-align: center; width: 100%; font-weight: 700;">Enter 6-Digit Activation Code</label>
                            <input type="text" id="activation_code" name="activation_code" class="form-input" placeholder="e.g. 849201" maxlength="6" pattern="[0-9]{6}" required style="text-align: center; letter-spacing: 8px; font-size: 1.4rem; font-weight: 700; font-family: monospace;" autofocus>
                        </div>

                        <button type="submit" class="btn-submit" style="margin-top: 12px; background: #dc2626;">
                            <i class="fa-solid fa-lock-open"></i> Verify &amp; Activate Account
                        </button>
                    </form>

                    <form method="POST" action="?view=activation" style="width: 100%; margin-top: 14px;">
                        <input type="hidden" name="action" value="resend_activation_code">
                        <input type="hidden" name="activation_user" value="<?php echo htmlspecialchars($pendingActivationUser ?? ''); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <button type="submit" style="background: none; border: none; color: #94a3b8; font-size: 0.82rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">
                            <i class="fa-solid fa-paper-plane"></i> Didn't get code? Resend to alternative email
                        </button>
                    </form>
                </div>

                <!-- VIEW 7: CREATE / SET ACCOUNT PASSWORD FORM -->
                <div id="set_passwordView" class="view-section <?php echo ($view === 'set_password') ? 'active' : ''; ?>">
                    <a href="#" onclick="switchView('staff'); return false;" class="back-link-left">
                        <i class="fa-solid fa-arrow-left"></i> Back to Sign In
                    </a>

                    <div class="shield-icon-box" style="background: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.35); color: #10b981;">
                        <i class="fa-solid fa-key"></i>
                    </div>

                    <h1 class="section-title">Create Password</h1>
                    <p class="section-subtitle">Set a secure password to complete your account setup</p>

                    <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 10px; padding: 10px 14px; margin-bottom: 20px; font-size: 0.88rem; color: #6ee7b7; text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; box-sizing: border-box;">
                        <i class="fa-solid fa-circle-check" style="color: #10b981;"></i>
                        <span>Verified Account: <strong style="color: #ffffff;"><?php echo htmlspecialchars($verifiedEmail !== '' ? $verifiedEmail : 'Your Account'); ?></strong></span>
                    </div>

                    <form method="POST" action="?view=set_password" style="width: 100%;">
                        <input type="hidden" name="action" value="set_account_password">
                        <input type="hidden" name="set_user" value="<?php echo htmlspecialchars($verifiedEmail); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <!-- New Password -->
                        <div class="form-group">
                            <label for="new_account_password" class="form-label">New Password</label>
                            <div class="input-relative">
                                <input 
                                    type="password" 
                                    id="new_account_password" 
                                    name="new_password" 
                                    class="form-input" 
                                    placeholder="At least 6 characters"
                                    minlength="6"
                                    required 
                                    autocomplete="new-password"
                                >
                                <button type="button" class="toggle-password-btn" onclick="togglePassVisibility('new_account_password', 'toggleNewPassIcon')" title="Toggle password visibility">
                                    <i class="fa-regular fa-eye" id="toggleNewPassIcon"></i>
                                </button>
                            </div>
                            <div class="helper-text">
                                <i class="fa-solid fa-lock"></i> Minimum 6 characters (letters and numbers recommended)
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="form-group">
                            <label for="confirm_account_password" class="form-label">Confirm Password</label>
                            <div class="input-relative">
                                <input 
                                    type="password" 
                                    id="confirm_account_password" 
                                    name="confirm_password" 
                                    class="form-input" 
                                    placeholder="Re-type new password"
                                    minlength="6"
                                    required 
                                    autocomplete="new-password"
                                >
                                <button type="button" class="toggle-password-btn" onclick="togglePassVisibility('confirm_account_password', 'toggleConfirmPassIcon')" title="Toggle password visibility">
                                    <i class="fa-regular fa-eye" id="toggleConfirmPassIcon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-submit" style="margin-top: 10px; background: #10b981; border-color: #10b981;">
                            <i class="fa-solid fa-circle-check" style="margin-right: 6px;"></i> Set Password &amp; Sign In
                        </button>
                    </form>
                </div>

            </div>
        </div>

        <!-- Bottom Fixed Footer Exactly Matching Design -->
        <footer class="bottom-footer">
            &copy; 2026 Falhen Productions. <a href="/privacy.php">Privacy</a> &middot; <a href="/terms.php">Terms</a>
        </footer>

    </div>

    <script>
        // Helper to toggle password visibility
        function togglePassVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (!input || !icon) return;
            const isPass = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPass ? 'text' : 'password');
            if (isPass) {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password Visibility Toggle for main login
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('togglePasswordIcon');

        if (toggleBtn && passwordInput && toggleIcon) {
            toggleBtn.addEventListener('click', function () {
                togglePassVisibility('password', 'togglePasswordIcon');
            });
        }

        // View Switcher Function
        function switchView(viewName) {
            const container = document.getElementById('loginContainer');
            if (viewName === 'choice') {
                container.classList.remove('narrow');
                container.classList.add('wide');
            } else {
                container.classList.remove('wide');
                container.classList.add('narrow');
            }

            const sections = document.querySelectorAll('.view-section');
            sections.forEach(sec => sec.classList.remove('active'));

            const target = document.getElementById(viewName + 'View');
            if (target) {
                target.classList.add('active');
            }

            // Update URL without page reload
            const url = new URL(window.location);
            if (viewName === 'choice') {
                url.searchParams.delete('view');
            } else {
                url.searchParams.set('view', viewName);
            }
            window.history.pushState({}, '', url);
        }
    </script>
</body>
</html>
