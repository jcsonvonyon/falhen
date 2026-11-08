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
$coverImage = getCloudinaryUrl($currentAlbum['image'] ?? $currentAlbum['cover'] ?? 'https://res.cloudinary.com/pnabfi91/image/upload/v1791224271/falhen/portfolio/ysfriy8jlhhkcuzl8j16.jpg');

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

// Fetch photos: prefer synced/uploaded gallery_photos from Cloudinary, then Google Drive folder
$rawPhotos = [];
if (!empty($currentAlbum['gallery_photos']) && is_array($currentAlbum['gallery_photos'])) {
    $rawPhotos = $currentAlbum['gallery_photos'];
} elseif (!empty($currentAlbum['gdrive_url'])) {
    $rawPhotos = getGoogleDriveFolderPhotos($currentAlbum['gdrive_url']);
} elseif (!empty($currentAlbum['photos']) && is_array($currentAlbum['photos'])) {
    $rawPhotos = $currentAlbum['photos'];
}

if (empty($rawPhotos)) {
    $cNameLower = strtolower(trim($currentAlbum['client'] ?? ''));
    $cTitleLower = strtolower(trim($currentAlbum['title'] ?? ''));
    if ($currentAlbumId === 1 || strpos($cNameLower, 'king david') !== false || strpos($cTitleLower, 'king david') !== false) {
        $rawPhotos = [
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096443/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-01.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096445/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-02.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096448/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-03.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096450/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-04.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096452/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-05.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096454/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-06.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096456/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-07.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096458/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-08.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096459/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-09.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096461/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-10.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096464/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-11.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096466/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-12.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096468/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-13.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096470/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-14.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096472/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-15.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096475/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-16.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096477/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-17.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096478/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-18.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096480/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-19.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096482/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-20.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096483/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-21.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096485/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-22.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096487/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-23.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096488/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-24.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1794096490/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-25.jpg'
        ];
    } else {
        $rawPhotos = [
            $coverImage,
            'https://res.cloudinary.com/pnabfi91/image/upload/v1791224271/falhen/portfolio/ysfriy8jlhhkcuzl8j16.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1791222750/falhen/portfolio/blpzc2fznaqr0czm3ji8.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1791222920/falhen/portfolio/c5k5xwoa1misejie2oxy.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1791226024/falhen/portfolio/ldtm77cjygkwcgijkdqq.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1791224320/falhen/portfolio/qdkldutyaqhrena0mrza.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1791226059/falhen/portfolio/lavtrjw84dyapshxdgpl.jpg',
            'https://res.cloudinary.com/pnabfi91/image/upload/v1791226088/falhen/portfolio/sfa4gof0yfr0bc14r1eg.jpg'
        ];
    }
}

$photoItems = buildClientGalleryPhotoItems($rawPhotos, $currentAlbumId);
$totalPhotos = count($photoItems);

// Normalize category taxonomy for clean filtering
if (!function_exists('normalizeCategoryName')) {
    function normalizeCategoryName($cat) {
        $c = trim((string)$cat);
        if (empty($c)) return 'General';
        if (strcasecmp($c, 'Event') === 0 || strcasecmp($c, 'Events') === 0) return 'Events';
        if (strcasecmp($c, 'Commercial') === 0 || strcasecmp($c, 'Commercials') === 0) return 'Commercial';
        return ucfirst(strtolower($c));
    }
}

// Normalize categories in albumsRepo
foreach ($albumsRepo as $aId => $alb) {
    $albumsRepo[$aId]['category'] = normalizeCategoryName($alb['category'] ?? 'General');
}

// Collect all unique normalized categories for the directory filter
$allCategories = [];
foreach ($albumsRepo as $alb) {
    $cat = $alb['category'];
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

    <script>
        (function() {
            var theme = localStorage.getItem('falhen_theme') || 'daylight';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <style>
        :root,
        [data-theme="daylight"] {
            --portal-theme: "daylight";
            --smug-bg: #f8fafc;
            --smug-bg-gradient: radial-gradient(circle at 10% 8%, rgba(220, 38, 38, 0.04) 0%, transparent 40%),
                                radial-gradient(circle at 90% 12%, rgba(59, 130, 246, 0.04) 0%, transparent 40%),
                                radial-gradient(circle at 50% 80%, rgba(245, 158, 11, 0.02) 0%, transparent 50%);
            --smug-surface: rgba(255, 255, 255, 0.78);
            --smug-surface-solid: #ffffff;
            --smug-surface-hover: rgba(255, 255, 255, 0.95);
            --smug-border: rgba(15, 23, 42, 0.08);
            --smug-border-light: rgba(15, 23, 42, 0.14);
            --smug-glass-border: rgba(255, 255, 255, 0.85);
            --smug-glass-glow: 0 16px 36px -8px rgba(15, 23, 42, 0.06), inset 0 1px 1px rgba(255, 255, 255, 0.9);
            --smug-glass-glow-hover: 0 24px 48px -6px rgba(15, 23, 42, 0.12), inset 0 1px 1px #ffffff;
            --smug-red: #dc2626;
            --smug-red-hover: #b91c1c;
            --smug-text-primary: #09090b;
            --smug-text-secondary: #334155;
            --smug-text-muted: #64748b;
            --smug-topbar-bg: rgba(248, 250, 252, 0.82);
            --smug-glass-dock-bg: rgba(255, 255, 255, 0.82);
            --smug-badge-bg: rgba(255, 255, 255, 0.9);
            --smug-badge-border: rgba(15, 23, 42, 0.08);
            --smug-badge-text: #09090b;
            --smug-pill-bg: rgba(15, 23, 42, 0.04);
            --smug-pill-border: rgba(15, 23, 42, 0.08);
            --smug-pill-text: #334155;
            --smug-input-bg: rgba(255, 255, 255, 0.9);
        }

        [data-theme="noir"] {
            --portal-theme: "noir";
            --smug-bg: #070709;
            --smug-bg-gradient: radial-gradient(circle at 10% 8%, rgba(220, 38, 38, 0.08) 0%, transparent 40%),
                                radial-gradient(circle at 90% 12%, rgba(99, 102, 241, 0.07) 0%, transparent 40%);
            --smug-surface: rgba(17, 17, 24, 0.65);
            --smug-surface-solid: #0f0f13;
            --smug-surface-hover: rgba(24, 24, 34, 0.85);
            --smug-border: rgba(255, 255, 255, 0.08);
            --smug-border-light: rgba(255, 255, 255, 0.16);
            --smug-glass-border: rgba(255, 255, 255, 0.12);
            --smug-glass-glow: 0 20px 45px rgba(0, 0, 0, 0.6), inset 0 1px 1px rgba(255, 255, 255, 0.1);
            --smug-glass-glow-hover: 0 28px 60px rgba(0, 0, 0, 0.8), inset 0 1px 1px rgba(255, 255, 255, 0.2);
            --smug-red: #dc2626;
            --smug-red-hover: #ef4444;
            --smug-text-primary: #ffffff;
            --smug-text-secondary: #a1a1aa;
            --smug-text-muted: #71717a;
            --smug-topbar-bg: rgba(7, 7, 9, 0.85);
            --smug-glass-dock-bg: rgba(10, 10, 14, 0.68);
            --smug-badge-bg: rgba(7, 7, 9, 0.75);
            --smug-badge-border: rgba(255, 255, 255, 0.14);
            --smug-badge-text: #ffffff;
            --smug-pill-bg: rgba(255, 255, 255, 0.05);
            --smug-pill-border: rgba(255, 255, 255, 0.1);
            --smug-pill-text: #a1a1aa;
            --smug-input-bg: rgba(17, 17, 22, 0.85);
        }

        /* High-Contrast Daylight Rules */
        [data-theme="daylight"] h1,
        [data-theme="daylight"] h2,
        [data-theme="daylight"] h3,
        [data-theme="daylight"] .smug-album-title,
        [data-theme="daylight"] .smug-directory-title,
        [data-theme="daylight"] .smug-folder-title {
            color: #09090b !important;
        }

        [data-theme="daylight"] .smug-album-meta,
        [data-theme="daylight"] .smug-directory-subtitle,
        [data-theme="daylight"] .smug-folder-subtitle {
            color: #334155 !important;
        }

        [data-theme="daylight"] .btn-smug-ghost {
            color: #09090b !important;
            background: rgba(255, 255, 255, 0.92) !important;
            border: 1px solid rgba(15, 23, 42, 0.18) !important;
        }

        [data-theme="daylight"] .btn-smug-ghost:hover {
            background: #ffffff !important;
            border-color: #09090b !important;
            color: #09090b !important;
        }

        [data-theme="daylight"] .smug-back-link {
            color: #334155 !important;
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid rgba(15, 23, 42, 0.14) !important;
        }

        [data-theme="daylight"] .smug-back-link:hover {
            color: #09090b !important;
            background: #ffffff !important;
            border-color: #09090b !important;
        }

        [data-theme="daylight"] .smug-folder-glass-dock {
            background: rgba(255, 255, 255, 0.92) !important;
            border-top: 1px solid rgba(15, 23, 42, 0.1) !important;
        }

        [data-theme="daylight"] .smug-folder-card {
            background: rgba(255, 255, 255, 0.85) !important;
            border: 1px solid rgba(15, 23, 42, 0.1) !important;
            box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.08) !important;
        }

        [data-theme="daylight"] .smug-folder-count-badge {
            background: rgba(15, 23, 42, 0.06) !important;
            color: #09090b !important;
            border: 1px solid rgba(15, 23, 42, 0.1) !important;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            overflow-x: clip;
            width: 100%;
            max-width: 100vw;
        }

        body {
            background-color: var(--smug-bg);
            background-image: var(--smug-bg-gradient);
            background-attachment: fixed;
            color: var(--smug-text-primary);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            overflow-x: clip;
            width: 100%;
            max-width: 100vw;
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* SmugMug Minimal Top Brand Header */
        .smug-topbar {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            max-width: 100vw;
            overflow-x: clip;
            background: var(--smug-topbar-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-bottom: 1px solid var(--smug-border);
            z-index: 100;
            padding: 14px 32px;
            box-sizing: border-box;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .smug-topbar-inner {
            max-width: 1720px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            width: 100%;
            box-sizing: border-box;
        }

        .smug-brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--smug-text-primary);
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

        /* Daylight / Noir Atmosphere Switcher */
        .smug-theme-toggle {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--smug-surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--smug-border-light);
            color: var(--smug-text-primary);
            font-size: 0.78rem;
            font-weight: 600;
            padding: 7px 13px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
            touch-action: manipulation;
        }

        .smug-theme-toggle:hover {
            border-color: var(--smug-red);
            transform: translateY(-1px);
        }

        .smug-theme-toggle i {
            color: #f59e0b;
            font-size: 0.85rem;
            transition: transform 0.3s ease;
        }

        [data-theme="noir"] .smug-theme-toggle i {
            color: #a78bfa;
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

        .smug-nav-link:hover,
        .smug-nav-link.active {
            color: var(--smug-text-primary);
            background: var(--smug-pill-bg);
        }

        .smug-nav-cta {
            background: var(--smug-surface);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--smug-border-light);
            color: var(--smug-text-primary);
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
            background: var(--smug-surface-hover);
            border-color: var(--smug-border);
            transform: translateY(-1px);
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
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);
        }

        .smug-nav-primary .btn-text-short {
            display: none;
        }

        .smug-nav-primary:hover {
            background: var(--smug-red-hover);
            border-color: var(--smug-red-hover);
            transform: translateY(-1px);
        }

        /* Container */
        .smug-page-container {
            max-width: 1720px;
            margin: 0 auto;
            padding: 28px 32px 80px;
        }

        /* Breadcrumbs */
        .smug-back-nav-wrap {
            margin-bottom: 20px;
        }

        .smug-back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--smug-text-secondary);
            text-decoration: none;
            padding: 7px 15px;
            border-radius: 20px;
            background: var(--smug-surface);
            border: 1px solid var(--smug-border);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .smug-back-link:hover {
            color: var(--smug-text-primary);
            border-color: var(--smug-border-light);
            background: var(--smug-surface-hover);
            transform: translateX(-3px);
        }

        /* Album Hero Header (Clean, Editorial, Photography-First) */
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
            font-size: clamp(1.6rem, 3.5vw, 2.75rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.18;
            color: var(--smug-text-primary);
            margin-bottom: 10px;
            word-break: break-word;
        }

        .smug-album-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.88rem;
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
            color: #16a34a;
            font-size: 0.82rem;
            font-weight: 600;
        }

        [data-theme="noir"] .smug-verified-badge {
            color: #4ade80;
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
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            text-decoration: none;
            border: 1px solid transparent;
        }

        .btn-smug-action:hover {
            transform: translateY(-2px);
        }

        .btn-smug-primary {
            background: var(--smug-red);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);
        }

        .btn-smug-primary:hover {
            background: var(--smug-red-hover);
            box-shadow: 0 6px 18px rgba(220, 38, 38, 0.4);
        }

        .btn-smug-ghost {
            background: var(--smug-surface);
            border-color: var(--smug-border-light);
            color: var(--smug-text-primary);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .btn-smug-ghost:hover {
            background: var(--smug-surface-hover);
            border-color: var(--smug-text-secondary);
            color: var(--smug-text-primary);
        }

        /* SmugMug Pure Masonry / Justified Gallery Grid */
        .smug-gallery-grid {
            column-count: 4;
            column-gap: 14px;
            width: 100%;
            box-sizing: border-box;
        }

        @media (max-width: 1280px) {
            .smug-gallery-grid {
                column-count: 3;
                column-gap: 12px;
            }
        }

        @media (max-width: 900px) {
            .smug-gallery-grid {
                column-count: 2;
                column-gap: 10px;
            }
        }

        /* iPad / Tablet Responsiveness (641px - 1024px) */
        @media (min-width: 641px) and (max-width: 1024px) {
            .smug-topbar {
                padding: 12px 20px;
            }
            .smug-page-container {
                padding: 20px 20px 60px;
            }
            .smug-gallery-grid {
                column-count: 3 !important;
                column-gap: 12px !important;
            }
            .smug-folders-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 18px !important;
            }
            .smug-album-title {
                font-size: 2.1rem;
            }
            .smug-album-header {
                gap: 18px;
            }
        }

        /* Mobile Phone Responsiveness (<= 640px) */
        @media (max-width: 640px) {
            .smug-gallery-grid {
                column-count: 2 !important;
                column-gap: 8px !important;
                width: 100% !important;
            }
            .smug-photo-card {
                margin-bottom: 8px !important;
                border-radius: 8px !important;
            }
            .smug-photo-card img {
                border-radius: 8px !important;
            }
            .smug-photo-overlay {
                padding: 8px !important;
            }
            .smug-overlay-num {
                font-size: 0.7rem !important;
                padding: 2px 6px !important;
            }
            .smug-topbar {
                padding: 10px 14px;
                padding-left: max(14px, env(safe-area-inset-left));
                padding-right: max(14px, env(safe-area-inset-right));
                height: 54px;
                overflow: hidden;
            }
            .smug-topbar-inner {
                gap: 8px;
                width: 100%;
            }
            .smug-brand-link {
                gap: 8px;
            }
            .smug-brand-link img {
                height: 24px;
            }
            .smug-brand-tag {
                display: none !important;
            }
            .smug-topbar-actions {
                gap: 6px;
            }
            /* Hide verbose text links and secondary buttons on mobile top bar */
            .smug-nav-link,
            .smug-nav-cta,
            .smug-nav-primary,
            .smug-nav-download {
                display: none !important;
            }
            /* Minimalist icon-only Sun/Moon atmosphere switch */
            .smug-theme-toggle {
                padding: 0 !important;
                width: 36px !important;
                height: 36px !important;
                border-radius: 50% !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 0 !important;
            }
            .smug-theme-toggle span {
                display: none !important;
            }
            /* Keep mobile topbar sleek: logo on left, theme switch on right */
            .smug-page-container {
                padding: 16px 16px 60px !important;
                padding-left: max(16px, env(safe-area-inset-left)) !important;
                padding-right: max(16px, env(safe-area-inset-right)) !important;
                width: 100% !important;
                max-width: 100vw !important;
                box-sizing: border-box !important;
                overflow-x: clip !important;
            }
            .smug-back-nav-wrap {
                margin-bottom: 12px;
            }
            .smug-back-link {
                padding: 5px 12px;
                font-size: 0.8rem;
            }
            .smug-album-title {
                font-size: clamp(1.35rem, 5.5vw, 1.85rem) !important;
                line-height: 1.25 !important;
                word-break: break-word !important;
                overflow-wrap: break-word !important;
                margin-bottom: 6px !important;
            }
            .smug-album-meta {
                font-size: 0.8rem;
                gap: 8px;
            }
            .smug-album-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
                margin-bottom: 20px;
                padding-bottom: 16px;
            }
            .smug-header-actions {
                width: 100%;
                display: grid;
                grid-template-columns: 1.25fr 1fr;
                gap: 8px;
            }
            .btn-smug-action {
                justify-content: center;
                padding: 10px 12px;
                font-size: 0.82rem;
                width: 100%;
                white-space: nowrap;
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
            margin-bottom: 36px;
        }

        .smug-header-row {
            margin-bottom: 24px;
        }

        .smug-directory-title {
            font-size: clamp(2rem, 3.8vw, 3rem);
            font-weight: 800;
            color: var(--smug-text-primary);
            letter-spacing: -0.03em;
            line-height: 1.15;
            margin-bottom: 6px;
        }

        .smug-directory-subtitle {
            font-size: 0.95rem;
            color: var(--smug-text-secondary);
            font-weight: 500;
        }

        /* Glassmorphic Search & Filter Bar */
        .smug-glass-toolbar {
            background: var(--smug-surface);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--smug-glass-border);
            border-radius: 16px;
            padding: 12px 18px;
            box-shadow: var(--smug-glass-glow);
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            transition: all 0.3s ease;
        }

        .smug-search-wrap {
            position: relative;
            flex: 1;
            min-width: 240px;
            max-width: 380px;
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
            background: var(--smug-input-bg);
            border: 1px solid var(--smug-border);
            border-radius: 10px;
            padding: 9px 36px 9px 36px;
            color: var(--smug-text-primary);
            font-size: 0.88rem;
            outline: none;
            transition: all 0.2s ease;
            -webkit-appearance: none;
        }

        .smug-search-input:focus {
            border-color: var(--smug-red);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
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
            color: var(--smug-text-primary);
        }

        .smug-cat-pills-row {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 2px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            flex: 2;
        }

        .smug-cat-pills-row::-webkit-scrollbar {
            display: none;
        }

        .smug-cat-pill {
            background: var(--smug-pill-bg);
            border: 1px solid var(--smug-pill-border);
            color: var(--smug-pill-text);
            font-size: 0.8rem;
            font-weight: 600;
            padding: 7px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
            flex-shrink: 0;
            touch-action: manipulation;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .smug-cat-pill span {
            opacity: 0.6;
            font-size: 0.75rem;
        }

        .smug-cat-pill:hover {
            color: var(--smug-text-primary);
            border-color: var(--smug-border-light);
            background: var(--smug-surface-hover);
        }

        .smug-cat-pill.active {
            background: var(--smug-red);
            border-color: var(--smug-red);
            color: #ffffff;
            font-weight: 700;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);
        }

        .smug-cat-pill.active span {
            opacity: 0.95;
            color: #ffffff;
        }

        /* Dynamic & Engaging Glassmorphism Folders Grid */
        .smug-folders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 28px;
            margin-top: 24px;
        }

        .smug-folder-card {
            position: relative;
            background: var(--smug-surface);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid var(--smug-glass-border);
            border-radius: 14px;
            overflow: hidden;
            text-decoration: none;
            color: var(--smug-text-primary);
            box-shadow: var(--smug-glass-glow);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.35s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.25s ease;
            display: flex;
            flex-direction: column;
            -webkit-tap-highlight-color: transparent;
        }

        @media (hover: hover) {
            .smug-folder-card:hover {
                transform: translateY(-5px);
                box-shadow: var(--smug-glass-glow-hover);
                border-color: rgba(220, 38, 38, 0.35);
            }
            .smug-folder-card:hover .smug-folder-cover img {
                transform: scale(1.04);
            }
        }

        .smug-folder-card:active {
            transform: scale(0.99);
        }

        .smug-folder-cover {
            position: relative;
            width: 100%;
            aspect-ratio: 4 / 3;
            overflow: hidden;
            background: var(--smug-surface-solid);
        }

        .smug-folder-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 20% !important;
            display: block;
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Frosted Glass Floating Dock inside Cover */
        .smug-folder-glass-dock {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--smug-glass-dock-bg);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border-top: 1px solid var(--smug-glass-border);
            padding: 13px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            z-index: 2;
            transition: background 0.3s ease, border-color 0.3s ease;
        }

        .smug-dock-text {
            flex: 1;
            min-width: 0;
        }

        .smug-folder-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--smug-text-primary);
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin: 0;
        }

        .smug-folder-meta {
            font-size: 0.8rem;
            color: var(--smug-text-secondary);
            margin-top: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Empty State */
        .smug-empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--smug-surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px dashed var(--smug-border-light);
            border-radius: 16px;
            margin-top: 24px;
        }

        .smug-empty-icon {
            font-size: 2.5rem;
            color: var(--smug-text-muted);
            margin-bottom: 14px;
        }

        .smug-empty-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--smug-text-primary);
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
            transition: opacity 0.22s ease, transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
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

        /* Responsive Mobile & Tablet Lightbox */
        @media (max-width: 640px) {
            .smug-lightbox {
                height: 100dvh;
                height: 100vh;
                width: 100vw;
                background: rgba(4, 4, 8, 0.98);
            }
            .smug-lightbox-top {
                padding: 10px 14px;
                height: 52px;
                background: rgba(0, 0, 0, 0.65);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
            }
            .smug-lightbox-title {
                font-size: 0.82rem;
            }
            .smug-lightbox-sep,
            .smug-lightbox-albumname {
                display: none !important;
            }
            .smug-lightbox-tools {
                gap: 6px;
            }
            .smug-tool-btn {
                padding: 7px 10px;
                border-radius: 20px;
                font-size: 0.8rem;
                background: rgba(255, 255, 255, 0.1);
            }
            .smug-tool-btn span,
            .smug-tool-btn .tool-btn-text,
            #slideshowLabel,
            #zoomLabel {
                display: none !important;
            }
            .smug-tool-download-all {
                display: none !important;
            }
            .smug-nav-arrow {
                display: none !important;
            }
            .smug-thumbs-rail {
                display: none !important;
            }
            .smug-lightbox-stage {
                padding: 6px;
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                height: calc(100dvh - 52px);
                height: calc(100vh - 52px);
                overflow: hidden;
            }
            .smug-image-holder {
                height: 100%;
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: default;
            }
            .smug-lightbox-img {
                max-height: calc(100dvh - 72px) !important;
                max-height: calc(100vh - 72px) !important;
                max-width: 96vw !important;
                border-radius: 6px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
                object-fit: contain;
            }
            .smug-image-holder.zoomed .smug-lightbox-img {
                max-height: none !important;
                max-width: none !important;
                transform: scale(1.8);
            }
        }

        @media (min-width: 641px) and (max-width: 1024px) {
            .smug-lightbox-top {
                padding: 12px 18px;
            }
            .smug-lightbox-img {
                max-height: 80vh;
                max-width: 90vw;
            }
            .smug-thumbs-rail {
                height: 60px;
                padding: 6px 16px;
            }
            .smug-thumb {
                height: 44px;
            }
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
            background: var(--smug-surface-solid);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--smug-glass-border);
            border-radius: 16px;
            max-width: 480px;
            width: 100%;
            overflow: hidden;
            box-shadow: var(--smug-glass-glow-hover);
            transition: background 0.3s ease;
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
            color: var(--smug-text-primary);
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
            background: var(--smug-input-bg);
            border: 1px solid var(--smug-border-light);
            border-radius: 8px;
            padding: 11px 14px;
            color: var(--smug-text-primary);
            font-size: 0.92rem;
            outline: none;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .smug-input:focus {
            border-color: var(--smug-red);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
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
            color: var(--smug-text-primary);
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
            background: var(--smug-surface-solid);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--smug-glass-border);
            color: var(--smug-text-primary);
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: var(--smug-glass-glow);
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
            <a href="/clients" class="smug-brand-link" title="Falhen Client Deliveries">
                <img src="/assets/img/icons/logo.png" alt="Falhen Media">
                <span class="smug-brand-tag">Client Galleries</span>
            </a>

            <div class="smug-topbar-actions">
                <!-- Daylight / Noir Atmosphere Toggle -->
                <button type="button" class="smug-theme-toggle" id="portalThemeToggle" onclick="togglePortalTheme()" title="Toggle Daylight / Noir Atmosphere">
                    <i class="fa-solid fa-sun" id="themeActiveIcon"></i>
                    <span id="themeActiveLabel">Daylight</span>
                </button>

                <a href="https://www.falhen.com/portfolio" class="smug-nav-link smug-nav-portfolio">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Portfolio</span>
                </a>
                <a href="/clients" class="smug-nav-link smug-nav-galleries <?php echo $isClientsView ? 'active' : ''; ?>">
                    <i class="fa-regular fa-folder"></i>
                    <span>Galleries</span>
                </a>
                <?php if (!$isClientsView): ?>
                    <button type="button" class="smug-nav-cta smug-nav-slideshow" onclick="openDynamicFullView(0)" title="Start Slideshow">
                        <i class="fa-solid fa-play"></i>
                        <span>Slideshow</span>
                    </button>
                    <button type="button" class="smug-nav-primary smug-nav-download" onclick="triggerBulkDownload()" title="Download All Photos">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                        <span class="btn-text-full">Download All</span>
                        <span class="btn-text-short">Download</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="smug-page-container">

        <?php if ($isClientsView): ?>
            <!-- ================= CLIENTS DIRECTORY VIEW ================= -->
            <div class="smug-directory-header">
                <div class="smug-header-row">
                    <h1 class="smug-directory-title">Client Deliveries</h1>
                    <p class="smug-directory-subtitle">Curated client archives & proof galleries.</p>
                </div>

                <!-- Glassmorphic Search & Filter Bar -->
                <div class="smug-glass-toolbar">
                    <div class="smug-search-wrap">
                        <i class="fa-solid fa-magnifying-glass smug-search-icon"></i>
                        <input type="text" id="clientGallerySearch" class="smug-search-input" placeholder="Search gallery or client name..." autocomplete="off">
                        <button type="button" id="clearGallerySearch" class="smug-search-clear" onclick="clearGallerySearch()" style="display:none;" aria-label="Clear search">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="smug-cat-pills-row" id="catPillsRow">
                        <button type="button" class="smug-cat-pill active" data-cat="all" onclick="filterGalleryCategory('all', this)">
                            All <span><?php echo count($albumsRepo); ?></span>
                        </button>
                        <?php foreach ($allCategories as $catName): 
                            $cCount = 0;
                            foreach ($albumsRepo as $alb) {
                                if (strcasecmp(trim($alb['category'] ?? ''), $catName) === 0) $cCount++;
                            }
                        ?>
                            <button type="button" class="smug-cat-pill" data-cat="<?php echo htmlspecialchars(strtolower($catName)); ?>" onclick="filterGalleryCategory('<?php echo htmlspecialchars(strtolower($catName)); ?>', this)">
                                <?php echo htmlspecialchars($catName); ?> <span><?php echo $cCount; ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Dynamic Editorial Gallery Cards -->
            <div class="smug-folders-grid" id="smugFoldersGrid">
                <?php foreach ($albumsRepo as $aId => $album): 
                    $albCover = getCloudinaryUrl($album['image'] ?? $album['cover'] ?? 'https://res.cloudinary.com/pnabfi91/image/upload/v1791224271/falhen/portfolio/ysfriy8jlhhkcuzl8j16.jpg');
                    $albCount = (int)($album['photosCount'] ?? 25);
                    $albSlug = $album['slug'] ?? generateSlug($album['title'] ?? 'gallery');
                    $albCategory = normalizeCategoryName($album['category'] ?? 'Gallery');
                    $albClient = trim($album['client'] ?? '');
                ?>
                    <a href="/album/<?php echo urlencode($albSlug); ?>" 
                       class="smug-folder-card" 
                       data-title="<?php echo htmlspecialchars(strtolower($album['title'])); ?>" 
                       data-client="<?php echo htmlspecialchars(strtolower($albClient)); ?>" 
                       data-cat="<?php echo htmlspecialchars(strtolower($albCategory)); ?>">
                        <div class="smug-folder-cover">
                            <img src="<?php echo htmlspecialchars($albCover); ?>" alt="<?php echo htmlspecialchars($album['title']); ?>" loading="lazy">
                            <span class="smug-folder-lock-badge" style="display:none;"><i class="fa-solid fa-lock"></i> Client Delivery</span>
                            <div class="smug-folder-glass-dock">
                                <div class="smug-dock-text">
                                    <h2 class="smug-folder-title"><?php echo htmlspecialchars($album['title']); ?></h2>
                                    <p class="smug-folder-meta">
                                        <span><?php echo htmlspecialchars($albCategory); ?></span>
                                        <span>·</span>
                                        <span><?php echo $albCount; ?> Photos</span>
                                        <?php if (!empty($albClient) && strcasecmp($albClient, trim($album['title'])) !== 0): ?>
                                            <span>·</span>
                                            <span><?php echo htmlspecialchars($albClient); ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
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
            <!-- ================= ALBUM GALLERY VIEW ================= -->
            <div class="smug-back-nav-wrap">
                <a href="/clients" class="smug-back-link">
                    <i class="fa-solid fa-arrow-left"></i> <span>Deliveries</span>
                </a>
            </div>

            <div class="smug-album-header">
                <div class="smug-header-info">
                    <h1 class="smug-album-title"><?php echo htmlspecialchars($currentAlbum['title']); ?></h1>
                    <div class="smug-album-meta">
                        <span><?php echo $totalPhotos; ?> Photos</span>
                        <?php 
                        $dispClient = trim($currentAlbum['client'] ?? ($clientName ?: ''));
                        if (!empty($dispClient) && strcasecmp($dispClient, trim($currentAlbum['title'])) !== 0): ?>
                            <span class="dot">·</span>
                            <span><?php echo htmlspecialchars($dispClient); ?></span>
                        <?php endif; ?>
                        <?php if ($isClientVerified): ?>
                            <span class="dot">·</span>
                            <span class="smug-verified-badge" id="verifiedStatusBadge">
                                <i class="fa-solid fa-circle-check"></i> Verified
                            </span>
                        <?php else: ?>
                            <span class="smug-verified-badge" id="verifiedStatusBadge" style="display:none;"></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="smug-header-actions">
                    <button type="button" class="btn-smug-action btn-smug-primary" onclick="triggerBulkDownload()">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                        <span>Download Gallery (<?php echo $totalPhotos; ?>)</span>
                    </button>
                    <button type="button" class="btn-smug-action btn-smug-ghost" onclick="openDynamicFullView(0)">
                        <i class="fa-solid fa-play"></i>
                        <span>Slideshow</span>
                    </button>
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
                <span class="smug-lightbox-sep">·</span>
                <span class="smug-lightbox-albumname"><?php echo htmlspecialchars($currentAlbum['title'] ?? 'Gallery'); ?></span>
            </div>

            <div class="smug-lightbox-tools">
                <button type="button" class="smug-tool-btn smug-tool-slideshow" id="btnSlideshowToggle" onclick="toggleSlideshow()" title="Slideshow">
                    <i class="fa-solid fa-play" id="slideshowIcon"></i>
                    <span id="slideshowLabel">Slideshow</span>
                </button>
                <button type="button" class="smug-tool-btn smug-tool-zoom" onclick="toggleLightboxZoom()" title="Zoom">
                    <i class="fa-solid fa-magnifying-glass-plus" id="zoomIcon"></i>
                    <span id="zoomLabel">Zoom</span>
                </button>
                <button type="button" class="smug-tool-btn smug-tool-download" onclick="triggerSinglePhotoDownload(currentPhotoIndex)" title="Download Photo">
                    <i class="fa-solid fa-arrow-down"></i> <span class="tool-btn-text">Download Photo</span>
                </button>
                <button type="button" class="smug-tool-btn smug-tool-download-all" onclick="triggerBulkDownload()" title="Download All Photos">
                    <i class="fa-solid fa-cloud-arrow-down"></i> <span class="tool-btn-text">Download All</span>
                </button>
                <button type="button" class="smug-tool-close" onclick="closeDynamicFullView()" aria-label="Close Lightbox" title="Close (Esc)">
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

            img.style.opacity = '0.35';
            img.style.transform = 'scale(0.985)';
            setTimeout(() => {
                img.src = item.full_url;
                img.style.opacity = '1';
                img.style.transform = 'scale(1)';
            }, 60);

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

        // Enhanced Touch Gestures (Swipe Left/Right, Swipe Down to Close, Double-Tap Zoom)
        let touchStartX = 0;
        let touchStartY = 0;
        let touchStartTime = 0;
        let lastTapTime = 0;
        const stage = document.getElementById('lightboxStage');
        const mainImg = document.getElementById('lightboxMainImg');

        if (stage) {
            stage.addEventListener('touchstart', e => {
                if (e.touches && e.touches.length === 1) {
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                    touchStartTime = Date.now();
                }
            }, { passive: true });

            stage.addEventListener('touchend', e => {
                if (e.changedTouches && e.changedTouches.length === 1) {
                    const diffX = e.changedTouches[0].clientX - touchStartX;
                    const diffY = e.changedTouches[0].clientY - touchStartY;
                    const elapsed = Date.now() - touchStartTime;

                    // If user is currently zoomed in, allow free pan without navigating
                    const holder = document.getElementById('imageHolder');
                    if (holder && holder.classList.contains('zoomed')) return;

                    // Horizontal swipe to next / prev frame
                    if (Math.abs(diffX) > 42 && Math.abs(diffX) > Math.abs(diffY) * 1.3) {
                        if (diffX < 0) {
                            nextPhoto();
                        } else {
                            prevPhoto();
                        }
                    }
                    // Quick vertical swipe down to dismiss lightbox (iOS/Android gesture)
                    else if (diffY > 90 && Math.abs(diffY) > Math.abs(diffX) * 1.6 && elapsed < 400) {
                        closeDynamicFullView();
                    }
                }
            }, { passive: true });
        }

        // Double-tap on image to toggle zoom
        if (mainImg) {
            mainImg.addEventListener('touchend', function(e) {
                const now = Date.now();
                if (now - lastTapTime < 320) {
                    e.preventDefault();
                    toggleLightboxZoom();
                }
                lastTapTime = now;
            });
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

        // ================= ATMOSPHERIC DAYLIGHT & NOIR THEME ENGINE =================
        function initPortalTheme() {
            const savedTheme = localStorage.getItem('falhen_theme') || 'daylight';
            applyPortalTheme(savedTheme);
        }

        function togglePortalTheme() {
            const current = document.documentElement.getAttribute('data-theme') || 'daylight';
            const next = current === 'daylight' ? 'noir' : 'daylight';
            applyPortalTheme(next);
        }

        function applyPortalTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            document.body.setAttribute('data-theme', theme);
            localStorage.setItem('falhen_theme', theme);

            const icon = document.getElementById('themeActiveIcon');
            const label = document.getElementById('themeActiveLabel');
            if (icon && label) {
                if (theme === 'noir') {
                    icon.className = 'fa-solid fa-moon';
                    label.textContent = 'Noir';
                } else {
                    icon.className = 'fa-solid fa-sun';
                    label.textContent = 'Daylight';
                }
            }
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
            initPortalTheme();
            const searchInput = document.getElementById('clientGallerySearch');
            if (searchInput) {
                searchInput.addEventListener('input', applyGalleryDirectoryFilters);
            }
        });
    </script>
</body>
</html>
