<?php
/**
 * Vendor Portal Login Redirect
 * Falhen Media
 */
require_once __DIR__ . '/../includes/functions.php';

if (isVendorLoggedIn()) {
    header('Location: /vendor/index.php');
    exit;
}

$targetUrl = '/admin/login.php?view=vendor';
if (!empty($_GET['logged_out'])) {
    $targetUrl .= '&logged_out=1';
}
if (!empty($_GET['error'])) {
    $targetUrl .= '&error=' . urlencode($_GET['error']);
}

header('Location: ' . $targetUrl);
exit;
