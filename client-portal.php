<?php
/**
 * client-portal.php
 * Falhen Media — Independent Client Gallery Subdomain Portal (client.falhen.com)
 * Built with the falhen.com/portfolio dark editorial design system:
 * - Responsive Masonry Grid View & Dynamic Full-Screen Lightbox (Mobile, Tablet, Desktop)
 * - Google Drive API -> Cloudinary Lightweight Asset Sync
 * - 2-Step Client Security Gate (Name, Email, Phone + 6-Digit OTP) & Bulk ZIP Download
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

$masterPortfolioItems = getPortfolioRepo();
$albumsRepo = [];

foreach ($masterPortfolioItems as $item) {
    if (($item['media_type'] ?? 'photo') === 'photo' || !isset($item['media_type'])) {
        $albumsRepo[(int)$item['id']] = $item;
    }
}
if (empty($albumsRepo)) {
    foreach ($masterPortfolioItems as $item) {
        $albumsRepo[(int)$item['id']] = $item;
    }
}

// Check if client email was passed via query or session (e.g. from /dashboard?view=client)
$requestedClientEmail = strtolower(trim($_GET['client_email'] ?? $_POST['client_email'] ?? ''));
if (!empty($requestedClientEmail) && filter_var($requestedClientEmail, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['portal_lookup_email'] = $requestedClientEmail;
}

// Determine active album
$albumId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['album_id']) ? (int)$_GET['album_id'] : (int)key($albumsRepo));
$currentAlbum = reset($albumsRepo);

if (isset($albumsRepo[$albumId])) {
    $currentAlbum = $albumsRepo[$albumId];
} else {
    foreach ($masterPortfolioItems as $mIt) {
        if ((int)($mIt['id'] ?? 0) === $albumId) {
            $currentAlbum = $mIt;
            break;
        }
    }
}

$currentAlbumId = (int)($currentAlbum['id'] ?? 1);
$coverImage = getCloudinaryUrl($currentAlbum['image'] ?? $currentAlbum['cover'] ?? '/assets/img/portfolio/portfolio_halima.png');
$pageTitle = htmlspecialchars($currentAlbum['title'] ?? 'Client Gallery') . " — Client Portal | Falhen Media";

// Client Authentication & Verification State
$isClientVerified = !empty($_SESSION['client_download_verified']) && !empty($_SESSION['registered_client_email']);
$downloadToken = $_SESSION['client_download_token'] ?? '';
$clientEmail = $_SESSION['registered_client_email'] ?? $_SESSION['portal_lookup_email'] ?? $_COOKIE['falhen_client_email'] ?? '';
$clientName = $_SESSION['registered_client_name'] ?? '';
$clientPhone = $_SESSION['registered_client_phone'] ?? '';

// Fetch photos from Google Drive folder if configured, then sync/transform via Cloudinary
$rawPhotos = [];
$hasGdriveSource = !empty($currentAlbum['gdrive_url']);
if ($hasGdriveSource) {
    $rawPhotos = getGoogleDriveFolderPhotos($currentAlbum['gdrive_url']);
}

if (empty($rawPhotos) && !empty($currentAlbum['gallery_photos']) && is_array($currentAlbum['gallery_photos'])) {
    $rawPhotos = $currentAlbum['gallery_photos'];
} elseif (empty($rawPhotos) && !empty($currentAlbum['photos']) && is_array($currentAlbum['photos'])) {
    $rawPhotos = $currentAlbum['photos'];
}

if (empty($rawPhotos)) {
    $cNameLower = strtolower(trim($currentAlbum['client'] ?? ''));
    $cTitleLower = strtolower(trim($currentAlbum['title'] ?? ''));
    if ($currentAlbumId === 1 || strpos($cNameLower, 'king david') !== false || strpos($cTitleLower, 'king david') !== false) {
        $rawPhotos = [
            '/assets/img/portfolio/king_david_01.jpg',
            '/assets/img/portfolio/king_david_02.jpg',
            '/assets/img/portfolio/king_david_03.jpg',
            $coverImage,
            '/assets/img/portfolio/portfolio_wedding.png',
            '/assets/img/portfolio/portfolio_award.png',
            '/assets/img/portfolio/portfolio_commercial.png',
            '/assets/img/portfolio/portfolio_halima.png'
        ];
    } else {
        $rawPhotos = [
            $coverImage,
            '/assets/img/portfolio/portfolio_halima.png',
            '/assets/img/portfolio/portfolio_wedding.png',
            '/assets/img/portfolio/portfolio_award.png',
            '/assets/img/portfolio/king_david_01.jpg',
            '/assets/img/portfolio/king_david_02.jpg',
            '/assets/img/portfolio/king_david_03.jpg',
            '/assets/img/portfolio/portfolio_commercial.png'
        ];
    }
}

$photoItems = buildClientGalleryPhotoItems($rawPhotos, $currentAlbumId);
$totalPhotos = count($photoItems);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php if (function_exists('renderGoogleAnalyticsTag')) renderGoogleAnalyticsTag(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        :root {
            --bg-dark: #030305;
            --surface-card: #0b0b0f;
            --surface-elevated: #111218;
            --brand-red: #dc2626;
            --brand-red-hover: #ef4444;
            --brand-blue: #2563eb;
            --brand-blue-hover: #1d4ed8;
            --text-main: #ffffff;
            --text-secondary: #d4d4d8;
            --text-muted: #a1a1aa;
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-highlight: rgba(255, 255, 255, 0.16);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Top Sticky Navigation Bar matching falhen.com/portfolio */
        .port-nav-bar {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(3, 3, 5, 0.9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-subtle);
            z-index: 100;
            padding: 16px 28px;
        }

        .port-nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .port-nav-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .port-back-link {
            color: #d4d4d8;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 10px;
            border: 1px solid var(--border-subtle);
            background: rgba(255, 255, 255, 0.03);
            transition: all 0.2s ease;
        }

        .port-back-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--border-highlight);
        }

        .subdomain-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.4px;
            background: rgba(220, 38, 38, 0.12);
            border: 1px solid rgba(220, 38, 38, 0.35);
            color: #f87171;
        }

        .port-logo-center img {
            height: 30px;
            width: auto;
            display: block;
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.5));
        }

        .port-nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-nav-action {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--border-highlight);
            color: #ffffff;
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 0.86rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-nav-action:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.28);
        }

        .btn-nav-primary {
            background: var(--brand-red);
            color: #ffffff;
            border: none;
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 0.86rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.38);
            transition: all 0.2s ease;
        }

        .btn-nav-primary:hover {
            background: var(--brand-red-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.52);
        }

        /* Editorial Hero Stage matching falhen.com/portfolio */
        .client-hero-stage {
            position: relative;
            padding: 56px 28px 42px;
            background:
                linear-gradient(180deg, rgba(3, 3, 5, 0.78) 0%, rgba(3, 3, 5, 0.96) 85%, #030305 100%),
                radial-gradient(circle at 50% 20%, rgba(220, 38, 38, 0.22) 0%, transparent 62%),
                url('<?php echo htmlspecialchars($coverImage); ?>') center/cover no-repeat;
            border-bottom: 1px solid var(--border-subtle);
        }

        .client-hero-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 44px;
            align-items: center;
        }

        .hero-featured-card {
            position: relative;
            border-radius: 22px;
            overflow: hidden;
            background: #000;
            border: 1px solid rgba(255, 255, 255, 0.14);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.85), 0 0 30px rgba(220, 38, 38, 0.12);
            aspect-ratio: 4 / 3.6;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .hero-featured-card:hover {
            transform: translateY(-3px);
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
            background: linear-gradient(180deg, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.55) 100%);
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            padding: 18px;
        }

        .hero-zoom-pill {
            background: rgba(3, 3, 5, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.22);
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 24px;
            font-size: 0.82rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .hero-details-col {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .hero-badges-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .badge-crimson {
            background: var(--brand-red);
            color: #ffffff;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-sync {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #34d399;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-verified {
            background: rgba(37, 99, 235, 0.15);
            border: 1px solid rgba(37, 99, 235, 0.4);
            color: #60a5fa;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .hero-main-title {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 3rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 12px 0;
            line-height: 1.12;
            letter-spacing: -1px;
        }

        .hero-main-title span {
            color: var(--brand-red);
        }

        .hero-description {
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1.6;
            margin: 0 0 22px 0;
            max-width: 660px;
        }

        .hero-meta-pills {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .meta-pill {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #e4e4e7;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .hero-cta-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-download-all {
            background: var(--brand-red);
            color: #ffffff;
            border: none;
            padding: 13px 26px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
            transition: all 0.2s ease;
        }

        .btn-download-all:hover {
            background: var(--brand-red-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(220, 38, 38, 0.55);
        }

        .btn-fullview-launch {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid var(--border-highlight);
            color: #ffffff;
            padding: 13px 22px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-fullview-launch:hover {
            background: rgba(255, 255, 255, 0.14);
            transform: translateY(-2px);
        }

        /* Album Selector & View Controls Toolbar */
        .portal-toolbar-wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 26px 28px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
        }

        .album-selector-strip {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
        }

        .album-tab-pill {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 0.84rem;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .album-tab-pill:hover {
            color: #ffffff;
            border-color: var(--border-highlight);
            background: rgba(255, 255, 255, 0.08);
        }

        .album-tab-pill.active {
            background: rgba(220, 38, 38, 0.15);
            border-color: var(--brand-red);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.22);
        }

        .view-toggle-group {
            display: inline-flex;
            align-items: center;
            background: #0b0b0f;
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            padding: 4px;
            gap: 4px;
        }

        .view-toggle-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .view-toggle-btn.active {
            background: var(--brand-red);
            color: #ffffff;
        }

        /* ==============================================================
           R2: RESPONSIVE MASONRY GALLERY VIEW (Desktop, Tablet, Mobile)
           ============================================================== */
        .gallery-masonry-section {
            max-width: 1400px;
            margin: 0 auto;
            padding: 18px 28px 90px;
        }

        .masonry-gallery-grid {
            column-count: 3;
            column-gap: 22px;
            width: 100%;
        }

        .masonry-gallery-grid.cols-4 {
            column-count: 4;
        }

        .masonry-photo-card {
            break-inside: avoid;
            margin-bottom: 22px;
            position: relative;
            border-radius: 18px;
            overflow: hidden;
            background: var(--surface-card);
            border: 1px solid var(--border-subtle);
            cursor: pointer;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.3s, box-shadow 0.3s;
        }

        .masonry-photo-card:hover {
            transform: translateY(-4px);
            border-color: rgba(220, 38, 38, 0.45);
            box-shadow: 0 18px 44px rgba(0, 0, 0, 0.78);
        }

        .masonry-img-wrap {
            width: 100%;
            position: relative;
            overflow: hidden;
            background: #0d0e14;
        }

        .masonry-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.45s ease, filter 0.3s ease;
        }

        .masonry-photo-card:hover .masonry-img-wrap img {
            transform: scale(1.04);
        }

        .card-hover-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0.15) 0%, transparent 40%, rgba(0,0,0,0.82) 100%);
            opacity: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 14px;
            transition: opacity 0.25s ease;
        }

        .masonry-photo-card:hover .card-hover-overlay {
            opacity: 1;
        }

        .card-top-badges {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-num-badge {
            background: rgba(3, 3, 5, 0.78);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #ffffff;
            font-size: 0.74rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 12px;
        }

        .card-cdn-badge {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.45);
            color: #6ee7b7;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 10px;
        }

        .card-bottom-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .card-photo-caption {
            font-size: 0.82rem;
            font-weight: 600;
            color: #f4f4f5;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .card-action-btns {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .overlay-circle-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(15, 15, 22, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.22);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .overlay-circle-btn:hover {
            background: var(--brand-red);
            border-color: var(--brand-red);
            transform: scale(1.08);
        }

        /* ==============================================================
           DYNAMIC FULL-SCREEN LIGHTBOX VIEW (Mobile, Tablet, Desktop)
           ============================================================== */
        .carousel-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(3, 3, 5, 0.96);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 99999;
            display: none;
            flex-direction: column;
            justify-content: space-between;
            padding: 18px 28px 22px;
        }

        .carousel-top-bar {
            max-width: 1500px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .carousel-counter-pill {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid var(--border-highlight);
            padding: 7px 16px;
            border-radius: 20px;
            font-size: 0.86rem;
            font-weight: 600;
            color: #f4f4f5;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .carousel-top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-carousel-tool {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #ffffff;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 0.84rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-carousel-tool:hover {
            background: rgba(255, 255, 255, 0.16);
        }

        .btn-carousel-download {
            background: var(--brand-red);
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
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4);
            transition: all 0.2s;
        }

        .btn-carousel-download:hover {
            background: var(--brand-red-hover);
        }

        .btn-carousel-close {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 1.1rem;
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

        .carousel-center-stage {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            max-width: 1500px;
            width: 100%;
            margin: 0 auto;
            padding: 12px 0;
            overflow: hidden;
        }

        .carousel-arrow-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(20, 20, 28, 0.82);
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #ffffff;
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            z-index: 10;
            flex-shrink: 0;
        }

        .carousel-arrow-btn:hover {
            background: var(--brand-red);
            border-color: var(--brand-red);
            transform: scale(1.08);
        }

        .carousel-main-image-wrap {
            flex: 1;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 18px;
            overflow: hidden;
        }

        .carousel-main-img {
            max-width: 86vw;
            max-height: 70vh;
            border-radius: 14px;
            object-fit: contain;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: opacity 0.2s ease, transform 0.28s ease;
            cursor: zoom-in;
        }

        .carousel-main-img.zoomed {
            transform: scale(1.65);
            cursor: zoom-out;
        }

        .carousel-bottom-thumbs {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 10px;
            overflow-x: auto;
            padding: 8px 4px 2px;
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
        }

        .carousel-thumb-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* ==============================================================
           R3: 2-STEP CLIENT SECURITY & 6-DIGIT OTP DOWNLOAD MODAL
           ============================================================== */
        .client-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.82);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 100001;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .client-modal-box {
            background: #111218;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            width: 100%;
            max-width: 490px;
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.92), 0 0 35px rgba(220, 38, 38, 0.18);
        }

        .client-modal-header {
            padding: 24px 28px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .client-icon-badge {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .client-modal-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 4px 0;
        }

        .client-modal-subtitle {
            font-size: 0.84rem;
            color: #94a3b8;
            margin: 0 0 8px 0;
        }

        .client-stats-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.76rem;
            color: #e4e4e7;
        }

        .client-modal-body {
            padding: 22px 28px 24px;
        }

        .cdm-form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .cdm-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #e4e4e7;
            margin-bottom: 6px;
        }

        .cdm-input {
            width: 100%;
            background: #181922;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 10px;
            padding: 11px 14px;
            color: #ffffff;
            font-size: 0.92rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }

        .cdm-input:focus {
            border-color: var(--brand-red);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2);
        }

        .cdm-otp-input {
            letter-spacing: 8px;
            font-size: 1.35rem;
            font-weight: 800;
            text-align: center;
            padding: 12px;
        }

        .cdm-error-alert {
            display: none;
            background: rgba(239, 68, 68, 0.14);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.83rem;
            margin-bottom: 16px;
        }

        .cdm-info-alert {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #6ee7b7;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.83rem;
            margin-bottom: 16px;
        }

        .cdm-privacy-note {
            font-size: 0.78rem;
            color: #94a3b8;
            line-height: 1.45;
            margin: 12px 0 0;
        }

        .client-modal-footer {
            padding: 16px 28px 22px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-cdm-cancel {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #d4d4d8;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-cdm-submit {
            background: var(--brand-red);
            border: none;
            color: #ffffff;
            padding: 10px 22px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4);
        }

        .btn-cdm-submit:hover {
            background: var(--brand-red-hover);
        }

        /* Toast notification */
        .download-toast {
            position: fixed;
            bottom: 28px;
            right: 28px;
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #ffffff;
            padding: 14px 22px;
            border-radius: 12px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.75);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            z-index: 100005;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .download-toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Responsive Breakpoints (Tablet & Mobile) */
        @media (max-width: 1024px) {
            .client-hero-inner {
                grid-template-columns: 1fr;
                gap: 32px;
            }
            .hero-featured-card {
                max-width: 520px;
            }
            .masonry-gallery-grid,
            .masonry-gallery-grid.cols-4 {
                column-count: 2;
                column-gap: 16px;
            }
            .masonry-photo-card {
                margin-bottom: 16px;
            }
            .hero-main-title {
                font-size: 2.4rem;
            }
        }

        @media (max-width: 640px) {
            .port-nav-bar {
                padding: 12px 16px;
            }
            .port-back-link span,
            .subdomain-pill {
                display: none;
            }
            .client-hero-stage {
                padding: 28px 16px 26px;
            }
            .hero-main-title {
                font-size: 1.9rem;
            }
            .portal-toolbar-wrap {
                padding: 18px 16px 8px;
            }
            .gallery-masonry-section {
                padding: 12px 16px 80px;
            }
            .masonry-gallery-grid,
            .masonry-gallery-grid.cols-4 {
                column-count: 1;
                column-gap: 0;
            }
            .card-hover-overlay {
                opacity: 1;
                background: linear-gradient(180deg, rgba(0,0,0,0.2) 0%, transparent 50%, rgba(0,0,0,0.78) 100%);
            }
            .carousel-modal-backdrop {
                padding: 12px;
            }
            .carousel-arrow-btn {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>

    <!-- Top Sticky Navigation Bar (falhen.com/portfolio feel & look) -->
    <header class="port-nav-bar">
        <div class="port-nav-container">
            <div class="port-nav-left">
                <a href="/portfolio.php" class="port-back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back to Portfolio</span>
                </a>
                <span class="subdomain-pill">
                    <i class="fa-solid fa-lock"></i> client.falhen.com
                </span>
            </div>

            <a href="/" class="port-logo-center" aria-label="Falhen Media Home">
                <img src="/assets/img/icons/logo.png" alt="Falhen Media">
            </a>

            <div class="port-nav-actions">
                <button type="button" class="btn-nav-action" onclick="openDynamicFullView(0)">
                    <i class="fa-solid fa-expand"></i>
                    <span>Dynamic Full View</span>
                </button>
                <button type="button" class="btn-nav-primary" onclick="triggerBulkDownload()">
                    <i class="fa-solid fa-cloud-arrow-down"></i>
                    <span>Download All (<?php echo $totalPhotos; ?>)</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Editorial Hero Stage -->
    <section class="client-hero-stage">
        <div class="client-hero-inner">
            <!-- Featured Cover Preview -->
            <div class="hero-featured-card" onclick="openDynamicFullView(0)">
                <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="<?php echo htmlspecialchars($currentAlbum['title'] ?? 'Client Gallery'); ?>">
                <div class="hero-featured-overlay">
                    <span class="hero-zoom-pill">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Tap for Dynamic Full View
                    </span>
                </div>
            </div>

            <!-- Shoot Details & Actions -->
            <div class="hero-details-col">
                <div class="hero-badges-row">
                    <span class="badge-crimson"><?php echo htmlspecialchars($currentAlbum['category'] ?? 'Client Delivery'); ?></span>
                    <span class="badge-sync">
                        <i class="fa-solid fa-bolt"></i>
                        <?php echo $hasGdriveSource ? 'Synced via Google Drive & Cloudinary CDN' : 'Cloudinary Lightweight Optimized'; ?>
                    </span>
                    <?php if ($isClientVerified): ?>
                        <span class="badge-verified" id="verifiedStatusBadge">
                            <i class="fa-solid fa-shield-check"></i> Verified: <?php echo htmlspecialchars($clientEmail); ?>
                        </span>
                    <?php else: ?>
                        <span class="badge-verified" id="verifiedStatusBadge" style="display:none;"></span>
                    <?php endif; ?>
                </div>

                <h1 class="hero-main-title">
                    <?php echo htmlspecialchars($currentAlbum['title'] ?? 'Client Gallery'); ?>
                </h1>

                <p class="hero-description">
                    <?php echo htmlspecialchars($currentAlbum['description'] ?? 'Explore your curated photography gallery in responsive Masonry or Dynamic Full View. All assets are synced via Google Drive and optimized through Cloudinary for fast, lightweight device downloads.'); ?>
                </p>

                <div class="hero-meta-pills">
                    <span class="meta-pill">
                        <i class="fa-regular fa-user"></i>
                        Client: <?php echo htmlspecialchars($currentAlbum['client'] ?? ($clientName ?: 'Falhen VIP Client')); ?>
                    </span>
                    <span class="meta-pill">
                        <i class="fa-regular fa-images"></i>
                        <?php echo $totalPhotos; ?> Lightweight Photos
                    </span>
                    <span class="meta-pill">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                        Mobile · Tablet · Desktop Ready
                    </span>
                </div>

                <div class="hero-cta-row">
                    <button type="button" class="btn-download-all" id="btnHeroDownloadAll" onclick="triggerBulkDownload()">
                        <i class="fa-solid fa-file-zipper"></i>
                        Download All Photos (.ZIP)
                    </button>
                    <button type="button" class="btn-fullview-launch" onclick="openDynamicFullView(0)">
                        <i class="fa-solid fa-maximize"></i>
                        Launch Dynamic Full View
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Album Switcher & Masonry Density Controls -->
    <div class="portal-toolbar-wrap">
        <div class="album-selector-strip" role="tablist" aria-label="Client Galleries">
            <?php foreach ($albumsRepo as $aId => $albumItem): ?>
                <a href="?id=<?php echo (int)$aId; ?>"
                   class="album-tab-pill <?php echo ((int)$aId === $currentAlbumId) ? 'active' : ''; ?>">
                    <i class="fa-regular fa-folder-open"></i>
                    <?php echo htmlspecialchars($albumItem['title'] ?? ('Album #' . $aId)); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="view-toggle-group" aria-label="Gallery Layout Switcher">
            <button type="button" class="view-toggle-btn active" id="btnMasonry3" onclick="setMasonryColumns(3)">
                <i class="fa-solid fa-table-cells-large"></i> Editorial Masonry
            </button>
            <button type="button" class="view-toggle-btn" id="btnMasonry4" onclick="setMasonryColumns(4)">
                <i class="fa-solid fa-grip"></i> Compact Masonry
            </button>
            <button type="button" class="view-toggle-btn" onclick="openDynamicFullView(0)">
                <i class="fa-solid fa-expand"></i> Full Screen
            </button>
        </div>
    </div>

    <!-- R2: Responsive Masonry Gallery Grid -->
    <main class="gallery-masonry-section">
        <div id="masonryGrid" class="masonry-gallery-grid">
            <?php foreach ($photoItems as $item): ?>
                <article class="masonry-photo-card"
                         data-index="<?php echo (int)$item['index']; ?>"
                         onclick="openDynamicFullView(<?php echo (int)$item['index']; ?>)">
                    <div class="masonry-img-wrap" style="aspect-ratio: <?php echo htmlspecialchars($item['aspect_ratio']); ?>;">
                        <img src="<?php echo htmlspecialchars($item['masonry_url']); ?>"
                             alt="<?php echo htmlspecialchars(($currentAlbum['title'] ?? 'Gallery') . ' — Photo ' . $item['number']); ?>"
                             loading="lazy">
                    </div>
                    <div class="card-hover-overlay">
                        <div class="card-top-badges">
                            <span class="card-num-badge">#<?php echo str_pad((string)$item['number'], 2, '0', STR_PAD_LEFT); ?></span>
                            <span class="card-cdn-badge">
                                <i class="fa-solid fa-cloud-bolt"></i> Lightweight HD
                            </span>
                        </div>
                        <div class="card-bottom-actions">
                            <span class="card-photo-caption">
                                <?php echo htmlspecialchars($currentAlbum['title'] ?? 'Falhen Shoot'); ?>
                            </span>
                            <div class="card-action-btns">
                                <button type="button"
                                        class="overlay-circle-btn"
                                        title="Open in Dynamic Full View"
                                        onclick="event.stopPropagation(); openDynamicFullView(<?php echo (int)$item['index']; ?>);">
                                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                                </button>
                                <button type="button"
                                        class="overlay-circle-btn"
                                        title="Download Photo #<?php echo (int)$item['number']; ?>"
                                        onclick="event.stopPropagation(); triggerSinglePhotoDownload(<?php echo (int)$item['index']; ?>);">
                                    <i class="fa-solid fa-download"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- R2: Dynamic Full-Screen Lightbox View -->
    <div id="dynamicFullViewModal" class="carousel-modal-backdrop" role="dialog" aria-modal="true" aria-label="Dynamic Full View">
        <div class="carousel-top-bar">
            <div class="carousel-counter-pill" id="fullViewCounter">
                <i class="fa-regular fa-image"></i> Photo 1 of <?php echo $totalPhotos; ?>
            </div>
            <div class="carousel-top-actions">
                <button type="button" class="btn-carousel-tool" onclick="toggleFullViewZoom()">
                    <i class="fa-solid fa-magnifying-glass-plus" id="zoomIcon"></i>
                    <span id="zoomLabel">Zoom</span>
                </button>
                <button type="button" class="btn-carousel-download" onclick="triggerSinglePhotoDownload(currentPhotoIndex)">
                    <i class="fa-solid fa-download"></i> Download Photo
                </button>
                <button type="button" class="btn-carousel-tool" onclick="triggerBulkDownload()">
                    <i class="fa-solid fa-file-zipper"></i> Download All
                </button>
                <button type="button" class="btn-carousel-close" onclick="closeDynamicFullView()" aria-label="Close Full View">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="carousel-center-stage" id="fullViewTouchStage">
            <button type="button" class="carousel-arrow-btn" onclick="prevFullViewPhoto()" aria-label="Previous Photo">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="carousel-main-image-wrap">
                <img id="fullViewMainImg"
                     class="carousel-main-img"
                     src="<?php echo htmlspecialchars($photoItems[0]['full_url'] ?? $coverImage); ?>"
                     alt="Full screen preview"
                     onclick="toggleFullViewZoom()">
            </div>
            <button type="button" class="carousel-arrow-btn" onclick="nextFullViewPhoto()" aria-label="Next Photo">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <div class="carousel-bottom-thumbs" id="fullViewThumbs">
            <?php foreach ($photoItems as $item): ?>
                <div class="carousel-thumb-item <?php echo ($item['index'] === 0) ? 'active' : ''; ?>"
                     onclick="goToFullViewPhoto(<?php echo (int)$item['index']; ?>)">
                    <img src="<?php echo htmlspecialchars($item['masonry_url']); ?>"
                         alt="Thumb <?php echo (int)$item['number']; ?>"
                         loading="lazy">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- R3: 2-Step Client Security Verification & OTP Download Modal -->
    <div id="clientDownloadModal" class="client-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="cdmModalTitle">
        <div class="client-modal-box">
            <div class="client-modal-header">
                <div class="client-icon-badge">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div>
                    <h2 class="client-modal-title" id="cdmModalTitle">Client Security Verification</h2>
                    <p class="client-modal-subtitle" id="cdmModalSubtitle">
                        Verify your name, email &amp; phone number to unlock your gallery download.
                    </p>
                    <span class="client-stats-pill" id="downloadModalStats">
                        <i class="fa-solid fa-images"></i> <?php echo $totalPhotos; ?> lightweight photos
                    </span>
                </div>
            </div>

            <div class="client-modal-body">
                <div id="cdm_error" class="cdm-error-alert"></div>
                <div id="cdm_info" class="cdm-info-alert" style="display:none;"></div>

                <!-- STEP 1: Client Identity (Full Name, Email, Phone Number) -->
                <div id="cdmStepIdentity">
                    <div class="cdm-form-group">
                        <label class="cdm-label" for="cdm_name">Full Name *</label>
                        <input type="text" id="cdm_name" class="cdm-input" placeholder="Your full name" value="<?php echo htmlspecialchars($clientName); ?>" required>
                    </div>

                    <div class="cdm-form-group">
                        <label class="cdm-label" for="cdm_email">Client Email Address *</label>
                        <input type="email" id="cdm_email" class="cdm-input" placeholder="client@falhen.com" value="<?php echo htmlspecialchars($clientEmail); ?>" required>
                    </div>

                    <div class="cdm-form-group">
                        <label class="cdm-label" for="cdm_phone">Phone Number *</label>
                        <input type="tel" id="cdm_phone" class="cdm-input" placeholder="+1 (555) 234-5678" value="<?php echo htmlspecialchars($clientPhone); ?>" required>
                    </div>
                </div>

                <!-- STEP 2: 6-Digit One-Time Verification Passcode (OTP) -->
                <div id="cdmStepOtp" style="display:none;">
                    <div class="cdm-form-group">
                        <label class="cdm-label" for="cdm_otp_code">Enter 6-Digit Verification Code *</label>
                        <input type="text"
                               id="cdm_otp_code"
                               class="cdm-input cdm-otp-input"
                               placeholder="000000"
                               maxlength="6"
                               inputmode="numeric"
                               autocomplete="one-time-code">
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; color:#94a3b8;">
                        <span id="otpDestinationHint">Sent to your registered email/phone</span>
                        <button type="button" onclick="resetToIdentityStep()" style="background:none; border:none; color:#ef4444; font-weight:700; cursor:pointer;">
                            Resend / Edit Details
                        </button>
                    </div>
                </div>

                <p class="cdm-privacy-note">
                    <i class="fa-solid fa-shield-halved" style="color: #ef4444; margin-right: 4px;"></i>
                    Protected by Falhen Media Client Security Gate. Your Name, Email, Phone Number, and 6-digit OTP verify authorized access before downloading.
                </p>
            </div>

            <div class="client-modal-footer">
                <button type="button" class="btn-cdm-cancel" onclick="closeClientDownloadModal()">Cancel</button>
                <button type="button" class="btn-cdm-submit" id="btnCdmSubmit" onclick="handleVerificationSubmit()">
                    <i class="fa-solid fa-paper-plane"></i> Send Verification Code
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Feedback -->
    <div id="downloadToast" class="download-toast">
        <i class="fa-solid fa-circle-check" style="color:#ef4444;"></i>
        <span id="downloadToastText">Preparing download...</span>
    </div>

    <script>
        const galleryItems = <?php echo json_encode($photoItems); ?>;
        const currentAlbumId = <?php echo (int)$currentAlbumId; ?>;
        const shootTitle = <?php echo json_encode($currentAlbum['title'] ?? 'Falhen_Gallery'); ?>;
        let isClientVerified = <?php echo json_encode($isClientVerified); ?>;
        let downloadToken = <?php echo json_encode($downloadToken); ?>;
        let currentPhotoIndex = 0;
        let verificationStep = 'identity'; // 'identity' | 'otp'
        let pendingDownloadCallback = null;

        function showToast(message) {
            const toast = document.getElementById('downloadToast');
            const textEl = document.getElementById('downloadToastText');
            textEl.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3400);
        }

        function setMasonryColumns(cols) {
            const grid = document.getElementById('masonryGrid');
            const btn3 = document.getElementById('btnMasonry3');
            const btn4 = document.getElementById('btnMasonry4');
            if (cols === 4) {
                grid.classList.add('cols-4');
                btn4.classList.add('active');
                btn3.classList.remove('active');
            } else {
                grid.classList.remove('cols-4');
                btn3.classList.add('active');
                btn4.classList.remove('active');
            }
        }

        // Dynamic Full View Controls
        function openDynamicFullView(index) {
            if (index < 0 || index >= galleryItems.length) index = 0;
            currentPhotoIndex = index;
            updateDynamicFullView();
            document.getElementById('dynamicFullViewModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeDynamicFullView() {
            const img = document.getElementById('fullViewMainImg');
            img.classList.remove('zoomed');
            document.getElementById('dynamicFullViewModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        function toggleFullViewZoom() {
            const img = document.getElementById('fullViewMainImg');
            const label = document.getElementById('zoomLabel');
            img.classList.toggle('zoomed');
            label.textContent = img.classList.contains('zoomed') ? 'Reset' : 'Zoom';
        }

        function prevFullViewPhoto() {
            currentPhotoIndex = (currentPhotoIndex - 1 + galleryItems.length) % galleryItems.length;
            updateDynamicFullView();
        }

        function nextFullViewPhoto() {
            currentPhotoIndex = (currentPhotoIndex + 1) % galleryItems.length;
            updateDynamicFullView();
        }

        function goToFullViewPhoto(index) {
            currentPhotoIndex = index;
            updateDynamicFullView();
        }

        function updateDynamicFullView() {
            const item = galleryItems[currentPhotoIndex];
            const img = document.getElementById('fullViewMainImg');
            img.classList.remove('zoomed');
            document.getElementById('zoomLabel').textContent = 'Zoom';
            img.style.opacity = '0.25';

            setTimeout(() => {
                img.src = item.full_url;
                img.style.opacity = '1';
            }, 90);

            document.getElementById('fullViewCounter').innerHTML =
                `<i class="fa-regular fa-image"></i> Photo ${currentPhotoIndex + 1} of ${galleryItems.length}`;

            const thumbs = document.querySelectorAll('#fullViewThumbs .carousel-thumb-item');
            thumbs.forEach((t, idx) => {
                if (idx === currentPhotoIndex) {
                    t.classList.add('active');
                    t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                } else {
                    t.classList.remove('active');
                }
            });
        }

        // Keyboard & Touch-Swipe Support for Dynamic Full View
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('dynamicFullViewModal');
            if (modal && modal.style.display === 'flex') {
                if (e.key === 'ArrowLeft') prevFullViewPhoto();
                else if (e.key === 'ArrowRight') nextFullViewPhoto();
                else if (e.key === 'Escape') closeDynamicFullView();
            }
        });

        let touchStartX = 0;
        const touchStage = document.getElementById('fullViewTouchStage');
        touchStage.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        touchStage.addEventListener('touchend', function(e) {
            const touchEndX = e.changedTouches[0].screenX;
            if (touchEndX + 45 < touchStartX) nextFullViewPhoto();
            else if (touchEndX > touchStartX + 45) prevFullViewPhoto();
        }, { passive: true });

        // Security Gate & Download Orchestration
        function requireVerifiedClient(callback, statsHtml) {
            if (isClientVerified) {
                callback();
            } else {
                pendingDownloadCallback = callback;
                openClientDownloadModal(statsHtml);
            }
        }

        function openClientDownloadModal(statsHtml) {
            if (statsHtml) {
                document.getElementById('downloadModalStats').innerHTML = statsHtml;
            }
            resetToIdentityStep();
            document.getElementById('clientDownloadModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeClientDownloadModal() {
            document.getElementById('clientDownloadModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        function resetToIdentityStep() {
            verificationStep = 'identity';
            document.getElementById('cdmStepIdentity').style.display = 'block';
            document.getElementById('cdmStepOtp').style.display = 'none';
            document.getElementById('cdm_error').style.display = 'none';
            document.getElementById('cdm_info').style.display = 'none';
            const btn = document.getElementById('btnCdmSubmit');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Verification Code';
        }

        async function handleVerificationSubmit() {
            const name = document.getElementById('cdm_name').value.trim();
            const email = document.getElementById('cdm_email').value.trim();
            const phone = document.getElementById('cdm_phone').value.trim();
            const errBox = document.getElementById('cdm_error');
            const infoBox = document.getElementById('cdm_info');
            const submitBtn = document.getElementById('btnCdmSubmit');

            errBox.style.display = 'none';

            if (verificationStep === 'identity') {
                if (!name || name.length < 2) {
                    errBox.textContent = 'Please enter your full name.';
                    errBox.style.display = 'block';
                    return;
                }
                if (!email || !email.includes('@')) {
                    errBox.textContent = 'Please enter a valid client email address.';
                    errBox.style.display = 'block';
                    return;
                }
                if (!phone || phone.replace(/[^0-9+]/g, '').length < 7) {
                    errBox.textContent = 'Please enter a valid phone number.';
                    errBox.style.display = 'block';
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Dispatching 6-Digit Code...';

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
                        verificationStep = 'otp';
                        document.getElementById('cdmStepIdentity').style.display = 'none';
                        document.getElementById('cdmStepOtp').style.display = 'block';
                        infoBox.style.display = 'block';
                        infoBox.innerHTML = `<i class="fa-solid fa-envelope-circle-check"></i> ${res.message}` +
                            (res.dev_otp_hint ? `<br><strong>Passcode: ${res.dev_otp_hint}</strong>` : '');
                        if (res.dev_otp_hint) {
                            document.getElementById('cdm_otp_code').value = res.dev_otp_hint;
                        }
                        submitBtn.innerHTML = '<i class="fa-solid fa-unlock-keyhole"></i> Verify & Start Download';
                        document.getElementById('cdm_otp_code').focus();
                    } else {
                        errBox.textContent = res.message || 'Verification failed. Please check your credentials.';
                        errBox.style.display = 'block';
                        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Verification Code';
                    }
                } catch (err) {
                    errBox.textContent = 'Network error while requesting verification code.';
                    errBox.style.display = 'block';
                    submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Verification Code';
                } finally {
                    submitBtn.disabled = false;
                }
            } else {
                const otpCode = document.getElementById('cdm_otp_code').value.trim();
                if (!otpCode || otpCode.length !== 6) {
                    errBox.textContent = 'Please enter the 6-digit verification code.';
                    errBox.style.display = 'block';
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying Passcode...';

                try {
                    const fd = new FormData();
                    fd.append('action', 'verify_code');
                    fd.append('email', email);
                    fd.append('name', name);
                    fd.append('phone', phone);
                    fd.append('code', otpCode);
                    fd.append('album_id', currentAlbumId);
                    fd.append('album_title', shootTitle);

                    const resp = await fetch('/api/verify-download.php', { method: 'POST', body: fd });
                    const res = await resp.json();

                    if (res.success && res.verified) {
                        isClientVerified = true;
                        downloadToken = res.download_token || '';
                        const badge = document.getElementById('verifiedStatusBadge');
                        if (badge) {
                            badge.style.display = 'inline-flex';
                            badge.innerHTML = `<i class="fa-solid fa-shield-check"></i> Verified: ${res.email}`;
                        }
                        closeClientDownloadModal();
                        showToast('Identity verified! Starting your download...');
                        if (typeof pendingDownloadCallback === 'function') {
                            const cb = pendingDownloadCallback;
                            pendingDownloadCallback = null;
                            cb();
                        }
                    } else {
                        errBox.textContent = res.message || 'Invalid verification code.';
                        errBox.style.display = 'block';
                    }
                } catch (err) {
                    errBox.textContent = 'Network error while verifying code.';
                    errBox.style.display = 'block';
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-unlock-keyhole"></i> Verify & Start Download';
                }
            }
        }

        // Trigger Single Photo Download (Protected by Security Gate)
        function triggerSinglePhotoDownload(index) {
            const item = galleryItems[index] || galleryItems[0];
            requireVerifiedClient(() => {
                const url = `/api/download-album.php?album_id=${currentAlbumId}&photo_index=${item.index}&token=${encodeURIComponent(downloadToken)}`;
                showToast(`Downloading Photo #${item.number}...`);
                const a = document.createElement('a');
                a.href = url;
                a.download = '';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }, `<i class="fa-regular fa-image"></i> Photo #${item.number} · Lightweight HD`);
        }

        // Trigger Bulk Packaged ZIP Download (Protected by Security Gate)
        function triggerBulkDownload() {
            requireVerifiedClient(() => {
                const url = `/api/download-album.php?album_id=${currentAlbumId}&token=${encodeURIComponent(downloadToken)}`;
                showToast(`Packaging & downloading ${galleryItems.length} photos (.ZIP)...`);
                const a = document.createElement('a');
                a.href = url;
                a.download = '';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }, `<i class="fa-solid fa-file-zipper"></i> All ${galleryItems.length} Photos · Packaged ZIP Archive`);
        }
    </script>
</body>
</html>
