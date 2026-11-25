<?php
// portfolio-photo.php - Client Portfolio Gallery Page with Carousel & Photo Download
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo_helper.php';

$masterPortfolioItems = getPortfolioRepo();
$albumsRepo = [];

foreach ($masterPortfolioItems as $item) {
    if (($item['media_type'] ?? 'photo') === 'photo' || !isset($item['media_type'])) {
        $albumsRepo[$item['id']] = $item;
    }
}

if (empty($albumsRepo)) {
    foreach ($masterPortfolioItems as $item) {
        $albumsRepo[$item['id']] = $item;
    }
}

$hasRequestedAlbum = isset($_GET['id']);
$albumId = $hasRequestedAlbum ? (int)$_GET['id'] : key($albumsRepo);
$currentAlbum = null;

if (isset($albumsRepo[$albumId])) {
    $currentAlbum = $albumsRepo[$albumId];
} else {
    foreach ($masterPortfolioItems as $mIt) {
        if ((int)$mIt['id'] === $albumId) {
            $currentAlbum = $mIt;
            break;
        }
    }
}

// Strict 404 check if album was explicitly requested but does not exist
if (!$currentAlbum) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$coverImage = getCloudinaryUrl($currentAlbum['image'] ?? $currentAlbum['cover'] ?? 'https://res.cloudinary.com/pnabfi91/image/upload/v1791224271/falhen/portfolio/ysfriy8jlhhkcuzl8j16.jpg');
$cleanCanonical = getSiteBaseUrl() . '/portfolio-photo?id=' . (int)$currentAlbum['id'];

$seo = getSeoConfig('portfolio', [
    'title'       => htmlspecialchars($currentAlbum['title']) . ' — Photography Gallery',
    'description' => 'Explore the high-resolution photography gallery for ' . ($currentAlbum['title'] ?? 'Falhen Productions') . '. Event, portrait, and commercial photography by Falhen Productions.',
    'canonical'   => $cleanCanonical,
    'image'       => $coverImage,
    'keywords'    => 'photography gallery, concert photography, portrait portfolio, Falhen Productions'
]);

// Registered Client Authentication Status
$isClientLoggedIn = !empty($_SESSION['registered_client_email']) || !empty($_COOKIE['falhen_client_email']);
$clientEmail = $_SESSION['registered_client_email'] ?? $_COOKIE['falhen_client_email'] ?? '';
$clientName = $_SESSION['registered_client_name'] ?? '';

// Related albums for footer "More Portfolio Albums" (always display 3 albums)
$albumCategory = trim($currentAlbum['category'] ?? 'Portrait');
if (empty($albumCategory)) $albumCategory = 'Portrait';
$currentAlbumId = (int)($currentAlbum['id'] ?? 0);
$relatedAlbums = [];

// 1. Same category first (photo only)
foreach ($masterPortfolioItems as $mIt) {
    if ((int)($mIt['id'] ?? 0) === $currentAlbumId) continue;
    if (($mIt['media_type'] ?? 'photo') !== 'photo' && isset($mIt['media_type'])) continue;
    if (strcasecmp(trim($mIt['category'] ?? ''), $albumCategory) === 0) {
        $relatedAlbums[] = $mIt;
    }
}

// 2. Backfill with other photo albums until we have 3 albums
if (count($relatedAlbums) < 3) {
    foreach ($masterPortfolioItems as $mIt) {
        if ((int)($mIt['id'] ?? 0) === $currentAlbumId) continue;
        if (($mIt['media_type'] ?? 'photo') !== 'photo' && isset($mIt['media_type'])) continue;

        $alreadyAdded = false;
        foreach ($relatedAlbums as $r) {
            if ((int)($r['id'] ?? 0) === (int)($mIt['id'] ?? 0)) {
                $alreadyAdded = true;
                break;
            }
        }
        if (!$alreadyAdded) {
            $relatedAlbums[] = $mIt;
        }
        if (count($relatedAlbums) >= 3) break;
    }
}

$relatedAlbums = array_slice($relatedAlbums, 0, 3);

// Dynamic heading: if all 3 are from the same category, use "More {Category} Albums", otherwise "More Portfolio Albums"
$allSameCat = true;
foreach ($relatedAlbums as $r) {
    if (strcasecmp(trim($r['category'] ?? ''), $albumCategory) !== 0) {
        $allSameCat = false;
        break;
    }
}
$sectionHeading = $allSameCat ? "More {$albumCategory} Albums" : "More Portfolio Albums";

// Resolve album gallery photos (Cloudinary CDN first -> Google Drive -> legacy photos -> cover image)
$clientPhotos = [];
if (!empty($currentAlbum['gallery_photos']) && is_array($currentAlbum['gallery_photos'])) {
    $clientPhotos = $currentAlbum['gallery_photos'];
} elseif (!empty($currentAlbum['gdrive_url'])) {
    $clientPhotos = getGoogleDriveFolderPhotos($currentAlbum['gdrive_url']);
} elseif (!empty($currentAlbum['photos']) && is_array($currentAlbum['photos'])) {
    $clientPhotos = $currentAlbum['photos'];
}

if (empty($clientPhotos) && !empty($coverImage)) {
    $clientPhotos = [$coverImage];
}

// Normalize via Cloudinary helper & remove duplicates while preserving order
$clientPhotos = array_values(array_unique(array_filter(array_map(function($url) {
    $u = trim((string)$url);
    return $u !== '' ? getCloudinaryUrl($u) : '';
}, $clientPhotos))));
$totalPhotoCount = count($clientPhotos);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="referrer" content="no-referrer">
    
    <?php require_once __DIR__ . '/includes/analytics.php'; ?>
    <?php renderSeoHeadTags($seo); ?>
    <?php 
    renderSchemaJsonLd('global', [
        'canonical' => $cleanCanonical,
        'breadcrumbs' => [
            ['name' => 'Home', 'url' => getSiteBaseUrl() . '/'],
            ['name' => 'Portfolio', 'url' => getSiteBaseUrl() . '/portfolio'],
            ['name' => $currentAlbum['title'], 'url' => $cleanCanonical]
        ]
    ]); 
    ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="shortcut icon" type="image/png" href="/assets/img/icons/favicon.png">
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>
    
    <!-- Main Style -->
    <link rel="stylesheet" href="/assets/css/style.css">

    <script>
        const albumHeroCoverUrl = <?php echo json_encode($coverImage); ?>;

        function handlePhotoFallback(imgEl) {
            if (!imgEl) return;
            const step = parseInt(imgEl.dataset.fallbackStep || '0', 10);
            const currentSrc = imgEl.getAttribute('src') || '';

            // Never mutate galleryPhotos or substitute another client's photos
            if (!currentSrc) return;

            // Step 1: If Google Drive direct CDN URL fails, retry Drive thumbnail endpoint
            if (step === 0 && currentSrc.includes('googleusercontent.com/d/')) {
                const match = currentSrc.match(/\/d\/([a-zA-Z0-9_-]+)/);
                if (match && match[1]) {
                    imgEl.dataset.fallbackStep = '1';
                    imgEl.src = 'https://drive.google.com/thumbnail?id=' + match[1] + '&sz=w1600';
                    return;
                }
            }

            // Step 2: Gracefully fall back ONLY to this album's own cover photo
            if (step <= 1 && albumHeroCoverUrl && currentSrc !== albumHeroCoverUrl) {
                imgEl.dataset.fallbackStep = '2';
                imgEl.src = albumHeroCoverUrl;
            }
        }
    </script>

    <style>
        :root {
            --bg-dark: #030305;
            --surface-card: #0b0b0f;
            --surface-card-hover: #121218;
            --brand-red: #dc2626;
            --brand-red-hover: #b91c1c;
            --brand-green: #10b981;
            --brand-green-hover: #059669;
            --text-main: #ffffff;
            --text-muted: #a1a1aa;
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-highlight: rgba(255, 255, 255, 0.16);
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Top Sticky Navigation Bar matching Readdy mockup */
        .gallery-nav-bar {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(3, 3, 5, 0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-subtle);
            z-index: 100;
            padding: 14px 32px;
        }

        .gallery-nav-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .gallery-nav-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #d4d4d8;
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .gallery-nav-back:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.06);
        }

        .gallery-nav-logo img {
            height: 32px;
            width: auto;
            display: block;
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.4));
        }

        .gallery-nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-nav-share {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--border-highlight);
            color: #ffffff;
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-nav-share:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.25);
        }

        .btn-nav-book {
            background: var(--brand-red);
            color: #ffffff;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
            transition: all 0.2s ease;
        }

        .btn-nav-book:hover {
            background: var(--brand-red-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.5);
            color: #ffffff;
        }

        /* Hero Stage */
        .gallery-hero-section {
            max-width: 1400px;
            margin: 0 auto;
            padding: 50px 32px 40px;
        }

        .gallery-hero-grid {
            display: grid;
            grid-template-columns: 460px 1fr;
            gap: 48px;
            align-items: center;
        }

        /* Featured Image Card (Left Column) */
        .hero-featured-card {
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            background: #000;
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.8), 0 0 30px rgba(220, 38, 38, 0.1);
            aspect-ratio: 4 / 3.8;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hero-featured-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.9), 0 0 40px rgba(220, 38, 38, 0.2);
        }

        .hero-featured-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .hero-featured-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0.4) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .hero-featured-card:hover .hero-featured-overlay {
            opacity: 1;
        }

        .hero-featured-zoom-badge {
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 0.88rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        /* Shoot Meta Details (Right Column) */
        .hero-details-col {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .gallery-breadcrumbs {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-muted);
            font-size: 0.84rem;
            font-weight: 500;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .gallery-breadcrumbs a {
            color: #d4d4d8;
            text-decoration: none;
            transition: color 0.2s;
        }

        .gallery-breadcrumbs a:hover {
            color: #ffffff;
        }

        .gallery-breadcrumbs .crumb-sep {
            color: rgba(255, 255, 255, 0.2);
        }

        .gallery-breadcrumbs .crumb-current {
            color: #f4f4f5;
            font-weight: 600;
        }

        .gallery-badges-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .badge-cat-pill {
            background: var(--brand-red);
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 20px;
            text-transform: capitalize;
            letter-spacing: 0.3px;
        }

        .badge-featured-pill {
            background: rgba(234, 179, 8, 0.12);
            color: #facc15;
            border: 1px solid rgba(234, 179, 8, 0.3);
            font-size: 0.78rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .gallery-main-title {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 3.2rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 14px 0;
            line-height: 1.12;
            letter-spacing: -1px;
        }

        .gallery-description {
            color: #a1a1aa;
            font-size: 1.05rem;
            line-height: 1.65;
            margin: 0 0 24px 0;
            max-width: 640px;
        }

        .gallery-meta-pills-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }

        .meta-pill-chip {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            padding: 6px 16px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #e4e4e7;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .meta-pill-chip i {
            color: #a1a1aa;
            font-size: 0.82rem;
        }

        /* Download All Client Blue Action Button */
        .btn-download-all-green, .btn-download-all-blue {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.35);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-download-all-green:hover, .btn-download-all-blue:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.5);
            color: #ffffff;
        }

        /* Photos Grid Section */
        .gallery-photos-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 32px 90px;
        }

        .gallery-section-divider {
            height: 1px;
            background: var(--border-subtle);
            margin: 10px 0 36px;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .gallery-photo-card {
            position: relative;
            border-radius: 18px;
            overflow: hidden;
            background: var(--surface-card);
            border: 1px solid var(--border-subtle);
            aspect-ratio: 2 / 3;
            cursor: pointer;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.3s, box-shadow 0.3s;
        }

        .gallery-photo-card:hover {
            transform: translateY(-3px);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.7);
        }

        .gallery-photo-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.45s ease;
        }

        .gallery-photo-card:hover img {
            transform: scale(1.05);
        }

        /* Floating Circular Action Buttons Overlay */
        .card-hover-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.42);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            opacity: 0;
            transition: opacity 0.25s ease;
            z-index: 2;
        }

        .gallery-photo-card:hover .card-hover-overlay {
            opacity: 1;
        }

        .overlay-circle-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            color: #ffffff;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.4);
        }

        .overlay-circle-btn.zoom-btn {
            background: rgba(255, 255, 255, 0.32);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.22);
            color: #ffffff;
        }

        .overlay-circle-btn.zoom-btn:hover {
            background: rgba(255, 255, 255, 0.46);
            transform: scale(1.12);
        }

        .overlay-circle-btn.download-btn {
            background: #2563eb;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.45);
        }

        .overlay-circle-btn.download-btn:hover {
            background: #1d4ed8;
            transform: scale(1.12);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.6);
        }

        /* Carousel / Lightbox Viewer Modal */
        .carousel-modal {
            position: fixed;
            inset: 0;
            background: rgba(3, 3, 6, 0.97);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            z-index: 99999;
            display: none;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px 24px;
            box-sizing: border-box;
            animation: fadeIn 0.25s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .carousel-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1500px;
            width: 100%;
            margin: 0 auto;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .carousel-counter-badge {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            color: #e4e4e7;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .carousel-client-info {
            text-align: center;
            color: #ffffff;
            font-weight: 700;
            font-size: 1.05rem;
        }

        .carousel-top-tools {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-carousel-download {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
            transition: all 0.2s;
        }

        .btn-carousel-download:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.55);
        }

        .btn-carousel-close {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #ffffff;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-carousel-close:hover {
            background: var(--brand-red);
            border-color: var(--brand-red);
        }

        /* Carousel Center Stage */
        .carousel-center-stage {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            max-width: 1500px;
            width: 100%;
            margin: 0 auto;
            padding: 16px 0;
            box-sizing: border-box;
            overflow: hidden;
        }

        .carousel-arrow-btn {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: rgba(20, 20, 28, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            z-index: 10;
            flex-shrink: 0;
        }

        .carousel-arrow-btn:hover {
            background: rgba(220, 38, 38, 0.85);
            border-color: var(--brand-red);
            transform: scale(1.1);
        }

        .carousel-main-image-wrap {
            flex: 1;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 20px;
            box-sizing: border-box;
            user-select: none;
        }

        .carousel-main-img {
            max-width: 88vw;
            max-height: 68vh;
            border-radius: 14px;
            object-fit: contain;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: opacity 0.2s ease, transform 0.25s ease;
        }

        /* Carousel Bottom Thumbnails Strip */
        .carousel-bottom-thumbs {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 10px;
            overflow-x: auto;
            padding: 8px 4px 4px;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
        }

        .carousel-thumb-item {
            width: 72px;
            height: 52px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
            border: 2px solid transparent;
            cursor: pointer;
            opacity: 0.45;
            transition: all 0.2s ease;
            background: #000;
        }

        .carousel-thumb-item.active {
            opacity: 1;
            border-color: var(--brand-red);
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.5);
        }

        .carousel-thumb-item:hover {
            opacity: 0.9;
        }

        .carousel-thumb-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* Toast feedback */
        .download-toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            padding: 14px 22px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.6);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.92rem;
            font-weight: 600;
            z-index: 100000;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
        }

        .download-toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .download-toast i {
            color: #3b82f6;
            font-size: 1.2rem;
        }

        /* Mobile Responsiveness */
        @media (max-width: 1024px) {
            .gallery-hero-grid {
                grid-template-columns: 1fr;
                gap: 36px;
            }
            .hero-featured-card {
                max-width: 540px;
                margin: 0 auto;
            }
            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .gallery-main-title {
                font-size: 2.6rem;
            }
        }

        .gallery-action-btns-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .btn-hero-share {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #ffffff;
            padding: 12px 22px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-hero-share:hover {
            background: rgba(255, 255, 255, 0.16);
            border-color: rgba(255, 255, 255, 0.3);
            color: #ffffff;
            transform: translateY(-2px);
        }

        @media (max-width: 640px) {
            .gallery-nav-bar {
                padding: 12px 16px;
            }
            .gallery-nav-back span {
                display: none;
            }
            .gallery-nav-back {
                width: 38px;
                height: 38px;
                padding: 0;
                justify-content: center;
                background: rgba(255, 255, 255, 0.06);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 10px;
                font-size: 0.95rem;
            }
            .btn-nav-book {
                display: none;
            }
            .btn-nav-share {
                width: 38px;
                height: 38px;
                padding: 0;
                justify-content: center;
                border-radius: 10px;
                font-size: 0.95rem;
            }
            .btn-nav-share span {
                display: none;
            }
            .gallery-hero-section {
                padding: 20px 16px 20px;
            }
            .gallery-hero-grid {
                gap: 24px;
            }
            .hero-featured-card {
                border-radius: 16px;
            }
            .gallery-main-title {
                font-size: 1.85rem;
                letter-spacing: -0.3px;
                margin-bottom: 6px;
            }
            .gallery-description {
                font-size: 0.88rem;
                margin-bottom: 16px;
            }
            .gallery-meta-pills-row {
                gap: 8px;
                margin-bottom: 20px;
            }
            .meta-pill-chip {
                padding: 5px 12px;
                font-size: 0.78rem;
            }
            .gallery-action-btns-row {
                width: 100%;
                gap: 8px;
            }
            .btn-download-all-green {
                flex: 1;
                justify-content: center;
                padding: 11px 14px;
                font-size: 0.85rem;
                border-radius: 10px;
            }
            .btn-hero-share {
                padding: 11px 16px;
                font-size: 0.85rem;
                border-radius: 10px;
            }
            .gallery-photos-container {
                padding: 10px 16px 75px;
            }
            .gallery-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 10px !important;
            }
            .gallery-photo-card {
                border-radius: 12px;
                aspect-ratio: 4 / 5;
            }
            .carousel-arrow-btn {
                width: 42px;
                height: 42px;
                font-size: 1rem;
            }
            .card-hover-overlay {
                opacity: 1;
                background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 60%);
                align-items: flex-end;
                padding-bottom: 12px;
                gap: 10px;
            }
            .overlay-circle-btn {
                width: 36px;
                height: 36px;
                font-size: 0.92rem;
            }
            .gallery-related-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 12px !important;
            }
        }

        /* Portfolio Gallery Footer Section */
        .gallery-footer-section {
            width: 100%;
            background: #030305;
            padding: 30px 0 80px;
        }

        .gallery-footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
            box-sizing: border-box;
        }

        /* 1. "Love what you see?" CTA Card matching Readdy mockup */
        .gallery-cta-banner {
            background: #0d0f17;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 22px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 48px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .gallery-cta-left {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .gallery-cta-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.35);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .gallery-cta-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 4px 0;
        }

        .gallery-cta-sub {
            font-size: 0.88rem;
            color: #94a3b8;
            margin: 0;
        }

        .gallery-cta-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .btn-cta-share {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #ffffff;
            padding: 9px 18px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-cta-share:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        .btn-cta-book {
            background: var(--brand-red, #dc2626);
            color: #ffffff;
            border: none;
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
            transition: all 0.2s ease;
        }

        .btn-cta-book:hover {
            background: var(--brand-red-hover, #ef4444);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.5);
            color: #ffffff;
        }

        /* 2. "More [Category] Albums" Section */
        .gallery-related-section {
            margin-bottom: 50px;
        }

        .gallery-related-heading {
            font-size: 1.35rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 22px 0;
            letter-spacing: -0.3px;
        }

        .gallery-related-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .related-album-card {
            text-decoration: none;
            display: block;
        }

        .related-album-thumb {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            background: #0b0b0f;
            border: 1px solid rgba(255, 255, 255, 0.1);
            aspect-ratio: 4 / 3;
            margin-bottom: 12px;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.3s, box-shadow 0.3s;
        }

        .related-album-card:hover .related-album-thumb {
            transform: translateY(-3px);
            border-color: rgba(255, 255, 255, 0.25);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.7);
        }

        .related-album-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }

        .related-album-card:hover .related-album-thumb img {
            transform: scale(1.04);
        }

        .related-album-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.65) 0%, transparent 60%);
            display: flex;
            align-items: flex-end;
            padding: 14px;
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .related-album-card:hover .related-album-overlay {
            opacity: 1;
        }

        .related-album-badge {
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.2);
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .related-album-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #f4f4f5;
            margin: 0;
            line-height: 1.4;
            transition: color 0.2s;
        }

        .related-album-card:hover .related-album-title {
            color: #ffffff;
        }

        /* 3. Bottom Navigation Bar */
        .gallery-bottom-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 26px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .btn-bottom-back {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-bottom-back:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        .btn-bottom-book {
            background: var(--brand-red, #dc2626);
            color: #ffffff;
            border: none;
            padding: 10px 22px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
            transition: all 0.2s ease;
        }

        .btn-bottom-book:hover {
            background: var(--brand-red-hover, #ef4444);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.5);
            color: #ffffff;
        }

        @media (max-width: 900px) {
            .gallery-related-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }

        @media (max-width: 640px) {
            .gallery-cta-banner {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
                gap: 18px;
            }
            .gallery-cta-actions {
                width: 100%;
                justify-content: flex-start;
                gap: 10px;
            }
            .gallery-related-grid {
                grid-template-columns: 1fr;
            }
            .gallery-bottom-nav {
                flex-direction: row;
                gap: 12px;
            }
        }

        /* Client Verification / Download Modal */
        .client-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(3, 7, 18, 0.82);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            z-index: 100001;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.2s ease;
        }

        .client-modal-box {
            background: #11131a;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 20px;
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            box-shadow: 0 28px 70px rgba(0, 0, 0, 0.9), 0 0 35px rgba(220, 38, 38, 0.12);
            position: relative;
        }

        .client-modal-header {
            padding: 22px 26px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .client-icon-badge {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(220, 38, 38, 0.14);
            border: 1px solid rgba(220, 38, 38, 0.35);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .client-modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 4px 0;
            letter-spacing: -0.01em;
        }

        .client-modal-subtitle {
            font-size: 0.85rem;
            color: #94a3b8;
            margin: 0 0 6px 0;
            line-height: 1.4;
        }

        .client-stats-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.76rem;
            color: #cbd5e1;
        }

        .client-modal-body {
            padding: 22px 26px;
        }

        .cdm-form-group {
            margin-bottom: 16px;
            text-align: left;
        }

        .cdm-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #cbd5e1;
            margin-bottom: 6px;
        }

        .cdm-input {
            width: 100%;
            background: #181a24;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            padding: 12px 14px;
            color: #ffffff;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: all 0.2s ease;
            outline: none;
        }

        .cdm-input:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2);
            background: #1d202e;
        }

        .cdm-error-alert {
            background: #450a0a;
            border: 1px solid #dc2626;
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            line-height: 1.45;
            margin-bottom: 16px;
            display: none;
        }

        .cdm-info-alert {
            background: #082f49;
            border: 1px solid #0284c7;
            color: #7dd3fc;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            line-height: 1.45;
            margin-bottom: 16px;
            display: none;
        }

        .cdm-privacy-note {
            font-size: 0.78rem;
            color: #94a3b8;
            line-height: 1.45;
            margin: 10px 0 0 0;
        }

        .client-modal-footer {
            padding: 14px 26px 20px;
            background: rgba(0, 0, 0, 0.25);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-cdm-cancel {
            background: transparent;
            border: none;
            color: #94a3b8;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-cdm-cancel:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
        }

        .btn-cdm-submit {
            background: #dc2626;
            border: none;
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 0.94rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
            transition: all 0.2s ease;
        }

        .btn-cdm-submit:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }

        @media (max-width: 640px) {
            .client-modal-backdrop {
                padding: 12px;
            }
            .client-modal-box {
                max-width: 440px;
                border-radius: 18px;
            }
            .cdm-input {
                font-size: 16px !important;
                padding: 12px 14px;
            }
            .btn-cdm-submit {
                min-height: 48px;
                width: 100%;
                justify-content: center;
            }
            .client-modal-footer {
                flex-direction: column-reverse;
                gap: 8px;
            }
            .btn-cdm-cancel {
                width: 100%;
                text-align: center;
            }
        }
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-cdm-cancel:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }

        .btn-cdm-submit {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 9px 22px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-cdm-submit:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.55);
        }

        .cdm-error-alert {
            background: rgba(220, 38, 38, 0.12);
            border: 1px solid rgba(220, 38, 38, 0.35);
            color: #f87171;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.82rem;
            line-height: 1.45;
            margin-bottom: 14px;
            display: none;
            text-align: left;
        }

        .cdm-error-alert a {
            color: #93c5fd;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <!-- Top Sticky Navigation Bar -->
    <header class="gallery-nav-bar">
        <div class="gallery-nav-inner">
            <a href="/portfolio.php?tab=photo" class="gallery-nav-back">
                <i class="fa-solid fa-arrow-left"></i> Back to Gallery
            </a>
            
            <a href="/" class="gallery-nav-logo">
                <img src="/assets/img/icons/logo.png" alt="Falhen Productions">
            </a>

            <div class="gallery-nav-actions">
                <button type="button" class="btn-nav-share" onclick="shareGalleryPage()">
                    <i class="fa-solid fa-share-nodes"></i> <span>Share</span>
                </button>
                <a href="/contact.php" class="btn-nav-book">
                    <i class="fa-regular fa-calendar-check"></i> Book a Shoot
                </a>
            </div>
        </div>
    </header>

    <!-- Main Hero Stage -->
    <section class="gallery-hero-section">
        <div class="gallery-hero-grid">
            
            <!-- Featured Showcase Cover (Left Column) -->
            <div class="hero-featured-card" onclick="openHeroCover()" title="Click to view full cover image">
                <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="<?php echo htmlspecialchars($currentAlbum['title']); ?>" referrerpolicy="no-referrer">
                <div class="hero-featured-overlay">
                    <div class="hero-featured-zoom-badge">
                        <i class="fa-solid fa-magnifying-glass-plus"></i> View Full Image
                    </div>
                </div>
            </div>

            <!-- Shoot Meta & Actions (Right Column) -->
            <div class="hero-details-col">
                
                <!-- Breadcrumbs -->
                <div class="gallery-breadcrumbs">
                    <a href="/portfolio.php?tab=photo"><i class="fa-solid fa-arrow-left"></i> Back to Portfolio</a>
                    <span class="crumb-sep">|</span>
                    <a href="/">Home</a>
                    <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem; opacity: 0.6;"></i>
                    <a href="/portfolio.php">Portfolio</a>
                    <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem; opacity: 0.6;"></i>
                    <span class="crumb-current"><?php echo htmlspecialchars($currentAlbum['title']); ?></span>
                </div>

                <!-- Badges -->
                <div class="gallery-badges-row">
                    <span class="badge-cat-pill"><?php echo htmlspecialchars($currentAlbum['category'] ?? 'Portrait'); ?></span>
                    <?php if (!empty($currentAlbum['featured'])): ?>
                        <span class="badge-featured-pill"><i class="fa-solid fa-star"></i> Featured</span>
                    <?php endif; ?>
                </div>

                <!-- Main Title -->
                <h1 class="gallery-main-title"><?php echo htmlspecialchars($currentAlbum['title']); ?></h1>

                <!-- Subtitle / Description -->
                <p class="gallery-description">
                    <?php 
                    echo !empty($currentAlbum['desc']) 
                        ? htmlspecialchars($currentAlbum['desc']) 
                        : "A comprehensive photographic showcase capturing authentic emotions, editorial aesthetic, and cinematic lighting curated by Falhen Productions."; 
                    ?>
                </p>

                <!-- Metadata Chips -->
                <div class="gallery-meta-pills-row">
                    <div class="meta-pill-chip">
                        <i class="fa-regular fa-images"></i> Gallery
                    </div>
                    <?php if (!empty($currentAlbum['client'])): ?>
                        <div class="meta-pill-chip">
                            <i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($currentAlbum['client']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($currentAlbum['location'])): ?>
                        <div class="meta-pill-chip">
                            <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($currentAlbum['location']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons: Download All and Share (matching reference) -->
                <div class="gallery-action-btns-row">
                    <button type="button" class="btn-download-all-green" id="btnDownloadAll" onclick="downloadAllPhotos()">
                        <i class="fa-solid fa-cloud-arrow-down"></i> Download All (<?php echo $totalPhotoCount; ?>)
                    </button>
                    <button type="button" class="btn-hero-share" onclick="shareGalleryPage()">
                        <i class="fa-solid fa-share-nodes"></i> Share
                    </button>
                </div>
            </div>

        </div>
    </section>

    <!-- Gallery Photos Grid Container -->
    <main class="gallery-photos-container">
        <div class="gallery-section-divider"></div>

        <div class="gallery-grid" id="galleryGrid">
            <?php foreach ($clientPhotos as $idx => $photo): ?>
                <div class="gallery-photo-card" onclick="openCarousel(<?php echo $idx; ?>)">
                    <img src="<?php echo htmlspecialchars(getCloudinaryUrl($photo, 'f_auto,q_auto:good,w_900,c_limit')); ?>" alt="<?php echo htmlspecialchars($currentAlbum['title']) . ' — Photo ' . ($idx + 1); ?>" loading="lazy" referrerpolicy="no-referrer" onerror="handlePhotoFallback(this)">
                    
                    <!-- Floating Circular Action Buttons Overlay -->
                    <div class="card-hover-overlay">
                        <button type="button" class="overlay-circle-btn zoom-btn" title="View in Carousel" onclick="event.stopPropagation(); openCarousel(<?php echo $idx; ?>)">
                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                        </button>
                        <button type="button" class="overlay-circle-btn download-btn" title="Download Photo" onclick="event.stopPropagation(); downloadSinglePhoto('<?php echo htmlspecialchars($photo); ?>', <?php echo $idx + 1; ?>)">
                            <i class="fa-solid fa-download"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Portfolio Gallery Footer Section -->
    <footer class="gallery-footer-section">
        <div class="gallery-footer-container">
            
            <!-- 1. "Love what you see?" CTA Card -->
            <div class="gallery-cta-banner">
                <div class="gallery-cta-left">
                    <div class="gallery-cta-icon">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                    <div class="gallery-cta-text">
                        <h3 class="gallery-cta-title">Love what you see?</h3>
                        <p class="gallery-cta-sub">Book your own session &mdash; portraits, weddings, events, corporate.</p>
                    </div>
                </div>
                <div class="gallery-cta-actions">
                    <button type="button" class="btn-cta-share" onclick="shareGalleryPage()">
                        <i class="fa-solid fa-share-nodes"></i> <span>Share</span>
                    </button>
                    <a href="/contact.php" class="btn-cta-book">
                        <i class="fa-regular fa-calendar-check"></i> Book a Shoot
                    </a>
                </div>
            </div>

            <!-- 2. "More [Category] Albums" Section -->
            <?php if (!empty($relatedAlbums)): ?>
                <section class="gallery-related-section">
                    <h2 class="gallery-related-heading"><?php echo htmlspecialchars($sectionHeading); ?></h2>
                    <div class="gallery-related-grid">
                        <?php foreach ($relatedAlbums as $rel): ?>
                            <a href="/portfolio-photo.php?id=<?php echo (int)$rel['id']; ?>" class="related-album-card">
                                <div class="related-album-thumb">
                                    <img src="<?php echo htmlspecialchars(getCloudinaryUrl($rel['cover'] ?? $rel['image'] ?? '')); ?>" alt="<?php echo htmlspecialchars($rel['title']); ?>" loading="lazy" referrerpolicy="no-referrer">
                                    <div class="related-album-overlay">
                                        <span class="related-album-badge"><i class="fa-regular fa-images"></i> View Album</span>
                                    </div>
                                </div>
                                <h3 class="related-album-title"><?php echo htmlspecialchars($rel['title']); ?></h3>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- 3. Bottom Navigation Bar -->
            <div class="gallery-bottom-nav">
                <a href="/portfolio.php?tab=photo" class="btn-bottom-back">
                    <i class="fa-solid fa-arrow-left"></i> Back to Portfolio
                </a>
                <a href="/contact.php" class="btn-bottom-book">
                    <i class="fa-regular fa-calendar-check"></i> Book a Shoot
                </a>
            </div>

        </div>
    </footer>

    <!-- Fullscreen Carousel / Lightbox Viewer Modal -->
    <div id="carouselModal" class="carousel-modal" role="dialog" aria-modal="true">
        
        <!-- Modal Top Bar -->
        <div class="carousel-top-bar">
            <div class="carousel-counter-badge" id="carouselCounter">
                <i class="fa-regular fa-image"></i> Photo 1 of <?php echo $totalPhotoCount; ?>
            </div>
            
            <div class="carousel-client-info">
                <?php echo htmlspecialchars($currentAlbum['title']); ?>
            </div>

            <div class="carousel-top-tools">
                <button type="button" class="btn-carousel-download" onclick="downloadCurrentCarouselPhoto()">
                    <i class="fa-solid fa-arrow-down-to-bracket"></i> Download
                </button>
                <button type="button" class="btn-carousel-close" onclick="closeCarousel()" title="Close viewer (Esc)">&times;</button>
            </div>
        </div>

        <!-- Center Stage -->
        <div class="carousel-center-stage">
            <button type="button" class="carousel-arrow-btn prev" onclick="prevCarouselPhoto()" title="Previous (Left Arrow)">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <div class="carousel-main-image-wrap" onclick="closeCarousel()">
                <img id="carouselMainImg" class="carousel-main-img" src="<?php echo htmlspecialchars($coverImage); ?>" alt="<?php echo htmlspecialchars($currentAlbum['title']); ?> — Full view" onclick="event.stopPropagation()" referrerpolicy="no-referrer" onerror="handlePhotoFallback(this)">
            </div>

            <button type="button" class="carousel-arrow-btn next" onclick="nextCarouselPhoto()" title="Next (Right Arrow)">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <!-- Bottom Thumbnails Strip -->
        <div class="carousel-bottom-thumbs" id="carouselThumbnails">
            <?php foreach ($clientPhotos as $idx => $photo): ?>
                <div class="carousel-thumb-item <?php echo $idx === 0 ? 'active' : ''; ?>" id="thumb-<?php echo $idx; ?>" onclick="goToCarouselPhoto(<?php echo $idx; ?>)">
                    <img src="<?php echo htmlspecialchars(getCloudinaryUrl($photo, 'f_auto,q_auto:eco,w_180,h_120,c_fill')); ?>" alt="Thumb <?php echo $idx + 1; ?>" loading="lazy" referrerpolicy="no-referrer" onerror="handlePhotoFallback(this)">
                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- Client & Verified Guest Download Security Gate Modal -->
    <div id="clientDownloadModal" class="client-modal-backdrop" onclick="closeClientDownloadModal()">
        <div class="client-modal-box" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div class="client-modal-header">
                <div class="client-icon-badge">
                    <i class="fa-solid fa-cloud-arrow-down"></i>
                </div>
                <div style="flex: 1; text-align: left;">
                    <h3 class="client-modal-title" id="cdmTitle">Download Collection</h3>
                    <div class="client-modal-subtitle" id="cdmSubtitle"><?php echo htmlspecialchars($currentAlbum['title']); ?></div>
                    <div class="client-stats-pill" id="downloadModalStats">
                        <i class="fa-regular fa-image"></i> 1 photo ~5 MB
                    </div>
                </div>
                <button type="button" onclick="closeClientDownloadModal()" style="background:none; border:none; color:#a1a1aa; font-size:1.4rem; cursor:pointer; padding:0; line-height:1;" aria-label="Close">&times;</button>
            </div>

            <!-- Modal Form Body -->
            <div class="client-modal-body">
                <div id="cdm_error" class="cdm-error-alert"></div>
                <div id="cdm_info" class="cdm-info-alert"></div>

                <div id="cdmIdentityStep">
                    <div class="cdm-form-group">
                        <label class="cdm-label" for="cdm_name">Full Name *</label>
                        <input type="text" id="cdm_name" class="cdm-input" placeholder="Your full name" value="" autocomplete="name" required>
                    </div>

                    <div class="cdm-form-group">
                        <label class="cdm-label" for="cdm_email">Email Address *</label>
                        <input type="email" id="cdm_email" class="cdm-input" placeholder="name@domain.com" value="" autocomplete="email" inputmode="email" required>
                        <span style="font-size: 0.76rem; color: #94a3b8; margin-top: 5px; display: block; line-height: 1.4;">Your 6-digit access code and gallery delivery link will be sent here.</span>
                    </div>

                    <div class="cdm-form-group">
                        <label class="cdm-label" for="cdm_phone">Phone Number *</label>
                        <input type="tel" id="cdm_phone" class="cdm-input" placeholder="+1 (555) 000-0000" autocomplete="tel" inputmode="tel" required>
                    </div>
                </div>

                <div class="cdm-form-group" id="cdmOtpGroup" style="display:none; margin-top: 4px;">
                    <label class="cdm-label" for="cdm_otp_code" style="text-align: center;">Enter 6-Digit Passcode *</label>
                    <input type="text"
                           id="cdm_otp_code"
                           class="cdm-input"
                           placeholder="000000"
                           maxlength="6"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           autocomplete="one-time-code"
                           style="letter-spacing: 12px; font-weight: 800; text-align: center; font-size: 1.85rem; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; padding: 14px 16px; border-radius: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.82rem; margin-top: 8px;">
                        <span id="cdmOtpNoticeText" style="color: #94a3b8;">Code sent to your email</span>
                        <button type="button" onclick="resetCdmToIdentityStep()" style="background: none; border: none; color: #f87171; font-weight: 700; cursor: pointer; text-decoration: underline; padding: 0;">
                            Edit contact / Resend
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="client-modal-footer">
                <button type="button" class="btn-cdm-cancel" onclick="closeClientDownloadModal()">Cancel</button>
                <button type="button" class="btn-cdm-submit" id="btnCdmSubmit" onclick="submitClientVerification()">
                    <i class="fa-solid fa-paper-plane"></i> Send Access Code
                </button>
            </div>
        </div>
    </div>

    <!-- Download Toast Feedback -->
    <div id="downloadToast" class="download-toast">
        <i class="fa-solid fa-circle-check"></i>
        <span id="downloadToastText">Downloading photo...</span>
    </div>

    <!-- Fixed Mobile Floating Bottom Dock Navigation Widget -->
    <nav class="mobile-bottom-dock">
        <a href="/" class="dock-item">
            <div class="dock-icon-box"><i class="fa-solid fa-house"></i></div>
            <span class="dock-label">HOME</span>
        </a>
        <a href="/services.php" class="dock-item">
            <div class="dock-icon-box"><i class="fa-solid fa-briefcase"></i></div>
            <span class="dock-label">SERVICES</span>
        </a>
        <a href="/portfolio.php" class="dock-item active">
            <div class="dock-icon-box"><i class="fa-solid fa-film"></i></div>
            <span class="dock-label">WORK</span>
        </a>
        <a href="/contact.php" class="dock-item">
            <div class="dock-icon-box"><i class="fa-solid fa-envelope"></i></div>
            <span class="dock-label">CONTACT</span>
        </a>
        <button type="button" class="dock-item dock-top-btn" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
            <div class="dock-icon-box"><i class="fa-solid fa-arrow-up"></i></div>
            <span class="dock-label">TOP</span>
        </button>
    </nav>

    <?php require_once __DIR__ . '/includes/cookie-banner.php'; ?>
    <script src="/assets/js/main.js"></script>

    <script>
        // Gallery Photos Data
        const galleryPhotos = <?php echo json_encode($clientPhotos); ?>;
        const shootTitle = <?php echo json_encode($currentAlbum['title']); ?>;
        const currentAlbumId = <?php echo (int)($currentAlbum['id'] ?? 0); ?>;
        let currentPhotoIndex = 0;
        let activeDownloadToken = '';

        // Toast Feedback Helper
        function showToast(text) {
            const toast = document.getElementById('downloadToast');
            const toastText = document.getElementById('downloadToastText');
            toastText.textContent = text;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3400);
        }

        // Open the album's own hero cover photo cleanly
        function openHeroCover() {
            const normalizeKey = (u) => String(u || '').split('/').pop().split('?')[0].toLowerCase();
            const coverKey = normalizeKey(albumHeroCoverUrl);
            const matchIdx = galleryPhotos.findIndex(p => p === albumHeroCoverUrl || (coverKey && normalizeKey(p) === coverKey));

            if (matchIdx >= 0) {
                openCarousel(matchIdx);
            } else {
                currentPhotoIndex = 0;
                const imgEl = document.getElementById('carouselMainImg');
                if (imgEl && albumHeroCoverUrl) {
                    imgEl.dataset.fallbackStep = '0';
                    imgEl.src = albumHeroCoverUrl;
                    imgEl.style.opacity = '1';
                    imgEl.style.transform = 'scale(1)';
                }
                const counter = document.getElementById('carouselCounter');
                if (counter) {
                    counter.innerHTML = `<i class="fa-regular fa-image"></i> Cover · ${galleryPhotos.length} Photos`;
                }
                document.getElementById('carouselModal').style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        // Open Carousel at specific index
        function openCarousel(index) {
            if (!galleryPhotos.length) return;
            if (index < 0 || index >= galleryPhotos.length) index = 0;
            currentPhotoIndex = index;
            updateCarouselView();
            document.getElementById('carouselModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // Close Carousel
        function closeCarousel() {
            document.getElementById('carouselModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        // Navigate Carousel
        function prevCarouselPhoto() {
            currentPhotoIndex = (currentPhotoIndex - 1 + galleryPhotos.length) % galleryPhotos.length;
            updateCarouselView();
        }

        function nextCarouselPhoto() {
            currentPhotoIndex = (currentPhotoIndex + 1) % galleryPhotos.length;
            updateCarouselView();
        }

        function goToCarouselPhoto(index) {
            currentPhotoIndex = index;
            updateCarouselView();
        }

        function updateCarouselView() {
            const imgEl = document.getElementById('carouselMainImg');
            imgEl.style.opacity = '0';
            imgEl.style.transform = 'scale(0.98)';
            
            setTimeout(() => {
                imgEl.src = galleryPhotos[currentPhotoIndex];
                imgEl.style.opacity = '1';
                imgEl.style.transform = 'scale(1)';
            }, 100);

            document.getElementById('carouselCounter').innerHTML = 
                `<i class="fa-regular fa-image"></i> Photo ${currentPhotoIndex + 1} of ${galleryPhotos.length}`;

            // Update Thumbnails strip
            const allThumbs = document.querySelectorAll('.carousel-thumb-item');
            allThumbs.forEach((th, idx) => {
                if (idx === currentPhotoIndex) {
                    th.classList.add('active');
                    th.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                } else {
                    th.classList.remove('active');
                }
            });
        }

        // Keyboard Controls
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('carouselModal');
            if (modal && modal.style.display === 'flex') {
                if (e.key === 'ArrowLeft') {
                    prevCarouselPhoto();
                } else if (e.key === 'ArrowRight') {
                    nextCarouselPhoto();
                } else if (e.key === 'Escape') {
                    closeCarousel();
                }
            }
        });

        // Touch Swipe Support for Mobile
        let touchStartX = 0;
        let touchEndX = 0;
        const modalEl = document.getElementById('carouselModal');

        modalEl.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        modalEl.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleGesture();
        }, { passive: true });

        function handleGesture() {
            const threshold = 50;
            if (touchEndX + threshold < touchStartX) {
                nextCarouselPhoto();
            } else if (touchEndX > touchStartX + threshold) {
                prevCarouselPhoto();
            }
        }

        // Client Verification & Download Interception
        let isClientVerified = <?php echo json_encode($isClientLoggedIn); ?>;
        let pendingDownload = null;

        function checkClientAuthBeforeDownload(downloadAction, statsText) {
            if (isClientVerified) {
                downloadAction();
            } else {
                pendingDownload = downloadAction;
                openClientDownloadModal(statsText);
            }
        }

        function resetCdmToIdentityStep() {
            otpStepActive = false;
            const idStep = document.getElementById('cdmIdentityStep');
            const otpGroup = document.getElementById('cdmOtpGroup');
            const errBox = document.getElementById('cdm_error');
            const infoBox = document.getElementById('cdm_info');
            const submitBtn = document.getElementById('btnCdmSubmit');
            const titleEl = document.getElementById('cdmTitle');
            const subEl = document.getElementById('cdmSubtitle');

            if (idStep) idStep.style.display = 'block';
            if (otpGroup) otpGroup.style.display = 'none';
            if (errBox) errBox.style.display = 'none';
            if (infoBox) infoBox.style.display = 'none';
            if (titleEl) titleEl.textContent = 'Download Collection';
            if (subEl) subEl.textContent = shootTitle;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Access Code';
            }
        }

        function openClientDownloadModal(statsText) {
            const statsEl = document.getElementById('downloadModalStats');
            if (statsEl) {
                statsEl.innerHTML = statsText || '<i class="fa-regular fa-image"></i> 1 photo ~5 MB';
            }
            resetCdmToIdentityStep();
            document.getElementById('clientDownloadModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';

            setTimeout(() => {
                const nameInput = document.getElementById('cdm_name');
                if (nameInput && !nameInput.value) nameInput.focus();
            }, 100);
        }

        function closeClientDownloadModal() {
            document.getElementById('clientDownloadModal').style.display = 'none';
            document.body.style.overflow = '';
            resetCdmToIdentityStep();
        }

        let otpStepActive = false;

        async function submitClientVerification() {
            const name = document.getElementById('cdm_name').value.trim();
            const email = document.getElementById('cdm_email').value.trim();
            const phone = document.getElementById('cdm_phone').value.trim();
            const idStep = document.getElementById('cdmIdentityStep');
            const otpGroup = document.getElementById('cdmOtpGroup');
            const otpInput = document.getElementById('cdm_otp_code');
            const errBox = document.getElementById('cdm_error');
            const infoBox = document.getElementById('cdm_info');
            const submitBtn = document.getElementById('btnCdmSubmit');
            const titleEl = document.getElementById('cdmTitle');
            const subEl = document.getElementById('cdmSubtitle');
            const noticeEl = document.getElementById('cdmOtpNoticeText');

            if (errBox) errBox.style.display = 'none';

            if (!otpStepActive) {
                if (!name || name.length < 2) {
                    errBox.style.display = 'block';
                    errBox.textContent = 'Please enter your full name.';
                    return;
                }
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!email || !emailRegex.test(email)) {
                    errBox.style.display = 'block';
                    errBox.textContent = 'Please enter a valid email address.';
                    return;
                }
                const cleanPhone = phone.replace(/[^0-9+]/g, '');
                if (!cleanPhone || cleanPhone.length < 7) {
                    errBox.style.display = 'block';
                    errBox.textContent = 'Please enter a valid phone number.';
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending Access Code...';

                try {
                    const fd = new FormData();
                    fd.append('action', 'request_code');
                    fd.append('name', name);
                    fd.append('email', email);
                    fd.append('phone', phone);
                    fd.append('album_id', currentAlbumId);
                    fd.append('album_title', shootTitle);

                    const resp = await fetch('/api/verify-download.php', { method: 'POST', body: fd });
                    const res = await resp.json();

                    if (res.success) {
                        otpStepActive = true;
                        if (idStep) idStep.style.display = 'none';
                        if (otpGroup) otpGroup.style.display = 'block';
                        if (titleEl) titleEl.textContent = 'Enter Access Code';
                        if (subEl) subEl.textContent = 'Enter the 6-digit passcode sent to your email.';
                        if (infoBox) {
                            infoBox.style.display = 'block';
                            infoBox.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${res.message || 'Passcode dispatched. Please check your inbox.'}`;
                        }
                        if (noticeEl) noticeEl.textContent = 'Sent to ' + email;
                        submitBtn.innerHTML = '<i class="fa-solid fa-cloud-arrow-down"></i> Verify &amp; Download';
                        if (otpInput) {
                            otpInput.value = '';
                            otpInput.focus();
                        }
                    } else {
                        errBox.style.display = 'block';
                        errBox.textContent = res.message || 'Verification failed. Please check your details.';
                        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Access Code';
                    }
                } catch (err) {
                    errBox.style.display = 'block';
                    errBox.textContent = 'Network error while requesting verification code. Please try again.';
                    submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Access Code';
                } finally {
                    submitBtn.disabled = false;
                }
            } else {
                const code = otpInput ? otpInput.value.trim() : '';
                if (!code || code.length !== 6) {
                    errBox.style.display = 'block';
                    errBox.textContent = 'Please enter the 6-digit verification code.';
                    return;
                }
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying Passcode...';

                try {
                    const fd = new FormData();
                    fd.append('action', 'verify_code');
                    fd.append('name', name);
                    fd.append('email', email);
                    fd.append('phone', phone);
                    fd.append('code', code);
                    fd.append('album_id', currentAlbumId);
                    fd.append('album_title', shootTitle);

                    const resp = await fetch('/api/verify-download.php', { method: 'POST', body: fd });
                    const res = await resp.json();

                    if (res.success && res.verified) {
                        isClientVerified = true;
                        activeDownloadToken = res.download_token || '';
                        closeClientDownloadModal();
                        const deliveryNote = res.download_emailed ? ' and delivery dispatched to your inbox' : '';
                        showToast('✓ Access granted! High-resolution download started' + deliveryNote + '.');
                        if (typeof pendingDownload === 'function') {
                            pendingDownload(activeDownloadToken);
                            pendingDownload = null;
                        }
                    } else {
                        errBox.style.display = 'block';
                        errBox.textContent = res.message || 'Invalid verification code. Please check your email.';
                        submitBtn.innerHTML = '<i class="fa-solid fa-cloud-arrow-down"></i> Verify &amp; Download';
                    }
                } catch (err) {
                    errBox.style.display = 'block';
                    errBox.textContent = 'Network error while verifying code.';
                    submitBtn.innerHTML = '<i class="fa-solid fa-cloud-arrow-down"></i> Verify &amp; Download';
                } finally {
                    submitBtn.disabled = false;
                }
            }
        }

        // Download Single Photo (Protected by Client Verification)
        function downloadSinglePhoto(url, photoNum) {
            checkClientAuthBeforeDownload(() => {
                executeSinglePhotoDownload(url, photoNum);
            }, '<i class="fa-regular fa-image"></i> 1 photo ~5 MB');
        }

        async function executeSinglePhotoDownload(url, photoNum) {
            const cleanTitle = shootTitle.replace(/[^a-zA-Z0-9_-]/g, '_');
            const filename = `${cleanTitle}_Photo_${String(photoNum).padStart(2, '0')}.jpg`;
            showToast(`Downloading photo ${photoNum}...`);

            try {
                const response = await fetch(url, { mode: 'cors' });
                if (!response.ok) throw new Error('Network error');
                const blob = await response.blob();
                const blobUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = blobUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(blobUrl);
                document.body.removeChild(a);
                showToast(`Downloaded: ${filename}`);
            } catch (err) {
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                a.target = '_blank';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        }

        // Download from Carousel (Protected by Client Verification)
        function downloadCurrentCarouselPhoto() {
            downloadSinglePhoto(galleryPhotos[currentPhotoIndex], currentPhotoIndex + 1);
        }

        // Download All Photos Functionality (Protected by Verification Gate & Authenticated ZIP API)
        function downloadAllPhotos() {
            checkClientAuthBeforeDownload((token) => {
                executeAllPhotosDownload(token || activeDownloadToken);
            }, `<i class="fa-solid fa-images"></i> ${galleryPhotos.length} photos (.ZIP archive)`);
        }

        async function executeAllPhotosDownload(token) {
            const directUrl = `/api/download-album.php?album_id=${currentAlbumId}&token=${encodeURIComponent(token || '')}`;
            showToast(`Checking gallery archive for ${galleryPhotos.length} photos (.ZIP)...`);

            try {
                const statusResp = await fetch(`/api/download-album.php?action=status&album_id=${currentAlbumId}&token=${encodeURIComponent(token || '')}`);
                const statusData = await statusResp.json();

                if (statusData && statusData.ready) {
                    showToast(`✓ Gallery package ready! Downloading ${galleryPhotos.length} photos (.ZIP)...`);
                    window.location.href = statusData.download_url || directUrl;
                    return;
                }

                showToast(`Packaging ${galleryPhotos.length} studio-master photos in parallel... Please wait a moment.`);
                const prepResp = await fetch(`/api/download-album.php?action=prepare&album_id=${currentAlbumId}&token=${encodeURIComponent(token || '')}`);
                const prepData = await prepResp.json();

                if (prepData && prepData.ready) {
                    showToast(`✓ High-resolution archive complete! Downloading now...`);
                    window.location.href = prepData.download_url || directUrl;
                } else {
                    window.location.href = directUrl;
                }
            } catch (e) {
                window.location.href = directUrl;
            }
        }

        // Native Share Web API or Clipboard copy
        async function shareGalleryPage() {
            const shareData = {
                title: `${shootTitle} — Falhen Productions`,
                text: `Explore the portfolio gallery for ${shootTitle} by Falhen Productions.`,
                url: window.location.href
            };

            if (navigator.share && navigator.canShare && navigator.canShare(shareData)) {
                try {
                    await navigator.share(shareData);
                } catch (e) {
                    // user cancelled share
                }
            } else {
                navigator.clipboard.writeText(window.location.href).then(() => {
                    showToast('Gallery link copied to clipboard!');
                }).catch(() => {
                    showToast('Link copied!');
                });
            }
        }
    </script>
</body>
</html>
