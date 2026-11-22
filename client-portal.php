<?php
/**
 * client-portal.php
 * Falhen Productions — Independent Client Gallery Subdomain Portal (client.falhen.com)
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
    if (!empty($item['hidden']) || !empty($item['is_hidden']) || !empty($item['deleted']) || ($item['status'] ?? '') === 'hidden' || ($item['visibility'] ?? '') === 'hidden') {
        continue; // Strictly respect hidden and deleted album status
    }
    if (($item['media_type'] ?? 'photo') === 'photo' || !isset($item['media_type'])) {
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
    $seoTitle = "Client Galleries & Deliveries — Falhen Productions";
    $seoDesc = "Access, review proofs, and download high-resolution photography deliveries for Falhen Productions clients.";
    $seoKeywords = "client galleries, photography delivery, client portal, falhen productions, weddings, portraits, commercial, corporate";
    $pageTitle = $seoTitle;
} else {
    $albumTitle = $currentAlbum['title'] ?? 'Client Gallery';
    $albumCategory = $currentAlbum['category'] ?? 'Photography';
    $albumClient = $currentAlbum['client'] ?? 'Private Client';

    $seoTitle = !empty($currentAlbum['seo_title']) ? $currentAlbum['seo_title'] : $albumTitle;
    $seoTitle = preg_replace('/\s*[\|\-]\s*Falhen\s*(Media|Productions)?\s*$/i', '', (string)$seoTitle);

    $rawDesc = trim((string)($currentAlbum['seo_desc'] ?? ''));
    if ($rawDesc === '' || (function_exists('isLegacyBoilerplateSeoDesc') && isLegacyBoilerplateSeoDesc($rawDesc))) {
        $backstory = trim(preg_replace('/\s+/', ' ', strip_tags((string)($currentAlbum['desc'] ?? ''))));
        if ($backstory !== '') {
            $rawDesc = strlen($backstory) > 160 ? rtrim(substr($backstory, 0, 157)) . '...' : $backstory;
        } else {
            $locBit = !empty($currentAlbum['location']) ? ' in ' . trim($currentAlbum['location']) : '';
            $rawDesc = "{$albumTitle} ({$albumCategory}){$locBit}. View and download high-resolution photography.";
        }
    }
    $seoDesc = preg_replace('/\s*\|\s*Falhen\s*(Media|Productions)?\s*$/i', '', (string)$rawDesc);
    $seoKeywords = !empty($currentAlbum['seo_keywords']) ? $currentAlbum['seo_keywords'] : "{$albumCategory}, client gallery, high resolution, photo download";
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
    $rawPhotos = !empty($coverImage) ? [$coverImage] : [];
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
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--smug-text-primary);
            flex-shrink: 0;
        }

        .smug-brand-link img {
            height: 40px;
            width: auto;
            display: block;
            object-fit: contain;
            /* logo.png is pure white; invert to crisp jet-black on Daylight (white) theme */
            filter: brightness(0);
            transition: filter 0.25s ease, transform 0.2s ease;
        }

        [data-theme="noir"] .smug-brand-link img {
            filter: none;
        }

        .smug-brand-wordmark {
            font-size: 1.02rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--smug-text-primary);
            line-height: 1;
        }

        .smug-brand-tag {
            font-size: 0.72rem;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--smug-text-muted);
            border-left: 1px solid var(--smug-border-light);
            padding-left: 12px;
        }

        .smug-topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Daylight / Noir Atmosphere Switcher */
        .smug-theme-toggle {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--smug-surface);
            border: 1px solid var(--smug-border-light);
            color: var(--smug-text-primary);
            font-size: 0.78rem;
            font-weight: 600;
            padding: 7px 13px;
            border-radius: 20px;
            cursor: pointer;
            transition: border-color 0.2s ease, background-color 0.2s ease;
            user-select: none;
            touch-action: manipulation;
        }

        .smug-theme-toggle:hover {
            border-color: var(--smug-red);
        }

        .smug-theme-toggle i {
            color: #d97706;
            font-size: 0.85rem;
        }

        [data-theme="noir"] .smug-theme-toggle i {
            color: #f8fafc;
        }

        .smug-nav-link {
            color: var(--smug-text-secondary);
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: 8px;
            transition: color 0.2s ease, background-color 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
        }

        .smug-nav-link:hover,
        .smug-nav-link.active {
            color: var(--smug-text-primary);
            background: var(--smug-pill-bg);
        }

        .smug-nav-portfolio {
            border: 1px solid var(--smug-border-light);
            color: var(--smug-text-primary);
        }

        .smug-nav-portfolio:hover {
            border-color: var(--smug-red);
            color: var(--smug-red);
            background: transparent;
        }
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

        /* ================= EDITORIAL MOSAIC GRID WITH FROSTED GLASSMORPHISM ================= */
        .smug-gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-auto-flow: dense;
            gap: 16px;
            width: 100%;
            box-sizing: border-box;
        }

        @media (max-width: 1280px) {
            .smug-gallery-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 14px;
            }
        }

        @media (max-width: 900px) {
            .smug-gallery-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
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
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 12px !important;
            }
            .smug-photo-card.mosaic-lead {
                grid-column: span 2 !important;
                grid-row: span 2 !important;
            }
            .smug-photo-card.mosaic-wide {
                grid-column: span 2 !important;
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

        /* Mobile Phone Responsiveness (<= 640px) — Editorial & Tightly Formatted */
        @media (max-width: 640px) {
            .smug-page-container {
                padding: 18px 16px 40px !important;
                padding-left: max(16px, env(safe-area-inset-left)) !important;
                padding-right: max(16px, env(safe-area-inset-right)) !important;
                width: 100% !important;
                max-width: 100vw !important;
                box-sizing: border-box !important;
                overflow-x: hidden !important;
            }
            .smug-topbar {
                padding: 0 16px !important;
                padding-left: max(16px, env(safe-area-inset-left)) !important;
                padding-right: max(16px, env(safe-area-inset-right)) !important;
                height: 60px !important;
                display: flex !important;
                align-items: center !important;
            }
            .smug-topbar-inner {
                gap: 10px !important;
                width: 100% !important;
                height: 60px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
            }
            .smug-brand-link {
                gap: 8px !important;
            }
            .smug-brand-link img {
                height: 36px !important;
            }
            .smug-brand-wordmark {
                font-size: 0.96rem !important;
            }
            .smug-brand-tag {
                display: none !important;
            }
            .smug-topbar-actions {
                gap: 8px !important;
                display: flex !important;
                align-items: center !important;
            }
            /* Keep main Portfolio link cleanly visible on mobile for effortless site flow */
            .smug-nav-galleries {
                display: none !important;
            }
            .smug-nav-portfolio {
                display: inline-flex !important;
                padding: 7px 12px !important;
                font-size: 0.8rem !important;
                border-radius: 999px !important;
                gap: 5px !important;
            }
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
            /* Directory Header & Flat Editorial Controls on Mobile */
            .smug-directory-header {
                margin-bottom: 20px !important;
            }
            .smug-header-row {
                margin-bottom: 14px !important;
                text-align: left !important;
            }
            .smug-directory-title {
                font-size: 1.75rem !important;
                font-weight: 800 !important;
                letter-spacing: -0.03em !important;
                line-height: 1.12 !important;
                margin: 0 0 6px !important;
            }
            .smug-directory-subtitle {
                font-size: 0.92rem !important;
                line-height: 1.45 !important;
                margin: 0 !important;
                color: var(--smug-text-secondary) !important;
            }
            .smug-glass-toolbar {
                padding: 12px !important;
                gap: 10px !important;
                border-radius: 14px !important;
                flex-direction: column !important;
                align-items: stretch !important;
                width: 100% !important;
                box-sizing: border-box !important;
                box-shadow: none !important;
            }
            .smug-search-wrap {
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            .smug-search-input {
                font-size: 16px !important;
                padding: 10px 36px 10px 36px !important;
                border-radius: 10px !important;
            }
            .smug-cat-pills-row {
                width: 100% !important;
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
                padding: 2px 24px 2px 0 !important;
                flex: none !important;
                gap: 8px !important;
                -webkit-mask-image: linear-gradient(to right, #000 86%, transparent 100%) !important;
                mask-image: linear-gradient(to right, #000 86%, transparent 100%) !important;
            }
            .smug-cat-pill {
                padding: 7px 13px !important;
                font-size: 0.8rem !important;
                border-radius: 999px !important;
                flex-shrink: 0 !important;
            }
            .smug-folders-grid {
                grid-template-columns: 1fr !important;
                gap: 16px !important;
            }
            .smug-folder-card {
                border-radius: 14px !important;
                overflow: hidden !important;
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.18) !important;
                border: 1px solid var(--smug-glass-border) !important;
                background: var(--smug-surface) !important;
            }
            .smug-folder-cover {
                aspect-ratio: 4 / 3.6 !important;
                background: var(--smug-surface-solid) !important;
            }
            .smug-folder-cover img {
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
                object-position: center 20% !important;
            }
            .smug-folder-caption {
                padding: 13px 15px !important;
            }
            .smug-folder-title {
                font-size: 1.05rem !important;
                white-space: normal !important;
                line-height: 1.28 !important;
            }
            .smug-folder-meta {
                font-size: 0.82rem !important;
                margin-top: 5px !important;
                flex-wrap: wrap !important;
                white-space: normal !important;
            }
            /* Album Detail View on Mobile */
            .smug-back-nav-wrap {
                margin-bottom: 12px !important;
            }
            .smug-back-link {
                padding: 6px 14px !important;
                font-size: 0.82rem !important;
                border-radius: 999px !important;
            }
            .smug-album-title {
                font-size: clamp(1.45rem, 5.5vw, 1.85rem) !important;
                line-height: 1.18 !important;
                word-break: break-word !important;
                overflow-wrap: break-word !important;
                margin: 0 0 6px !important;
                font-weight: 800 !important;
            }
            .smug-album-meta {
                font-size: 0.82rem !important;
                gap: 6px !important;
            }
            .smug-album-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 14px !important;
                margin-bottom: 18px !important;
                padding-bottom: 16px !important;
            }
            .smug-header-actions {
                width: 100% !important;
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 10px !important;
            }
            .btn-smug-action {
                justify-content: center !important;
                padding: 11px 10px !important;
                font-size: 0.84rem !important;
                border-radius: 10px !important;
                width: 100% !important;
                white-space: nowrap !important;
                gap: 6px !important;
            }
            .smug-gallery-grid {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 8px !important;
                width: 100% !important;
                margin: 0 !important;
                box-sizing: border-box !important;
            }
            .smug-photo-card {
                aspect-ratio: 4 / 5 !important;
                border-radius: 10px !important;
                box-shadow: none !important;
                overflow: hidden !important;
                width: 100% !important;
                min-height: 0 !important;
                position: relative !important;
            }
            .smug-photo-card.mosaic-lead,
            .smug-photo-card.mosaic-wide {
                grid-column: span 2 !important;
                grid-row: span 1 !important;
                aspect-ratio: 16 / 10 !important;
            }
            .smug-photo-card.mosaic-tall {
                grid-column: span 1 !important;
                grid-row: span 1 !important;
                aspect-ratio: 4 / 5 !important;
            }
            .smug-photo-card .smug-card-media-wrap,
            .smug-photo-card img {
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
                display: block !important;
            }
            .smug-photo-glass-overlay {
                opacity: 1 !important;
                background: linear-gradient(180deg, rgba(0,0,0,0) 62%, rgba(0,0,0,0.62) 100%) !important;
                padding: 6px 8px !important;
                pointer-events: auto !important;
                justify-content: flex-end !important;
                display: flex !important;
                align-items: flex-end !important;
            }
            .smug-glass-actions {
                display: flex !important;
                gap: 6px !important;
            }
            .smug-glass-btn {
                width: 32px !important;
                height: 32px !important;
                font-size: 0.74rem !important;
                border-radius: 50% !important;
            }
            /* Verification Modal Bottom-Sheet on Mobile */
            .smug-modal-overlay {
                padding: 8px !important;
                align-items: flex-end !important;
            }
            .smug-modal-box {
                max-width: 100% !important;
                width: 100% !important;
                max-height: 90dvh !important;
                max-height: 90vh !important;
                border-radius: 18px 18px 8px 8px !important;
                display: flex !important;
                flex-direction: column !important;
            }
            .smug-modal-header {
                padding: 16px 18px 12px !important;
                flex-shrink: 0 !important;
            }
            .smug-modal-title {
                font-size: 1.1rem !important;
            }
            .smug-modal-subtitle {
                font-size: 0.8rem !important;
            }
            .smug-modal-body {
                padding: 14px 18px !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
                flex: 1 !important;
            }
            .smug-input {
                font-size: 16px !important;
                padding: 10px 12px !important;
            }
            .smug-otp-input {
                font-size: 1.45rem !important;
                letter-spacing: 8px !important;
            }
            .smug-modal-footer {
                padding: 12px 18px 16px !important;
                flex-direction: column-reverse !important;
                gap: 8px !important;
                flex-shrink: 0 !important;
            }
            .btn-modal-submit {
                width: 100% !important;
                justify-content: center !important;
                padding: 12px !important;
                font-size: 0.92rem !important;
                border-radius: 8px !important;
            }
            .btn-modal-cancel {
                width: 100% !important;
                text-align: center !important;
                padding: 6px !important;
            }
            .smug-portal-footer-inner {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 18px !important;
            }
            .smug-portal-footer-links {
                flex-wrap: wrap !important;
                gap: 14px 18px !important;
            }
        }

        /* Landscape Mobile Orientation Support */
        @media (max-height: 500px) and (orientation: landscape) {
            .smug-topbar,
            .smug-topbar-inner {
                height: 48px !important;
            }
            .smug-brand-link img {
                height: 30px !important;
            }
            .smug-folders-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 14px !important;
            }
            .smug-gallery-grid {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 8px !important;
            }
        }

        .smug-photo-card {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--smug-border-light);
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.35s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.25s ease;
            will-change: transform;
        }

        [data-theme="noir"] .smug-photo-card {
            background: rgba(15, 23, 42, 0.45);
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.35);
        }

        .smug-photo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.12);
            border-color: rgba(255, 255, 255, 0.3);
        }

        [data-theme="noir"] .smug-photo-card:hover {
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.55);
            border-color: rgba(255, 255, 255, 0.22);
        }

        .smug-photo-card.mosaic-lead {
            grid-column: span 2;
            grid-row: span 2;
        }

        .smug-photo-card.mosaic-wide {
            grid-column: span 2;
        }

        .smug-photo-card.mosaic-tall {
            grid-row: span 2;
        }

        .smug-photo-card .smug-card-media-wrap {
            width: 100%;
            height: 100%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .smug-photo-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .smug-photo-card:hover img {
            transform: scale(1.035);
        }

        /* Authentic Frosted Glassmorphism Overlay */
        .smug-photo-glass-overlay,
        .smug-photo-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.02) 40%, rgba(0, 0, 0, 0.65) 100%);
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
            padding: 14px 16px;
            opacity: 0;
            transition: opacity 0.28s ease, backdrop-filter 0.28s ease;
            pointer-events: none;
        }

        .smug-photo-card:hover .smug-photo-glass-overlay,
        .smug-photo-card:hover .smug-photo-overlay {
            opacity: 1;
            pointer-events: auto;
        }

        /* Glassmorphism Floating Badge */
        .smug-glass-badge,
        .smug-overlay-num {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.22);
            backdrop-filter: blur(14px) saturate(180%);
            -webkit-backdrop-filter: blur(14px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #ffffff;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.4px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
        }

        [data-theme="noir"] .smug-glass-badge,
        [data-theme="noir"] .smug-overlay-num {
            background: rgba(15, 23, 42, 0.65);
            border-color: rgba(255, 255, 255, 0.18);
        }

        .smug-glass-actions,
        .smug-overlay-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .smug-glass-btn,
        .smug-overlay-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(14px) saturate(180%);
            -webkit-backdrop-filter: blur(14px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #ffffff;
            font-size: 0.88rem;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .smug-glass-btn:hover,
        .smug-overlay-btn:hover {
            background: #ffffff;
            color: #0f172a;
            transform: scale(1.1);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35);
        }

        /* CLIENTS DIRECTORY VIEW (?view=clients) */
        .smug-directory-header {
            margin-bottom: 36px;
        }

        .smug-header-row {
            margin-bottom: 16px;
        }

        .smug-directory-title {
            font-size: clamp(1.85rem, 3.5vw, 2.75rem);
            font-weight: 800;
            color: var(--smug-text-primary);
            letter-spacing: -0.03em;
            line-height: 1.15;
            margin-bottom: 0;
        }

        .smug-directory-subtitle {
            display: none;
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

        /* Clean Editorial Caption Block Below Cover Image (Zero text obscuring portraits) */
        .smug-folder-caption {
            background: var(--smug-surface-solid);
            padding: 15px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-top: 1px solid var(--smug-border);
            transition: background-color 0.25s ease, border-color 0.25s ease;
        }

        [data-theme="noir"] .smug-folder-caption {
            background: #0d131f;
            border-top-color: rgba(255, 255, 255, 0.08);
        }

        .smug-dock-text {
            flex: 1;
            min-width: 0;
        }

        .smug-folder-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--smug-text-primary);
            line-height: 1.28;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin: 0;
        }

        .smug-folder-meta {
            font-size: 0.82rem;
            color: var(--smug-text-secondary);
            margin: 4px 0 0 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .smug-folder-arrow {
            color: var(--smug-text-muted);
            font-size: 0.82rem;
            flex-shrink: 0;
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .smug-folder-card:hover .smug-folder-arrow {
            transform: translateX(3px);
            color: var(--smug-red);
        }

        /* Editorial Closing Portal Footer (Seamless Bridge to falhen.com) */
        .smug-portal-footer {
            border-top: 1px solid var(--smug-border);
            background: var(--smug-surface-solid);
            padding: 36px 32px;
            margin-top: 40px;
            transition: background-color 0.25s ease, border-color 0.25s ease;
        }

        [data-theme="noir"] .smug-portal-footer {
            background: #070a12;
            border-top-color: rgba(255, 255, 255, 0.08);
        }

        .smug-portal-footer-inner {
            max-width: 1720px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            flex-wrap: wrap;
        }

        .smug-portal-footer-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .smug-portal-footer-brand img {
            height: 32px;
            width: auto;
            filter: brightness(0);
        }

        [data-theme="noir"] .smug-portal-footer-brand img {
            filter: none;
        }

        .smug-portal-footer-links {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .smug-portal-footer-links a {
            color: var(--smug-text-secondary);
            text-decoration: none;
            font-size: 0.86rem;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .smug-portal-footer-links a:hover {
            color: var(--smug-red);
        }

        .smug-portal-footer-cta {
            background: var(--smug-red);
            color: #ffffff !important;
            padding: 9px 18px;
            border-radius: 999px;
            font-weight: 700 !important;
        }

        .smug-portal-footer-cta:hover {
            background: var(--smug-red-hover);
            color: #ffffff !important;
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

        .smug-slider-viewport {
            flex: 1;
            height: 100%;
            width: 100%;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            touch-action: pan-y;
            user-select: none;
        }

        .smug-slider-track {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            will-change: transform;
            transform: translate3d(0, 0, 0);
        }

        .smug-slide-frame {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            opacity: 0;
            visibility: hidden;
            will-change: transform, opacity;
            transform: translate3d(0, 0, 0);
        }

        .smug-slide-frame.frame-current {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translate3d(0, 0, 0);
            cursor: zoom-in;
            z-index: 2;
        }

        .smug-slide-frame.frame-prev {
            opacity: 1;
            visibility: visible;
            transform: translate3d(-100%, 0, 0);
            z-index: 1;
        }

        .smug-slide-frame.frame-next {
            opacity: 1;
            visibility: visible;
            transform: translate3d(100%, 0, 0);
            z-index: 1;
        }

        .smug-slide-frame.zoomed,
        .smug-image-holder.zoomed {
            cursor: zoom-out;
            overflow: auto;
            display: block;
        }

        .smug-lightbox-img {
            max-height: 82vh;
            max-width: 90vw;
            object-fit: contain;
            border-radius: 4px;
            user-select: none;
            -webkit-user-drag: none;
            pointer-events: auto;
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .smug-slide-frame.zoomed .smug-lightbox-img,
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
            .smug-slider-viewport,
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
            .smug-slide-frame.zoomed .smug-lightbox-img,
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

    <!-- Editorial Brand Top Bar -->
    <header class="smug-topbar">
        <div class="smug-topbar-inner">
            <a href="/clients" class="smug-brand-link" title="Falhen Productions — Client Galleries">
                <img src="/assets/img/icons/logo.png" alt="Falhen Productions">
                <span class="smug-brand-wordmark">FALHEN</span>
                <span class="smug-brand-tag">Client Galleries</span>
            </a>

            <div class="smug-topbar-actions">
                <a href="https://www.falhen.com/portfolio" class="smug-nav-link smug-nav-portfolio" title="Explore Main Website Portfolio">
                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.72rem;"></i>
                    <span>Portfolio</span>
                </a>
                <a href="/clients" class="smug-nav-link smug-nav-galleries <?php echo $isClientsView ? 'active' : ''; ?>">
                    <i class="fa-regular fa-folder"></i>
                    <span>Galleries</span>
                </a>

                <!-- Daylight / Noir Atmosphere Toggle -->
                <button type="button" class="smug-theme-toggle" id="portalThemeToggle" onclick="togglePortalTheme()" title="Toggle Daylight / Noir Atmosphere" aria-label="Toggle Light or Dark Theme">
                    <i class="fa-solid fa-sun" id="themeActiveIcon"></i>
                    <span id="themeActiveLabel">Daylight</span>
                </button>
            </div>
        </div>
    </header>

    <div class="smug-page-container">

        <?php if ($isClientsView): ?>
            <!-- ================= CLIENTS DIRECTORY VIEW ================= -->
            <div class="smug-directory-header">
                <div class="smug-header-row">
                    <h1 class="smug-directory-title">Client Deliveries</h1>
                </div>

                <!-- Search & Dynamic Category Filter Bar -->
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
                                if (strcasecmp(normalizeCategoryName($alb['category'] ?? ''), $catName) === 0) $cCount++;
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
                    $albCount = (int)($album['photosCount'] ?? count($album['gallery_photos'] ?? []));
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
                        </div>
                        <div class="smug-folder-caption">
                            <div class="smug-dock-text">
                                <h2 class="smug-folder-title"><?php echo htmlspecialchars($album['title']); ?></h2>
                                <p class="smug-folder-meta">
                                    <span><?php echo htmlspecialchars($albCategory); ?></span>
                                    <?php if (!empty($albClient) && stripos($album['title'], $albClient) === false): ?>
                                        <span>·</span>
                                        <span><?php echo htmlspecialchars($albClient); ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <i class="fa-solid fa-chevron-right smug-folder-arrow" aria-hidden="true"></i>
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
                        <?php if (!empty($currentAlbum['category'])): ?>
                            <span class="dot">·</span>
                            <span><?php echo htmlspecialchars(normalizeCategoryName($currentAlbum['category'])); ?></span>
                        <?php endif; ?>
                        <?php 
                        $dispClient = trim($currentAlbum['client'] ?? ($clientName ?: ''));
                        // Deduplicate: only display client name if it is NOT already part of the album title
                        if (!empty($dispClient) && stripos($currentAlbum['title'] ?? '', $dispClient) === false): ?>
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
                        <span>Download Gallery</span>
                    </button>
                    <button type="button" class="btn-smug-action btn-smug-ghost" onclick="openDynamicFullView(0)">
                        <i class="fa-solid fa-play"></i>
                        <span>Slideshow</span>
                    </button>
                </div>
            </div>

            <!-- Editorial Mosaic Photography Grid with Frosted Glassmorphism -->
            <main class="smug-gallery-grid" id="smugMasonryGrid">
                <?php foreach ($photoItems as $item): 
                    $pIdx = (int)$item['index'];
                    $mosaicClass = 'mosaic-item';
                    if ($pIdx % 7 === 0 && $pIdx > 0) {
                        $mosaicClass .= ' mosaic-lead';
                    } elseif ($pIdx % 5 === 0 && $pIdx > 0) {
                        $mosaicClass .= ' mosaic-wide';
                    } elseif ($pIdx % 3 === 0) {
                        $mosaicClass .= ' mosaic-tall';
                    }
                ?>
                    <article class="smug-photo-card <?php echo $mosaicClass; ?>" onclick="openDynamicFullView(<?php echo $pIdx; ?>)">
                        <div class="smug-card-media-wrap">
                            <img src="<?php echo htmlspecialchars($item['masonry_url']); ?>"
                                 alt="<?php echo htmlspecialchars(($currentAlbum['title'] ?? 'Gallery') . ' — Photo ' . $item['number']); ?>"
                                 loading="lazy">
                        </div>
                        <div class="smug-photo-glass-overlay">
                            <div class="smug-glass-actions">
                                <button type="button"
                                        class="smug-glass-btn"
                                        title="View Fullscreen"
                                        onclick="event.stopPropagation(); openDynamicFullView(<?php echo $pIdx; ?>);"
                                        aria-label="View Fullscreen">
                                    <i class="fa-solid fa-expand"></i>
                                </button>
                                <button type="button"
                                        class="smug-glass-btn"
                                        title="Download Photo"
                                        onclick="event.stopPropagation(); triggerSinglePhotoDownload(<?php echo $pIdx; ?>);"
                                        aria-label="Download Photo">
                                    <i class="fa-solid fa-arrow-down"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </main>

        <?php endif; ?>

    </div>

    <!-- Editorial Closing Portal Footer -->
    <footer class="smug-portal-footer">
        <div class="smug-portal-footer-inner">
            <div class="smug-portal-footer-brand">
                <img src="/assets/img/icons/logo.png" alt="Falhen Productions">
                <div>
                    <div class="smug-brand-wordmark">FALHEN PRODUCTIONS</div>
                    <div style="font-size: 0.78rem; color: var(--smug-text-muted); margin-top: 3px;">&copy; <?php echo date('Y'); ?> Editorial Photography &amp; Visual Storytelling</div>
                </div>
            </div>
            <nav class="smug-portal-footer-links" aria-label="Main Website Navigation">
                <a href="https://www.falhen.com/">Home</a>
                <a href="https://www.falhen.com/portfolio">Main Portfolio</a>
                <a href="/clients">Client Galleries</a>
                <a href="https://www.falhen.com/services">Services</a>
                <a href="https://www.falhen.com/contact" class="smug-portal-footer-cta">Book a Shoot</a>
            </nav>
        </div>
    </footer>

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
            <div class="smug-slider-viewport" id="sliderViewport">
                <div class="smug-slider-track" id="sliderTrack">
                    <div class="smug-slide-frame frame-prev" id="slideFramePrev">
                        <img class="smug-lightbox-img" id="lightboxPrevImg" src="" alt="Previous photo">
                    </div>
                    <div class="smug-slide-frame frame-current" id="slideFrameCurrent" onclick="handleFrameClick(event)">
                        <img id="lightboxMainImg"
                             class="smug-lightbox-img"
                             src="<?php echo htmlspecialchars($photoItems[0]['full_url'] ?? $coverImage); ?>"
                             alt="Fullscreen photo">
                    </div>
                    <div class="smug-slide-frame frame-next" id="slideFrameNext">
                        <img class="smug-lightbox-img" id="lightboxNextImg" src="" alt="Next photo">
                    </div>
                </div>
            </div>
            <button type="button" class="smug-nav-arrow" onclick="nextPhoto()" aria-label="Next">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <div class="smug-thumbs-rail" id="thumbsRail">
            <?php foreach ($photoItems as $item): ?>
                <div id="thumb-<?php echo (int)$item['index']; ?>"
                     class="smug-thumb <?php echo ($item['index'] === 0) ? 'active' : ''; ?>"
                     onclick="goToPhoto(<?php echo (int)$item['index']; ?>)">
                    <img src="<?php echo htmlspecialchars($item['thumb_url'] ?? $item['masonry_url']); ?>" alt="Photo <?php echo (int)$item['number']; ?>" loading="lazy">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 2-STEP CLIENT & GUEST IDENTITY & OTP VERIFICATION MODAL -->
    <div id="smugVerifyModal" class="smug-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="verifyModalTitle">
        <div class="smug-modal-box">
            <div class="smug-modal-header">
                <div>
                    <h2 class="smug-modal-title" id="verifyModalTitle">Security &amp; Download Access Gate</h2>
                    <p class="smug-modal-subtitle" id="verifyModalSubtitle">Verify your details to unlock high-resolution photos. A delivery link is also sent directly to your inbox.</p>
                </div>
                <button type="button" class="smug-tool-close" onclick="closeVerifyModal()" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="smug-modal-body">
                <div id="verifyErrorAlert" class="smug-modal-alert smug-alert-error"></div>
                <div id="verifyInfoAlert" class="smug-modal-alert smug-alert-info"></div>

                <!-- STEP 1: Name, Email, Phone & Guest Security Fields -->
                <div id="verifyStepIdentity">
                    <div class="smug-field-group">
                        <label class="smug-label" for="client_input_name">Full Name *</label>
                        <input type="text" id="client_input_name" class="smug-input" placeholder="Your full name" value="<?php echo htmlspecialchars($clientName); ?>" required>
                    </div>

                    <div class="smug-field-group">
                        <label class="smug-label" for="client_input_email">Email Address *</label>
                        <input type="email" id="client_input_email" class="smug-input" placeholder="name@domain.com" value="" required autocomplete="email">
                        <span style="font-size: 0.74rem; color: var(--smug-text-muted); margin-top: 4px; display: block;">Authorized clients and verified guests receive a 6-digit passcode and direct download delivery here.</span>
                    </div>

                    <div class="smug-field-group">
                        <label class="smug-label" for="client_input_phone">Phone Number *</label>
                        <input type="tel" id="client_input_phone" class="smug-input" placeholder="+1 (555) 000-0000" value="<?php echo htmlspecialchars($clientPhone); ?>" required>
                    </div>

                    <!-- Enhanced Security Layer for Public Guest Access -->
                    <div id="verifyGuestDetails" style="display: none; margin-top: 16px; padding-top: 14px; border-top: 1px dashed rgba(255,255,255,0.14);">
                        <div style="font-size: 0.76rem; font-weight: 700; color: #f87171; letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-shield-halved"></i> Guest Security &amp; Usage Protocol
                        </div>
                        <p style="font-size: 0.8rem; color: #94a3b8; line-height: 1.45; margin: 0 0 12px 0;">
                            This gallery is protected. As an event attendee, relative, or partner, please confirm your relationship and download purpose.
                        </p>

                        <div class="smug-field-group">
                            <label class="smug-label" for="client_input_org">Organization / Relationship to Event *</label>
                            <input type="text" id="client_input_org" class="smug-input" placeholder="e.g. Wedding Guest, Family Member, Sponsor, Press / Media, Vendor">
                        </div>

                        <div class="smug-field-group">
                            <label class="smug-label" for="client_input_reason">Reason for Download *</label>
                            <textarea id="client_input_reason" class="smug-input" rows="2" placeholder="e.g. Personal keepsake and printing, social media recap, editorial coverage..." style="resize: vertical; min-height: 54px;"></textarea>
                        </div>
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

        // ================= DYNAMIC LIGHTBOX & HARDWARE-ACCELERATED SLIDER =================
        let isTransitioning = false;
        let ignoreNextClick = false;
        const preloadedUrls = new Set();

        function getBestPhotoUrl(item) {
            if (!item) return '';
            const isMobile = window.innerWidth <= 768;
            return (isMobile && item.mobile_url) ? item.mobile_url : (item.full_url || item.masonry_url);
        }

        function preloadUrl(url) {
            if (!url || preloadedUrls.has(url)) return;
            preloadedUrls.add(url);
            const img = new Image();
            img.src = url;
        }

        function preloadSurroundingPhotos(idx) {
            if (!galleryItems || galleryItems.length <= 1) return;
            const count = galleryItems.length;
            const next1 = (idx + 1) % count;
            const next2 = (idx + 2) % count;
            const prev1 = (idx - 1 + count) % count;
            const prev2 = (idx - 2 + count) % count;

            preloadUrl(getBestPhotoUrl(galleryItems[next1]));
            preloadUrl(getBestPhotoUrl(galleryItems[prev1]));
            preloadUrl(getBestPhotoUrl(galleryItems[next2]));
            preloadUrl(getBestPhotoUrl(galleryItems[prev2]));
        }

        let currentActiveThumb = null;
        function updateLightboxUI() {
            const counter = document.getElementById('lightboxCounter');
            if (counter) {
                counter.textContent = `Photo ${currentPhotoIndex + 1} of ${galleryItems.length}`;
            }

            // O(1) active thumbnail switch without DOM layout thrashing
            if (!currentActiveThumb) {
                currentActiveThumb = document.getElementById('thumb-0');
            }
            if (currentActiveThumb) {
                currentActiveThumb.classList.remove('active');
            }
            const targetThumb = document.getElementById('thumb-' + currentPhotoIndex);
            if (targetThumb) {
                targetThumb.classList.add('active');
                targetThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                currentActiveThumb = targetThumb;
            }
        }

        function updateFramesContent() {
            if (!galleryItems || galleryItems.length === 0) return;
            const count = galleryItems.length;
            const curItem = galleryItems[currentPhotoIndex];
            const prevIdx = (currentPhotoIndex - 1 + count) % count;
            const nextIdx = (currentPhotoIndex + 1) % count;

            const mainImg = document.getElementById('lightboxMainImg');
            const prevImg = document.getElementById('lightboxPrevImg');
            const nextImg = document.getElementById('lightboxNextImg');

            if (mainImg) {
                const targetSrc = getBestPhotoUrl(curItem);
                if (mainImg.src !== targetSrc) {
                    mainImg.src = targetSrc;
                }
            }
            if (prevImg) {
                prevImg.src = getBestPhotoUrl(galleryItems[prevIdx]);
            }
            if (nextImg) {
                nextImg.src = getBestPhotoUrl(galleryItems[nextIdx]);
            }

            updateLightboxUI();
            preloadSurroundingPhotos(currentPhotoIndex);
        }

        function updateLightboxView() {
            updateFramesContent();
        }

        function openDynamicFullView(idx) {
            if (!galleryItems || galleryItems.length === 0) return;
            if (idx < 0 || idx >= galleryItems.length) idx = 0;
            currentPhotoIndex = idx;
            resetLightboxZoom();
            const track = document.getElementById('sliderTrack');
            if (track) {
                track.style.transition = 'none';
                track.style.transform = 'translate3d(0, 0, 0)';
            }
            updateFramesContent();
            document.getElementById('smugLightbox').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeDynamicFullView() {
            stopSlideshow();
            resetLightboxZoom();
            const track = document.getElementById('sliderTrack');
            if (track) {
                track.style.transition = 'none';
                track.style.transform = 'translate3d(0, 0, 0)';
            }
            document.getElementById('smugLightbox').style.display = 'none';
            document.body.style.overflow = '';
        }

        function slideToPhoto(targetIdx, direction) {
            if (!galleryItems || galleryItems.length <= 1) return;
            const track = document.getElementById('sliderTrack');
            if (!track) return;

            resetLightboxZoom();

            // If an animation is already running, snap and commit instantly
            if (isTransitioning) {
                track.style.transition = 'none';
                track.style.transform = 'translate3d(0, 0, 0)';
                currentPhotoIndex = targetIdx;
                updateFramesContent();
                isTransitioning = false;
                return;
            }

            if (direction === undefined || direction === 0) {
                currentPhotoIndex = targetIdx;
                updateFramesContent();
                return;
            }

            isTransitioning = true;
            const offset = (direction > 0) ? -100 : 100;

            track.style.transition = 'transform 0.28s cubic-bezier(0.22, 1, 0.36, 1)';
            track.style.transform = `translate3d(${offset}%, 0, 0)`;

            let slideFinished = false;
            const finishSlide = () => {
                if (slideFinished) return;
                slideFinished = true;
                track.removeEventListener('transitionend', finishSlide);
                track.style.transition = 'none';
                track.style.transform = 'translate3d(0, 0, 0)';
                currentPhotoIndex = targetIdx;
                updateFramesContent();
                void track.offsetWidth; // force reflow
                isTransitioning = false;
            };

            track.addEventListener('transitionend', finishSlide, { once: true });
            setTimeout(finishSlide, 320);
        }

        function nextPhoto() {
            if (!galleryItems || galleryItems.length === 0) return;
            const targetIdx = (currentPhotoIndex + 1) % galleryItems.length;
            slideToPhoto(targetIdx, 1);
        }

        function prevPhoto() {
            if (!galleryItems || galleryItems.length === 0) return;
            const targetIdx = (currentPhotoIndex - 1 + galleryItems.length) % galleryItems.length;
            slideToPhoto(targetIdx, -1);
        }

        function goToPhoto(idx) {
            if (idx === currentPhotoIndex) return;
            if (idx < 0 || idx >= galleryItems.length) return;
            const direction = idx > currentPhotoIndex ? 1 : -1;
            if (Math.abs(idx - currentPhotoIndex) === 1 ||
                (currentPhotoIndex === 0 && idx === galleryItems.length - 1) ||
                (currentPhotoIndex === galleryItems.length - 1 && idx === 0)) {
                slideToPhoto(idx, direction);
            } else {
                currentPhotoIndex = idx;
                resetLightboxZoom();
                updateFramesContent();
            }
        }

        function handleFrameClick(e) {
            if (ignoreNextClick) {
                ignoreNextClick = false;
                return;
            }
            toggleLightboxZoom();
        }

        function toggleLightboxZoom() {
            const frame = document.getElementById('slideFrameCurrent');
            const label = document.getElementById('zoomLabel');
            if (!frame) return;
            frame.classList.toggle('zoomed');
            if (label) label.textContent = frame.classList.contains('zoomed') ? 'Reset' : 'Zoom';
        }

        function resetLightboxZoom() {
            const frame = document.getElementById('slideFrameCurrent');
            const label = document.getElementById('zoomLabel');
            if (frame) frame.classList.remove('zoomed');
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
            if (icon) icon.className = 'fa-solid fa-pause';
            if (label) label.textContent = 'Pause';
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

        // Enhanced Real-Time Touch Gestures (1:1 Drag, Spring Physics, Swipe Down Dismiss)
        let touchStartX = 0;
        let touchStartY = 0;
        let touchStartTime = 0;
        let isTouchDragging = false;
        let lastTapTime = 0;

        const viewport = document.getElementById('sliderViewport');
        const trackEl = document.getElementById('sliderTrack');

        if (viewport && trackEl) {
            viewport.addEventListener('touchstart', e => {
                const frame = document.getElementById('slideFrameCurrent');
                if (frame && frame.classList.contains('zoomed')) return;

                if (e.touches && e.touches.length === 1) {
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                    touchStartTime = Date.now();
                    isTouchDragging = false;
                }
            }, { passive: true });

            viewport.addEventListener('touchmove', e => {
                const frame = document.getElementById('slideFrameCurrent');
                if (frame && frame.classList.contains('zoomed')) return;
                if (!e.touches || e.touches.length !== 1) return;

                const currentX = e.touches[0].clientX;
                const currentY = e.touches[0].clientY;
                const diffX = currentX - touchStartX;
                const diffY = currentY - touchStartY;

                if (!isTouchDragging && Math.abs(diffX) > 8 && Math.abs(diffX) > Math.abs(diffY)) {
                    isTouchDragging = true;
                    ignoreNextClick = true;
                }

                if (isTouchDragging) {
                    if (e.cancelable) e.preventDefault();
                    trackEl.style.transition = 'none';
                    trackEl.style.transform = `translate3d(${diffX}px, 0, 0)`;
                }
            }, { passive: false });

            viewport.addEventListener('touchend', e => {
                const frame = document.getElementById('slideFrameCurrent');
                if (frame && frame.classList.contains('zoomed')) return;
                if (!e.changedTouches || e.changedTouches.length !== 1) return;

                const diffX = e.changedTouches[0].clientX - touchStartX;
                const diffY = e.changedTouches[0].clientY - touchStartY;
                const elapsed = Date.now() - touchStartTime;
                const velocity = Math.abs(diffX) / (elapsed || 1);

                if (isTouchDragging) {
                    isTouchDragging = false;
                    setTimeout(() => { ignoreNextClick = false; }, 100);

                    const threshold = window.innerWidth * 0.18;
                    if (diffX < -threshold || (diffX < -25 && velocity > 0.45)) {
                        const targetIdx = (currentPhotoIndex + 1) % galleryItems.length;
                        trackEl.style.transition = 'transform 0.22s cubic-bezier(0.22, 1, 0.36, 1)';
                        trackEl.style.transform = 'translate3d(-100%, 0, 0)';
                        setTimeout(() => {
                            trackEl.style.transition = 'none';
                            trackEl.style.transform = 'translate3d(0, 0, 0)';
                            currentPhotoIndex = targetIdx;
                            updateFramesContent();
                        }, 230);
                    } else if (diffX > threshold || (diffX > 25 && velocity > 0.45)) {
                        const targetIdx = (currentPhotoIndex - 1 + galleryItems.length) % galleryItems.length;
                        trackEl.style.transition = 'transform 0.22s cubic-bezier(0.22, 1, 0.36, 1)';
                        trackEl.style.transform = 'translate3d(100%, 0, 0)';
                        setTimeout(() => {
                            trackEl.style.transition = 'none';
                            trackEl.style.transform = 'translate3d(0, 0, 0)';
                            currentPhotoIndex = targetIdx;
                            updateFramesContent();
                        }, 230);
                    } else {
                        trackEl.style.transition = 'transform 0.22s cubic-bezier(0.22, 1, 0.36, 1)';
                        trackEl.style.transform = 'translate3d(0, 0, 0)';
                    }
                } else {
                    if (diffY > 80 && Math.abs(diffY) > Math.abs(diffX) * 1.5 && elapsed < 350) {
                        closeDynamicFullView();
                    }
                }
            }, { passive: true });
        }

        // Double-tap on image to toggle zoom
        const currentFrameEl = document.getElementById('slideFrameCurrent');
        if (currentFrameEl) {
            currentFrameEl.addEventListener('touchend', function(e) {
                const now = Date.now();
                if (now - lastTapTime < 300) {
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
            const guestBox = document.getElementById('verifyGuestDetails');
            if (guestBox) guestBox.style.display = 'none';
            const btn = document.getElementById('btnVerifySubmit');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Verification Code';
        }

        async function submitVerification() {
            const name = document.getElementById('client_input_name').value.trim();
            const email = document.getElementById('client_input_email').value.trim();
            const phone = document.getElementById('client_input_phone').value.trim();
            const guestBox = document.getElementById('verifyGuestDetails');
            const orgInput = document.getElementById('client_input_org');
            const reasonInput = document.getElementById('client_input_reason');
            const org = orgInput ? orgInput.value.trim() : '';
            const reason = reasonInput ? reasonInput.value.trim() : '';
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

                // If guest details are displayed, validate required fields
                const isGuestSectionVisible = guestBox && guestBox.style.display !== 'none';
                if (isGuestSectionVisible) {
                    if (!org || org.length < 2) {
                        errBox.textContent = 'Please enter your organization or relationship to this event.';
                        errBox.style.display = 'block';
                        if (orgInput) orgInput.focus();
                        return;
                    }
                    if (!reason || reason.length < 3) {
                        errBox.textContent = 'Please enter a brief reason for requesting this download.';
                        errBox.style.display = 'block';
                        if (reasonInput) reasonInput.focus();
                        return;
                    }
                }

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking Access Permissions...';

                try {
                    const fd = new FormData();
                    fd.append('action', 'request_code');
                    fd.append('name', name);
                    fd.append('email', email);
                    fd.append('phone', phone);
                    fd.append('organization', org);
                    fd.append('download_reason', reason);
                    fd.append('album_id', currentAlbumId);
                    fd.append('album_title', currentAlbumTitle);

                    const resp = await fetch('/api/verify-download.php', { method: 'POST', body: fd });
                    const res = await resp.json();

                    if (res.requires_guest_details) {
                        // Unlisted public requester: show enhanced guest verification form
                        if (guestBox) guestBox.style.display = 'block';
                        infoBox.style.display = 'block';
                        infoBox.innerHTML = '<i class="fa-solid fa-shield-halved"></i> <strong>Guest Access Protocol:</strong> ' + (res.message || 'Please specify your organization and reason to receive your verification code.');
                        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Verify Identity &amp; Send Code';
                        if (orgInput) orgInput.focus();
                    } else if (res.success) {
                        verificationStep = 'otp';
                        document.getElementById('verifyStepIdentity').style.display = 'none';
                        document.getElementById('verifyStepOtp').style.display = 'block';
                        infoBox.style.display = 'block';
                        const tierLabel = res.access_tier === 'public_verified' ? 'Verified Guest Access' : 'Client Access Passcode';
                        infoBox.innerHTML = `<i class="fa-solid fa-envelope-circle-check"></i> <strong>${tierLabel}:</strong> ${res.message}`;
                        const otpNotice = document.getElementById('otpNoticeText');
                        if (otpNotice) otpNotice.textContent = 'Verification code sent to your contact email';
                        submitBtn.innerHTML = '<i class="fa-solid fa-unlock-keyhole"></i> Verify &amp; Unlock Download';
                        document.getElementById('client_input_otp').focus();
                    } else {
                        errBox.textContent = res.message || 'Verification failed. Please check your credentials.';
                        errBox.style.display = 'block';
                        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Verification Code';
                    }
                } catch (err) {
                    errBox.textContent = 'Network error while requesting verification code. Please try again.';
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
                    fd.append('organization', org);
                    fd.append('download_reason', reason);
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
                            if (res.access_tier === 'public_verified') {
                                badge.innerHTML = '<i class="fa-solid fa-user-check"></i> Verified Guest';
                            } else {
                                badge.innerHTML = '<i class="fa-solid fa-circle-check"></i> Verified Client';
                            }
                        }
                        closeVerifyModal();

                        const deliveryNote = res.download_emailed ? ' and delivery dispatched to your inbox' : '';
                        showToast('✓ Identity verified! High-res download started' + deliveryNote + '.');

                        // Immediately trigger the requested file download without synthetic click blocking
                        if (typeof pendingDownloadCallback === 'function') {
                            const cb = pendingDownloadCallback;
                            pendingDownloadCallback = null;
                            cb(downloadToken);
                        } else if (res.download_zip_url) {
                            initiateBrowserDownload(res.download_zip_url);
                        }
                    } else {
                        errBox.textContent = res.message || 'Invalid verification code. Please check your email.';
                        errBox.style.display = 'block';
                    }
                } catch (err) {
                    errBox.textContent = 'Network error while verifying code.';
                    errBox.style.display = 'block';
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-unlock-keyhole"></i> Verify &amp; Unlock Download';
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
                showToast('Downloading high-resolution photo...');
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
