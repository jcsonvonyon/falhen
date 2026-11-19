<?php
/**
 * cron/sync_worker.php
 * Falhen Productions — Headless Cloudinary Background Synchronization Worker
 * Allows albums with 200+ photos to sync in the background without holding browser tabs open.
 */

// Allow long-running background execution
@ignore_user_abort(true);
@set_time_limit(900); // 15 minutes max
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/cloudinary.php';
require_once __DIR__ . '/../includes/cloudinary_helper.php';

// CRITICAL: Ensure session lock is NEVER held by background worker!
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Auth verification for HTTP invocation
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    $authKey = $_GET['key'] ?? $_POST['key'] ?? '';
    if ($authKey !== 'falhen_deploy_2026' && (!function_exists('isAdminLoggedIn') || !isAdminLoggedIn())) {
        http_response_code(403);
        die("Unauthorized background worker invocation.");
    }
}

$jobId = '';
if ($isCli) {
    $jobId = $argv[1] ?? '';
} else {
    $jobId = trim($_GET['job_id'] ?? $_POST['job_id'] ?? '');
}

$jobsFile = __DIR__ . '/../config/cloudinary_sync_jobs.json';
if (!file_exists($jobsFile)) {
    die("No active sync jobs found.\n");
}

$jobs = @json_decode(file_get_contents($jobsFile), true) ?: [];
if (empty($jobId)) {
    // Pick first pending or processing job
    foreach ($jobs as $id => $j) {
        if (($j['status'] ?? '') === 'queued' || ($j['status'] ?? '') === 'processing') {
            $jobId = $id;
            break;
        }
    }
}

if (empty($jobId) || empty($jobs[$jobId])) {
    die("Job ID [{$jobId}] not found or already completed.\n");
}

$job = &$jobs[$jobId];
$job['status'] = 'processing';
$job['updated_at'] = date('c');
@file_put_contents($jobsFile, json_encode($jobs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

$slug = $job['slug'] ?? 'gallery';
$albumId = (int)($job['album_id'] ?? 0);
$photos = $job['photos'] ?? [];
$total = count($photos);
$syncedPhotos = $job['synced_photos'] ?? [];
$startIndex = count($syncedPhotos) + 1;

echo "[" . date('Y-m-d H:i:s') . "] Background Sync Worker started for Job [{$jobId}] — Slug: {$slug}, Total: {$total}\n";

$processed = count($syncedPhotos);

foreach (array_slice($photos, $processed) as $rawPhoto) {
    $currIdx = $processed + 1;
    $syncRes = syncSinglePhotoToCloudinary($rawPhoto, $slug, $currIdx);
    
    if (!empty($syncRes['success']) && !empty($syncRes['url'])) {
        $syncedPhotos[] = $syncRes['url'];
    } else {
        $syncedPhotos[] = $rawPhoto; // Fallback
    }
    
    $processed++;
    $job['processed'] = $processed;
    $job['percent'] = ($total > 0) ? min(100, round(($processed / $total) * 100)) : 100;
    $job['synced_photos'] = $syncedPhotos;
    $job['updated_at'] = date('c');

    // Update job progress file every 4 photos
    if ($processed % 4 === 0 || $processed === $total) {
        @file_put_contents($jobsFile, json_encode($jobs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        echo "Progress: {$processed}/{$total} (" . $job['percent'] . "%)\n";
    }

    // Polite CPU & I/O throttle so background worker never spikes server load or slows down web visitors
    usleep(100000); // 100ms pause
}

$job['status'] = 'completed';
$job['percent'] = 100;
$job['completed_at'] = date('c');
@file_put_contents($jobsFile, json_encode($jobs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

// If an album ID is associated, persist synced photos directly into the portfolio repository!
if ($albumId > 0 && !empty($syncedPhotos)) {
    $settings = getSiteSettings();
    $portfolioItems = $settings['portfolio_items'] ?? [];
    $updated = false;

    foreach ($portfolioItems as &$pItem) {
        if ((int)($pItem['id'] ?? 0) === $albumId) {
            $pItem['gallery_photos'] = $syncedPhotos;
            if (empty($pItem['image']) && !empty($syncedPhotos[0])) {
                $pItem['image'] = $syncedPhotos[0];
            }
            $pItem['photosCount'] = count($syncedPhotos);
            $updated = true;
            break;
        }
    }
    if ($updated) {
        saveSiteSettings(['portfolio_items' => $portfolioItems]);
        echo "Successfully updated portfolio item [ID: {$albumId}] with " . count($syncedPhotos) . " synced photos.\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Sync Worker finished successfully for Job [{$jobId}].\n";
