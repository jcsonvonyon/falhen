<?php
// portfolio-video.php - Single Video Reel Modal Overlay View
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
$currentVideo = reset($videosRepo);
if (isset($videosRepo[$videoId])) {
    $currentVideo = $videosRepo[$videoId];
} else {
    foreach ($masterPortfolioItems as $mIt) {
        if ((int)$mIt['id'] === $videoId) {
            $currentVideo = $mIt;
            break;
        }
    }
}

$pageTitle = htmlspecialchars($currentVideo['title']) . " — Video Reel | Falhen Media";

// YouTube Video ID extraction
$videoUrl = $currentVideo['video_url'] ?? '';
$youtubeId = extractYouTubeId($videoUrl);
if (empty($youtubeId)) {
    $youtubeId = 'ySus5ZS0b94';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
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
        body {
            background-color: #030305;
            color: #ffffff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* Full Overlay Backdrop */
        .modal-page-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.88);
            backdrop-filter: blur(12px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-y: auto;
        }

        /* Modal Overlay Card matching screenshot */
        .modal-card-box {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            width: 100%;
            max-width: 860px;
            overflow: hidden;
            box-shadow: 0 30px 90px rgba(0, 0, 0, 0.9);
            position: relative;
            margin: auto;
            animation: modalFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.96) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Header Video Stage */
        .modal-image-header {
            position: relative;
            width: 100%;
            aspect-ratio: 16/9;
            background: #000;
            overflow: hidden;
        }
        .modal-image-header iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Top Bar Overlay Buttons inside header */
        .modal-top-back-btn {
            position: absolute;
            top: 20px;
            left: 24px;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            color: #ffffff;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            z-index: 5;
        }
        .modal-top-back-btn:hover {
            background: rgba(220, 38, 38, 0.9);
            border-color: #dc2626;
            color: #ffffff;
        }

        .modal-top-close-btn {
            position: absolute;
            top: 20px;
            right: 24px;
            width: 38px;
            height: 38px;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            color: #ffffff;
            text-decoration: none;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 700;
            transition: all 0.2s ease;
            z-index: 5;
            cursor: pointer;
        }
        .modal-top-close-btn:hover {
            background: #dc2626;
            border-color: #dc2626;
        }

        /* Modal Body Content */
        .modal-body-content {
            padding: 32px 36px 36px;
            position: relative;
        }

        .modal-badges-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .cat-pill-red {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.76rem;
            font-weight: 800;
            padding: 5px 14px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .star-badge-gold {
            background: rgba(234, 179, 8, 0.15);
            color: #facc15;
            border: 1px solid rgba(234, 179, 8, 0.3);
            font-size: 0.76rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .modal-item-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 16px 0;
            line-height: 1.2;
        }

        .modal-item-desc {
            color: #a1a1aa;
            font-size: 1rem;
            line-height: 1.65;
            margin: 0 0 28px 0;
        }

        /* Bottom Footer Info Bar */
        .modal-footer-bar {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .modal-client-info {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 700;
        }
        .modal-client-info i { color: #dc2626; font-size: 0.85rem; }

        .modal-tags-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .modal-tag-pill {
            background: rgba(255, 255, 255, 0.06);
            color: #d4d4d8;
            border: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.78rem;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .modal-tag-pill:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }
    </style>
</head>
<body>

    <!-- Full Modal Overlay Backdrop -->
    <div class="modal-page-backdrop">
        <div class="modal-card-box">
            
            <!-- Header Video Stage with Overlay Buttons -->
            <div class="modal-image-header">
                <iframe 
                    src="https://www.youtube-nocookie.com/embed/<?php echo htmlspecialchars($youtubeId); ?>?autoplay=1&rel=0&modestbranding=1" 
                    title="<?php echo htmlspecialchars($currentVideo['title']); ?>" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
                </iframe>
                
                <a href="/portfolio.php?tab=video" class="modal-top-back-btn">
                    <i class="fa-solid fa-arrow-left"></i> Videos
                </a>

                <a href="/portfolio.php?tab=video" class="modal-top-close-btn" title="Close">
                    &times;
                </a>
            </div>

            <!-- Modal Content Body -->
            <div class="modal-body-content">
                <!-- Badges Row -->
                <div class="modal-badges-row">
                    <span class="cat-pill-red"><?php echo htmlspecialchars($currentVideo['category'] ?? 'Video Reel'); ?></span>
                    <?php if (!empty($currentVideo['featured'])): ?>
                        <span class="star-badge-gold"><i class="fa-solid fa-star"></i> Featured</span>
                    <?php endif; ?>
                </div>

                <!-- Main Title -->
                <h1 class="modal-item-title"><?php echo htmlspecialchars($currentVideo['title']); ?></h1>

                <!-- Description Text -->
                <p class="modal-item-desc">
                    <?php echo !empty($currentVideo['desc']) ? htmlspecialchars($currentVideo['desc']) : 'High-definition video reel produced and directed by Falhen Media. Crafted with cinema-grade cinematography, color grading, and sound design.'; ?>
                </p>

                <!-- Footer Info Bar -->
                <div class="modal-footer-bar">
                    <!-- Client Name -->
                    <?php if (!empty($currentVideo['client'])): ?>
                        <div class="modal-client-info">
                            <i class="fa-solid fa-user"></i>
                            <span><?php echo htmlspecialchars($currentVideo['client']); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="modal-client-info">
                            <i class="fa-solid fa-film"></i>
                            <span>Falhen Media Video Production</span>
                        </div>
                    <?php endif; ?>

                    <!-- Service Pills -->
                    <div class="modal-tags-group">
                        <span class="modal-tag-pill">Cinematography</span>
                        <span class="modal-tag-pill">Editing</span>
                        <span class="modal-tag-pill">Color Grading</span>
                    </div>
                </div>
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
</body>
</html>
