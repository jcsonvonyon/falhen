<?php
// portfolio-single.php - Album Gallery View matching falhen.com/gallery/sharon-portrait screenshots
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Album View — Falhen Media";

$masterPortfolioItems = getPortfolioRepo();
$albumsRepo = [];

foreach ($masterPortfolioItems as $item) {
    $item['cover'] = $item['cover'] ?? ($item['image'] ?? '/assets/img/portfolio/portfolio_halima.png');
    $item['subtitle'] = $item['subtitle'] ?? ($item['desc'] ?? '');
    $item['photosCount'] = $item['photosCount'] ?? 30;
    $albumsRepo[$item['id']] = $item;
}

$albumId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$currentAlbum = reset($albumsRepo);
if (isset($albumsRepo[$albumId])) {
    $currentAlbum = $albumsRepo[$albumId];
}


$pageTitle = $currentAlbum['title'] . " — Album Gallery | Falhen Media";

// 30 Pictures Array for the Album
$galleryPhotos = [
    1 => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=800&auto=format&fit=crop',
    2 => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=800&auto=format&fit=crop',
    3 => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=800&auto=format&fit=crop',
    4 => '/assets/img/portfolio/portfolio_halima.png',
    5 => '/assets/img/portfolio/portfolio_wedding.png',
    6 => '/assets/img/portfolio/portfolio_commercial.png',
    7 => '/assets/img/portfolio/portfolio_award.png',
    8 => '/assets/img/services/service_photo.png',
    9 => '/assets/img/services/service_creative.png',
    10 => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=800&auto=format&fit=crop',
    11 => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?q=80&w=800&auto=format&fit=crop',
    12 => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?q=80&w=800&auto=format&fit=crop',
    13 => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=800&auto=format&fit=crop',
    14 => 'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?q=80&w=800&auto=format&fit=crop',
    15 => '/assets/img/team/team_henry.png',
    16 => '/assets/img/services/service_video.png',
    17 => '/assets/img/services/service_wedding.png',
    18 => '/assets/img/services/service_stream.png',
    19 => '/assets/img/services/service_post.png',
    20 => '/assets/img/services/service_anim.png',
    21 => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?q=80&w=800&auto=format&fit=crop',
    22 => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?q=80&w=800&auto=format&fit=crop',
    23 => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=800&auto=format&fit=crop',
    24 => 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?q=80&w=800&auto=format&fit=crop',
    25 => 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?q=80&w=800&auto=format&fit=crop',
    26 => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=800&auto=format&fit=crop',
    27 => '/assets/img/portfolio/portfolio_halima.png',
    28 => '/assets/img/portfolio/portfolio_wedding.png',
    29 => '/assets/img/portfolio/portfolio_commercial.png',
    30 => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=800&auto=format&fit=crop'
];

// Related Albums (excluding current)
$relatedAlbums = array_filter($albumsRepo, function($a) use ($albumId, $currentAlbum) {
    return $a['id'] !== $albumId && $a['category'] === $currentAlbum['category'];
});
if (empty($relatedAlbums)) {
    $relatedAlbums = array_filter($albumsRepo, function($a) use ($albumId) {
        return $a['id'] !== $albumId;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="shortcut icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="apple-touch-icon" href="/assets/img/icons/favicon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        body {
            background-color: #030305;
            color: #d4d4d8;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Top Sub-Nav Bar */
        .single-nav-bar {
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #030305;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .single-nav-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .single-back-link {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .single-back-link:hover {
            color: #ffffff;
        }

        .single-logo-center img {
            height: 28px;
            width: auto;
            object-fit: contain;
        }

        .nav-actions-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-share-glass {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 0.88rem;
            font-weight: 600;
            padding: 9px 18px;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
        }

        .btn-share-glass:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        .btn-book-red {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.88rem;
            font-weight: 700;
            padding: 10px 22px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-book-red:hover {
            background: #ef4444;
            transform: translateY(-1px);
        }

        /* Hero Stage */
        .album-hero-section {
            padding: 60px 24px 50px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: radial-gradient(circle at 70% 30%, rgba(220, 38, 38, 0.12) 0%, rgba(3, 3, 5, 1) 70%);
        }

        .album-hero-container {
            max-width: 1240px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 50px;
            align-items: center;
        }

        .album-hero-cover-box {
            position: relative;
            width: 100%;
            height: 380px;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8);
        }

        .album-hero-cover-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Metadata Column */
        .breadcrumbs-row {
            font-size: 0.85rem;
            color: #71717a;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .breadcrumbs-row a {
            color: #a1a1aa;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .breadcrumbs-row a:hover {
            color: #ffffff;
        }

        .meta-badges-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .badge-pill-red {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 50px;
        }

        .badge-pill-gold {
            background: rgba(234, 179, 8, 0.18);
            border: 1px solid rgba(234, 179, 8, 0.35);
            color: #facc15;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .album-main-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 12px 0;
            line-height: 1.1;
            letter-spacing: -1.5px;
        }

        .album-main-subtitle {
            font-size: 1.1rem;
            color: #a1a1aa;
            margin: 0 0 28px 0;
            line-height: 1.6;
        }

        .meta-info-pills {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .info-pill-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #d4d4d8;
            font-size: 0.86rem;
            font-weight: 600;
            padding: 6px 16px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-download-all {
            background: #10b981;
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 800;
            padding: 14px 28px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-download-all:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(16, 185, 129, 0.6);
        }

        /* 30 Pictures Grid (3 Columns) */
        .photos-main-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 60px 24px;
        }

        .gallery-30-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 70px;
        }

        .photo-card-item {
            position: relative;
            height: 520px;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: #0e0e12;
            cursor: pointer;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .photo-card-item:hover {
            border-color: rgba(220, 38, 38, 0.6);
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(220, 38, 38, 0.25);
        }

        .photo-card-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scale(1.10);
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .photo-card-item:hover img {
            transform: scale(1.0);
        }

        .photo-overlay-actions {
            position: absolute;
            inset: 0;
            background: rgba(3, 3, 5, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            opacity: 0;
            transition: opacity 0.35s ease;
            z-index: 3;
        }

        .photo-card-item:hover .photo-overlay-actions {
            opacity: 1;
        }

        .btn-photo-action {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            cursor: pointer;
            border: none;
            transition: transform 0.3s ease;
        }

        .btn-photo-zoom {
            background: rgba(14, 14, 18, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            backdrop-filter: blur(8px);
        }

        .btn-photo-download {
            background: #10b981;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .photo-card-item:hover .btn-photo-action {
            transform: scale(1.05);
        }

        /* Love What You See Card Banner */
        .love-card-banner {
            background: linear-gradient(135deg, rgba(35, 10, 15, 0.95) 0%, rgba(14, 14, 18, 0.95) 100%);
            border: 1px solid rgba(220, 38, 38, 0.3);
            border-radius: 18px;
            padding: 28px 36px;
            margin-bottom: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .love-banner-left {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .love-banner-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #ff4d4d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .love-banner-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 4px 0;
        }

        .love-banner-desc {
            color: #a1a1aa;
            font-size: 0.9rem;
            margin: 0;
        }

        .love-banner-btns {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Related Albums Section */
        .related-albums-section {
            margin-bottom: 60px;
        }

        .related-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 24px 0;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        /* Footer Navigation Bar */
        .single-footer-bar {
            padding: 24px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Fullscreen Lightbox Modal */
        .lightbox-modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(14px);
            z-index: 99999;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .lightbox-modal-backdrop.active {
            display: flex;
        }

        .lightbox-box-wrap {
            max-width: 960px;
            width: 100%;
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.95);
        }

        .lightbox-close-btn {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            z-index: 10;
            transition: all 0.25s ease;
        }

        .lightbox-close-btn:hover {
            background: #dc2626;
            border-color: #dc2626;
        }

        .lightbox-stage-img {
            width: 100%;
            max-height: 600px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .lightbox-stage-img img {
            width: 100%;
            max-height: 600px;
            object-fit: contain;
        }

        .lightbox-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(14, 14, 18, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1rem;
            z-index: 5;
            transition: all 0.25s ease;
        }

        .lightbox-nav-btn:hover {
            background: #dc2626;
            border-color: #dc2626;
        }

        .lightbox-prev-btn { left: 16px; }
        .lightbox-next-btn { right: 16px; }

        .lightbox-info-row {
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0e0e12;
        }

        .lightbox-counter-text {
            color: #71717a;
            font-size: 0.88rem;
            font-weight: 600;
        }

        @media (max-width: 992px) {
            .album-hero-container {
                grid-template-columns: 1fr;
            }
            .album-hero-cover-box {
                height: 300px;
            }
            .gallery-30-grid,
            .related-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .love-card-banner {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
        }

        /* Download Request & Verification Modals */
        .dl-modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.88);
            backdrop-filter: blur(12px);
            z-index: 99999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .dl-modal-backdrop.active {
            display: flex;
        }

        .dl-modal-box {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            max-width: 480px;
            width: 100%;
            padding: 32px;
            position: relative;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.9);
        }

        .dl-header-row {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
        }

        .dl-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .dl-icon-box.gold {
            background: rgba(234, 179, 8, 0.15);
            border-color: rgba(234, 179, 8, 0.3);
            color: #facc15;
        }

        .dl-title-group h3 {
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 4px 0;
        }

        .dl-title-group p {
            color: #a1a1aa;
            font-size: 0.84rem;
            margin: 0 0 4px 0;
        }

        .dl-meta-tag {
            font-size: 0.78rem;
            color: #71717a;
            font-weight: 600;
        }

        .dl-form-field {
            margin-bottom: 16px;
            text-align: left;
        }

        .dl-form-field label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            color: #a1a1aa;
            margin-bottom: 6px;
        }

        .dl-form-field input {
            width: 100%;
            background: #030305;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            padding: 12px 16px;
            color: #ffffff;
            font-size: 0.92rem;
            outline: none;
            transition: border-color 0.25s ease;
            box-sizing: border-box;
        }

        .dl-form-field input:focus {
            border-color: rgba(220, 38, 38, 0.6);
        }

        .dl-note-box {
            font-size: 0.75rem;
            color: #71717a;
            line-height: 1.4;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .dl-actions-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-dl-cancel {
            flex: 1;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 700;
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .btn-dl-cancel:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-dl-submit {
            flex: 1.3;
            background: #10b981;
            border: none;
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 800;
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-dl-submit:hover {
            background: #059669;
        }

        .btn-dl-verify {
            background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%);
            color: #000000;
            box-shadow: 0 4px 15px rgba(234, 179, 8, 0.4);
        }

        .btn-dl-verify:hover {
            background: linear-gradient(135deg, #facc15 0%, #eab308 100%);
        }

        /* 6 Digit Code Input Boxes */
        .code-inputs-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin: 20px 0 16px 0;
        }

        .code-box {
            width: 48px;
            height: 54px;
            background: #030305;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            color: #ffffff;
            font-size: 1.4rem;
            font-weight: 800;
            text-align: center;
            outline: none;
            transition: border-color 0.25s ease;
        }

        .code-box:focus {
            border-color: #facc15;
            box-shadow: 0 0 12px rgba(234, 179, 8, 0.4);
        }

        .resend-text {
            font-size: 0.8rem;
            color: #71717a;
            text-align: center;
            margin-bottom: 24px;
        }

        .resend-link {
            color: #facc15;
            cursor: pointer;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <!-- Top Sub-Nav Bar -->
    <header class="single-nav-bar">
        <div class="single-nav-container">
            <a href="/portfolio.php" class="single-back-link">
                <i class="fa-solid fa-arrow-left"></i> Back to Portfolio
            </a>
            <a href="/" class="single-logo-center">
                <img src="/assets/img/icons/logo.png" alt="Falhen Logo">
            </a>
            <div class="nav-actions-right">
                <button class="btn-share-glass" onclick="shareAlbum()">
                    <i class="fa-solid fa-share-nodes"></i> Share
                </button>
                <a href="/index.php#lets-talk" class="btn-book-red">
                    <i class="fa-regular fa-calendar-check"></i> Book a Shoot
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Stage -->
    <section class="album-hero-section">
        <div class="album-hero-container">
            <div class="album-hero-cover-box">
                <img src="<?php echo htmlspecialchars($currentAlbum['cover']); ?>" alt="<?php echo htmlspecialchars($currentAlbum['title']); ?>">
            </div>

            <div>
                <div class="breadcrumbs-row">
                    <a href="/">Home</a>
                    <span>&rsaquo;</span>
                    <a href="/portfolio.php">Portfolio</a>
                    <span>&rsaquo;</span>
                    <span style="color:#ffffff; font-weight:600;"><?php echo htmlspecialchars($currentAlbum['title']); ?></span>
                </div>

                <div class="meta-badges-row">
                    <span class="badge-pill-red"><?php echo htmlspecialchars($currentAlbum['category']); ?></span>
                    <?php if (!empty($currentAlbum['featured'])): ?>
                        <span class="badge-pill-gold"><i class="fa-solid fa-star"></i> Featured</span>
                    <?php endif; ?>
                </div>

                <h1 class="album-main-title"><?php echo htmlspecialchars($currentAlbum['title']); ?></h1>
                <p class="album-main-subtitle"><?php echo htmlspecialchars($currentAlbum['subtitle']); ?></p>

                <div class="meta-info-pills">
                    <span class="info-pill-item"><i class="fa-regular fa-images"></i> Gallery</span>
                    <span class="info-pill-item"><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($currentAlbum['client']); ?></span>
                    <span class="info-pill-item"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($currentAlbum['location']); ?></span>
                </div>

                <div style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
                    <button class="btn-download-all" onclick="downloadAllPhotos()">
                        <i class="fa-solid fa-download"></i> Download All (30)
                    </button>
                    <?php if (!empty($currentAlbum['gdrive_url'])): ?>
                        <a href="<?php echo htmlspecialchars($currentAlbum['gdrive_url']); ?>" target="_blank" class="btn-download-all" style="background: #4285f4; box-shadow: 0 4px 20px rgba(66, 133, 244, 0.4); text-decoration: none;">
                            <i class="fa-brands fa-google-drive"></i> Open Google Drive Album
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- 30 Pictures Grid -->
    <main class="photos-main-container">
        
        <div class="gallery-30-grid">
            <?php foreach ($galleryPhotos as $idx => $photoUrl): ?>
                <div class="photo-card-item" onclick="openSingleLightbox(<?php echo $idx; ?>)">
                    <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Gallery Photo <?php echo $idx; ?>">
                    <div class="photo-overlay-actions">
                        <button class="btn-photo-action btn-photo-zoom" title="Zoom Photo">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                        <button class="btn-photo-action btn-photo-download" onclick="event.stopPropagation(); downloadSinglePhoto('<?php echo htmlspecialchars($photoUrl); ?>')" title="Download Photo">
                            <i class="fa-solid fa-download"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Love What You See Card Banner -->
        <div class="love-card-banner">
            <div class="love-banner-left">
                <div class="love-banner-icon">
                    <i class="fa-solid fa-camera-retro"></i>
                </div>
                <div>
                    <h3 class="love-banner-title">Love what you see?</h3>
                    <p class="love-banner-desc">Book your own session — portraits, weddings, events, corporate.</p>
                </div>
            </div>
            <div class="love-banner-btns">
                <button class="btn-share-glass" onclick="shareAlbum()">
                    <i class="fa-solid fa-share-nodes"></i> Share
                </button>
                <a href="/index.php#lets-talk" class="btn-book-red">
                    <i class="fa-regular fa-calendar-check"></i> Book a Shoot
                </a>
            </div>
        </div>

        <!-- More Related Albums -->
        <?php if (!empty($relatedAlbums)): ?>
            <div class="related-albums-section">
                <h3 class="related-title">More <?php echo htmlspecialchars($currentAlbum['category']); ?> Albums</h3>
                <div class="related-grid">
                    <?php foreach (array_slice($relatedAlbums, 0, 3) as $rel): ?>
                        <a href="/portfolio-single.php?id=<?php echo $rel['id']; ?>" class="photo-card-item" style="height:320px; text-decoration:none;">
                            <img src="<?php echo htmlspecialchars($rel['cover']); ?>" alt="<?php echo htmlspecialchars($rel['title']); ?>">
                            <div style="position:absolute; bottom:0; inset-x:0; padding:16px; background:linear-gradient(0deg, rgba(3,3,5,0.95), transparent); z-index:3;">
                                <h4 style="margin:0; font-size:1rem; color:#fff; font-weight:800;"><?php echo htmlspecialchars($rel['title']); ?></h4>
                                <span style="font-size:0.78rem; color:#ef4444; font-weight:600;"><?php echo htmlspecialchars($rel['category']); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bottom Navigation Bar -->
        <div class="single-footer-bar">
            <a href="/portfolio.php" class="single-back-link">
                <i class="fa-solid fa-arrow-left"></i> Back to Portfolio
            </a>
            <a href="/index.php#lets-talk" class="btn-book-red">
                <i class="fa-regular fa-calendar-check"></i> Book a Shoot
            </a>
        </div>

    </main>

    <!-- Fullscreen Lightbox Modal -->
    <div class="lightbox-modal-backdrop" id="singleLightboxModal">
        <div class="lightbox-box-wrap">
            <button class="lightbox-close-btn" onclick="closeSingleLightbox()">&times;</button>
            <div class="lightbox-stage-img">
                <button class="lightbox-nav-btn lightbox-prev-btn" onclick="navLightbox(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                <img id="singleLightboxImg" src="" alt="Fullscreen Photo">
                <button class="lightbox-nav-btn lightbox-next-btn" onclick="navLightbox(1)"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
            <div class="lightbox-info-row">
                <div>
                    <h3 style="margin:0 0 4px 0; font-size:1.1rem; color:#fff; font-weight:800;"><?php echo htmlspecialchars($currentAlbum['title']); ?></h3>
                    <span class="lightbox-counter-text" id="singleLightboxCounter">Photo 1 of 30</span>
                </div>
                <button class="btn-download-all" style="padding:10px 20px; font-size:0.85rem;" onclick="downloadCurrentLightboxPhoto()">
                    <i class="fa-solid fa-download"></i> Download Photo
                </button>
            </div>
        </div>
    </div>

    <!-- Download Step 1: Request User Details Modal -->
    <div class="dl-modal-backdrop" id="downloadRequestModal">
        <div class="dl-modal-box">
            <div class="dl-header-row">
                <div class="dl-icon-box">
                    <i class="fa-solid fa-cloud-arrow-down"></i>
                </div>
                <div class="dl-title-group">
                    <h3>Download Photos</h3>
                    <p id="dlModalTitle"><?php echo htmlspecialchars($currentAlbum['title']); ?></p>
                    <span class="dl-meta-tag" id="dlModalMeta"><i class="fa-regular fa-images"></i> 30 photos &bull; ~150 MB</span>
                </div>
            </div>

            <form id="dlRequestForm" onsubmit="handleDownloadRequest(event)">
                <div class="dl-form-field">
                    <label for="dlFullName">Full Name *</label>
                    <input type="text" id="dlFullName" required placeholder="Your full name">
                </div>
                <div class="dl-form-field">
                    <label for="dlEmail">Email Address *</label>
                    <input type="email" id="dlEmail" required placeholder="your@email.com">
                </div>
                <div class="dl-form-field">
                    <label for="dlPhone">Phone Number *</label>
                    <input type="tel" id="dlPhone" required placeholder="+1 (555) 000-0000">
                </div>

                <div class="dl-note-box">
                    <i class="fa-solid fa-shield-halved" style="color:#10b981; margin-top:2px;"></i>
                    <span>Your information helps us track downloads and improve our service. We never share your details with third parties.</span>
                </div>

                <div class="dl-actions-row">
                    <button type="button" class="btn-dl-cancel" onclick="closeDownloadModal()">Cancel</button>
                    <button type="submit" class="btn-dl-submit" id="btnRequestSubmit">
                        <i class="fa-solid fa-download"></i> Start Download
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Download Step 2: Verification Code Modal -->
    <div class="dl-modal-backdrop" id="downloadVerifyModal">
        <div class="dl-modal-box">
            <div class="dl-header-row">
                <div class="dl-icon-box gold">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="dl-title-group">
                    <h3>Verify Your Email</h3>
                    <p>Enter the 6-digit code sent to your inbox</p>
                </div>
            </div>

            <p style="font-size:0.86rem; color:#a1a1aa; margin:0 0 16px 0; text-align:center;">
                We sent a verification code to <strong id="verifyEmailDisplay" style="color:#ffffff;"></strong>
            </p>

            <form id="dlVerifyForm" onsubmit="handleDownloadVerify(event)">
                <div class="code-inputs-group">
                    <input type="text" class="code-box" maxlength="1" data-index="0" autofocus autocomplete="off">
                    <input type="text" class="code-box" maxlength="1" data-index="1" autocomplete="off">
                    <input type="text" class="code-box" maxlength="1" data-index="2" autocomplete="off">
                    <input type="text" class="code-box" maxlength="1" data-index="3" autocomplete="off">
                    <input type="text" class="code-box" maxlength="1" data-index="4" autocomplete="off">
                    <input type="text" class="code-box" maxlength="1" data-index="5" autocomplete="off">
                </div>

                <div class="resend-text">
                    Didn't receive it? <span class="resend-link" onclick="resendVerificationCode()">Resend code</span>
                </div>

                <div class="dl-actions-row">
                    <button type="button" class="btn-dl-cancel" onclick="closeVerifyModal()">Cancel</button>
                    <button type="submit" class="btn-dl-submit btn-dl-verify" id="btnVerifySubmit">
                        <i class="fa-solid fa-check"></i> Verify & Download
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom Dark Glass Alert Modal Popup -->
    <div class="dl-modal-backdrop" id="customAlertModal">
        <div class="dl-modal-box" style="max-width: 440px; text-align: center;">
            <div id="customAlertIconBox" class="dl-icon-box" style="margin: 0 auto 16px auto; width: 56px; height: 56px; border-radius: 16px; font-size: 1.5rem;">
                <i class="fa-solid fa-shield-halved" id="customAlertIcon"></i>
            </div>
            <h3 id="customAlertTitle" style="font-size: 1.3rem; font-weight: 800; color: #ffffff; margin: 0 0 8px 0;">Notice</h3>
            <p id="customAlertMessage" style="font-size: 0.9rem; color: #a1a1aa; line-height: 1.6; margin: 0 0 24px 0;"></p>
            
            <button type="button" class="btn-dl-submit" style="width: 100%; background: #dc2626;" onclick="closeCustomAlert()">
                OK
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const galleryPhotos = <?php echo json_encode(array_values($galleryPhotos)); ?>;
        let currentIndex = 0;

        function openSingleLightbox(index) {
            currentIndex = index - 1;
            if (currentIndex < 0) currentIndex = 0;
            if (currentIndex >= galleryPhotos.length) currentIndex = galleryPhotos.length - 1;

            updateLightbox();
            document.getElementById('singleLightboxModal').classList.add('active');
        }

        function updateLightbox() {
            document.getElementById('singleLightboxImg').src = galleryPhotos[currentIndex];
            document.getElementById('singleLightboxCounter').textContent = `Photo ${currentIndex + 1} of ${galleryPhotos.length}`;
        }

        function navLightbox(direction) {
            currentIndex += direction;
            if (currentIndex < 0) currentIndex = galleryPhotos.length - 1;
            if (currentIndex >= galleryPhotos.length) currentIndex = 0;
            updateLightbox();
        }

        function closeSingleLightbox() {
            document.getElementById('singleLightboxModal').classList.remove('active');
        }

        let pendingDownloadTarget = null; // 'all' or specific image URL
        let isVerifiedClientSession = false;

        function downloadSinglePhoto(url) {
            pendingDownloadTarget = url;
            if (isVerifiedClientSession) {
                executeDirectDownload(url, 'falhen-photo.jpg');
            } else {
                document.getElementById('dlModalMeta').innerHTML = '<i class="fa-regular fa-image"></i> 1 photo &bull; ~5 MB';
                document.getElementById('downloadRequestModal').classList.add('active');
            }
        }

        function downloadCurrentLightboxPhoto() {
            downloadSinglePhoto(galleryPhotos[currentIndex]);
        }

        function downloadAllPhotos() {
            pendingDownloadTarget = 'all';
            if (isVerifiedClientSession) {
                executeDirectDownload('/assets/img/portfolio/portfolio_wedding.png', 'falhen-album-assets.zip');
            } else {
                document.getElementById('dlModalMeta').innerHTML = '<i class="fa-regular fa-images"></i> 30 photos &bull; ~150 MB';
                document.getElementById('downloadRequestModal').classList.add('active');
            }
        }

        function executeDirectDownload(url, filename) {
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function closeDownloadModal() {
            document.getElementById('downloadRequestModal').classList.remove('active');
        }

        function closeVerifyModal() {
            document.getElementById('downloadVerifyModal').classList.remove('active');
        }

        let userClientEmail = '';

        function showCustomAlert(title, message, type = 'info') {
            const modal = document.getElementById('customAlertModal');
            const titleElem = document.getElementById('customAlertTitle');
            const msgElem = document.getElementById('customAlertMessage');
            const iconBox = document.getElementById('customAlertIconBox');
            const iconElem = document.getElementById('customAlertIcon');

            titleElem.textContent = title;
            msgElem.innerHTML = message;

            if (type === 'success') {
                iconBox.className = 'dl-icon-box';
                iconBox.style.background = 'rgba(16, 185, 129, 0.18)';
                iconBox.style.borderColor = 'rgba(16, 185, 129, 0.4)';
                iconBox.style.color = '#10b981';
                iconElem.className = 'fa-solid fa-circle-check';
            } else if (type === 'error') {
                iconBox.className = 'dl-icon-box';
                iconBox.style.background = 'rgba(220, 38, 38, 0.18)';
                iconBox.style.borderColor = 'rgba(220, 38, 38, 0.4)';
                iconBox.style.color = '#ef4444';
                iconElem.className = 'fa-solid fa-circle-exclamation';
            } else if (type === 'code') {
                iconBox.className = 'dl-icon-box gold';
                iconBox.style.background = 'rgba(234, 179, 8, 0.18)';
                iconBox.style.borderColor = 'rgba(234, 179, 8, 0.4)';
                iconBox.style.color = '#facc15';
                iconElem.className = 'fa-solid fa-key';
            } else {
                iconBox.className = 'dl-icon-box';
                iconBox.style.background = 'rgba(59, 130, 246, 0.18)';
                iconBox.style.borderColor = 'rgba(59, 130, 246, 0.4)';
                iconBox.style.color = '#60a5fa';
                iconElem.className = 'fa-solid fa-circle-info';
            }

            modal.classList.add('active');
        }

        function closeCustomAlert() {
            document.getElementById('customAlertModal').classList.remove('active');
        }

        async function handleDownloadRequest(e) {
            e.preventDefault();
            const name = document.getElementById('dlFullName').value.trim();
            const email = document.getElementById('dlEmail').value.trim();
            const phone = document.getElementById('dlPhone').value.trim();
            const submitBtn = document.getElementById('btnRequestSubmit');

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking client...';

            const formData = new FormData();
            formData.append('action', 'request_code');
            formData.append('name', name);
            formData.append('email', email);
            formData.append('phone', phone);

            try {
                const res = await fetch('/api/verify-download.php', { method: 'POST', body: formData });
                const data = await res.json();

                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-download"></i> Start Download';

                if (data.success) {
                    userClientEmail = data.email;
                    closeDownloadModal();
                    document.getElementById('verifyEmailDisplay').textContent = data.email;
                    document.getElementById('downloadVerifyModal').classList.add('active');
                    
                    const codeBoxes = document.querySelectorAll('.code-box');
                    codeBoxes.forEach(box => box.value = '');
                    if (codeBoxes[0]) codeBoxes[0].focus();
                } else {
                    showCustomAlert('Access Denied', data.message, 'error');
                }
            } catch (err) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-download"></i> Start Download';
                showCustomAlert('Error', 'An error occurred while verifying details. Please try again.', 'error');
            }
        }

        async function handleDownloadVerify(e) {
            e.preventDefault();
            const codeBoxes = document.querySelectorAll('.code-box');
            let enteredCode = '';
            codeBoxes.forEach(b => enteredCode += b.value);

            if (enteredCode.length < 6) {
                showCustomAlert('Incomplete Code', 'Please enter the complete 6-digit verification code.', 'error');
                return;
            }

            const verifyBtn = document.getElementById('btnVerifySubmit');
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying...';

            const formData = new FormData();
            formData.append('action', 'verify_code');
            formData.append('email', userClientEmail);
            formData.append('code', enteredCode);

            try {
                const res = await fetch('/api/verify-download.php', { method: 'POST', body: formData });
                const data = await res.json();

                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<i class="fa-solid fa-check"></i> Verify & Download';

                if (data.success) {
                    isVerifiedClientSession = true;
                    showCustomAlert('Verification Successful', 'Your verification was successful. Your photo download is starting now!', 'success');
                    closeVerifyModal();

                    if (pendingDownloadTarget === 'all' || !pendingDownloadTarget) {
                        executeDirectDownload(data.download_url || '/assets/img/portfolio/portfolio_wedding.png', 'falhen-album-assets.zip');
                    } else {
                        executeDirectDownload(pendingDownloadTarget, 'falhen-photo.jpg');
                    }
                } else {
                    showCustomAlert('Verification Failed', data.message, 'error');
                }
            } catch (err) {
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<i class="fa-solid fa-check"></i> Verify & Download';
                showCustomAlert('Error', 'An error occurred during code verification.', 'error');
            }
        }

        function resendVerificationCode() {
            showCustomAlert('Code Resent', 'A new 6-digit verification code has been resent to ' + userClientEmail, 'info');
        }

        // Auto-tabbing for 6 digit code inputs
        document.querySelectorAll('.code-box').forEach((box, idx, boxes) => {
            box.addEventListener('input', (e) => {
                if (box.value.length === 1 && idx < boxes.length - 1) {
                    boxes[idx + 1].focus();
                }
            });
            box.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !box.value && idx > 0) {
                    boxes[idx - 1].focus();
                }
            });
            box.addEventListener('paste', (e) => {
                const pasteData = e.clipboardData.getData('text').trim();
                if (pasteData.length === 6 && /^\d+$/.test(pasteData)) {
                    e.preventDefault();
                    pasteData.split('').forEach((char, i) => {
                        if (boxes[i]) boxes[i].value = char;
                    });
                    boxes[boxes.length - 1].focus();
                }
            });
        });

        function shareAlbum() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo htmlspecialchars(addslashes($currentAlbum['title'])); ?>',
                    text: 'Check out this portfolio album on Falhen Media',
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(window.location.href);
                alert('Album link copied to clipboard!');
            }
        }

        // Backdrop click to close
        document.getElementById('singleLightboxModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSingleLightbox();
            }
        });
    </script>
</body>
</html>
