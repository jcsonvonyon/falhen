<?php
/**
 * api/admin-client-gallery.php
 * Falhen Media — Admin AJAX Endpoint for Client Galleries Management
 * - Google Drive API v3 photo extraction
 * - Cloudinary chunked synchronization with SEO-friendly public IDs
 * - Automated SEO keyword strategy and meta optimization generator
 * - Direct multi-asset uploads
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/gdrive.php';
require_once __DIR__ . '/../includes/cloudinary_helper.php';

// Enforce admin login
if (!function_exists('isAdminLoggedIn') || !isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Please log in to the Falhen Admin Dashboard.'
    ]);
    exit;
}

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');

switch ($action) {

    // 1. Preview Google Drive Folder without limits & detect existing uploads to prevent duplicates
    case 'preview_gdrive':
        $gdriveUrl = trim($_POST['gdrive_url'] ?? '');
        $slug = trim($_POST['slug'] ?? 'general');

        if (empty($gdriveUrl)) {
            echo json_encode(['success' => false, 'message' => 'Please provide a valid Google Drive folder URL.']);
            exit;
        }

        // Fetch ALL photos without arbitrary limits (0 = unlimited)
        $photos = getGoogleDriveFolderPhotos($gdriveUrl, 0);
        if (empty($photos)) {
            echo json_encode([
                'success' => false,
                'message' => 'No image assets could be detected in this Google Drive folder. Ensure the folder is set to "Anyone with the link can view".'
            ]);
            exit;
        }

        // Read existing uploaded images for this folder/slug to detect duplicates
        $existingMap = getFolderUploadedMap($slug);
        $alreadyUploadedCount = 0;
        $pendingUploadCount = 0;

        foreach ($photos as $pUrl) {
            $gId = extractGoogleDriveFileId($pUrl);
            $hKey = md5($pUrl);
            if (($gId && isset($existingMap['by_gdrive_id'][$gId])) || isset($existingMap['by_hash'][$hKey])) {
                $alreadyUploadedCount++;
            } else {
                $pendingUploadCount++;
            }
        }

        echo json_encode([
            'success'          => true,
            'total'            => count($photos),
            'already_uploaded' => $alreadyUploadedCount,
            'pending_upload'   => $pendingUploadCount,
            'photos'           => array_values($photos),
            'thumbnails'       => array_map(function($url) {
                return (strpos($url, 'lh3.googleusercontent.com') !== false)
                    ? preg_replace('/=w\d+$/', '=w400', $url)
                    : $url;
            }, array_slice($photos, 0, 24))
        ]);
        exit;

    // 2. Chunked Sync of photo batches to Cloudinary (avoids 30s cPanel timeout & skips duplicates)
    case 'sync_chunk':
        $slug = trim($_POST['slug'] ?? 'general');
        $photos = $_POST['photos'] ?? [];
        $startIndex = (int)($_POST['start_index'] ?? 1);

        if (!is_array($photos) || empty($photos)) {
            echo json_encode(['success' => false, 'message' => 'No photos provided for synchronization chunk.']);
            exit;
        }

        $results = [];
        $currentIndex = $startIndex;

        foreach ($photos as $rawPhotoUrl) {
            $rawPhotoUrl = trim((string)$rawPhotoUrl);
            if (empty($rawPhotoUrl)) continue;

            $syncRes = syncSinglePhotoToCloudinary($rawPhotoUrl, $slug, $currentIndex);
            if (!empty($syncRes['success'])) {
                $results[] = [
                    'success'      => true,
                    'original_url' => $rawPhotoUrl,
                    'synced_url'   => $syncRes['url'],
                    'public_id'    => $syncRes['public_id'] ?? '',
                    'cached'       => !empty($syncRes['cached']),
                    'duplicate'    => !empty($syncRes['duplicate']),
                    'index'        => $currentIndex
                ];
            } else {
                $results[] = [
                    'success'      => false,
                    'original_url' => $rawPhotoUrl,
                    'synced_url'   => $rawPhotoUrl, // Fallback
                    'error'        => $syncRes['message'] ?? 'Upload failed',
                    'index'        => $currentIndex
                ];
            }
            $currentIndex++;
        }

        echo json_encode([
            'success'     => true,
            'processed'   => count($results),
            'next_index'  => $currentIndex,
            'results'     => $results
        ]);
        exit;

    // 3. Generate SEO slug, title tag, meta description, and keywords
    case 'generate_seo':
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'Gallery');
        $client = trim($_POST['client'] ?? '');
        $desc = trim($_POST['desc'] ?? '');

        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Title is required to generate SEO metadata.']);
            exit;
        }

        $seo = generateClientGallerySeo($title, $category, $client, $desc);
        echo json_encode([
            'success' => true,
            'seo'     => $seo
        ]);
        exit;

    // 4. Direct multi-file upload directly to Cloudinary with SEO slug naming
    case 'direct_upload':
        $slug = trim($_POST['slug'] ?? 'general');
        $index = (int)($_POST['index'] ?? 1);

        if (empty($_FILES['file']['tmp_name'])) {
            echo json_encode(['success' => false, 'message' => 'No upload file payload received.']);
            exit;
        }

        $syncRes = syncSinglePhotoToCloudinary($_FILES['file']['tmp_name'], $slug, $index);
        if (!empty($syncRes['success'])) {
            echo json_encode([
                'success'   => true,
                'url'       => $syncRes['url'],
                'public_id' => $syncRes['public_id'] ?? '',
                'index'     => $index
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $syncRes['message'] ?? 'Direct Cloudinary upload failed.'
            ]);
        }
        exit;

    // 5. Automatic Cloudinary Gallery Sync back into client gallery record
    case 'sync_cloudinary_folder':
        $albumSlug = trim($_POST['slug'] ?? $_GET['slug'] ?? '');
        $albumId = isset($_POST['album_id']) ? (int)$_POST['album_id'] : (isset($_GET['album_id']) ? (int)$_GET['album_id'] : null);

        if (empty($albumSlug) && $albumId === null) {
            echo json_encode(['success' => false, 'message' => 'Please provide an album slug or ID.']);
            exit;
        }

        $res = syncCloudinaryClientGalleryFolder($albumSlug, $albumId);
        echo json_encode($res);
        exit;

    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Unknown action: ' . htmlspecialchars($action)
        ]);
        exit;
}

