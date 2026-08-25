<?php
/**
 * Cloudinary CDN Configuration
 * Falhen Media
 */

define('CLOUDINARY_CLOUD_NAME', getenv('CLOUDINARY_CLOUD_NAME') ?: 'pnabfi91');
define('CLOUDINARY_API_KEY', getenv('CLOUDINARY_API_KEY') ?: '256358136624942');
define('CLOUDINARY_API_SECRET', getenv('CLOUDINARY_API_SECRET') ?: 'WMCn-3aZDtDrKUyT7al-PUWzzT8');
define('CLOUDINARY_BASE_URL', 'https://res.cloudinary.com/' . CLOUDINARY_CLOUD_NAME . '/');
