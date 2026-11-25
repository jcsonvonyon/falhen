<?php
/**
 * api/download-album.php
 * High-Speed Parallel Bulk ZIP & Single Asset Downloader (Zero-Downscale Master Quality)
 * Protected by the 2-Step Client Identity + 6-Digit OTP Verification Gate
 * Features:
 * - Immediate session write lock release (prevents Cloudflare 524 timeouts on subsequent page access)
 * - Parallel curl_multi download pool for packaging (10x-20x faster than single-threaded HTTP)
 * - Server-side persistent archive caching (instant sub-second downloads on subsequent requests)
 * - Direct Cloudinary edge delivery (fl_attachment) for instant single photo downloads
 * - Asynchronous status/preparation endpoint (?action=status / ?action=prepare)
 */

// Allow ample execution time and memory for parallel packaging
@set_time_limit(300);
@ini_set('memory_limit', '512M');
@ignore_user_abort(true);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

$albumId = isset($_GET['album_id']) ? (int)$_GET['album_id'] : 0;
$photoIndex = isset($_GET['photo_index']) ? (int)$_GET['photo_index'] : -1;
$providedToken = trim($_GET['token'] ?? $_POST['token'] ?? ($_SERVER['HTTP_X_DOWNLOAD_TOKEN'] ?? ''));
$action = trim($_GET['action'] ?? $_POST['action'] ?? '');

// Verify folder-scoped OTP authorization or signed download token for THIS album
$albumVerified = !empty($_SESSION['verified_albums'][$albumId]);
$albumToken = $_SESSION['verified_album_tokens'][$albumId] ?? '';
$tokenMatches = (!empty($providedToken) && !empty($albumToken) && hash_equals($albumToken, $providedToken));

// Backward compatibility check for single active verification session strictly matching album
if (!$albumVerified && !$tokenMatches && !empty($_SESSION['client_download_token']) && !empty($providedToken)) {
    if (hash_equals($_SESSION['client_download_token'], $providedToken)) {
        $lastAlbId = (int)($_SESSION['last_verified_album_id'] ?? 0);
        if ($lastAlbId === 0 || $lastAlbId === $albumId) {
            $tokenMatches = true;
        }
    }
}

if (!$albumVerified && !$tokenMatches) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => 'DOWNLOAD_GATE_LOCKED',
        'message' => 'Security verification required. Please verify your Name, Email, Phone Number, and 6-digit OTP code for this specific gallery before downloading.'
    ]);
    exit;
}

// Extract client logging details from session BEFORE releasing write lock
$clientLogInfo = [
    'client_name'     => $_SESSION['registered_client_name'] ?? 'Authorized Client',
    'client_email'    => $_SESSION['download_email'] ?? ($_SESSION['portal_lookup_email'] ?? ($_SESSION['verified_client_emails'][$albumId] ?? '')),
    'client_phone'    => $_SESSION['registered_client_phone'] ?? '',
    'organization'    => $_SESSION['download_organization'] ?? null,
    'download_reason' => $_SESSION['download_reason'] ?? null,
    'access_tier'     => $_SESSION['download_access_tier'] ?? 'authorized_client',
];

// CRITICAL: Immediately release PHP session write lock!
// This guarantees that any concurrent page loads or portal visits by the client never block waiting for session locks.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Resolve album from Portfolio Repository
$masterPortfolioItems = getPortfolioRepo();
$currentAlbum = null;

foreach ($masterPortfolioItems as $item) {
    if ((int)($item['id'] ?? 0) === $albumId) {
        $currentAlbum = $item;
        break;
    }
}
if (!$currentAlbum && !empty($masterPortfolioItems)) {
    $currentAlbum = reset($masterPortfolioItems);
    $albumId = (int)($currentAlbum['id'] ?? 1);
}

$albumTitle = $currentAlbum['title'] ?? 'Falhen_Client_Gallery';
$cleanAlbumSlug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $albumTitle);
$coverImage = getCloudinaryUrl($currentAlbum['image'] ?? $currentAlbum['cover'] ?? 'https://res.cloudinary.com/pnabfi91/image/upload/v1791224271/falhen/portfolio/ysfriy8jlhhkcuzl8j16.jpg');

// Resolve album photos (prefer Cloudinary gallery_photos -> Google Drive synced -> curated fallback)
$rawPhotos = [];
if (!empty($currentAlbum['gallery_photos']) && is_array($currentAlbum['gallery_photos'])) {
    $rawPhotos = $currentAlbum['gallery_photos'];
} elseif (!empty($currentAlbum['gdrive_url'])) {
    $rawPhotos = getGoogleDriveFolderPhotos($currentAlbum['gdrive_url']);
} elseif (!empty($currentAlbum['photos']) && is_array($currentAlbum['photos'])) {
    $rawPhotos = $currentAlbum['photos'];
}
if (empty($rawPhotos)) {
    if (!empty($coverImage)) {
        $rawPhotos = [$coverImage];
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No downloadable photo assets found for this gallery.']);
        exit;
    }
}

$photoItems = buildClientGalleryPhotoItems($rawPhotos, $albumId);
$photoCount = count($photoItems);

// Persistent server-side ZIP archive cache path
$archiveDir = __DIR__ . '/../storage/archives';
if (!is_dir($archiveDir)) {
    @mkdir($archiveDir, 0755, true);
}
$archiveHash = substr(md5($albumTitle . '_' . json_encode($rawPhotos)), 0, 12);
$cacheZipFile = $archiveDir . '/falhen_gallery_album_' . $albumId . '_' . $photoCount . '_' . $archiveHash . '.zip';
$zipFilename = $cleanAlbumSlug . '_Falhen_Gallery.zip';

// Action: Query or Prepare Status (?action=status or ?action=prepare)
if ($action === 'status') {
    $isReady = file_exists($cacheZipFile) && (filesize($cacheZipFile) > 1024);
    header('Content-Type: application/json');
    echo json_encode([
        'success'      => true,
        'ready'        => $isReady,
        'album_id'     => $albumId,
        'photo_count'  => $photoCount,
        'size_bytes'   => $isReady ? filesize($cacheZipFile) : 0,
        'download_url' => '/api/download-album.php?album_id=' . $albumId . '&token=' . urlencode($providedToken)
    ]);
    exit;
}

/**
 * Helper to download multiple photo assets concurrently using curl_multi
 * @param array $urls Map of [index => URL]
 * @param int $concurrency Max parallel sockets
 * @return array Map of [index => binary_string|null]
 */
function downloadPhotosInParallel(array $urls, int $concurrency = 16): array {
    $results = [];
    $chunks = array_chunk($urls, $concurrency, true);

    foreach ($chunks as $chunk) {
        $mh = curl_multi_init();
        $handles = [];

        foreach ($chunk as $idx => $urlOrPath) {
            // Local file resolution shortcut
            if (strpos($urlOrPath, '/') === 0 && !preg_match('#^//#', $urlOrPath)) {
                $localPath = realpath(__DIR__ . '/..' . parse_url($urlOrPath, PHP_URL_PATH));
                $rootPath = realpath(__DIR__ . '/..');
                if ($localPath && $rootPath && strpos($localPath, $rootPath) === 0 && is_file($localPath)) {
                    $localData = @file_get_contents($localPath);
                    if ($localData !== false) {
                        $results[$idx] = $localData;
                        continue;
                    }
                }
            }

            if (!preg_match('#^https?://#i', $urlOrPath)) {
                $results[$idx] = null;
                continue;
            }

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $urlOrPath,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 4,
                CURLOPT_TIMEOUT        => 25,
                CURLOPT_CONNECTTIMEOUT => 6,
                CURLOPT_USERAGENT      => 'FalhenProductionsClientPortal/3.0',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$idx] = $ch;
        }

        if (!empty($handles)) {
            $running = null;
            do {
                $status = curl_multi_exec($mh, $running);
                if ($running > 0) {
                    curl_multi_select($mh, 0.05);
                }
            } while ($running > 0 && $status === CURLM_OK);

            foreach ($handles as $idx => $ch) {
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $content = curl_multi_getcontent($ch);
                if ($httpCode === 200 && !empty($content) && strlen($content) > 64) {
                    $results[$idx] = $content;
                } else {
                    $results[$idx] = null;
                }
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }
        }
        curl_multi_close($mh);
    }

    return $results;
}

// Mode A: Single Photo Download (?photo_index=N)
if ($photoIndex >= 0 && isset($photoItems[$photoIndex])) {
    $targetItem = $photoItems[$photoIndex];
    // Master high-resolution image URL (no downscaling, no eco compression)
    $candidateUrl = $targetItem['raw_url'];
    if (!empty($targetItem['is_cloudinary'])) {
        // Strip any intermediate transformations to deliver the original full-resolution master
        $candidateUrl = preg_replace('#/upload/(?:[a-zA-Z0-9_,:]+/)+(v\d+/)#', '/upload/$1', $candidateUrl);
    }

    $filename = sprintf('%s_Photo_%02d.jpg', $cleanAlbumSlug, $photoIndex + 1);

    if (function_exists('logClientDownload')) {
        logClientDownload(array_merge($clientLogInfo, [
            'email_sent'    => 0,
            'album_id'      => $albumId,
            'album_title'   => $albumTitle,
            'download_type' => sprintf('Single Photo #%02d', $photoIndex + 1)
        ]));
    }

    // Direct Cloudinary edge attachment redirect: Zero server load, instant high-speed download
    if (isCloudinaryUrl($candidateUrl)) {
        $attachmentUrl = getCloudinaryUrl($candidateUrl, 'fl_attachment');
        header('Location: ' . $attachmentUrl);
        exit;
    }

    // Google Drive direct export redirect
    $gdriveId = extractGoogleDriveFileId($candidateUrl);
    if (!empty($gdriveId)) {
        header('Location: https://drive.google.com/uc?export=download&id=' . urlencode($gdriveId));
        exit;
    }

    // Fallback streaming for local or other assets
    $parallelRes = downloadPhotosInParallel([$candidateUrl], 1);
    $bytes = $parallelRes[0] ?? null;

    if ($bytes === null) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Photo asset could not be retrieved.']);
        exit;
    }

    header('Content-Type: image/jpeg');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: no-store');
    echo $bytes;
    exit;
}

// Mode B: Bulk Packaged ZIP Archive Download

// 1. FAST PATH: Serve directly from persistent server disk cache in < 0.1s
if (file_exists($cacheZipFile) && (filesize($cacheZipFile) > 1024)) {
    if ($action === 'prepare') {
        header('Content-Type: application/json');
        echo json_encode([
            'success'      => true,
            'ready'        => true,
            'album_id'     => $albumId,
            'photo_count'  => $photoCount,
            'size_bytes'   => filesize($cacheZipFile),
            'download_url' => '/api/download-album.php?album_id=' . $albumId . '&token=' . urlencode($providedToken)
        ]);
        exit;
    }

    if (function_exists('logClientDownload')) {
        logClientDownload(array_merge($clientLogInfo, [
            'email_sent'    => 1,
            'album_id'      => $albumId,
            'album_title'   => $albumTitle,
            'download_type' => sprintf('Full Gallery ZIP (%d photos) [Cached]', $photoCount)
        ]));
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
    header('Content-Length: ' . filesize($cacheZipFile));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    readfile($cacheZipFile);
    exit;
}

// 2. BUILD PATH: Assemble ZIP using high-speed parallel multi-cURL
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ZipArchive extension is not available on this server.']);
    exit;
}

$tmpZip = tempnam(sys_get_temp_dir(), 'falhen_album_');
$zip = new ZipArchive();
if ($zip->open($tmpZip, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Could not initialize ZIP archive.']);
    exit;
}

// Prepare full-resolution URLs (strip any downscaling transformations to guarantee studio master quality)
$urlsToDownload = [];
foreach ($photoItems as $idx => $pItem) {
    $url = $pItem['raw_url'];
    if (!empty($pItem['is_cloudinary'])) {
        $url = preg_replace('#/upload/(?:[a-zA-Z0-9_,:]+/)+(v\d+/)#', '/upload/$1', $url);
    } elseif (strpos($url, 'lh3.googleusercontent.com/d/') !== false) {
        $url = preg_replace('/=w\d+.*$/', '=w6000', $url);
    }
    $urlsToDownload[$idx] = $url;
}

// Fetch all photos in parallel concurrent batches (16 parallel connections)
$downloadedPhotos = downloadPhotosInParallel($urlsToDownload, 16);

$addedCount = 0;
foreach ($downloadedPhotos as $idx => $bytes) {
    if ($bytes !== null) {
        $entryName = sprintf('%s/%s_Photo_%02d.jpg', $cleanAlbumSlug, $cleanAlbumSlug, $idx + 1);
        $zip->addFromString($entryName, $bytes);
        $addedCount++;
    }
}

// Include a studio certificate receipt inside the ZIP
$receiptText = "FALHEN PRODUCTIONS — CLIENT GALLERY ARCHIVE\n"
    . "=====================================\n"
    . "Album: " . $albumTitle . "\n"
    . "Client: " . ($clientLogInfo['client_name'] ?? 'Verified Client') . "\n"
    . "Total Photos Packaged: " . $addedCount . "\n"
    . "Downloaded At: " . date('Y-m-d H:i:s T') . "\n"
    . "Quality: Studio Master High-Resolution\n"
    . "Portal: https://client.falhen.com\n";
$zip->addFromString($cleanAlbumSlug . '/README_FALHEN_GALLERY.txt', $receiptText);
$zip->close();

$zipSize = filesize($tmpZip);

// Store to persistent server archive cache for future instant downloads
if ($addedCount > 0 && $zipSize > 1024) {
    @copy($tmpZip, $cacheZipFile);
}

if (function_exists('logClientDownload')) {
    logClientDownload(array_merge($clientLogInfo, [
        'email_sent'    => 1,
        'album_id'      => $albumId,
        'album_title'   => $albumTitle,
        'download_type' => sprintf('Full Gallery ZIP (%d photos) [Fresh Package]', $addedCount)
    ]));
}

// If caller just wanted to prepare the archive via AJAX
if ($action === 'prepare') {
    @unlink($tmpZip);
    header('Content-Type: application/json');
    echo json_encode([
        'success'      => true,
        'ready'        => true,
        'album_id'     => $albumId,
        'photo_count'  => $addedCount,
        'size_bytes'   => $zipSize,
        'download_url' => '/api/download-album.php?album_id=' . $albumId . '&token=' . urlencode($providedToken)
    ]);
    exit;
}

// Deliver ZIP file directly to browser
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
header('Content-Length: ' . $zipSize);
header('Cache-Control: no-store, no-cache, must-revalidate');
readfile($tmpZip);
@unlink($tmpZip);
exit;
