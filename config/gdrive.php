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
