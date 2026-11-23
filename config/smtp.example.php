<?php
/**
 * SMTP Configuration Template
 * Copy this file to smtp.php and provide your live provider credentials.
 */
return [
    'smtp_host'       => 'smtp.resend.com',
    'smtp_port'       => 465,
    'smtp_secure'     => 'ssl',
    'smtp_auth'       => true,
    'smtp_user'       => 'resend',
    'smtp_pass'       => 'your_resend_api_key_here',
    'smtp_from_email' => 'hello@falhen.com',
    'smtp_from_name'  => 'Falhen Productions'
];
