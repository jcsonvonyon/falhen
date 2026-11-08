<?php
/**
 * api/download-album.php
 * Lightweight Cloudinary & Google Drive Packaged Bulk ZIP & Single Asset Downloader
 * Protected by the 2-Step Client Identity + 6-Digit OTP Verification Gate
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

$albumId = isset($_GET['album_id']) ? (int)$_GET['album_id'] : 0;
$photoIndex = isset($_GET['photo_index']) ? (int)$_GET['photo_index'] : -1;
$providedToken = trim($_GET['token'] ?? $_POST['token'] ?? ($_SERVER['HTTP_X_DOWNLOAD_TOKEN'] ?? ''));

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

// Resolve album photos (Google Drive synced -> gallery_photos -> curated fallback)
$rawPhotos = [];
if (!empty($currentAlbum['gdrive_url'])) {
    $rawPhotos = getGoogleDriveFolderPhotos($currentAlbum['gdrive_url']);
}
if (empty($rawPhotos) && !empty($currentAlbum['gallery_photos']) && is_array($currentAlbum['gallery_photos'])) {
    $rawPhotos = $currentAlbum['gallery_photos'];
}
if (empty($rawPhotos) && !empty($currentAlbum['photos']) && is_array($currentAlbum['photos'])) {
    $rawPhotos = $currentAlbum['photos'];
}
if (empty($rawPhotos)) {
    $rawPhotos = [
        'https://res.cloudinary.com/pnabfi91/image/upload/v1794096443/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-01.jpg',
        'https://res.cloudinary.com/pnabfi91/image/upload/v1794096445/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-02.jpg',
        'https://res.cloudinary.com/pnabfi91/image/upload/v1794096448/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-03.jpg',
        $coverImage
    ];
}

$photoItems = buildClientGalleryPhotoItems($rawPhotos, $albumId);

/**
 * Helper to resolve a photo item's binary content (local file first, then remote HTTP fetch)
 */
function fetchLightweightPhotoBytes(string $urlOrPath): ?string {
    // 1. Check if it maps to a local file in the project root
    if (strpos($urlOrPath, '/') === 0 && !preg_match('#^//#', $urlOrPath)) {
        $localPath = realpath(__DIR__ . '/..' . parse_url($urlOrPath, PHP_URL_PATH));
        $rootPath = realpath(__DIR__ . '/..');
        if ($localPath && $rootPath && strpos($localPath, $rootPath) === 0 && is_file($localPath)) {
            $data = @file_get_contents($localPath);
            if ($data !== false) {
                return $data;
            }
        }
    }

    // 2. Remote URL fetch (Cloudinary lightweight URL or Google Drive image)
    if (preg_match('#^https?://#i', $urlOrPath)) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 8,
                'user_agent' => 'FalhenMediaClientPortal/2.0',
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        $data = @file_get_contents($urlOrPath, false, $ctx);
        if ($data !== false && strlen($data) > 64) {
            return $data;
        }
    }

    // 3. Fallback to local default portfolio image so download never fails offline
    $fallbackLocal = __DIR__ . '/../assets/img/portfolio/king_david_01.jpg';
    if (is_file($fallbackLocal)) {
        return @file_get_contents($fallbackLocal) ?: null;
    }
    return null;
}

// Mode A: Single Photo Download (?photo_index=N)
if ($photoIndex >= 0 && isset($photoItems[$photoIndex])) {
    $targetItem = $photoItems[$photoIndex];
    $sourceUrl = $targetItem['masonry_url'] ?? $targetItem['raw_url'];
    $bytes = fetchLightweightPhotoBytes($sourceUrl);

    if ($bytes === null) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Photo asset could not be retrieved.']);
        exit;
    }

    $filename = sprintf('%s_Photo_%02d.jpg', $cleanAlbumSlug, $photoIndex + 1);
    header('Content-Type: image/jpeg');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: no-store');
    echo $bytes;
    exit;
}

// Mode B: Bulk Packaged ZIP Archive Download
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

$addedCount = 0;
foreach ($photoItems as $idx => $pItem) {
    // Prefer lightweight Cloudinary/local asset for fast bulk packaging
    $candidateUrl = $pItem['raw_url'];
    if ($pItem['is_cloudinary']) {
        $candidateUrl = $pItem['masonry_url'];
    }
    $bytes = fetchLightweightPhotoBytes($candidateUrl);
    if ($bytes !== null) {
        $entryName = sprintf('%s/%s_Photo_%02d.jpg', $cleanAlbumSlug, $cleanAlbumSlug, $idx + 1);
        $zip->addFromString($entryName, $bytes);
        $addedCount++;
    }
}

// Include a manifest receipt inside the ZIP without leaking private email addresses
$receiptText = "FALHEN MEDIA — CLIENT GALLERY ARCHIVE\n"
    . "=====================================\n"
    . "Album: " . $albumTitle . "\n"
    . "Client: " . ($_SESSION['registered_client_name'] ?? 'Verified Client') . "\n"
    . "Total Photos Packaged: " . $addedCount . "\n"
    . "Downloaded At: " . date('Y-m-d H:i:s T') . "\n"
    . "Verification: Private Folder OTP Verification Passed\n"
    . "Portal: https://client.falhen.com\n";
$zip->addFromString($cleanAlbumSlug . '/README_FALHEN_GALLERY.txt', $receiptText);
$zip->close();

$zipFilename = $cleanAlbumSlug . '_Falhen_Gallery.zip';
$zipSize = filesize($tmpZip);

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
header('Content-Length: ' . $zipSize);
header('Cache-Control: no-store, no-cache, must-revalidate');
readfile($tmpZip);
@unlink($tmpZip);
exit;
