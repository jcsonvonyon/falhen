<?php
/**
 * client-portal.php
 * Falhen Media — Independent Client Gallery Subdomain Portal (client.falhen.com)
 * SmugMug-inspired clean photography delivery UX:
 * - Minimalist breadcrumbs & typography-first album header
 * - Edge-to-edge responsive photography gallery (Masonry / Justified)
 * - Dynamic Full-Screen Lightbox Slideshow with thumbnail track & keyboard navigation
 * - Complete Clients / Folders directory view (?view=clients)
 * - 2-Step Client Security Gate (Name, Email, Phone + 6-Digit OTP) & Bulk ZIP Download
 * - Cloudinary CDN optimized assets & Google Drive sync
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

// Check if client email was passed via query or session
$requestedClientEmail = strtolower(trim($_GET['client_email'] ?? $_POST['client_email'] ?? ''));
if (!empty($requestedClientEmail) && filter_var($requestedClientEmail, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['portal_lookup_email'] = $requestedClientEmail;
}

// Determine active album by slug or ID
$requestedSlug = trim($_GET['slug'] ?? '');
if (empty($requestedSlug)) {
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (preg_match('#^/(?:album|gallery)/([^/]+)/?$#i', $uriPath, $m)) {
        $requestedSlug = $m[1];
    } elseif (preg_match('#^/clients/[^/]+/([^/]+)/?$#i', $uriPath, $m)) {
        $requestedSlug = $m[1];
    }
}

// Check if client requested directory view or landed on root/clients without specific album
$hasExplicitAlbum = !empty($requestedSlug) || isset($_GET['id']) || isset($_GET['album_id']);
$isClientsView = (isset($_GET['view']) && $_GET['view'] === 'clients') || isset($_GET['clients']) || !$hasExplicitAlbum;

$albumId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['album_id']) ? (int)$_GET['album_id'] : (int)key($albumsRepo));
$currentAlbum = reset($albumsRepo);

if ($hasExplicitAlbum && !empty($requestedSlug)) {
    foreach ($albumsRepo as $aId => $mIt) {
        if (($mIt['slug'] ?? '') === $requestedSlug || generateSlug($mIt['title'] ?? '') === $requestedSlug) {
            $currentAlbum = $mIt;
            $albumId = (int)$aId;
            $isClientsView = false;
            break;
        }
    }
} elseif ($hasExplicitAlbum && isset($albumsRepo[$albumId])) {
    $currentAlbum = $albumsRepo[$albumId];
    $isClientsView = false;
} elseif ($hasExplicitAlbum) {
    foreach ($masterPortfolioItems as $mIt) {
        if ((int)($mIt['id'] ?? 0) === $albumId) {
            $currentAlbum = $mIt;
            $isClientsView = false;
            break;
        }
    }
}

$currentAlbumId = (int)($currentAlbum['id'] ?? 1);
$albumSlug = $currentAlbum['slug'] ?? generateSlug($currentAlbum['title'] ?? 'gallery');
$coverImage = getCloudinaryUrl($currentAlbum['image'] ?? $currentAlbum['cover'] ?? '/assets/img/portfolio/portfolio_halima.png');

if ($isClientsView) {
    $seoTitle = "Client Galleries & Deliveries — Falhen Media";
    $seoDesc = "Access, review proofs, and download high-resolution photography deliveries for Falhen Media clients.";
    $seoKeywords = "client galleries, photography delivery, client portal, falhen media, weddings, portraits, commercial, corporate";
    $pageTitle = $seoTitle;
} else {
    $seoTitle = !empty($currentAlbum['seo_title']) ? $currentAlbum['seo_title'] : ($currentAlbum['title'] ?? 'Client Gallery') . " — Client Portal | Falhen Media";
    $seoDesc = !empty($currentAlbum['seo_desc']) ? $currentAlbum['seo_desc'] : "Explore the exclusive " . ($currentAlbum['category'] ?? 'photography') . " gallery for " . ($currentAlbum['client'] ?? 'clients') . ". Curated and delivered in high-resolution by Falhen Media.";
    $seoKeywords = !empty($currentAlbum['seo_keywords']) ? $currentAlbum['seo_keywords'] : (($currentAlbum['category'] ?? 'Photography') . ", client gallery, high resolution, download, falhen media");
    $pageTitle = htmlspecialchars($seoTitle);
}

// Client Authentication & Verification State (Folder-Scoped)
$isClientVerified = !empty($_SESSION['verified_albums'][$currentAlbumId]) || (!empty($_SESSION['client_download_verified']) && (int)($_SESSION['last_verified_album_id'] ?? 0) === $currentAlbumId);
$downloadToken = $_SESSION['verified_album_tokens'][$currentAlbumId] ?? ($_SESSION['client_download_token'] ?? '');
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

// Collect all categories for the directory filter
$allCategories = [];
foreach ($albumsRepo as $alb) {
    $cat = trim($alb['category'] ?? 'General');
    if ($cat && !in_array($cat, $allCategories, true)) {
        $allCategories[] = $cat;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php if (function_exists('renderGoogleAnalyticsTag')) renderGoogleAnalyticsTag(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seoDesc); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seoKeywords); ?>">
    <link rel="canonical" href="<?php echo $isClientsView ? 'https://client.falhen.com/' : 'https://client.falhen.com/album/' . htmlspecialchars($albumSlug); ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?php echo $isClientsView ? 'website' : 'article'; ?>">
    <meta property="og:url" content="<?php echo $isClientsView ? 'https://client.falhen.com/' : 'https://client.falhen.com/album/' . htmlspecialchars($albumSlug); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($seoTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seoDesc); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($coverImage); ?>">
    
    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($seoTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($seoDesc); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($coverImage); ?>">

    <link rel="icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        :root {
            --smug-bg: #070709;
            --smug-surface: #0f0f13;
            --smug-border: rgba(255, 255, 255, 0.08);
            --smug-border-light: rgba(255, 255, 255, 0.16);
            --smug-red: #dc2626;
            --smug-red-hover: #ef4444;
            --smug-text-primary: #ffffff;
            --smug-text-secondary: #a1a1aa;
            --smug-text-muted: #71717a;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--smug-bg);
            color: var(--smug-text-primary);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* SmugMug Minimal Top Brand Header */
        .smug-topbar {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(7, 7, 9, 0.94);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--smug-border);
            z-index: 100;
            padding: 14px 32px;
        }

        .smug-topbar-inner {
            max-width: 1720px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .smug-brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #ffffff;
        }

        .smug-brand-link img {
            height: 28px;
            width: auto;
            display: block;
        }

        .smug-brand-tag {
            font-size: 0.72rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--smug-text-muted);
            border-left: 1px solid var(--smug-border-light);
            padding-left: 12px;
        }

        .smug-topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .smug-nav-link {
            color: var(--smug-text-secondary);
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: 8px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .smug-nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.05);
        }

        .smug-nav-cta {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--smug-border-light);
            color: #ffffff;
            font-size: 0.84rem;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .smug-nav-cta:hover {
            background: #ffffff;
            color: #000000;
            border-color: #ffffff;
        }

        .smug-nav-primary {
            background: var(--smug-red);
            border: 1px solid var(--smug-red);
            color: #ffffff;
            font-size: 0.84rem;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .smug-nav-primary:hover {
            background: var(--smug-red-hover);
            border-color: var(--smug-red-hover);
        }

        /* Container */
        .smug-page-container {
            max-width: 1720px;
            margin: 0 auto;
            padding: 28px 32px 80px;
        }

        /* Breadcrumbs (SmugMug style) */
        .smug-breadcrumbs {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.84rem;
            color: var(--smug-text-muted);
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .smug-breadcrumbs a {
            color: var(--smug-text-secondary);
            text-decoration: none;
            transition: color 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .smug-breadcrumbs a:hover {
            color: #ffffff;
        }

        .smug-breadcrumbs .sep {
            color: var(--smug-text-muted);
            opacity: 0.6;
        }

        .smug-breadcrumbs .current {
            color: #ffffff;
            font-weight: 600;
        }

        /* Album Hero Header (Clean, Photography-First) */
        .smug-album-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--smug-border);
            margin-bottom: 32px;
            flex-wrap: wrap;
        }

        .smug-header-info {
            max-width: 800px;
        }

        .smug-album-title {
            font-size: clamp(2rem, 3.2vw, 3rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.15;
            color: #ffffff;
            margin-bottom: 10px;
        }

        .smug-album-meta {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 0.9rem;
            color: var(--smug-text-secondary);
            flex-wrap: wrap;
        }

        .smug-album-meta .dot {
            opacity: 0.4;
        }

        .smug-verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #4ade80;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .smug-header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-smug-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .btn-smug-primary {
            background: var(--smug-red);
            color: #ffffff;
        }

        .btn-smug-primary:hover {
            background: var(--smug-red-hover);
        }

        .btn-smug-ghost {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--smug-border-light);
            color: #ffffff;
        }

        .btn-smug-ghost:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.28);
        }

        /* SmugMug Pure Masonry / Justified Gallery Grid */
        .smug-gallery-grid {
            column-count: 3;
            column-gap: 14px;
        }

        @media (max-width: 1200px) {
            .smug-gallery-grid {
                column-count: 2;
                column-gap: 12px;
            }
        }

        /* iPad / Tablet Responsiveness (641px - 1024px) */
        @media (min-width: 641px) and (max-width: 1024px) {
            .smug-topbar {
                padding: 14px 24px;
            }
            .smug-page-container {
                padding: 24px 24px 60px;
            }
            .smug-gallery-grid {
                column-count: 2;
                column-gap: 14px;
            }
            .smug-folders-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 20px !important;
            }
            .smug-album-title {
                font-size: 2.2rem;
            }
            .smug-album-header {
                gap: 18px;
            }
        }

        /* Mobile Phone Responsiveness (<= 640px) */
        @media (max-width: 640px) {
            .smug-gallery-grid {
                column-count: 1;
                column-gap: 0;
            }
            .smug-topbar {
                padding: 12px 16px;
                padding-left: max(16px, env(safe-area-inset-left));
                padding-right: max(16px, env(safe-area-inset-right));
            }
            .smug-brand-tag {
                display: none;
            }
            .smug-topbar-actions {
                gap: 8px;
            }
            .smug-nav-link span,
            .smug-nav-cta span {
                display: inline-block;
            }
            .smug-page-container {
                padding: 16px 16px 60px;
                padding-left: max(16px, env(safe-area-inset-left));
                padding-right: max(16px, env(safe-area-inset-right));
            }
            .smug-breadcrumbs {
                overflow-x: auto;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 4px;
            }
            .smug-breadcrumbs::-webkit-scrollbar {
                display: none;
            }
            .smug-album-title {
                font-size: 1.75rem;
            }
            .smug-album-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            .smug-header-actions {
                width: 100%;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            .btn-smug-action {
                justify-content: center;
                padding: 11px 14px;
                width: 100%;
            }
            .smug-folders-grid {
                grid-template-columns: 1fr !important;
                gap: 16px !important;
            }
        }

        .smug-photo-card {
            break-inside: avoid;
            margin-bottom: 14px;
            position: relative;
            border-radius: 4px;
            overflow: hidden;
            background: #111116;
            cursor: pointer;
        }

        .smug-photo-card img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .smug-photo-card:hover img {
            transform: scale(1.02);
        }

        /* Subtle minimal hover overlay */
        .smug-photo-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0) 50%, rgba(0,0,0,0.7) 100%);
            opacity: 0;
            transition: opacity 0.25s ease;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            padding: 16px;
        }

        .smug-photo-card:hover .smug-photo-overlay {
            opacity: 1;
        }

        .smug-overlay-num {
            font-size: 0.78rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.85);
            letter-spacing: 0.5px;
        }

        .smug-overlay-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .smug-overlay-btn {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .smug-overlay-btn:hover {
            background: #ffffff;
            color: #000000;
            border-color: #ffffff;
        }

        /* CLIENTS DIRECTORY VIEW (?view=clients) */
        .smug-directory-header {
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--smug-border);
        }

        .smug-directory-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--smug-red);
            margin-bottom: 12px;
        }

        .smug-directory-title {
            font-size: clamp(2rem, 3.5vw, 2.8rem);
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .smug-directory-subtitle {
            font-size: 0.95rem;
            color: var(--smug-text-secondary);
            max-width: 720px;
            line-height: 1.5;
            margin-bottom: 24px;
        }

        /* Search & Filter Toolbar */
        .smug-directory-toolbar {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: 20px;
        }

        .smug-search-wrap {
            position: relative;
            max-width: 480px;
            width: 100%;
        }

        .smug-search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--smug-text-muted);
            font-size: 0.85rem;
            pointer-events: none;
        }

        .smug-search-input {
            width: 100%;
            background: #111116;
            border: 1px solid var(--smug-border-light);
            border-radius: 8px;
            padding: 11px 40px 11px 38px;
            color: #ffffff;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s ease;
            -webkit-appearance: none;
        }

        .smug-search-input:focus {
            border-color: rgba(255, 255, 255, 0.4);
            background: #15151c;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
        }

        .smug-search-clear {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--smug-text-muted);
            cursor: pointer;
            padding: 4px;
            font-size: 0.85rem;
        }

        .smug-search-clear:hover {
            color: #ffffff;
        }

        .smug-cat-pills-row {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 4px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .smug-cat-pills-row::-webkit-scrollbar {
            display: none;
        }

        .smug-cat-pill {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--smug-border);
            color: var(--smug-text-secondary);
            font-size: 0.82rem;
            font-weight: 600;
            padding: 7px 16px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.18s ease;
            user-select: none;
            flex-shrink: 0;
            touch-action: manipulation;
        }

        .smug-cat-pill:hover {
            color: #ffffff;
            border-color: var(--smug-border-light);
            background: rgba(255, 255, 255, 0.08);
        }

        .smug-cat-pill.active {
            background: rgba(220, 38, 38, 0.15);
            border-color: var(--smug-red);
            color: #ffffff;
            font-weight: 700;
        }

        .smug-directory-stats {
            font-size: 0.8rem;
            color: var(--smug-text-muted);
            margin-top: 10px;
        }

        /* Folders Grid */
        .smug-folders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }

        .smug-folder-card {
            background: var(--smug-surface);
            border: 1px solid var(--smug-border);
            border-radius: 10px;
            overflow: hidden;
            text-decoration: none;
            color: #ffffff;
            transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
            display: flex;
            flex-direction: column;
            -webkit-tap-highlight-color: transparent;
        }

        @media (hover: hover) {
            .smug-folder-card:hover {
                border-color: var(--smug-border-light);
                transform: translateY(-4px);
                box-shadow: 0 16px 36px rgba(0, 0, 0, 0.55);
            }
            .smug-folder-card:hover .smug-folder-cover img {
                transform: scale(1.04);
            }
            .smug-folder-card:hover .smug-folder-title {
                color: #ffffff;
            }
            .smug-folder-card:hover .smug-folder-arrow {
                transform: translateX(4px);
                color: #ffffff;
            }
        }

        .smug-folder-card:active {
            transform: scale(0.99);
        }

        .smug-folder-cover {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 10;
            overflow: hidden;
            background: #111116;
        }

        .smug-folder-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }

        .smug-folder-cat-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(0, 0, 0, 0.72);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 4px;
        }

        .smug-folder-lock-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(7, 7, 9, 0.78);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #cbd5e1;
            font-size: 0.68rem;
            font-weight: 600;
            padding: 4px 9px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .smug-folder-body {
            padding: 18px 20px 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }

        .smug-folder-title {
            font-size: clamp(1.05rem, 1.8vw, 1.25rem);
            font-weight: 700;
            color: #f1f5f9;
            line-height: 1.3;
            transition: color 0.2s ease;
        }

        .smug-folder-meta {
            font-size: 0.82rem;
            color: var(--smug-text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: auto;
        }

        .smug-folder-meta .dot {
            opacity: 0.4;
        }

        .smug-folder-count {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .smug-folder-arrow {
            margin-left: auto;
            color: var(--smug-text-muted);
            font-size: 0.85rem;
            transition: transform 0.2s ease, color 0.2s ease;
        }

        /* Empty State */
        .smug-empty-state {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px dashed var(--smug-border-light);
            border-radius: 12px;
            margin-top: 20px;
        }

        .smug-empty-icon {
            font-size: 2.5rem;
            color: var(--smug-text-muted);
            margin-bottom: 14px;
        }

        .smug-empty-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .smug-empty-desc {
            font-size: 0.88rem;
            color: var(--smug-text-secondary);
            max-width: 440px;
            margin: 0 auto 20px;
            line-height: 1.5;
        }

        /* FULLSCREEN DYNAMIC LIGHTBOX / SLIDESHOW */
        .smug-lightbox {
            position: fixed;
            inset: 0;
            background: rgba(4, 4, 6, 0.98);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            z-index: 9999;
            display: none;
            flex-direction: column;
        }

        .smug-lightbox-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 28px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            color: #ffffff;
            background: rgba(0, 0, 0, 0.4);
        }

        .smug-lightbox-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--smug-text-secondary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .smug-lightbox-count {
            color: #ffffff;
            font-weight: 700;
        }

        .smug-lightbox-tools {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .smug-tool-btn {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #ffffff;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .smug-tool-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .smug-tool-close {
            background: none;
            border: none;
            color: var(--smug-text-secondary);
            font-size: 1.25rem;
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 6px;
            transition: color 0.15s ease;
        }

        .smug-tool-close:hover {
            color: #ffffff;
        }

        .smug-lightbox-stage {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            padding: 12px 20px;
        }

        .smug-nav-arrow {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.2s ease;
        }

        .smug-nav-arrow:hover {
            background: #ffffff;
            color: #000000;
        }

        .smug-image-holder {
            flex: 1;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            cursor: zoom-in;
        }

        .smug-image-holder.zoomed {
            cursor: zoom-out;
            overflow: auto;
            display: block;
        }

        .smug-lightbox-img {
            max-height: 82vh;
            max-width: 90vw;
            object-fit: contain;
            transition: opacity 0.2s ease, transform 0.3s ease;
        }

        .smug-image-holder.zoomed .smug-lightbox-img {
            max-height: none;
            max-width: none;
            transform: scale(1.6);
            transform-origin: center center;
            cursor: zoom-out;
        }

        .smug-thumbs-rail {
            height: 72px;
            background: rgba(0, 0, 0, 0.6);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 24px;
            overflow-x: auto;
            white-space: nowrap;
        }

        .smug-thumb {
            height: 52px;
            aspect-ratio: 3 / 2;
            border-radius: 3px;
            overflow: hidden;
            border: 2px solid transparent;
            cursor: pointer;
            opacity: 0.45;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .smug-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .smug-thumb.active {
            border-color: #ffffff;
            opacity: 1;
        }

        .smug-thumb:hover {
            opacity: 0.9;
        }

        /* REFINED CLIENT SECURITY OTP MODAL */
        .smug-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .smug-modal-box {
            background: #111116;
            border: 1px solid var(--smug-border-light);
            border-radius: 12px;
            max-width: 480px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.7);
        }

        .smug-modal-header {
            padding: 24px 28px 20px;
            border-bottom: 1px solid var(--smug-border);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .smug-modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 4px;
        }

        .smug-modal-subtitle {
            font-size: 0.84rem;
            color: var(--smug-text-secondary);
            line-height: 1.4;
        }

        .smug-modal-body {
            padding: 24px 28px;
        }

        .smug-field-group {
            margin-bottom: 16px;
        }

        .smug-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: var(--smug-text-secondary);
            margin-bottom: 6px;
        }

        .smug-input {
            width: 100%;
            background: #09090c;
            border: 1px solid var(--smug-border-light);
            border-radius: 6px;
            padding: 11px 14px;
            color: #ffffff;
            font-size: 0.92rem;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .smug-input:focus {
            border-color: #ffffff;
        }

        .smug-otp-input {
            font-size: 1.6rem;
            letter-spacing: 12px;
            text-align: center;
            font-weight: 700;
        }

        .smug-modal-alert {
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 0.82rem;
            margin-bottom: 16px;
            display: none;
        }

        .smug-alert-error {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #f87171;
        }

        .smug-alert-info {
            background: rgba(37, 99, 235, 0.15);
            border: 1px solid rgba(37, 99, 235, 0.4);
            color: #93c5fd;
        }

        .smug-modal-footer {
            padding: 16px 28px 24px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            border-top: 1px solid var(--smug-border);
        }

        .btn-modal-cancel {
            background: none;
            border: none;
            color: var(--smug-text-secondary);
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            padding: 8px 16px;
        }

        .btn-modal-cancel:hover {
            color: #ffffff;
        }

        .btn-modal-submit {
            background: var(--smug-red);
            border: none;
            color: #ffffff;
            padding: 10px 22px;
            border-radius: 6px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s ease;
        }

        .btn-modal-submit:hover {
            background: var(--smug-red-hover);
        }

        /* Toast feedback */
        .smug-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #111116;
            border: 1px solid var(--smug-border-light);
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.86rem;
            font-weight: 500;
            z-index: 10001;
            opacity: 0;
            pointer-events: none;
            transform: translateY(12px);
            transition: all 0.25s ease;
        }

        .smug-toast.show {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }
    </style>
</head>
<body>

    <!-- Minimal SmugMug Top Bar -->
    <header class="smug-topbar">
        <div class="smug-topbar-inner">
            <a href="/clients" class="smug-brand-link">
                <img src="/assets/img/icons/logo.png" alt="Falhen Media">
                <span class="smug-brand-tag">Client Galleries</span>
            </a>

            <div class="smug-topbar-actions">
                <a href="https://www.falhen.com/portfolio" class="smug-nav-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Portfolio</span>
                </a>
                <a href="/clients" class="smug-nav-link <?php echo $isClientsView ? 'style="color:#fff;"' : ''; ?>">
                    <i class="fa-regular fa-folder"></i>
                    <span>All Clients</span>
                </a>
                <?php if (!$isClientsView): ?>
                    <button type="button" class="smug-nav-cta" onclick="openDynamicFullView(0)">
                        <i class="fa-solid fa-play"></i>
                        <span>Slideshow</span>
                    </button>
                    <button type="button" class="smug-nav-primary" onclick="triggerBulkDownload()">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                        <span>Download All</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="smug-page-container">

        <?php if ($isClientsView): ?>
            <!-- ================= CLIENTS DIRECTORY VIEW (SmugMug /Clients) ================= -->
            <nav class="smug-breadcrumbs" aria-label="Breadcrumb">
                <a href="https://www.falhen.com">Home</a>
                <span class="sep">/</span>
                <a href="https://www.falhen.com/portfolio">Portfolio</a>
                <span class="sep">/</span>
                <span class="current">Clients</span>
            </nav>

            <div class="smug-directory-header">
                <div class="smug-directory-eyebrow">
                    <i class="fa-solid fa-camera-retro"></i>
                    <span>Curated Client Deliveries</span>
                </div>
                <h1 class="smug-directory-title">Client Deliveries</h1>
                <p class="smug-directory-subtitle">Select your curated gallery below to stream in full-screen slideshow, view photo proofs, and download high-resolution assets.</p>

                <!-- Search & Category Filters Toolbar -->
                <div class="smug-directory-toolbar">
                    <div class="smug-search-wrap">
                        <i class="fa-solid fa-magnifying-glass smug-search-icon"></i>
                        <input type="text" id="clientGallerySearch" class="smug-search-input" placeholder="Search gallery or client name..." autocomplete="off">
                        <button type="button" id="clearGallerySearch" class="smug-search-clear" onclick="clearGallerySearch()" style="display:none;" aria-label="Clear search">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="smug-cat-pills-row" id="catPillsRow">
                        <button type="button" class="smug-cat-pill active" data-cat="all" onclick="filterGalleryCategory('all', this)">
                            All (<?php echo count($albumsRepo); ?>)
                        </button>
                        <?php foreach ($allCategories as $catName): 
                            $cCount = 0;
                            foreach ($albumsRepo as $alb) {
                                if (strcasecmp(trim($alb['category'] ?? ''), $catName) === 0) $cCount++;
                            }
                        ?>
                            <button type="button" class="smug-cat-pill" data-cat="<?php echo htmlspecialchars(strtolower($catName)); ?>" onclick="filterGalleryCategory('<?php echo htmlspecialchars(strtolower($catName)); ?>', this)">
                                <?php echo htmlspecialchars($catName); ?> (<?php echo $cCount; ?>)
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="smug-directory-stats" id="galleryCountStatus">
                    Showing <span id="visibleGalleryCount"><?php echo count($albumsRepo); ?></span> of <?php echo count($albumsRepo); ?> galleries
                </div>
            </div>

            <div class="smug-folders-grid" id="smugFoldersGrid">
                <?php foreach ($albumsRepo as $aId => $album): 
                    $albCover = getCloudinaryUrl($album['image'] ?? $album['cover'] ?? '/assets/img/portfolio/portfolio_halima.png');
                    $albCount = (int)($album['photosCount'] ?? 25);
                    $albSlug = $album['slug'] ?? generateSlug($album['title'] ?? 'gallery');
                    $albCategory = $album['category'] ?? 'Gallery';
                    $albClient = $album['client'] ?? 'Falhen Client';
                ?>
                    <a href="/album/<?php echo urlencode($albSlug); ?>" 
                       class="smug-folder-card" 
                       data-title="<?php echo htmlspecialchars(strtolower($album['title'])); ?>" 
                       data-client="<?php echo htmlspecialchars(strtolower($albClient)); ?>" 
                       data-cat="<?php echo htmlspecialchars(strtolower($albCategory)); ?>">
                        <div class="smug-folder-cover">
                            <img src="<?php echo htmlspecialchars($albCover); ?>" alt="<?php echo htmlspecialchars($album['title']); ?>" loading="lazy">
                            <span class="smug-folder-cat-badge"><?php echo htmlspecialchars($albCategory); ?></span>
                            <span class="smug-folder-lock-badge"><i class="fa-solid fa-lock"></i> Client Delivery</span>
                        </div>
                        <div class="smug-folder-body">
                            <h2 class="smug-folder-title"><?php echo htmlspecialchars($album['title']); ?></h2>
                            <div class="smug-folder-meta">
                                <span><?php echo htmlspecialchars($albClient); ?></span>
                                <span class="dot">·</span>
                                <span class="smug-folder-count"><i class="fa-regular fa-images"></i> <?php echo $albCount; ?> Photos</span>
                                <span class="smug-folder-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Empty State for Directory Search -->
            <div id="noGalleriesFound" class="smug-empty-state" style="display:none;">
                <div class="smug-empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                <h3 class="smug-empty-title">No client galleries found</h3>
                <p class="smug-empty-desc">No delivery matches your search query. Try searching for a different name or category.</p>
                <button type="button" class="btn-smug-action btn-smug-ghost" onclick="clearGallerySearch()">Reset Filters</button>
            </div>

        <?php else: ?>
            <!-- ================= ALBUM GALLERY VIEW (SmugMug /Clients/CATEGORY/ALBUM) ================= -->
            <nav class="smug-breadcrumbs" aria-label="Breadcrumb">
                <a href="https://www.falhen.com">Home</a>
                <span class="sep">/</span>
                <a href="/clients">Clients</a>
                <span class="sep">/</span>
                <a href="/clients?category=<?php echo urlencode($currentAlbum['category'] ?? 'Gallery'); ?>">
                    <?php echo htmlspecialchars($currentAlbum['category'] ?? 'Gallery'); ?>
                </a>
                <span class="sep">/</span>
                <span class="current"><?php echo htmlspecialchars($currentAlbum['title']); ?></span>
            </nav>

            <div class="smug-album-header">
                <div class="smug-header-info">
                    <h1 class="smug-album-title"><?php echo htmlspecialchars($currentAlbum['title']); ?></h1>
                    <div class="smug-album-meta">
                        <span><?php echo $totalPhotos; ?> Photos</span>
                        <span class="dot">·</span>
                        <span>Client: <?php echo htmlspecialchars($currentAlbum['client'] ?? ($clientName ?: 'Falhen VIP')); ?></span>
                        <span class="dot">·</span>
                        <span>Delivered by Falhen Media</span>
                        <?php if ($isClientVerified): ?>
                            <span class="dot">·</span>
                            <span class="smug-verified-badge" id="verifiedStatusBadge">
                                <i class="fa-solid fa-circle-check"></i> Verified Client
                            </span>
                        <?php else: ?>
                            <span class="smug-verified-badge" id="verifiedStatusBadge" style="display:none;"></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="smug-header-actions">
                    <button type="button" class="btn-smug-action btn-smug-primary" onclick="triggerBulkDownload()">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                        <span>Download All (<?php echo $totalPhotos; ?>)</span>
                    </button>
                    <button type="button" class="btn-smug-action btn-smug-ghost" onclick="openDynamicFullView(0)">
                        <i class="fa-solid fa-play"></i>
                        <span>Slideshow</span>
                    </button>
                    <a href="/clients" class="btn-smug-action btn-smug-ghost">
                        <i class="fa-regular fa-folder-open"></i>
                        <span>Browse Albums</span>
                    </a>
                </div>
            </div>

            <!-- Pure Masonry Photography Grid (SmugMug style) -->
            <main class="smug-gallery-grid" id="smugMasonryGrid">
                <?php foreach ($photoItems as $item): ?>
                    <article class="smug-photo-card" onclick="openDynamicFullView(<?php echo (int)$item['index']; ?>)">
                        <img src="<?php echo htmlspecialchars($item['masonry_url']); ?>"
                             alt="<?php echo htmlspecialchars($currentAlbum['title'] . ' — Frame ' . $item['number']); ?>"
                             loading="lazy">
                        <div class="smug-photo-overlay">
                            <span class="smug-overlay-num">Frame #<?php echo str_pad((string)$item['number'], 2, '0', STR_PAD_LEFT); ?></span>
                            <div class="smug-overlay-actions">
                                <button type="button"
                                        class="smug-overlay-btn"
                                        title="View Fullscreen"
                                        onclick="event.stopPropagation(); openDynamicFullView(<?php echo (int)$item['index']; ?>);">
                                    <i class="fa-solid fa-expand"></i>
                                </button>
                                <button type="button"
                                        class="smug-overlay-btn"
                                        title="Download Photo"
                                        onclick="event.stopPropagation(); triggerSinglePhotoDownload(<?php echo (int)$item['index']; ?>);">
                                    <i class="fa-solid fa-arrow-down"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </main>

        <?php endif; ?>

    </div>

    <!-- FULLSCREEN LIGHTBOX & SLIDESHOW MODAL -->
    <div id="smugLightbox" class="smug-lightbox" role="dialog" aria-modal="true" aria-label="Dynamic Lightbox">
        <div class="smug-lightbox-top">
            <div class="smug-lightbox-title">
                <span class="smug-lightbox-count" id="lightboxCounter">Photo 1 of <?php echo $totalPhotos; ?></span>
                <span>·</span>
                <span><?php echo htmlspecialchars($currentAlbum['title'] ?? 'Gallery'); ?></span>
            </div>

            <div class="smug-lightbox-tools">
                <button type="button" class="smug-tool-btn" id="btnSlideshowToggle" onclick="toggleSlideshow()">
                    <i class="fa-solid fa-play" id="slideshowIcon"></i>
                    <span id="slideshowLabel">Slideshow</span>
                </button>
                <button type="button" class="smug-tool-btn" onclick="toggleLightboxZoom()">
                    <i class="fa-solid fa-magnifying-glass-plus" id="zoomIcon"></i>
                    <span id="zoomLabel">Zoom</span>
                </button>
                <button type="button" class="smug-tool-btn" onclick="triggerSinglePhotoDownload(currentPhotoIndex)">
                    <i class="fa-solid fa-arrow-down"></i> Download Photo
                </button>
                <button type="button" class="smug-tool-btn" onclick="triggerBulkDownload()">
                    <i class="fa-solid fa-cloud-arrow-down"></i> Download All
                </button>
                <button type="button" class="smug-tool-close" onclick="closeDynamicFullView()" aria-label="Close Lightbox">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="smug-lightbox-stage" id="lightboxStage">
            <button type="button" class="smug-nav-arrow" onclick="prevPhoto()" aria-label="Previous">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="smug-image-holder" id="imageHolder" onclick="toggleLightboxZoom()">
                <img id="lightboxMainImg"
                     class="smug-lightbox-img"
                     src="<?php echo htmlspecialchars($photoItems[0]['full_url'] ?? $coverImage); ?>"
                     alt="Fullscreen photo">
            </div>
            <button type="button" class="smug-nav-arrow" onclick="nextPhoto()" aria-label="Next">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <div class="smug-thumbs-rail" id="thumbsRail">
            <?php foreach ($photoItems as $item): ?>
                <div class="smug-thumb <?php echo ($item['index'] === 0) ? 'active' : ''; ?>"
                     onclick="goToPhoto(<?php echo (int)$item['index']; ?>)">
                    <img src="<?php echo htmlspecialchars($item['masonry_url']); ?>" alt="Frame <?php echo (int)$item['number']; ?>" loading="lazy">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 2-STEP CLIENT IDENTITY & OTP VERIFICATION MODAL -->
    <div id="smugVerifyModal" class="smug-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="verifyModalTitle">
        <div class="smug-modal-box">
            <div class="smug-modal-header">
                <div>
                    <h2 class="smug-modal-title" id="verifyModalTitle">Client Security Verification</h2>
                    <p class="smug-modal-subtitle" id="verifyModalSubtitle">Please verify your details to download high-resolution photos.</p>
                </div>
                <button type="button" class="smug-tool-close" onclick="closeVerifyModal()" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="smug-modal-body">
                <div id="verifyErrorAlert" class="smug-modal-alert smug-alert-error"></div>
                <div id="verifyInfoAlert" class="smug-modal-alert smug-alert-info"></div>

                <!-- STEP 1: Name, Email, Phone -->
                <div id="verifyStepIdentity">
                    <div class="smug-field-group">
                        <label class="smug-label" for="client_input_name">Full Name *</label>
                        <input type="text" id="client_input_name" class="smug-input" placeholder="Your full name" value="<?php echo htmlspecialchars($clientName); ?>" required>
                    </div>

                    <div class="smug-field-group">
                        <label class="smug-label" for="client_input_email">Email Address *</label>
                        <input type="email" id="client_input_email" class="smug-input" placeholder="Enter your authorized email address" value="" required autocomplete="email">
                    </div>

                    <div class="smug-field-group">
                        <label class="smug-label" for="client_input_phone">Phone Number *</label>
                        <input type="tel" id="client_input_phone" class="smug-input" placeholder="+1 (555) 234-5678" value="<?php echo htmlspecialchars($clientPhone); ?>" required>
                    </div>
                </div>

                <!-- STEP 2: 6-Digit OTP -->
                <div id="verifyStepOtp" style="display: none;">
                    <div class="smug-field-group">
                        <label class="smug-label" for="client_input_otp">Enter 6-Digit Security Code *</label>
                        <input type="text"
                               id="client_input_otp"
                               class="smug-input smug-otp-input"
                               placeholder="000000"
                               maxlength="6"
                               inputmode="numeric"
                               autocomplete="one-time-code">
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; color:var(--smug-text-muted);">
                        <span id="otpNoticeText">Code sent to your contact info</span>
                        <button type="button" onclick="resetToIdentityStep()" style="background:none; border:none; color:#ef4444; font-weight:600; cursor:pointer;">
                            Edit info / Resend
                        </button>
                    </div>
                </div>
            </div>

            <div class="smug-modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeVerifyModal()">Cancel</button>
                <button type="button" class="btn-modal-submit" id="btnVerifySubmit" onclick="submitVerification()">
                    <i class="fa-solid fa-paper-plane"></i> Send Verification Code
                </button>
            </div>
        </div>
    </div>

    <!-- Feedback Toast -->
    <div id="smugToast" class="smug-toast">
        <i class="fa-solid fa-circle-check" style="color: #ef4444;"></i>
        <span id="smugToastMsg">Notification</span>
    </div>

    <script>
        const galleryItems = <?php echo json_encode($photoItems); ?>;
        const currentAlbumId = <?php echo (int)$currentAlbumId; ?>;
        const currentAlbumTitle = <?php echo json_encode($currentAlbum['title'] ?? 'Falhen Gallery'); ?>;
        let isClientVerified = <?php echo json_encode($isClientVerified); ?>;
        let downloadToken = <?php echo json_encode($downloadToken); ?>;

        let currentPhotoIndex = 0;
        let slideshowTimer = null;
        let verificationStep = 'identity';
        let pendingDownloadCallback = null;

        function showToast(msg) {
            const toast = document.getElementById('smugToast');
            const msgEl = document.getElementById('smugToastMsg');
            msgEl.textContent = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3600);
        }

        // ================= DYNAMIC LIGHTBOX & SLIDESHOW =================
        function openDynamicFullView(idx) {
            if (!galleryItems || galleryItems.length === 0) return;
            if (idx < 0 || idx >= galleryItems.length) idx = 0;
            currentPhotoIndex = idx;
            updateLightboxView();
            document.getElementById('smugLightbox').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeDynamicFullView() {
            stopSlideshow();
            resetLightboxZoom();
            document.getElementById('smugLightbox').style.display = 'none';
            document.body.style.overflow = '';
        }

        function nextPhoto() {
            currentPhotoIndex = (currentPhotoIndex + 1) % galleryItems.length;
            updateLightboxView();
        }

        function prevPhoto() {
            currentPhotoIndex = (currentPhotoIndex - 1 + galleryItems.length) % galleryItems.length;
            updateLightboxView();
        }

        function goToPhoto(idx) {
            currentPhotoIndex = idx;
            updateLightboxView();
        }

        function updateLightboxView() {
            const item = galleryItems[currentPhotoIndex];
            const img = document.getElementById('lightboxMainImg');
            resetLightboxZoom();

            img.style.opacity = '0.3';
            setTimeout(() => {
                img.src = item.full_url;
                img.style.opacity = '1';
            }, 80);

            document.getElementById('lightboxCounter').textContent =
                `Photo ${currentPhotoIndex + 1} of ${galleryItems.length}`;

            const thumbs = document.querySelectorAll('#thumbsRail .smug-thumb');
            thumbs.forEach((t, i) => {
                if (i === currentPhotoIndex) {
                    t.classList.add('active');
                    t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                } else {
                    t.classList.remove('active');
                }
            });
        }

        function toggleLightboxZoom() {
            const holder = document.getElementById('imageHolder');
            const label = document.getElementById('zoomLabel');
            holder.classList.toggle('zoomed');
            label.textContent = holder.classList.contains('zoomed') ? 'Reset' : 'Zoom';
        }

        function resetLightboxZoom() {
            const holder = document.getElementById('imageHolder');
            const label = document.getElementById('zoomLabel');
            if (holder) holder.classList.remove('zoomed');
            if (label) label.textContent = 'Zoom';
        }

        function toggleSlideshow() {
            if (slideshowTimer) {
                stopSlideshow();
            } else {
                startSlideshow();
            }
        }

        function startSlideshow() {
            const icon = document.getElementById('slideshowIcon');
            const label = document.getElementById('slideshowLabel');
            icon.className = 'fa-solid fa-pause';
            label.textContent = 'Pause';
            slideshowTimer = setInterval(nextPhoto, 3800);
            showToast('Slideshow started');
        }

        function stopSlideshow() {
            if (slideshowTimer) {
                clearInterval(slideshowTimer);
                slideshowTimer = null;
                const icon = document.getElementById('slideshowIcon');
                const label = document.getElementById('slideshowLabel');
                if (icon) icon.className = 'fa-solid fa-play';
                if (label) label.textContent = 'Slideshow';
            }
        }

        // Keyboard Navigation
        document.addEventListener('keydown', function(e) {
            const lb = document.getElementById('smugLightbox');
            if (lb && lb.style.display === 'flex') {
                if (e.key === 'ArrowRight') nextPhoto();
                else if (e.key === 'ArrowLeft') prevPhoto();
                else if (e.key === 'Escape') closeDynamicFullView();
                else if (e.key === ' ') {
                    e.preventDefault();
                    toggleSlideshow();
                }
            }
        });

        // Touch Swipe
        let touchX = 0;
        const stage = document.getElementById('lightboxStage');
        if (stage) {
            stage.addEventListener('touchstart', e => {
                touchX = e.changedTouches[0].screenX;
            }, { passive: true });
            stage.addEventListener('touchend', e => {
                const diff = e.changedTouches[0].screenX - touchX;
                if (diff < -50) nextPhoto();
                else if (diff > 50) prevPhoto();
            }, { passive: true });
        }

        // ================= SECURITY GATE & OTP =================
        function requireVerification(callback) {
            if (isClientVerified) {
                callback();
            } else {
                pendingDownloadCallback = callback;
                openVerifyModal();
            }
        }

        function openVerifyModal() {
            resetToIdentityStep();
            document.getElementById('smugVerifyModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeVerifyModal() {
            document.getElementById('smugVerifyModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        function resetToIdentityStep() {
            verificationStep = 'identity';
            document.getElementById('verifyStepIdentity').style.display = 'block';
            document.getElementById('verifyStepOtp').style.display = 'none';
            document.getElementById('verifyErrorAlert').style.display = 'none';
            document.getElementById('verifyInfoAlert').style.display = 'none';
            const btn = document.getElementById('btnVerifySubmit');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Verification Code';
        }

        async function submitVerification() {
            const name = document.getElementById('client_input_name').value.trim();
            const email = document.getElementById('client_input_email').value.trim();
            const phone = document.getElementById('client_input_phone').value.trim();
            const errBox = document.getElementById('verifyErrorAlert');
            const infoBox = document.getElementById('verifyInfoAlert');
            const submitBtn = document.getElementById('btnVerifySubmit');

            errBox.style.display = 'none';

            if (verificationStep === 'identity') {
                if (!name || name.length < 2) {
                    errBox.textContent = 'Please enter your full name.';
                    errBox.style.display = 'block';
                    return;
                }
                if (!email || !email.includes('@')) {
                    errBox.textContent = 'Please enter a valid email address.';
                    errBox.style.display = 'block';
                    return;
                }
                if (!phone || phone.replace(/[^0-9+]/g, '').length < 7) {
                    errBox.textContent = 'Please enter a valid phone number.';
                    errBox.style.display = 'block';
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending 6-Digit Code...';

                try {
                    const fd = new FormData();
                    fd.append('action', 'request_code');
                    fd.append('name', name);
                    fd.append('email', email);
                    fd.append('phone', phone);
                    fd.append('album_id', currentAlbumId);
                    fd.append('album_title', currentAlbumTitle);

                    const resp = await fetch('/api/verify-download.php', { method: 'POST', body: fd });
                    const res = await resp.json();

                    if (res.success) {
                        verificationStep = 'otp';
                        document.getElementById('verifyStepIdentity').style.display = 'none';
                        document.getElementById('verifyStepOtp').style.display = 'block';
                        infoBox.style.display = 'block';
                        infoBox.innerHTML = `<i class="fa-solid fa-envelope-circle-check"></i> ${res.message}` +
                            (res.dev_otp_hint ? `<br><strong>Passcode: ${res.dev_otp_hint}</strong>` : '');
                        if (res.dev_otp_hint) {
                            document.getElementById('client_input_otp').value = res.dev_otp_hint;
                        }
                        submitBtn.innerHTML = '<i class="fa-solid fa-unlock-keyhole"></i> Verify & Start Download';
                        document.getElementById('client_input_otp').focus();
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
                const otp = document.getElementById('client_input_otp').value.trim();
                if (!otp || otp.length !== 6) {
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
                    fd.append('code', otp);
                    fd.append('album_id', currentAlbumId);
                    fd.append('album_title', currentAlbumTitle);

                    const resp = await fetch('/api/verify-download.php', { method: 'POST', body: fd });
                    const res = await resp.json();

                    if (res.success && res.verified) {
                        isClientVerified = true;
                        downloadToken = res.download_token || '';
                        const badge = document.getElementById('verifiedStatusBadge');
                        if (badge) {
                            badge.style.display = 'inline-flex';
                            badge.innerHTML = '<i class="fa-solid fa-circle-check"></i> Verified Client';
                        }
                        closeVerifyModal();
                        showToast('Identity verified! Starting download...');

                        // Immediately trigger the requested file download without synthetic click blocking
                        if (typeof pendingDownloadCallback === 'function') {
                            const cb = pendingDownloadCallback;
                            pendingDownloadCallback = null;
                            cb(downloadToken);
                        } else if (res.download_zip_url) {
                            initiateBrowserDownload(res.download_zip_url);
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

        // ================= NATIVE INSTANT DOWNLOAD ENGINE =================
        function initiateBrowserDownload(url) {
            if (!url) return;

            // 1. Invisible streaming iframe ensures browser starts native file download without popup suppression
            let dlFrame = document.getElementById('falhenDlIframe');
            if (!dlFrame) {
                dlFrame = document.createElement('iframe');
                dlFrame.id = 'falhenDlIframe';
                dlFrame.style.display = 'none';
                dlFrame.style.width = '0px';
                dlFrame.style.height = '0px';
                dlFrame.style.border = 'none';
                document.body.appendChild(dlFrame);
            }
            dlFrame.src = url;

            // 2. Direct location fallback if iframe stream is prevented by browser policy
            setTimeout(() => {
                try {
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = '';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                } catch (e) {
                    window.location.assign(url);
                }
            }, 80);
        }

        // ================= DOWNLOAD TRIGGERS =================
        function triggerSinglePhotoDownload(idx) {
            const item = galleryItems[idx] || galleryItems[0];
            requireVerification((token) => {
                const activeToken = token || downloadToken;
                const url = `/api/download-album.php?album_id=${currentAlbumId}&photo_index=${item.index}&token=${encodeURIComponent(activeToken)}`;
                showToast(`Downloading Frame #${item.number}...`);
                initiateBrowserDownload(url);
            });
        }

        function triggerBulkDownload() {
            requireVerification((token) => {
                const activeToken = token || downloadToken;
                const url = `/api/download-album.php?album_id=${currentAlbumId}&token=${encodeURIComponent(activeToken)}`;
                showToast(`Packaging & downloading ${galleryItems.length} photos (.ZIP)...`);
                initiateBrowserDownload(url);
            });
        }

        // ================= CLIENT DIRECTORY SEARCH & CATEGORY FILTER =================
        let activeGalleryCategoryFilter = 'all';

        function filterGalleryCategory(cat, btn) {
            activeGalleryCategoryFilter = (cat || 'all').toLowerCase();
            document.querySelectorAll('.smug-cat-pill').forEach(p => p.classList.remove('active'));
            if (btn) btn.classList.add('active');
            applyGalleryDirectoryFilters();
        }

        function clearGallerySearch() {
            const input = document.getElementById('clientGallerySearch');
            if (input) input.value = '';
            const clearBtn = document.getElementById('clearGallerySearch');
            if (clearBtn) clearBtn.style.display = 'none';
            activeGalleryCategoryFilter = 'all';
            document.querySelectorAll('.smug-cat-pill').forEach((p, idx) => {
                if (idx === 0) p.classList.add('active');
                else p.classList.remove('active');
            });
            applyGalleryDirectoryFilters();
        }

        function applyGalleryDirectoryFilters() {
            const searchInput = document.getElementById('clientGallerySearch');
            const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
            const clearBtn = document.getElementById('clearGallerySearch');
            if (clearBtn) {
                clearBtn.style.display = query.length > 0 ? 'inline-flex' : 'none';
            }

            const cards = document.querySelectorAll('.smug-folder-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const title = (card.getAttribute('data-title') || '').toLowerCase();
                const client = (card.getAttribute('data-client') || '').toLowerCase();
                const cat = (card.getAttribute('data-cat') || '').toLowerCase();

                const matchesSearch = !query || title.includes(query) || client.includes(query) || cat.includes(query);
                const matchesCat = activeGalleryCategoryFilter === 'all' || cat === activeGalleryCategoryFilter;

                if (matchesSearch && matchesCat) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const countElem = document.getElementById('visibleGalleryCount');
            if (countElem) countElem.textContent = visibleCount;

            const emptyElem = document.getElementById('noGalleriesFound');
            if (emptyElem) {
                emptyElem.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('clientGallerySearch');
            if (searchInput) {
                searchInput.addEventListener('input', applyGalleryDirectoryFilters);
            }
        });
    </script>
</body>
</html>
