<?php
/**
 * Google Drive API Configuration & Integration
 * Falhen Media
 */

define('GOOGLE_DRIVE_API_KEY', getenv('GOOGLE_DRIVE_API_KEY') ?: 'AIzaSyByxOOljrNzQClUJ03KttshbQ68_C7mleQ');

/**
 * Convert Google Drive Sharing Link to Direct Embedable View URL
 */
function getGoogleDriveEmbedUrl($driveUrl) {
    if (empty($driveUrl)) return '';
    
    if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/i', $driveUrl, $matches)) {
        $fileId = $matches[1];
        return "https://drive.google.com/file/d/{$fileId}/preview";
    }
    
    if (preg_match('/id=([a-zA-Z0-9_-]+)/i', $driveUrl, $matches)) {
        $fileId = $matches[1];
        return "https://drive.google.com/file/d/{$fileId}/preview";
    }

    return $driveUrl;
}

/**
 * Get Google Drive API File Metadata Endpoint
 */
function getGoogleDriveApiEndpoint($fileId) {
    $apiKey = GOOGLE_DRIVE_API_KEY;
    return "https://www.googleapis.com/drive/v3/files/{$fileId}?key={$apiKey}&fields=id,name,mimeType,webContentLink,webViewLink,thumbnailLink";
}

/**
 * Extract photo URLs from a Google Drive Folder or File link (fetches all photos by default)
 * @param string $driveUrl Google Drive folder or file share URL
 * @param int $limit Maximum number of photos to extract (0 = unlimited / fetch all)
 * @return array Array of direct image URLs
 */
function getGoogleDriveFolderPhotos($driveUrl, $limit = 0) {
    if (empty($driveUrl)) return [];

    $driveUrl = trim($driveUrl);
    $limit = max(0, (int)$limit);
    $photos = [];

    // Case 1: Single file link
    if (strpos($driveUrl, '/folders/') === false) {
        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/i', $driveUrl, $matches) || preg_match('/id=([a-zA-Z0-9_-]+)/i', $driveUrl, $matches)) {
            $fileId = $matches[1];
            return ["https://lh3.googleusercontent.com/d/{$fileId}=w2560"];
        }
    }

    // Case 2: Folder link
    if (preg_match('/\/folders\/([a-zA-Z0-9_-]+)/i', $driveUrl, $matches)) {
        $folderId = $matches[1];

        // Check local cache (1 hour TTL)
        $cacheDir = __DIR__ . '/../backups';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        $cacheFile = $cacheDir . '/gdrive_cache_' . md5($folderId) . '.json';
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
            $cachedData = @json_decode(file_get_contents($cacheFile), true);
            if (is_array($cachedData) && !empty($cachedData)) {
                return ($limit > 0) ? array_slice($cachedData, 0, $limit) : $cachedData;
            }
        }

        // Method A: Query Google Drive API v3 with pagination for unlimited photos
        $apiKey = defined('GOOGLE_DRIVE_API_KEY') ? GOOGLE_DRIVE_API_KEY : '';
        if (!empty($apiKey)) {
            $nextPageToken = '';
            $pageCount = 0;
            $maxPages = ($limit > 0) ? (int)ceil($limit / 100) : 30; // up to 3,000 photos

            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 8,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) FalhenMedia/1.0',
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);

            do {
                $pageTokenParam = !empty($nextPageToken) ? '&pageToken=' . urlencode($nextPageToken) : '';
                $apiUrl = "https://www.googleapis.com/drive/v3/files?q=" . urlencode("'{$folderId}' in parents and trashed=false") . "&key={$apiKey}&fields=" . urlencode("nextPageToken,files(id,name,mimeType,thumbnailLink,webContentLink)") . "&pageSize=100" . $pageTokenParam;

                $jsonResp = @file_get_contents($apiUrl, false, $ctx);
                if (!$jsonResp) break;

                $data = @json_decode($jsonResp, true);
                if (!empty($data['files']) && is_array($data['files'])) {
                    foreach ($data['files'] as $f) {
                        $mime = strtolower($f['mimeType'] ?? '');
                        $name = strtolower($f['name'] ?? '');
                        if (strpos($mime, 'image/') === 0 || preg_match('/\.(jpg|jpeg|png|webp|gif|heic|tiff|cr2|nef|arw)$/i', $name)) {
                            $id = $f['id'];
                            $photos[] = "https://lh3.googleusercontent.com/d/{$id}=w2560";
                            if ($limit > 0 && count($photos) >= $limit) {
                                break 2;
                            }
                        }
                    }
                }

                $nextPageToken = $data['nextPageToken'] ?? '';
                $pageCount++;
            } while (!empty($nextPageToken) && ($limit <= 0 || count($photos) < $limit) && $pageCount < $maxPages);
        }

        // Method B: If API returned nothing or folder requires HTML parsing, try public folder HTML
        if (empty($photos)) {
            $htmlUrl = "https://drive.google.com/drive/folders/{$folderId}";
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 8,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);

            $html = @file_get_contents($htmlUrl, false, $ctx);
            if ($html) {
                // Match image IDs from Google Drive initial state JSON
                if (preg_match_all('/\["([a-zA-Z0-9_-]{28,45})",\["([^"]+\.(?:jpg|jpeg|png|webp|gif|JPG|PNG|JPEG|HEIC))"/i', $html, $m)) {
                    foreach ($m[1] as $fId) {
                        $photos[] = "https://lh3.googleusercontent.com/d/{$fId}=w2560";
                        if ($limit > 0 && count($photos) >= $limit) {
                            break;
                        }
                    }
                }
            }
        }

        // Cache results if non-empty
        if (!empty($photos)) {
            $photos = array_values(array_unique($photos));
            if ($limit > 0) {
                $photos = array_slice($photos, 0, $limit);
            }
            @file_put_contents($cacheFile, json_encode($photos));
        }
    }

    return $photos;
}

