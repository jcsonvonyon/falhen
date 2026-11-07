<?php
/**
 * Cloudinary Media Helper & API Handler
 * Falhen Media
 */

require_once __DIR__ . '/../config/cloudinary.php';

/**
 * Check if a URL is hosted on Cloudinary
 */
function isCloudinaryUrl($url)
{
    return (bool) preg_match('/res\.cloudinary\.com/i', (string) $url);
}

/**
 * Transform & optimize image URL via Cloudinary CDN
 */
function getCloudinaryUrl($pathOrUrl, $transform = 'f_auto,q_auto')
{
    if (empty($pathOrUrl)) {
        return '/assets/img/hero.jpg';
    }

    $cloudName = CLOUDINARY_CLOUD_NAME;

    // If it's already a full Cloudinary URL
    if (isCloudinaryUrl($pathOrUrl)) {
        if (!empty($transform) && strpos($pathOrUrl, '/upload/') !== false && strpos($pathOrUrl, $transform) === false) {
            return str_replace('/upload/', '/upload/' . $transform . '/', $pathOrUrl);
        }
        return $pathOrUrl;
    }

    // If it's a Cloudinary public ID (no http, no slashes at start)
    if (!preg_match('/^https?:\/\//i', $pathOrUrl) && !preg_match('/^\//', $pathOrUrl)) {
        return "https://res.cloudinary.com/{$cloudName}/image/upload/{$transform}/{$pathOrUrl}";
    }

    // Return direct path or fetch URL fallback
    return $pathOrUrl;
}

/**
 * Upload local file or remote URL directly to Cloudinary account using API Signature
 */
function uploadToCloudinary($fileOrUrl, $folder = 'falhen', $customPublicId = null)
{
    $cloudName = CLOUDINARY_CLOUD_NAME;
    $apiKey = CLOUDINARY_API_KEY;
    $apiSecret = CLOUDINARY_API_SECRET;

    if (empty($cloudName) || empty($apiKey) || empty($apiSecret)) {
        return ['success' => false, 'message' => 'Cloudinary API credentials are missing.'];
    }

    // Auto-detect PDF or raw documents to prevent Cloudinary image 401 error
    $ext = strtolower(pathinfo((string) $fileOrUrl, PATHINFO_EXTENSION));
    $resourceType = 'image';
    if ($ext === 'pdf' || $ext === 'doc' || $ext === 'docx' || strpos((string) $fileOrUrl, '.pdf') !== false) {
        $resourceType = 'raw';
    } else if ($ext === 'mp4' || $ext === 'mov' || $ext === 'avi' || $ext === 'webm') {
        $resourceType = 'video';
    }

    $timestamp = time();
    $paramsToSign = [
        'folder' => $folder,
        'timestamp' => $timestamp
    ];
    if (!empty($customPublicId)) {
        $paramsToSign['public_id'] = $customPublicId;
    }
    ksort($paramsToSign);

    $stringToSign = "";
    foreach ($paramsToSign as $k => $v) {
        $stringToSign .= "{$k}={$v}&";
    }
    $stringToSign = rtrim($stringToSign, '&') . $apiSecret;
    $signature = sha1($stringToSign);

    $endpoint = "https://api.cloudinary.com/v1_1/{$cloudName}/{$resourceType}/upload";

    $postFields = [
        'api_key' => $apiKey,
        'timestamp' => $timestamp,
        'folder' => $folder,
        'signature' => $signature
    ];
    if (!empty($customPublicId)) {
        $postFields['public_id'] = $customPublicId;
    }

    if (preg_match('/^https?:\/\//i', $fileOrUrl) || preg_match('/^data:image\//i', $fileOrUrl)) {
        $postFields['file'] = $fileOrUrl;
    } else if (file_exists($fileOrUrl)) {
        $postFields['file'] = new CURLFile($fileOrUrl);
    } else {
        return ['success' => false, 'message' => 'Invalid file or URL for Cloudinary upload.'];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'message' => 'Curl upload error: ' . $error];
    }

    $data = json_decode($response, true);
    if (!empty($data['secure_url'])) {
        return [
            'success' => true,
            'url' => $data['secure_url'],
            'public_id' => $data['public_id'] ?? '',
            'format' => $data['format'] ?? '',
            'bytes' => $data['bytes'] ?? 0
        ];
    }

    return [
        'success' => false,
        'message' => $data['error']['message'] ?? 'Cloudinary upload failed.'
    ];
}

/**
 * Sync a Google Drive image URL to Cloudinary (with local map cache) so clients
 * receive lightweight, CDN-optimized previews and downloads.
 */
function syncDrivePhotoToCloudinary($photoUrl, $albumId = 0, $autoUpload = false)
{
    if (empty($photoUrl)) {
        return '/assets/img/portfolio/portfolio_halima.png';
    }

    if (isCloudinaryUrl($photoUrl)) {
        return getCloudinaryUrl($photoUrl, 'f_auto,q_auto');
    }

    // Check persistent sync map
    $mapFile = __DIR__ . '/../config/cloudinary_sync_map.json';
    $syncMap = file_exists($mapFile) ? @json_decode(file_get_contents($mapFile), true) : [];
    if (!is_array($syncMap)) {
        $syncMap = [];
    }

    $hashKey = md5($photoUrl);
    if (!empty($syncMap[$hashKey]['cloudinary_url'])) {
        return getCloudinaryUrl($syncMap[$hashKey]['cloudinary_url'], 'f_auto,q_auto');
    }

    // Optional on-demand upload when triggered from admin sync or background sync
    if ($autoUpload && preg_match('/^https?:\/\//i', $photoUrl)) {
        $folder = 'falhen/client_galleries/album_' . (int) $albumId;
        $uploaded = uploadToCloudinary($photoUrl, $folder);
        if (!empty($uploaded['success']) && !empty($uploaded['url'])) {
            $syncMap[$hashKey] = [
                'source_url' => $photoUrl,
                'cloudinary_url' => $uploaded['url'],
                'public_id' => $uploaded['public_id'] ?? '',
                'album_id' => (int) $albumId,
                'synced_at' => date('c')
            ];
            @file_put_contents($mapFile, json_encode($syncMap, JSON_PRETTY_PRINT));
            return getCloudinaryUrl($uploaded['url'], 'f_auto,q_auto');
        }
    }

    // If Google Drive lh3 thumbnail link, optimize width for lightweight delivery
    if (strpos($photoUrl, 'lh3.googleusercontent.com/d/') !== false) {
        return preg_replace('/=w\d+$/', '=w1200', $photoUrl);
    }

    return $photoUrl;
}

/**
 * Build structured lightweight gallery photo items (masonry preview, full-view HD, download URL)
 */
function buildClientGalleryPhotoItems(array $rawPhotos, $albumId = 0)
{
    $items = [];
    foreach (array_values($rawPhotos) as $idx => $rawUrl) {
        $syncedUrl = syncDrivePhotoToCloudinary($rawUrl, $albumId, false);
        $masonryUrl = isCloudinaryUrl($syncedUrl)
            ? getCloudinaryUrl($syncedUrl, 'f_auto,q_auto:eco,w_800,c_limit')
            : (strpos($syncedUrl, 'lh3.googleusercontent.com/d/') !== false
                ? preg_replace('/=w\d+$/', '=w800', $syncedUrl)
                : $syncedUrl);

        $fullViewUrl = isCloudinaryUrl($syncedUrl)
            ? getCloudinaryUrl($syncedUrl, 'f_auto,q_auto:good,w_1920,c_limit')
            : (strpos($syncedUrl, 'lh3.googleusercontent.com/d/') !== false
                ? preg_replace('/=w\d+$/', '=w1920', $syncedUrl)
                : $syncedUrl);

        // Assign deterministic natural aspect ratio for editorial Masonry variety
        $ratios = ['3 / 4', '4 / 5', '1 / 1', '4 / 3', '2 / 3', '5 / 4'];
        $aspectRatio = $ratios[$idx % count($ratios)];

        $items[] = [
            'index' => $idx,
            'number' => $idx + 1,
            'raw_url' => $rawUrl,
            'masonry_url' => $masonryUrl,
            'full_url' => $fullViewUrl,
            'download_url' => "/api/download-album.php?album_id=" . (int) $albumId . "&photo_index=" . $idx,
            'aspect_ratio' => $aspectRatio,
            'is_cloudinary' => isCloudinaryUrl($syncedUrl),
            'is_gdrive' => (strpos($rawUrl, 'googleusercontent.com') !== false || strpos($rawUrl, 'drive.google.com') !== false)
        ];
    }
    return $items;
}

/**
 * Synchronize a single photo asset to Cloudinary using an SEO-friendly public_id
 */
function syncSinglePhotoToCloudinary($photoUrlOrFile, $slug = 'general', $index = 1)
{
    $cleanSlug = preg_replace('/[^a-z0-9_-]/i', '-', strtolower(trim((string) $slug))) ?: 'gallery';
    $folder = 'falhen/client-galleries/' . $cleanSlug;
    $publicId = $cleanSlug . '-' . str_pad((string) $index, 2, '0', STR_PAD_LEFT);

    // Check persistent map cache
    $mapFile = __DIR__ . '/../config/cloudinary_sync_map.json';
    $syncMap = file_exists($mapFile) ? @json_decode(file_get_contents($mapFile), true) : [];
    if (!is_array($syncMap)) {
        $syncMap = [];
    }

    $hashKey = md5((string) $photoUrlOrFile);
    if (!empty($syncMap[$hashKey]['cloudinary_url'])) {
        return [
            'success' => true,
            'url' => $syncMap[$hashKey]['cloudinary_url'],
            'public_id' => $syncMap[$hashKey]['public_id'] ?? $publicId,
            'cached' => true
        ];
    }

    // If it's a Google Drive link, ensure direct download stream
    $targetSource = $photoUrlOrFile;
    if (strpos((string) $photoUrlOrFile, 'lh3.googleusercontent.com/d/') !== false) {
        $targetSource = preg_replace('/=w\d+$/', '=w2400', (string) $photoUrlOrFile);
    }

    $res = uploadToCloudinary($targetSource, $folder, $publicId);
    if (!empty($res['success']) && !empty($res['url'])) {
        $syncMap[$hashKey] = [
            'source_url' => $photoUrlOrFile,
            'cloudinary_url' => $res['url'],
            'public_id' => $res['public_id'] ?? $publicId,
            'slug' => $cleanSlug,
            'synced_at' => date('c')
        ];
        @file_put_contents($mapFile, json_encode($syncMap, JSON_PRETTY_PRINT));
        return [
            'success' => true,
            'url' => $res['url'],
            'public_id' => $res['public_id'] ?? $publicId,
            'cached' => false
        ];
    }

    return $res;
}

/**
 * Synchronize a full Google Drive folder to Cloudinary with SEO paths
 */
function syncGoogleDriveFolderToCloudinary($gdriveUrl, $slug = 'general', $limit = 50)
{
    if (!function_exists('getGoogleDriveFolderPhotos')) {
        require_once __DIR__ . '/../config/gdrive.php';
    }

    $rawPhotos = getGoogleDriveFolderPhotos($gdriveUrl);
    if (empty($rawPhotos)) {
        return [
            'success' => false,
            'message' => 'No photos found in Google Drive folder or folder is not public.'
        ];
    }

    $synced = [];
    $photosSlice = array_slice($rawPhotos, 0, $limit);

    foreach ($photosSlice as $i => $rawUrl) {
        $res = syncSinglePhotoToCloudinary($rawUrl, $slug, $i + 1);
        if (!empty($res['success']) && !empty($res['url'])) {
            $synced[] = $res['url'];
        } else {
            $synced[] = $rawUrl; // fallback to original
        }
    }

    return [
        'success' => true,
        'total_found' => count($rawPhotos),
        'synced_count' => count($synced),
        'photos' => $synced
    ];
}

