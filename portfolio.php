<?php
// portfolio.php - Portfolio Showcase matching falhen.com/portfolio screenshots
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "The Portfolio — Twelve years of stories, brands, and moments | Falhen Media";

$masterPortfolioItems = getPortfolioRepo();

$portfolioAlbums = [];
$portfolioVideos = [];
$portfolioProjects = [];
$allProjects = [];

foreach ($masterPortfolioItems as $item) {
    $mediaType = $item['media_type'] ?? 'photo';
    if ($mediaType === 'photo') {
        $portfolioAlbums[$item['id']] = $item;
    } elseif ($mediaType === 'video') {
        $portfolioVideos[$item['id']] = $item;
    } elseif ($mediaType === 'project') {
        $portfolioProjects[$item['id']] = $item;
    }
    $allProjects[] = $item;
}

$featuredAlbums = array_filter($portfolioAlbums, function($item) {
    return !empty($item['featured']);
});

$featuredVideos = array_filter($portfolioVideos, function($item) {
    return !empty($item['featured']);
});

$featuredAllProjects = array_filter($allProjects, function($item) {
    return !empty($item['featured']);
});
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
        .port-nav-bar {
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #030305;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .port-nav-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .port-back-link {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .port-back-link:hover {
            color: #ffffff;
        }

        .port-logo-center img {
            height: 28px;
            width: auto;
            object-fit: contain;
        }

        .btn-port-contact {
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

        .btn-port-contact:hover {
            background: #ef4444;
            transform: translateY(-1px);
        }

        /* Hero Stage */
        .port-hero-section {
            position: relative;
            padding: 90px 24px 70px 24px;
            text-align: center;
            background: linear-gradient(180deg, rgba(3, 3, 5, 0.72) 0%, rgba(3, 3, 5, 0.92) 80%, rgba(3, 3, 5, 1) 100%),
                        radial-gradient(circle at 50% 30%, rgba(220, 38, 38, 0.25) 0%, transparent 60%),
                        url('/assets/img/portfolio/portfolio-hero.jpg') center/cover no-repeat;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            overflow: hidden;
        }

        .port-hero-title {
            font-size: 4rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -1.5px;
            margin: 0 0 12px 0;
            line-height: 1.1;
        }

        .port-hero-title span {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .port-hero-subtitle {
            font-size: 1.15rem;
            color: #a1a1aa;
            margin: 0 0 40px 0;
            font-weight: 500;
        }

        /* Type Switcher Bar */
        .gallery-type-bar {
            display: inline-flex;
            align-items: center;
            background: rgba(14, 14, 18, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 6px;
            gap: 8px;
            backdrop-filter: blur(12px);
        }

        .gallery-tab-btn {
            background: transparent;
            border: none;
            color: #a1a1aa;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
        }

        .gallery-tab-btn.active {
            background: #dc2626;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
        }

        .gallery-tab-btn span.tab-tag {
            font-size: 0.76rem;
            opacity: 0.85;
            font-weight: 600;
            margin-left: 4px;
        }

        .type-counter-badge {
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            padding-left: 16px;
            padding-right: 12px;
            color: #71717a;
            font-size: 0.84rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Main Container */
        .port-main-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 60px 24px;
        }

        /* Booking Callout Banner */
        .book-shoot-card {
            background: linear-gradient(135deg, rgba(35, 10, 15, 0.95) 0%, rgba(14, 14, 18, 0.95) 100%);
            border: 1px solid rgba(220, 38, 38, 0.3);
            border-radius: 18px;
            padding: 24px 32px;
            margin-bottom: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .book-shoot-left {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .book-shoot-icon {
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

        .book-shoot-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 4px 0;
        }

        .book-shoot-desc {
            color: #a1a1aa;
            font-size: 0.9rem;
            margin: 0;
        }

        .btn-book-shoot {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 700;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 18px rgba(220, 38, 38, 0.4);
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-book-shoot:hover {
            background: #ef4444;
            transform: translateY(-2px);
        }

        /* Section Headers */
        .port-section-header {
            margin-bottom: 30px;
            display: flex;
            align-items: baseline;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 16px;
        }

        .port-section-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .port-section-title i {
            color: #eab308;
            font-size: 1.1rem;
        }

        .port-section-subtitle {
            color: #71717a;
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0;
        }

        /* Featured Albums Grid (3 columns) */
        .featured-albums-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            align-items: start;
            margin-bottom: 70px;
        }

        .album-card {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
        }

        .album-card:hover {
            border-color: rgba(220, 38, 38, 0.5);
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(220, 38, 38, 0.2);
        }

        .album-thumb-wrap {
            position: relative;
            width: 100%;
            height: 320px;
            overflow: hidden;
        }

        .album-thumb-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scale(1.10);
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .album-card:hover .album-thumb-wrap img {
            transform: scale(1.0);
        }

        .album-badges {
            position: absolute;
            top: 14px;
            left: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            z-index: 3;
        }

        .badge-feat {
            background: rgba(234, 179, 8, 0.2);
            border: 1px solid rgba(234, 179, 8, 0.4);
            color: #facc15;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 50px;
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .badge-cat {
            background: rgba(14, 14, 18, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 50px;
            backdrop-filter: blur(8px);
        }

        .album-overlay-center {
            position: absolute;
            inset: 0;
            background: rgba(3, 3, 5, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.35s ease;
            z-index: 2;
        }

        .album-card:hover .album-overlay-center,
        .grid-album-card:hover .album-overlay-center {
            opacity: 1;
        }

        .icon-view-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #dc2626;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.6);
            transform: scale(0.8);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .album-card:hover .icon-view-btn,
        .grid-album-card:hover .icon-view-btn {
            transform: scale(1);
        }

        .grid-star-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #eab308;
            color: #000000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            z-index: 3;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
        }

        .album-content-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
        }

        .album-title-text {
            font-size: 1.1rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 8px 0;
            line-height: 1.3;
        }

        .album-desc-text {
            color: #a1a1aa;
            font-size: 0.84rem;
            line-height: 1.5;
            margin-bottom: 0;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            opacity: 0;
            max-height: 0;
            visibility: hidden;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .album-card:hover .album-desc-text {
            opacity: 1;
            max-height: 100px;
            margin-bottom: 16px;
            visibility: visible;
        }

        .album-footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #71717a;
            font-weight: 600;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .album-view-link {
            color: #dc2626;
            display: flex;
            align-items: center;
            gap: 4px;
            opacity: 0;
            visibility: hidden;
            transform: translateX(-6px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .album-card:hover .album-view-link {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
            gap: 8px;
        }

        /* Category Filter Tabs */
        .filter-pills-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .cat-pill-btn,
        .project-pill-btn {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.22);
            color: #ffffff;
            font-family: inherit;
            font-size: 0.86rem;
            font-weight: 700;
            padding: 8px 22px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .cat-pill-btn.active,
        .cat-pill-btn:hover,
        .project-pill-btn.active,
        .project-pill-btn:hover {
            background: #dc2626;
            border-color: #dc2626;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
        }

        /* All Albums & Video Grid (3 columns) */
        .all-albums-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 50px;
        }

        @media (max-width: 992px) {
            .all-albums-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .all-albums-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Projects 4-Column Grid & Card Styling */
        .projects-4col-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 50px;
        }

        @media (max-width: 1200px) {
            .projects-4col-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .projects-4col-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .projects-4col-grid {
                grid-template-columns: 1fr;
            }
        }

        .project-card {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            height: 260px;
            display: flex;
            flex-direction: column;
        }

        .project-card:hover {
            border-color: rgba(220, 38, 38, 0.6);
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(220, 38, 38, 0.25);
        }

        .project-card-image-wrap {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .project-card-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scale(1.05);
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .project-card:hover .project-card-image-wrap img {
            transform: scale(1.0);
        }

        .project-card-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(3, 3, 5, 0.4) 0%, rgba(3, 3, 5, 0.2) 40%, rgba(3, 3, 5, 0.95) 100%);
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            z-index: 2;
        }

        .project-card-top-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .project-cat-badge {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 50px;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.4);
            letter-spacing: 0.2px;
        }

        .project-star-badge {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(234, 179, 8, 0.25);
            border: 1px solid rgba(234, 179, 8, 0.5);
            color: #facc15;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            backdrop-filter: blur(4px);
        }

        .project-card-bottom-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .project-client-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: #a1a1aa;
            margin: 0;
        }

        .project-card-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            line-height: 1.25;
        }

        .grid-album-card {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
        }

        .grid-album-card:hover {
            border-color: rgba(220, 38, 38, 0.5);
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(220, 38, 38, 0.2);
        }

        .grid-thumb-box {
            position: relative;
            width: 100%;
            height: 540px;
            overflow: hidden;
        }

        .grid-thumb-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scale(1.10);
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .grid-album-card:hover .grid-thumb-box img {
            transform: scale(1.0);
        }

        .grid-card-footer {
            padding: 16px 20px;
            background: #0e0e12;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .grid-card-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 2px 0;
        }

        .grid-card-cat {
            font-size: 0.78rem;
            color: #71717a;
            font-weight: 600;
        }

        .grid-card-arrow {
            color: #71717a;
            font-size: 0.85rem;
            transition: color 0.25s ease, transform 0.25s ease;
        }

        .grid-album-card:hover .grid-card-arrow {
            color: #dc2626;
            transform: translateX(3px);
        }

        /* Lightbox Modal */
        .lightbox-modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.92);
            backdrop-filter: blur(12px);
            z-index: 99999;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .lightbox-modal-backdrop.active {
            display: flex;
        }

        .lightbox-modal-box {
            max-width: 900px;
            width: 100%;
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.9);
        }

        .lightbox-close-btn {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.25s ease;
            z-index: 10;
        }

        .lightbox-close-btn:hover {
            background: #dc2626;
            border-color: #dc2626;
        }

        .lightbox-img-wrap {
            width: 100%;
            max-height: 520px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .lightbox-img-wrap img {
            width: 100%;
            max-height: 520px;
            object-fit: contain;
        }

        .lightbox-info-bar {
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0e0e12;
        }

        .lightbox-title-wrap h3 {
            font-size: 1.3rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 4px 0;
        }

        .lightbox-title-wrap p {
            color: #a1a1aa;
            font-size: 0.88rem;
            margin: 0;
        }

        /* Footer Bar */
        .port-footer-bar {
            padding: 18px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.88rem;
            color: #71717a;
        }

        .port-footer-links {
            display: flex;
            gap: 24px;
        }

        .port-footer-links a {
            color: #a1a1aa;
            text-decoration: none;
            transition: color 0.25s ease;
        }

        /* Video Gallery Extensions */
        .video-duration-badge {
            position: absolute;
            bottom: 14px;
            right: 14px;
            background: rgba(14, 14, 18, 0.88);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            backdrop-filter: blur(8px);
            z-index: 3;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .icon-play-btn {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #dc2626;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            box-shadow: 0 4px 25px rgba(220, 38, 38, 0.7);
            transform: scale(0.85);
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            padding-left: 3px;
        }

        .album-card:hover .icon-play-btn,
        .grid-album-card:hover .icon-play-btn {
            transform: scale(1.05);
            background: #ef4444;
            box-shadow: 0 6px 30px rgba(239, 68, 68, 0.9);
        }

        /* Video Modal Window */
        .video-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(3, 3, 5, 0.92);
            backdrop-filter: blur(12px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            padding: 20px;
        }

        .video-modal-backdrop.active {
            opacity: 1;
            pointer-events: auto;
        }

        .video-modal-box {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            width: 100%;
            max-width: 920px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.85);
            display: flex;
            flex-direction: column;
        }

        .video-stage-wrap {
            width: 100%;
            background: #000000;
            aspect-ratio: 16/9;
            position: relative;
        }

        .video-stage-wrap video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        @media (max-width: 992px) {
            .featured-albums-grid,
            .all-albums-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .book-shoot-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
        }

        @media (max-width: 600px) {
            .featured-albums-grid,
            .all-albums-grid {
                grid-template-columns: 1fr;
            }
            .port-hero-title {
                font-size: 2.8rem;
            }
        }
    </style>
</head>
<body>

    <!-- Top Sub-Nav Bar -->
    <header class="port-nav-bar">
        <div class="port-nav-container">
            <a href="/index.php" class="port-back-link">
                <i class="fa-solid fa-arrow-left"></i> Back to Home
            </a>
            <a href="/" class="port-logo-center">
                <img src="/assets/img/icons/logo.png" alt="Falhen Logo">
            </a>
            <a href="/contact.php" class="btn-port-contact">
                <i class="fa-regular fa-paper-plane"></i> Contact Us
            </a>
        </div>
    </header>

    <!-- Hero Stage -->
    <section class="port-hero-section">
        <h1 class="port-hero-title">The <span>Portfolio</span></h1>
        <p class="port-hero-subtitle">Twelve years of stories, brands, and moments</p>

        <!-- Gallery Type Switcher Bar -->
        <div class="gallery-type-bar">
            <button class="gallery-tab-btn active" id="btnTypePhoto" onclick="switchGalleryType('photo')">
                <i class="fa-solid fa-camera"></i> Photo Gallery <span class="tab-tag">Albums</span>
            </button>
            <button class="gallery-tab-btn" id="btnTypeVideo" onclick="switchGalleryType('video')">
                <i class="fa-solid fa-film"></i> Video Gallery <span class="tab-tag">Reels</span>
            </button>
            <button class="gallery-tab-btn" id="btnTypeProject" onclick="switchGalleryType('project')">
                <i class="fa-solid fa-border-all"></i> All Projects <span class="tab-tag"><?php echo count($portfolioProjects); ?> projects</span>
            </button>
        </div>
    </section>

    <main class="port-main-container">
        
        <!-- Booking Callout Banner -->
        <div class="book-shoot-card" id="bookShootCard">
            <div class="book-shoot-left">
                <div class="book-shoot-icon">
                    <i class="fa-solid fa-camera-retro"></i>
                </div>
                <div>
                    <h3 class="book-shoot-title">Ready to book your shoot?</h3>
                    <p class="book-shoot-desc">Portraits, weddings, events, corporate — we've got you covered.</p>
                </div>
            </div>
            <a href="/index.php#lets-talk" class="btn-book-shoot">
                <i class="fa-regular fa-calendar-check"></i> Book a Shoot
            </a>
        </div>

        <!-- Photo Gallery View -->
        <div id="photoGalleryView">
            <!-- Category Filter Tabs -->
            <div class="filter-pills-row">
                <button class="cat-pill-btn active" data-filter="all" onclick="filterCategory('all', this)">All</button>
                <button class="cat-pill-btn" data-filter="Portrait" onclick="filterCategory('Portrait', this)">Portrait</button>
                <button class="cat-pill-btn" data-filter="Event" onclick="filterCategory('Event', this)">Event</button>
                <button class="cat-pill-btn" data-filter="Birthday" onclick="filterCategory('Birthday', this)">Birthday</button>
                <button class="cat-pill-btn" data-filter="Wedding" onclick="filterCategory('Wedding', this)">Wedding</button>
            </div>

            <!-- All Albums 3-Column Grid -->
            <div class="all-albums-grid" id="allAlbumsGrid">
                <?php foreach ($portfolioAlbums as $item): ?>
                    <div class="grid-album-card" data-cat="<?php echo htmlspecialchars($item['category']); ?>" onclick="window.location.href='/portfolio-photo.php?id=<?php echo $item['id']; ?>'">
                        <div class="grid-thumb-box">
                            <?php if (!empty($item['featured'])): ?>
                                <div class="grid-star-badge"><i class="fa-solid fa-star"></i></div>
                            <?php endif; ?>
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <div class="album-overlay-center">
                                <div class="icon-view-btn">
                                    <i class="fa-regular fa-image"></i>
                                </div>
                            </div>
                        </div>
                        <div class="grid-card-footer">
                            <div>
                                <h4 class="grid-card-title"><?php echo htmlspecialchars($item['title']); ?></h4>
                                <span class="grid-card-cat"><?php echo htmlspecialchars($item['category']); ?></span>
                            </div>
                            <i class="fa-solid fa-chevron-right grid-card-arrow"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Video Gallery View -->
        <div id="videoGalleryView" style="display: none;">
            <!-- Video Category Filter Tabs -->
            <div class="filter-pills-row">
                <button class="cat-pill-btn active" onclick="filterVideoCategory('all', this)">All</button>
                <button class="cat-pill-btn" onclick="filterVideoCategory('Commercial', this)">Commercial</button>
                <button class="cat-pill-btn" onclick="filterVideoCategory('Music Video', this)">Music Video</button>
                <button class="cat-pill-btn" onclick="filterVideoCategory('Wedding', this)">Wedding</button>
                <button class="cat-pill-btn" onclick="filterVideoCategory('Reels', this)">Reels</button>
                <button class="cat-pill-btn" onclick="filterVideoCategory('Documentary', this)">Documentary</button>
            </div>

            <!-- All Video Productions 3-Column Grid -->
            <div class="all-albums-grid" id="allVideosGrid">
                <?php foreach ($portfolioVideos as $vItem): ?>
                    <div class="grid-album-card" data-cat="<?php echo htmlspecialchars($vItem['category']); ?>" onclick="window.location.href='/portfolio-video.php?id=<?php echo $vItem['id']; ?>'">
                        <div class="grid-thumb-box">
                            <?php if (!empty($vItem['featured'])): ?>
                                <div class="grid-star-badge"><i class="fa-solid fa-star"></i></div>
                            <?php endif; ?>
                            <img src="<?php echo htmlspecialchars($vItem['image']); ?>" alt="<?php echo htmlspecialchars($vItem['title']); ?>">
                            <div class="video-duration-badge">
                                <i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($vItem['duration']); ?>
                            </div>
                            <div class="album-overlay-center">
                                <div class="icon-play-btn">
                                    <i class="fa-solid fa-play"></i>
                                </div>
                            </div>
                        </div>
                        <div class="grid-card-footer">
                            <div>
                                <h4 class="grid-card-title"><?php echo htmlspecialchars($vItem['title']); ?></h4>
                                <span class="grid-card-cat"><?php echo htmlspecialchars($vItem['category']); ?></span>
                            </div>
                            <i class="fa-solid fa-play grid-card-arrow" style="font-size:0.85rem; color:#dc2626;"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Projects Gallery View -->
        <div id="projectsGalleryView" style="display: none;">
            <!-- Category Filter Tabs -->
            <div class="filter-pills-row">
                <button class="project-pill-btn active" onclick="filterProjectsCategory('all', this)">All Work</button>
                <button class="project-pill-btn" onclick="filterProjectsCategory('Commercials', this)">Commercials</button>
                <button class="project-pill-btn" onclick="filterProjectsCategory('Corporate', this)">Corporate</button>
                <button class="project-pill-btn" onclick="filterProjectsCategory('Events', this)">Events</button>
                <button class="project-pill-btn" onclick="filterProjectsCategory('Documentary', this)">Documentary</button>
                <button class="project-pill-btn" onclick="filterProjectsCategory('Social', this)">Social</button>
                <button class="project-pill-btn" onclick="filterProjectsCategory('Broadcast', this)">Broadcast</button>
                <button class="project-pill-btn" onclick="filterProjectsCategory('Wedding', this)">Wedding</button>
            </div>

            <!-- Projects 4-Column Grid -->
            <div class="projects-4col-grid" id="projectsGrid">
                <?php foreach ($portfolioProjects as $pItem): ?>
                    <div class="project-card" data-cat="<?php echo htmlspecialchars($pItem['category']); ?>" onclick="window.location.href='/portfolio-project.php?id=<?php echo $pItem['id']; ?>'">
                        <div class="project-card-image-wrap">
                            <img src="<?php echo htmlspecialchars($pItem['image']); ?>" alt="<?php echo htmlspecialchars($pItem['title']); ?>">
                            <div class="project-card-overlay">
                                <div class="project-card-top-row">
                                    <span class="project-cat-badge"><?php echo htmlspecialchars($pItem['category']); ?></span>
                                    <?php if (!empty($pItem['featured'])): ?>
                                        <div class="project-star-badge"><i class="fa-solid fa-star"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="project-card-bottom-info">
                                    <?php if (!empty($pItem['client'])): ?>
                                        <div class="project-client-name"><?php echo htmlspecialchars($pItem['client']); ?></div>
                                    <?php endif; ?>
                                    <h4 class="project-card-title"><?php echo htmlspecialchars($pItem['title']); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Footer Bar -->
        <div class="port-footer-bar">
            <div>© 2026 Falhen Media. All rights reserved.</div>
            <div class="port-footer-links">
                <a href="/">Home</a>
                <a href="/about.php">About</a>
                <a href="/services.php">Services</a>
                <a href="/portfolio.php">Portfolio</a>
                <a href="/careers.php">Careers</a>
                <a href="/contact.php">Contact</a>
            </div>
        </div>

    </main>

    <!-- Lightbox Modal (Photo) -->
    <div class="lightbox-modal-backdrop" id="lightboxModal">
        <div class="lightbox-modal-box">
            <button class="lightbox-close-btn" onclick="closeLightbox()">&times;</button>
            <div class="lightbox-img-wrap">
                <img id="lightboxImg" src="" alt="Album Preview">
            </div>
            <div class="lightbox-info-bar">
                <div class="lightbox-title-wrap">
                    <h3 id="lightboxTitle">Album Title</h3>
                    <p id="lightboxDesc">Album Description</p>
                </div>
                <a href="/index.php#lets-talk" class="btn-book-shoot" style="padding: 10px 18px; font-size:0.85rem;">
                    <i class="fa-regular fa-paper-plane"></i> Inquire Project
                </a>
            </div>
        </div>
    </div>

    <!-- Video Lightbox Modal -->
    <div class="video-modal-backdrop" id="videoModal">
        <div class="video-modal-box">
            <button class="lightbox-close-btn" onclick="closeVideoModal()">&times;</button>
            <div class="video-stage-wrap">
                <video id="videoModalPlayer" controls preload="metadata">
                    <source src="" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
            <div class="lightbox-info-bar">
                <div class="lightbox-title-wrap">
                    <h3 id="videoModalTitle">Video Title</h3>
                    <p id="videoModalDesc">Video Description</p>
                </div>
                <a href="/index.php#lets-talk" class="btn-book-shoot" style="padding: 10px 18px; font-size:0.85rem;">
                    <i class="fa-regular fa-calendar-check"></i> Book Video Shoot
                </a>
            </div>
        </div>
    </div>

    <!-- Interactive Scripts -->
    <script>
        const portfolioProjectsData = <?php echo json_encode($portfolioProjects); ?>;

        function switchGalleryType(type) {
            const btnPhoto = document.getElementById('btnTypePhoto');
            const btnVideo = document.getElementById('btnTypeVideo');
            const btnProject = document.getElementById('btnTypeProject');
            const photoView = document.getElementById('photoGalleryView');
            const videoView = document.getElementById('videoGalleryView');
            const projectView = document.getElementById('projectsGalleryView');
            const bookShootCard = document.getElementById('bookShootCard');

            if (btnPhoto) btnPhoto.classList.remove('active');
            if (btnVideo) btnVideo.classList.remove('active');
            if (btnProject) btnProject.classList.remove('active');

            if (photoView) photoView.style.display = 'none';
            if (videoView) videoView.style.display = 'none';
            if (projectView) projectView.style.display = 'none';

            if (type === 'video') {
                if (btnVideo) btnVideo.classList.add('active');
                if (videoView) videoView.style.display = 'block';
                if (bookShootCard) bookShootCard.style.display = 'flex';
            } else if (type === 'project' || type === 'all') {
                if (btnProject) btnProject.classList.add('active');
                if (projectView) projectView.style.display = 'block';
                if (bookShootCard) bookShootCard.style.display = 'flex';
            } else {
                if (btnPhoto) btnPhoto.classList.add('active');
                if (photoView) photoView.style.display = 'block';
                if (bookShootCard) bookShootCard.style.display = 'flex';
            }
        }

        function filterProjectsCategory(cat, btn) {
            const buttons = document.querySelectorAll('#projectsGalleryView .project-pill-btn, #projectsGalleryView .cat-pill-btn');
            buttons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const cards = document.querySelectorAll('#projectsGrid .project-card, #projectsGrid .grid-album-card');
            cards.forEach(card => {
                const cardCat = card.getAttribute('data-cat');
                if (
                    cat === 'all' || 
                    cardCat === cat || 
                    (cat === 'Events' && (cardCat === 'Event' || cardCat === 'Events')) ||
                    (cat === 'Commercials' && (cardCat === 'Commercial' || cardCat === 'Commercials'))
                ) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function openLightbox(id) {
            const album = albumsData[id];
            if (!album) return;

            document.getElementById('lightboxImg').src = album.image;
            document.getElementById('lightboxTitle').textContent = album.title;
            document.getElementById('lightboxDesc').textContent = `${album.category} • Client: ${album.client} — ${album.desc}`;
            
            const modal = document.getElementById('lightboxModal');
            modal.classList.add('active');
        }

        function closeLightbox() {
            document.getElementById('lightboxModal').classList.remove('active');
        }

        function extractYouTubeIdJS(urlOrId) {
            if (!urlOrId) return '';
            urlOrId = urlOrId.trim();
            if (/^[a-zA-Z0-9_-]{11}$/.test(urlOrId)) return urlOrId;
            const m = urlOrId.match(/(?:v=|\/embed\/|\/watch\?v=|youtu\.be\/|\/v\/)([a-zA-Z0-9_-]{11})/i);
            return m ? m[1] : '';
        }

        function openVideoModal(id) {
            const video = videosData[id];
            if (!video) return;

            const modal = document.getElementById('videoModal');
            const stageWrap = modal.querySelector('.video-stage-wrap');
            const titleEl = document.getElementById('videoModalTitle');
            const descEl = document.getElementById('videoModalDesc');

            const ytId = extractYouTubeIdJS(video.video_url || '');

            if (stageWrap) {
                if (ytId) {
                    stageWrap.innerHTML = `<iframe src="https://www.youtube.com/embed/${ytId}?autoplay=1&rel=0" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="width:100%; height:100%; min-height:400px; aspect-ratio:16/9; border-radius:12px; border:none;"></iframe>`;
                } else {
                    stageWrap.innerHTML = `<video id="videoModalPlayer" controls autoplay style="width:100%; height:100%; aspect-ratio:16/9; border-radius:12px;"><source src="${video.video_url}" type="video/mp4">Your browser does not support video.</video>`;
                }
            }

            if (titleEl) titleEl.textContent = video.title;
            if (descEl) descEl.textContent = `${video.category} • Client: ${video.client || 'Falhen'} (${video.duration || '0:00'})`;

            modal.classList.add('active');
        }

        function closeVideoModal() {
            const modal = document.getElementById('videoModal');
            const stageWrap = modal.querySelector('.video-stage-wrap');
            if (stageWrap) {
                stageWrap.innerHTML = '';
            }
            modal.classList.remove('active');
        }

        // Close modals on backdrop click
        document.getElementById('lightboxModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLightbox();
            }
        });
        document.getElementById('videoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVideoModal();
            }
        });

        // Initialize tab based on URL parameter (e.g., ?tab=all or ?type=all)
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab') || urlParams.get('type');
            if (tabParam === 'project' || tabParam === 'all') {
                switchGalleryType('project');
            } else if (tabParam === 'video') {
                switchGalleryType('video');
            }
    </script>

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
</body>
</html>
