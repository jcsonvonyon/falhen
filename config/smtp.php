<?php
/**
 * SMTP Mailer Configuration
 * Falhen Media
 */

define('SMTP_HOST', getenv('SMTP_HOST') ?: 'mail.falhenmedia.com');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 465));
define('SMTP_USER', getenv('SMTP_USER') ?: 'noreply@falhenmedia.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'vqC35q8osR4&c3M?');
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'ssl'); // 'ssl' (port 465) or 'tls' (port 587)
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'noreply@falhenmedia.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Falhen Media');
