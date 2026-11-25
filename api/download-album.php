<?php
/**
 * api/download-album.php
 * High-Speed Parallel Bulk ZIP & Single Asset Downloader (Zero-Downscale Master Quality)
 * Protected by the 2-Step Client Identity + 6-Digit OTP Verification Gate
 * Features:
 * - Immediate session write lock release (prevents Cloudflare 524 timeouts on subsequent page access)
 * - Zero-RAM disk streaming multi-cURL packager (streams directly to disk, uses < 15MB RAM)
 * - Asynchronous background packaging worker support via CLI execution
 * - Server-side persistent archive caching in storage/archives/ (instant sub-second downloads)
 * - Direct Cloudinary edge delivery (fl_attachment) for instant single photo downloads
 * - Asynchronous status/preparation endpoint (?action=status / ?action=prepare)
 */

// Allow ample execution time and memory for parallel packaging
@set_time_limit(600);
@ini_set('memory_limit', '512M');
@ignore_user_abort(true);
@apache_setenv('no-gzip', '1');
@apache_setenv('noabort', '1');
@apache_setenv('noconntimeout', '1');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

$isCli = (php_sapi_name() === 'cli');
$albumId = isset($_GET['album_id']) ? (int)$_GET['album_id'] : 0;
$photoIndex = isset($_GET['photo_index']) ? (int)$_GET['photo_index'] : -1;
$providedToken = trim($_GET['token'] ?? $_POST['token'] ?? ($_SERVER['HTTP_X_DOWNLOAD_TOKEN'] ?? ''));
$action = trim($_GET['action'] ?? $_POST['action'] ?? '');

if ($isCli) {
    if (isset($argv) && is_array($argv)) {
        foreach ($argv as $arg) {
            if (preg_match('/^--album(?:_id)?=(\d+)/i', $arg, $m)) {
                $albumId = (int)$m[1];
            }
        }
    }
    $albumVerified = true;
    $tokenMatches = true;
} else {
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

    // Persistent Token Verification (Database & JSON store for direct email delivery links)
    $verifiedTokenData = null;
    if (!$albumVerified && !$tokenMatches && !empty($providedToken)) {
        if (function_exists('verifyClientDownloadToken')) {
            $verifiedTokenData = verifyClientDownloadToken($providedToken, $albumId);
            if ($verifiedTokenData !== null) {
                $tokenMatches = true;
                $albumVerified = true;
                if ($albumId === 0 && !empty($verifiedTokenData['album_id'])) {
                    $albumId = (int)$verifiedTokenData['album_id'];
                }
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

    // Extract client logging details from session or verified token BEFORE releasing write lock
    $clientLogInfo = [
        'client_name'     => $verifiedTokenData['name'] ?? ($_SESSION['registered_client_name'] ?? 'Authorized Client'),
        'client_email'    => $verifiedTokenData['email'] ?? ($_SESSION['download_email'] ?? ($_SESSION['portal_lookup_email'] ?? ($_SESSION['verified_client_emails'][$albumId] ?? ''))),
        'client_phone'    => $verifiedTokenData['phone'] ?? ($_SESSION['registered_client_phone'] ?? ''),
        'organization'    => $verifiedTokenData['organization'] ?? ($_SESSION['download_organization'] ?? null),
        'download_reason' => $verifiedTokenData['download_reason'] ?? ($_SESSION['download_reason'] ?? null),
        'access_tier'     => $verifiedTokenData['access_tier'] ?? ($_SESSION['download_access_tier'] ?? 'authorized_client'),
    ];

    // Re-hydrate session for this browser if verified via persistent token
    if ($verifiedTokenData !== null && session_status() === PHP_SESSION_ACTIVE) {
        if (!isset($_SESSION['verified_albums']) || !is_array($_SESSION['verified_albums'])) {
            $_SESSION['verified_albums'] = [];
        }
        $_SESSION['verified_albums'][$albumId] = true;
        if (!isset($_SESSION['verified_album_tokens']) || !is_array($_SESSION['verified_album_tokens'])) {
            $_SESSION['verified_album_tokens'] = [];
        }
        $_SESSION['verified_album_tokens'][$albumId] = $providedToken;
        if (!empty($clientLogInfo['client_name'])) {
            $_SESSION['registered_client_name'] = $clientLogInfo['client_name'];
        }
        if (!empty($clientLogInfo['client_email'])) {
            $_SESSION['download_email'] = $clientLogInfo['client_email'];
        }
        if (!empty($clientLogInfo['client_phone'])) {
            $_SESSION['registered_client_phone'] = $clientLogInfo['client_phone'];
        }
    }

    // CRITICAL: Immediately release PHP session write lock!
    // This guarantees that any concurrent page loads or portal visits by the client never block waiting for session locks.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

// Fallback client logging details for CLI builds
if (!isset($clientLogInfo)) {
    $clientLogInfo = [
        'client_name'     => 'Verified Client',
        'client_email'    => 'client@falhen.com',
        'client_phone'    => '',
        'organization'    => null,
        'download_reason' => null,
        'access_tier'     => 'authorized_client',
    ];
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
        if ($isCli) {
            echo "Error: No downloadable photos found for album {$albumId}\n";
            exit(1);
        }
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
$lockFile = $archiveDir . '/build_album_' . $albumId . '.lock';

// Action: Query Status (?action=status)
if ($action === 'status') {
    $isReady = file_exists($cacheZipFile) && (filesize($cacheZipFile) > 1024);
    $isPackaging = !$isReady && file_exists($lockFile) && (time() - filemtime($lockFile) < 180);
    header('Content-Type: application/json');
    echo json_encode([
        'success'      => true,
        'ready'        => $isReady,
        'packaging'    => $isPackaging,
        'album_id'     => $albumId,
        'photo_count'  => $photoCount,
        'size_bytes'   => $isReady ? filesize($cacheZipFile) : 0,
        'download_url' => '/api/download-album.php?album_id=' . $albumId . '&token=' . urlencode($providedToken)
    ]);
    exit;
}

// Action: Prepare Archive (?action=prepare)
if ($action === 'prepare') {
    $isReady = file_exists($cacheZipFile) && (filesize($cacheZipFile) > 1024);
    if ($isReady) {
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

    // Trigger background CLI worker if not already running
    $isRunning = file_exists($lockFile) && (time() - filemtime($lockFile) < 180);
    if (!$isRunning) {
        @touch($lockFile);
        $scriptPath = __FILE__;
        exec("php " . escapeshellarg($scriptPath) . " --album_id={$albumId} > /dev/null 2>&1 &");
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success'      => true,
        'ready'        => false,
        'packaging'    => true,
        'album_id'     => $albumId,
        'photo_count'  => $photoCount,
        'download_url' => '/api/download-album.php?album_id=' . $albumId . '&token=' . urlencode($providedToken)
    ]);
    exit;
}

/**
 * Download photo assets in parallel batches directly to temporary disk files,
 * adding them to ZipArchive via addFile() to guarantee minimal PHP memory usage (< 15MB).
 *
 * @param array $urls Map of [index => URL]
 * @param ZipArchive $zip Target open ZIP archive
 * @param string $cleanAlbumSlug
 * @param string $tmpDir Temporary directory for downloaded assets
 * @param int $concurrency Max parallel sockets (12 recommended for stability)
 * @return int Total photos successfully added
 */
function packagePhotosToZipInParallel(array $urls, ZipArchive $zip, string $cleanAlbumSlug, string $tmpDir, int $concurrency = 12): int {
    $addedCount = 0;
    $chunks = array_chunk($urls, $concurrency, true);

    foreach ($chunks as $chunk) {
        $mh = curl_multi_init();
        $handles = [];
        $fileHandles = [];
        $tmpFiles = [];

        foreach ($chunk as $idx => $urlOrPath) {
            $tmpFile = $tmpDir . '/photo_' . $idx . '_' . uniqid() . '.jpg';
            $tmpFiles[$idx] = $tmpFile;

            // Local file resolution shortcut
            if (strpos($urlOrPath, '/') === 0 && !preg_match('#^//#', $urlOrPath)) {
                $localPath = realpath(__DIR__ . '/..' . parse_url($urlOrPath, PHP_URL_PATH));
                $rootPath = realpath(__DIR__ . '/..');
                if ($localPath && $rootPath && strpos($localPath, $rootPath) === 0 && is_file($localPath)) {
                    if (@copy($localPath, $tmpFile)) {
                        $entryName = sprintf('%s/%s_Photo_%02d.jpg', $cleanAlbumSlug, $cleanAlbumSlug, $idx + 1);
                        $zip->addFile($tmpFile, $entryName);
                        $addedCount++;
                        continue;
                    }
                }
            }

            if (!preg_match('#^https?://#i', $urlOrPath)) {
                continue;
            }

            $fp = @fopen($tmpFile, 'wb');
            if (!$fp) {
                continue;
            }
            $fileHandles[$idx] = $fp;

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $urlOrPath,
                CURLOPT_FILE           => $fp, // Direct disk stream! Zero PHP RAM consumed.
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 4,
                CURLOPT_TIMEOUT        => 30,
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
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
                if (isset($fileHandles[$idx])) {
                    @fclose($fileHandles[$idx]);
                }

                $tmpFile = $tmpFiles[$idx] ?? '';
                if ($httpCode === 200 && file_exists($tmpFile) && filesize($tmpFile) > 64) {
                    $entryName = sprintf('%s/%s_Photo_%02d.jpg', $cleanAlbumSlug, $cleanAlbumSlug, $idx + 1);
                    $zip->addFile($tmpFile, $entryName);
                    $addedCount++;
                } else {
                    if (file_exists($tmpFile)) {
                        @unlink($tmpFile);
                    }
                }
            }
        }
        curl_multi_close($mh);
    }

    return $addedCount;
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
    $tmpDir = sys_get_temp_dir() . '/falhen_single_' . uniqid();
    @mkdir($tmpDir, 0755, true);
    $tmpFile = $tmpDir . '/single_photo.jpg';

    $fp = @fopen($tmpFile, 'wb');
    if ($fp) {
        $ch = curl_init($candidateUrl);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'FalhenProductionsClientPortal/3.0',
        ]);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    if (!file_exists($tmpFile) || filesize($tmpFile) < 64) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Photo asset could not be retrieved.']);
        exit;
    }

    header('Content-Type: image/jpeg');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmpFile));
    header('Cache-Control: no-store');
    readfile($tmpFile);
    @unlink($tmpFile);
    @rmdir($tmpDir);
    exit;
}

// Mode B: Bulk Packaged ZIP Archive Download

// 1. FAST PATH: Serve directly from persistent server disk cache in < 0.1s
if (file_exists($cacheZipFile) && (filesize($cacheZipFile) > 1024)) {
    if ($isCli) {
        echo "[CLI OK] Album #{$albumId} already cached at {$cacheZipFile} (" . filesize($cacheZipFile) . " bytes)\n";
        exit(0);
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

// 2. BUILD PATH: Assemble ZIP using zero-RAM disk-streaming engine
if (!class_exists('ZipArchive')) {
    if ($isCli) {
        echo "Error: ZipArchive extension is not available\n";
        exit(1);
    }
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ZipArchive extension is not available on this server.']);
    exit;
}

$tmpPhotoDir = sys_get_temp_dir() . '/falhen_pack_' . $albumId . '_' . uniqid();
if (!is_dir($tmpPhotoDir)) {
    @mkdir($tmpPhotoDir, 0755, true);
}

$tmpZip = tempnam(sys_get_temp_dir(), 'falhen_album_');
$zip = new ZipArchive();
if ($zip->open($tmpZip, ZipArchive::OVERWRITE) !== true) {
    if ($isCli) {
        echo "Error: Could not initialize ZIP archive\n";
        exit(1);
    }
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Could not initialize ZIP archive.']);
    exit;
}

// Prepare full-resolution URLs (prefer native high-res, strip any eco/downscale filters)
$urlsToDownload = [];
foreach ($photoItems as $idx => $pItem) {
    $url = $pItem['raw_url'];
    if (!empty($pItem['is_cloudinary'])) {
        $url = preg_replace('#/upload/(?:[a-zA-Z0-9_,:]+/)+(v\d+/)#', '/upload/$1', $url);
    } elseif (strpos($url, 'lh3.googleusercontent.com/d/') !== false) {
        $url = preg_replace('/=w\d+.*$/', '=w3840', $url); // 4K Ultra-HD native stream
    }
    $urlsToDownload[$idx] = $url;
}

// Fetch and add all photos in parallel directly to temporary disk files
$addedCount = packagePhotosToZipInParallel($urlsToDownload, $zip, $cleanAlbumSlug, $tmpPhotoDir, 12);

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

// Clean up temporary photo disk files
$downloadedFiles = @glob($tmpPhotoDir . '/*');
if ($downloadedFiles) {
    foreach ($downloadedFiles as $f) {
        @unlink($f);
    }
}
@rmdir($tmpPhotoDir);

$zipSize = file_exists($tmpZip) ? filesize($tmpZip) : 0;

// Store to persistent server archive cache for future instant downloads
if ($addedCount > 0 && $zipSize > 1024) {
    @copy($tmpZip, $cacheZipFile);
    if (file_exists($lockFile)) {
        @unlink($lockFile);
    }
}

if ($isCli) {
    echo "[CLI SUCCESS] Album #{$albumId} packaged ({$addedCount} photos, {$zipSize} bytes) into {$cacheZipFile}\n";
    @unlink($tmpZip);
    exit(0);
}

if (function_exists('logClientDownload')) {
    logClientDownload(array_merge($clientLogInfo, [
        'email_sent'    => 1,
        'album_id'      => $albumId,
        'album_title'   => $albumTitle,
        'download_type' => sprintf('Full Gallery ZIP (%d photos) [Fresh Package]', $addedCount)
    ]));
}

// Deliver ZIP file directly to browser
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
header('Content-Length: ' . $zipSize);
header('Cache-Control: no-store, no-cache, must-revalidate');
readfile($tmpZip);
@unlink($tmpZip);
exit;
