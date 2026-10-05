<?php
// portfolio-photo.php - Client Portfolio Gallery Page with Carousel & Photo Download
require_once __DIR__ . '/includes/functions.php';

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

$albumId = isset($_GET['id']) ? (int)$_GET['id'] : key($albumsRepo);
$currentAlbum = reset($albumsRepo);

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

$coverImage = getCloudinaryUrl($currentAlbum['image'] ?? $currentAlbum['cover'] ?? '/assets/img/portfolio/portfolio_halima.png');
$pageTitle = htmlspecialchars($currentAlbum['title']) . " — Gallery | Falhen Media";

// Curate client-specific gallery photo sets
$clientPhotos = [];

// 1. Check if custom photos array is saved in settings
if (!empty($currentAlbum['gallery_photos']) && is_array($currentAlbum['gallery_photos'])) {
    $clientPhotos = $currentAlbum['gallery_photos'];
} elseif (!empty($currentAlbum['photos']) && is_array($currentAlbum['photos'])) {
    $clientPhotos = $currentAlbum['photos'];
} else {
    // Curated high-res sets based on client / shoot
    $clientId = (int)($currentAlbum['id'] ?? 0);
    $clientName = strtolower(trim($currentAlbum['client'] ?? ''));
    $clientTitle = strtolower(trim($currentAlbum['title'] ?? ''));

    if ($clientId === 1 || strpos($clientName, 'king david') !== false || strpos($clientTitle, 'king david') !== false) {
        // King David - Academic Graduation & Studio Celebration Shoot
        $clientPhotos = [
            $coverImage,
            'https://images.unsplash.com/photo-1523240795612-9a054b0db644?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1627556704290-2b1f5853ff78?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1571260899304-425eee4c7efc?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?q=80&w=1200&auto=format&fit=crop'
        ];
    } elseif ($clientId === 3 || strpos($clientName, 'demola') !== false || strpos($clientTitle, 'demola') !== false) {
        // Demola The Violinist - Live Concert Tour
        $clientPhotos = [
            $coverImage,
            'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1465847899084-d164df4dedc6?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?q=80&w=1200&auto=format&fit=crop',
            '/assets/img/portfolio/portfolio_wedding.png',
            '/assets/img/portfolio/portfolio_award.png',
            'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1429962714451-bb934ecdc4ec?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?q=80&w=1200&auto=format&fit=crop'
        ];
    } elseif ($clientId === 2 || strpos($clientName, 'halima') !== false || strpos($clientTitle, 'halima') !== false) {
        // Halima Ogunde - 40th Birthday Celebration
        $clientPhotos = [
            $coverImage,
            '/assets/img/portfolio/portfolio_halima.png',
            'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1527529482837-4698179dc6ce?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1519671482749-fd09be7ccebf?q=80&w=1200&auto=format&fit=crop'
        ];
    } else {
        // Default curated showcase collection
        $clientPhotos = [
            $coverImage,
            'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=1200&auto=format&fit=crop',
            '/assets/img/portfolio/portfolio_halima.png',
            '/assets/img/portfolio/portfolio_wedding.png',
            '/assets/img/portfolio/portfolio_commercial.png',
            '/assets/img/portfolio/portfolio_award.png',
            '/assets/img/services/service_photo.png'
        ];
    }
}

// Remove duplicates while preserving order
$clientPhotos = array_values(array_unique($clientPhotos));
$totalPhotoCount = count($clientPhotos);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-LJ91C0YXCH"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-LJ91C0YXCH');
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="shortcut icon" type="image/png" href="/assets/img/icons/favicon.png">
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Main Style -->
    <link rel="stylesheet" href="/assets/css/style.css">

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

        /* Download All Green Action Button */
        .btn-download-all-green {
            background: var(--brand-green);
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
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.35);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-download-all-green:hover {
            background: var(--brand-green-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.5);
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
            aspect-ratio: 4 / 3;
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
            border: 1px solid rgba(255, 255, 255, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            color: #ffffff;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.4);
        }

        .overlay-circle-btn.zoom-btn {
            background: rgba(37, 99, 235, 0.88);
            backdrop-filter: blur(8px);
        }

        .overlay-circle-btn.zoom-btn:hover {
            background: #1d4ed8;
            transform: scale(1.14);
        }

        .overlay-circle-btn.download-btn {
            background: var(--brand-green);
        }

        .overlay-circle-btn.download-btn:hover {
            background: var(--brand-green-hover);
            transform: scale(1.14);
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
            background: var(--brand-green);
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
            transition: all 0.2s;
        }

        .btn-carousel-download:hover {
            background: var(--brand-green-hover);
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
            color: var(--brand-green);
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

        @media (max-width: 640px) {
            .gallery-nav-bar {
                padding: 12px 18px;
            }
            .btn-nav-share span {
                display: none;
            }
            .gallery-hero-section {
                padding: 30px 18px 24px;
            }
            .gallery-main-title {
                font-size: 2.1rem;
            }
            .gallery-photos-container {
                padding: 10px 18px 80px;
            }
            .gallery-grid {
                grid-template-columns: 1fr;
                gap: 16px;
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
                padding-bottom: 14px;
            }
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
                <img src="/assets/img/icons/logo.png" alt="Falhen Media">
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
            <div class="hero-featured-card" onclick="openCarousel(0)" title="Click to view full image in carousel">
                <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="<?php echo htmlspecialchars($currentAlbum['title']); ?>">
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
                        : "A comprehensive photographic showcase capturing authentic emotions, editorial aesthetic, and cinematic lighting curated by Falhen Media."; 
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

                <!-- Green Download All Action Button -->
                <button type="button" class="btn-download-all-green" id="btnDownloadAll" onclick="downloadAllPhotos()">
                    <i class="fa-solid fa-cloud-arrow-down"></i> Download All (<?php echo $totalPhotoCount; ?>)
                </button>
            </div>

        </div>
    </section>

    <!-- Gallery Photos Grid Container -->
    <main class="gallery-photos-container">
        <div class="gallery-section-divider"></div>

        <div class="gallery-grid" id="galleryGrid">
            <?php foreach ($clientPhotos as $idx => $photo): ?>
                <div class="gallery-photo-card" onclick="openCarousel(<?php echo $idx; ?>)">
                    <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($currentAlbum['title']) . ' — Photo ' . ($idx + 1); ?>" loading="lazy">
                    
                    <!-- Floating Circular Action Buttons Overlay -->
                    <div class="card-hover-overlay">
                        <button type="button" class="overlay-circle-btn zoom-btn" title="View in Carousel" onclick="event.stopPropagation(); openCarousel(<?php echo $idx; ?>)">
                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                        </button>
                        <button type="button" class="overlay-circle-btn download-btn" title="Download Photo" onclick="event.stopPropagation(); downloadSinglePhoto('<?php echo htmlspecialchars($photo); ?>', <?php echo $idx + 1; ?>)">
                            <i class="fa-solid fa-arrow-down-to-bracket"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

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
                <img id="carouselMainImg" class="carousel-main-img" src="" alt="Full view" onclick="event.stopPropagation()">
            </div>

            <button type="button" class="carousel-arrow-btn next" onclick="nextCarouselPhoto()" title="Next (Right Arrow)">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <!-- Bottom Thumbnails Strip -->
        <div class="carousel-bottom-thumbs" id="carouselThumbnails">
            <?php foreach ($clientPhotos as $idx => $photo): ?>
                <div class="carousel-thumb-item <?php echo $idx === 0 ? 'active' : ''; ?>" id="thumb-<?php echo $idx; ?>" onclick="goToCarouselPhoto(<?php echo $idx; ?>)">
                    <img src="<?php echo htmlspecialchars($photo); ?>" alt="Thumb <?php echo $idx + 1; ?>">
                </div>
            <?php endforeach; ?>
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
        const gdriveUrl = <?php echo json_encode($currentAlbum['gdrive_url'] ?? ''); ?>;
        let currentPhotoIndex = 0;

        // Toast Feedback Helper
        function showToast(text) {
            const toast = document.getElementById('downloadToast');
            const toastText = document.getElementById('downloadToastText');
            toastText.textContent = text;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Open Carousel at specific index
        function openCarousel(index) {
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

        // Download Single Photo
        async function downloadSinglePhoto(url, photoNum) {
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
                // Fallback for CORS restricted origins: trigger browser direct download
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                a.target = '_blank';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        }

        // Download from Carousel
        function downloadCurrentCarouselPhoto() {
            downloadSinglePhoto(galleryPhotos[currentPhotoIndex], currentPhotoIndex + 1);
        }

        // Download All Photos Functionality
        async function downloadAllPhotos() {
            const btn = document.getElementById('btnDownloadAll');
            const originalHtml = btn.innerHTML;
            
            if (gdriveUrl && gdriveUrl.startsWith('http')) {
                showToast('Opening Google Drive archive...');
                window.open(gdriveUrl, '_blank');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Downloading (${galleryPhotos.length})...`;
            showToast(`Starting download of ${galleryPhotos.length} high-resolution photos...`);

            for (let i = 0; i < galleryPhotos.length; i++) {
                await downloadSinglePhoto(galleryPhotos[i], i + 1);
                // Stagger downloads by 350ms to prevent browser blocking
                await new Promise(r => setTimeout(r, 350));
            }

            btn.disabled = false;
            btn.innerHTML = originalHtml;
            showToast('All photos downloaded successfully!');
        }

        // Native Share Web API or Clipboard copy
        async function shareGalleryPage() {
            const shareData = {
                title: `${shootTitle} — Falhen Media`,
                text: `Explore the portfolio gallery for ${shootTitle} by Falhen Media.`,
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
