<?php
/**
 * Vendor Portal Logout
 * Falhen Media
 */
require_once __DIR__ . '/../includes/functions.php';

clearVendorSession();

header('Location: /admin/login.php?view=vendor&logged_out=1');
exit;
