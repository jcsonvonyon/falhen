<?php
/**
 * SMTP & Transactional Mail Configuration Template — Falhen Productions
 *
 * Copy this file to `config/smtp.php` (ignored by git) and fill in your live credentials,
 * OR set the matching environment variables in your project `.env` file.
 *
 * Transport Priority Order (handled automatically by includes/smtp_mailer.php):
 *   1. MXroute Authenticated SMTP (PRIMARY — full SPF/DKIM alignment for falhen.com)
 *   2. Resend REST API            (FAST FAILOVER — HTTPS port 443, immune to SMTP port blocks)
 *   3. Configured Socket SMTP     (SECONDARY SMTP fallback)
 *   4. Native PHP mail()          (LAST-RESORT fallback)
 */
return [
    // --- 1. Primary Transport: MXroute Authenticated SMTP (Recommended for falhen.com) ---
    'MXROUTE_SMTP_HOST'   => 'sunfire.mxrouting.net',
    'MXROUTE_SMTP_PORT'   => 465,          // 465 (ssl) or 587 (tls / STARTTLS)
    'MXROUTE_SMTP_SECURE' => 'ssl',        // 'ssl' or 'tls'
    'MXROUTE_SMTP_USER'   => 'noreply@falhen.com',
    'MXROUTE_SMTP_PASS'   => 'your_mxroute_mailbox_password_here',

    // --- 2. Failover Transport: Resend REST API (https://resend.com) ---
    'RESEND_API_KEY'      => 're_your_resend_api_key_here',

    // --- 3. Generic Socket SMTP Fallback ---
    'SMTP_HOST'           => 'smtp.resend.com',
    'SMTP_PORT'           => 465,
    'SMTP_SECURE'         => 'ssl',
    'SMTP_USER'           => 'resend',
    'SMTP_PASS'           => 're_your_resend_api_key_here',

    // --- Sender Identity & Driver Preference ---
    'SMTP_FROM_EMAIL'     => 'noreply@falhen.com',
    'SMTP_FROM_NAME'      => 'Falhen Productions',
    'MAIL_DRIVER'         => 'mxroute',    // 'mxroute' (recommended) or 'resend'
];
