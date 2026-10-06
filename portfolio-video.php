<?php
// portfolio-video.php - Dedicated Video Reel Detail Showcase matching readdy.ai reference
require_once __DIR__ . '/includes/functions.php';

$masterPortfolioItems = getPortfolioRepo();
$videosRepo = [];

foreach ($masterPortfolioItems as $item) {
    if (($item['media_type'] ?? 'photo') === 'video') {
        $videosRepo[$item['id']] = $item;
    }
}

if (empty($videosRepo)) {
    foreach ($masterPortfolioItems as $item) {
        $videosRepo[$item['id']] = $item;
    }
}

$videoId = isset($_GET['id']) ? (int)$_GET['id'] : key($videosRepo);
$currentVideo = null;
if (isset($videosRepo[$videoId])) {
    $currentVideo = $videosRepo[$videoId];
} else {
    foreach ($masterPortfolioItems as $mIt) {
        if ((int)$mIt['id'] === $videoId) {
            $currentVideo = $mIt;
            break;
        }
    }
    if (!$currentVideo) {
        $currentVideo = reset($videosRepo);
    }
}

$pageTitle = htmlspecialchars($currentVideo['title']) . " — Video Reel | Falhen Media";

// YouTube Video ID extraction helper
$videoUrl = $currentVideo['video_url'] ?? '';
$youtubeId = extractYouTubeId($videoUrl);
if (empty($youtubeId) && strpos($videoUrl, '.mp4') === false) {
    $youtubeId = 'ySus5ZS0b94';
}

// Fetch related/more videos (excluding the current one)
$relatedVideos = [];
foreach ($videosRepo as $relId => $relItem) {
    if ((int)$relId !== (int)$currentVideo['id']) {
        $relatedVideos[] = $relItem;
    }
}
$relatedVideos = array_slice($relatedVideos, 0, 3);
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
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Main Style -->
    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background-color: #030305;
            color: #ffffff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* Top Sub-Nav Bar */
        .video-nav-bar {
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #030305;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(12px);
        }

        .video-nav-container {
            max-width: 1060px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-back-videos {
            color: #d4d4d8;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .btn-back-videos:hover {
            color: #ffffff;
        }

        .nav-center-logo img {
            height: 26px;
            width: auto;
            object-fit: contain;
            display: block;
        }

        .nav-right-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-nav-share {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 9px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all 0.25s ease;
        }

        .btn-nav-share:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-nav-cta {
            background: #dc2626;
            color: #ffffff;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 8px 18px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
            transition: all 0.25s ease;
        }

        .btn-nav-cta:hover {
            background: #ef4444;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.6);
        }

        /* Main Content Layout */
        .video-detail-main {
            max-width: 1060px;
            margin: 0 auto;
            padding: 14px 20px 70px 20px;
        }

        /* Breadcrumb Navigation */
        .video-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            color: #71717a;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .video-breadcrumb a {
            color: #a1a1aa;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .video-breadcrumb a:hover {
            color: #ffffff;
        }

        .video-breadcrumb .bc-sep {
            color: #52525b;
            font-size: 0.76rem;
        }

        .video-breadcrumb .bc-current {
            color: #e4e4e7;
            font-weight: 600;
        }

        /* Hero Video Player Stage - Fits Container Width & Screen Height */
        .video-player-stage {
            width: 100%;
            max-width: 100%;
            height: clamp(410px, calc(100vh - 110px), 605px);
            background: #000000;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin: 0 0 28px 0;
        }

        @media (max-height: 750px) {
            .video-player-stage {
                height: clamp(385px, calc(100vh - 105px), 555px);
                margin-bottom: 20px;
            }
        }

        @media (max-height: 620px) {
            .video-player-stage {
                height: clamp(340px, calc(100vh - 85px), 460px);
                margin-bottom: 16px;
            }
        }

        .video-poster-box {
            position: absolute;
            inset: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .video-poster-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }

        .video-poster-box:hover .video-poster-img {
            transform: scale(1.02);
        }

        .video-stage-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.2) 0%, rgba(0, 0, 0, 0.1) 40%, rgba(0, 0, 0, 0.6) 100%);
            transition: background 0.3s ease;
        }

        .video-poster-box:hover .video-stage-overlay {
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.25) 0%, rgba(0, 0, 0, 0.15) 40%, rgba(0, 0, 0, 0.7) 100%);
        }

        .video-stage-play-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(1);
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: #dc2626;
            color: #ffffff;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            cursor: pointer;
            box-shadow: 0 10px 35px rgba(220, 38, 38, 0.65);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            padding-left: 4px;
            z-index: 3;
        }

        .video-poster-box:hover .video-stage-play-btn {
            transform: translate(-50%, -50%) scale(1.1);
            background: #ef4444;
            box-shadow: 0 12px 40px rgba(239, 68, 68, 0.85);
        }

        .video-stage-duration-pill {
            position: absolute;
            bottom: 16px;
            right: 18px;
            background: rgba(0, 0, 0, 0.82);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #ffffff;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            backdrop-filter: blur(8px);
            z-index: 3;
        }

        .video-active-player {
            width: 100%;
            height: 100%;
            background: #000000;
        }

        .video-active-player iframe,
        .video-active-player video {
            width: 100%;
            height: 100%;
            display: block;
            border: none;
            outline: none;
        }

        /* Video Info Content Block */
        .video-info-content {
            width: 100%;
            max-width: 100%;
        }

        .video-meta-tags-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .video-cat-badge {
            color: #ef4444;
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .video-featured-badge {
            color: #facc15;
            font-size: 0.82rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .video-main-heading {
            font-size: 2.3rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
            margin: 0 0 14px 0;
            line-height: 1.2;
        }

        .video-meta-details-row {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .video-meta-item {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #d4d4d8;
            font-size: 0.92rem;
            font-weight: 500;
        }

        .video-meta-item i {
            color: #ef4444;
            font-size: 0.88rem;
        }

        .video-full-desc {
            color: #a1a1aa;
            font-size: 1.05rem;
            line-height: 1.75;
            margin: 0 0 32px 0;
            max-width: 860px;
        }

        /* Action Buttons */
        .video-actions-row {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 50px;
        }

        .btn-video-book {
            background: #dc2626;
            color: #ffffff;
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 12px 26px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            box-shadow: 0 4px 18px rgba(220, 38, 38, 0.45);
            transition: all 0.25s ease;
        }

        .btn-video-book:hover {
            background: #ef4444;
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(239, 68, 68, 0.65);
        }

        .btn-video-share-action {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #ffffff;
            font-family: inherit;
            font-size: 0.92rem;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
        }

        .btn-video-share-action:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.35);
        }

        /* Section Divider */
        .video-section-divider {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            margin: 0 0 44px 0;
        }

        /* More Videos Section */
        .more-videos-section {
            margin-top: 20px;
        }

        .more-videos-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .more-videos-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
        }

        .more-videos-link {
            color: #ef4444;
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: gap 0.2s ease, color 0.2s ease;
        }

        .more-videos-link:hover {
            color: #f87171;
            gap: 10px;
        }

        .more-videos-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        @media (max-width: 900px) {
            .more-videos-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .more-videos-grid {
                grid-template-columns: 1fr;
            }
            .video-main-heading {
                font-size: 1.8rem;
            }
        }

        .more-video-card {
            background: #0c0c10;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
        }

        .more-video-card:hover {
            border-color: rgba(220, 38, 38, 0.5);
            transform: translateY(-4px);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.6), 0 0 20px rgba(220, 38, 38, 0.15);
        }

        .more-video-thumb {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #000000;
            overflow: hidden;
        }

        .more-video-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }

        .more-video-card:hover .more-video-thumb img {
            transform: scale(1.05);
        }

        .more-video-duration {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.82);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
            z-index: 2;
        }

        .more-video-play-wrap {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        .more-play-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #dc2626;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            box-shadow: 0 4px 18px rgba(220, 38, 38, 0.7);
            padding-left: 2px;
            transition: all 0.3s ease;
        }

        .more-video-card:hover .more-play-circle {
            transform: scale(1.08);
            background: #ef4444;
            box-shadow: 0 6px 24px rgba(239, 68, 68, 0.9);
        }

        .more-video-info {
            padding: 14px 16px 18px 16px;
            background: #0c0c10;
        }

        .more-video-card-title {
            font-size: 0.98rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 4px 0;
            line-height: 1.3;
            transition: color 0.25s ease;
        }

        .more-video-card:hover .more-video-card-title {
            color: #ff4d4d;
        }

        .more-video-card-client {
            font-size: 0.82rem;
            color: #a1a1aa;
            font-weight: 500;
            margin: 0;
        }

        /* Toast Feedback */
        .share-toast-notification {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #18181b;
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 9999;
        }

        .share-toast-notification.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
            visibility: visible;
        }

        .share-toast-notification i {
            color: #22c55e;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>

    <!-- Top Sub-Nav Bar -->
    <header class="video-nav-bar">
        <div class="video-nav-container">
            <a href="/portfolio.php?tab=video" class="btn-back-videos">
                <i class="fa-solid fa-arrow-left"></i> All Videos
            </a>
            <a href="/" class="nav-center-logo">
                <img src="/assets/img/icons/logo.png" alt="Falhen Media">
            </a>
            <div class="nav-right-actions">
                <button type="button" class="btn-nav-share" onclick="shareCurrentPage()">
                    <i class="fa-solid fa-share-nodes"></i> Share
                </button>
                <a href="/contact.php" class="btn-nav-cta">
                    <i class="fa-regular fa-paper-plane"></i> Start a Project
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="video-detail-main">

        <!-- Breadcrumb Navigation -->
        <nav class="video-breadcrumb">
            <a href="/">Home</a>
            <span class="bc-sep">&gt;</span>
            <a href="/portfolio.php?tab=video">Videos</a>
            <span class="bc-sep">&gt;</span>
            <span class="bc-current"><?php echo htmlspecialchars($currentVideo['title']); ?></span>
        </nav>

        <!-- Hero Video Player Stage (16:9 Widescreen) -->
        <div class="video-player-stage" id="videoStage">
            <div class="video-poster-box" id="videoPosterBox" onclick="playCurrentVideo()">
                <img src="<?php echo htmlspecialchars($currentVideo['image']); ?>" alt="<?php echo htmlspecialchars($currentVideo['title']); ?>" class="video-poster-img">
                <div class="video-stage-overlay"></div>
                <button type="button" class="video-stage-play-btn" aria-label="Play Video">
                    <i class="fa-solid fa-play"></i>
                </button>
                <?php if (!empty($currentVideo['duration'])): ?>
                    <div class="video-stage-duration-pill">
                        <?php echo htmlspecialchars($currentVideo['duration']); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="video-active-player" id="videoActivePlayer" style="display: none;"></div>
        </div>

        <!-- Video Details Section -->
        <div class="video-info-content">
            <!-- Badges Row -->
            <div class="video-meta-tags-row">
                <span class="video-cat-badge"><?php echo htmlspecialchars(strtoupper($currentVideo['category'] ?? 'VIDEO')); ?></span>
                <?php if (!empty($currentVideo['featured'])): ?>
                    <span class="video-featured-badge"><i class="fa-solid fa-star"></i> Featured</span>
                <?php endif; ?>
            </div>

            <!-- Main Video Title -->
            <h1 class="video-main-heading"><?php echo htmlspecialchars($currentVideo['title']); ?></h1>

            <!-- Metadata Info Row -->
            <div class="video-meta-details-row">
                <?php if (!empty($currentVideo['client'])): ?>
                    <div class="video-meta-item">
                        <i class="fa-regular fa-user"></i>
                        <span><?php echo htmlspecialchars($currentVideo['client']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($currentVideo['duration'])): ?>
                    <div class="video-meta-item">
                        <i class="fa-regular fa-clock"></i>
                        <span><?php echo htmlspecialchars($currentVideo['duration']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($currentVideo['location'])): ?>
                    <div class="video-meta-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <span><?php echo htmlspecialchars($currentVideo['location']); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Video Description -->
            <p class="video-full-desc">
                <?php echo !empty($currentVideo['desc']) 
                    ? htmlspecialchars($currentVideo['desc']) 
                    : 'A high-impact video production produced by Falhen Media. This piece blends cinematic storytelling with sharp editing and color grading crafted to hold attention from the first frame to the last.'; ?>
            </p>

            <!-- Action Buttons -->
            <div class="video-actions-row">
                <a href="/contact.php" class="btn-video-book">
                    <i class="fa-regular fa-calendar-check"></i> Book a Discovery Call
                </a>
                <button type="button" class="btn-video-share-action" onclick="shareCurrentPage()">
                    <i class="fa-solid fa-share-nodes"></i> Share Video
                </button>
            </div>

            <!-- Divider -->
            <hr class="video-section-divider">

            <!-- More Videos Section -->
            <?php if (!empty($relatedVideos)): ?>
                <section class="more-videos-section">
                    <div class="more-videos-header">
                        <h2 class="more-videos-title">More Videos</h2>
                        <a href="/portfolio.php?tab=video" class="more-videos-link">
                            All Videos <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="more-videos-grid">
                        <?php foreach ($relatedVideos as $rel): ?>
                            <a href="/portfolio-video.php?id=<?php echo $rel['id']; ?>" class="more-video-card">
                                <div class="more-video-thumb">
                                    <img src="<?php echo htmlspecialchars($rel['image']); ?>" alt="<?php echo htmlspecialchars($rel['title']); ?>" loading="lazy">
                                    <?php if (!empty($rel['duration'])): ?>
                                        <div class="more-video-duration"><?php echo htmlspecialchars($rel['duration']); ?></div>
                                    <?php endif; ?>
                                    <div class="more-video-play-wrap">
                                        <div class="more-play-circle">
                                            <i class="fa-solid fa-play"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="more-video-info">
                                    <h4 class="more-video-card-title"><?php echo htmlspecialchars($rel['title']); ?></h4>
                                    <div class="more-video-card-client"><?php echo htmlspecialchars(!empty($rel['client']) ? $rel['client'] : $rel['category']); ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>

    </main>

    <!-- Toast Notification for Share Link -->
    <div id="shareToast" class="share-toast-notification">
        <i class="fa-solid fa-circle-check"></i> Link copied to clipboard!
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

    <script>
        function playCurrentVideo() {
            const posterBox = document.getElementById('videoPosterBox');
            const activePlayer = document.getElementById('videoActivePlayer');
            const videoUrl = <?php echo json_encode($currentVideo['video_url'] ?? ''); ?>;
            const ytId = <?php echo json_encode($youtubeId); ?>;

            if (!activePlayer || !posterBox) return;

            if (videoUrl && (videoUrl.includes('.mp4') || videoUrl.includes('.webm') || videoUrl.includes('.ogg'))) {
                activePlayer.innerHTML = `<video controls autoplay playsinline style="width:100%; height:100%; object-fit:contain; background:#000;"><source src="${videoUrl}" type="video/mp4">Your browser does not support HTML5 video.</video>`;
            } else if (ytId) {
                activePlayer.innerHTML = `<iframe src="https://www.youtube-nocookie.com/embed/${ytId}?autoplay=1&rel=0&modestbranding=1" title="Video Player" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="width:100%; height:100%; border:none;"></iframe>`;
            } else if (videoUrl) {
                activePlayer.innerHTML = `<video controls autoplay playsinline style="width:100%; height:100%; object-fit:contain; background:#000;"><source src="${videoUrl}" type="video/mp4">Your browser does not support HTML5 video.</video>`;
            } else {
                activePlayer.innerHTML = `<iframe src="https://www.youtube-nocookie.com/embed/ySus5ZS0b94?autoplay=1&rel=0" title="Video Player" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="width:100%; height:100%; border:none;"></iframe>`;
            }

            posterBox.style.display = 'none';
            activePlayer.style.display = 'block';
        }

        function shareCurrentPage() {
            const url = window.location.href;
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    url: url
                }).catch(() => {});
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(showToast);
            } else {
                showToast();
            }
        }

        function showToast() {
            const toast = document.getElementById('shareToast');
            if (toast) {
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 2500);
            }
        }
    </script>
    <script src="/assets/js/main.js"></script>
</body>
</html>
