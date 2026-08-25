<?php
/**
 * Cloudinary Media Helper & API Handler
 * Falhen Media
 */

require_once __DIR__ . '/../config/cloudinary.php';

/**
 * Check if a URL is hosted on Cloudinary
 */
function isCloudinaryUrl($url) {
    return (bool)preg_match('/res\.cloudinary\.com/i', (string)$url);
}

/**
 * Transform & optimize image URL via Cloudinary CDN
 */
function getCloudinaryUrl($pathOrUrl, $transform = 'f_auto,q_auto') {
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
function uploadToCloudinary($fileOrUrl, $folder = 'falhen') {
    $cloudName = CLOUDINARY_CLOUD_NAME;
    $apiKey = CLOUDINARY_API_KEY;
    $apiSecret = CLOUDINARY_API_SECRET;

    if (empty($cloudName) || empty($apiKey) || empty($apiSecret)) {
        return ['success' => false, 'message' => 'Cloudinary API credentials are missing.'];
    }

    $timestamp = time();
    $paramsToSign = [
        'folder' => $folder,
        'timestamp' => $timestamp
    ];
    ksort($paramsToSign);

    $stringToSign = "";
    foreach ($paramsToSign as $k => $v) {
        $stringToSign .= "{$k}={$v}&";
    }
    $stringToSign = rtrim($stringToSign, '&') . $apiSecret;
    $signature = sha1($stringToSign);

    $endpoint = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";

    $postFields = [
        'api_key' => $apiKey,
        'timestamp' => $timestamp,
        'folder' => $folder,
        'signature' => $signature
    ];

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
