<?php
// portfolio-photo.php - Single Photo Album View matching falhen.com modal overlay screenshot
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

$pageTitle = htmlspecialchars($currentAlbum['title']) . " — Photo Album | Falhen Media";

// Gallery Photos Array for the Album
$galleryPhotos = [
    1 => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=800&auto=format&fit=crop',
    2 => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=800&auto=format&fit=crop',
    3 => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=800&auto=format&fit=crop',
    4 => '/assets/img/portfolio/portfolio_halima.png',
    5 => '/assets/img/portfolio/portfolio_wedding.png',
    6 => '/assets/img/portfolio/portfolio_commercial.png',
    7 => '/assets/img/portfolio/portfolio_award.png',
    8 => '/assets/img/services/service_photo.png',
    9 => '/assets/img/services/service_creative.png'
];
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

        /* Header Image Box */
        .modal-image-header {
            position: relative;
            width: 100%;
            height: 380px;
            background: #000;
            overflow: hidden;
        }
        .modal-image-header img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.9);
        }
        .modal-header-gradient {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(3,3,5,0.4) 0%, rgba(14,14,18,1) 100%);
        }

        /* Top Bar Overlay Buttons inside image */
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

        /* Extra Photo Gallery Grid below modal body if photos present */
        .modal-gallery-sec {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
        .modal-gallery-heading {
            font-size: 1.1rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-photos-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        .modal-photo-item {
            aspect-ratio: 4/3;
            border-radius: 12px;
            overflow: hidden;
            background: #000;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .modal-photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .modal-photo-item:hover img { transform: scale(1.06); }

        /* Lightbox Modal */
        .lightbox-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.95);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }
        .lightbox-img {
            max-width: 90vw;
            max-height: 85vh;
            border-radius: 12px;
            object-fit: contain;
        }
        .lightbox-close {
            position: absolute;
            top: 24px;
            right: 32px;
            color: #fff;
            font-size: 2rem;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <!-- Full Modal Overlay Backdrop -->
    <div class="modal-page-backdrop">
        <div class="modal-card-box">
            
            <!-- Top Image Header with Overlay Buttons -->
            <div class="modal-image-header">
                <img src="<?php echo htmlspecialchars(getCloudinaryUrl($currentAlbum['image'] ?? $currentAlbum['cover'])); ?>" alt="<?php echo htmlspecialchars($currentAlbum['title']); ?>">
                <div class="modal-header-gradient"></div>
                
                <a href="/portfolio.php?tab=photo" class="modal-top-back-btn">
                    <i class="fa-solid fa-arrow-left"></i> Photos
                </a>

                <a href="/portfolio.php?tab=photo" class="modal-top-close-btn" title="Close">
                    &times;
                </a>
            </div>

            <!-- Modal Content Body -->
            <div class="modal-body-content">
                <!-- Badges Row -->
                <div class="modal-badges-row">
                    <span class="cat-pill-red"><?php echo htmlspecialchars($currentAlbum['category'] ?? 'Portrait'); ?></span>
                    <?php if (!empty($currentAlbum['featured'])): ?>
                        <span class="star-badge-gold"><i class="fa-solid fa-star"></i> Featured</span>
                    <?php endif; ?>
                </div>

                <!-- Main Title -->
                <h1 class="modal-item-title"><?php echo htmlspecialchars($currentAlbum['title']); ?></h1>

                <!-- Description Text -->
                <p class="modal-item-desc">
                    <?php echo !empty($currentAlbum['desc']) ? htmlspecialchars($currentAlbum['desc']) : 'High-resolution photo collection captured by Falhen Media. Explore the album gallery or access the full Google Drive folder below.'; ?>
                </p>

                <!-- Footer Info Bar -->
                <div class="modal-footer-bar">
                    <!-- Client Name -->
                    <?php if (!empty($currentAlbum['client'])): ?>
                        <div class="modal-client-info">
                            <i class="fa-solid fa-user"></i>
                            <span><?php echo htmlspecialchars($currentAlbum['client']); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="modal-client-info">
                            <i class="fa-solid fa-camera"></i>
                            <span>Falhen Media Shoot</span>
                        </div>
                    <?php endif; ?>

                    <!-- Tags / Action Buttons -->
                    <div class="modal-tags-group">
                        <span class="modal-tag-pill">Photography</span>
                        <span class="modal-tag-pill">Portrait Shoot</span>
                        <?php if (!empty($currentAlbum['gdrive_url'])): ?>
                            <a href="<?php echo htmlspecialchars($currentAlbum['gdrive_url']); ?>" target="_blank" rel="noopener noreferrer" class="modal-tag-pill" style="background: rgba(66,133,244,0.15); color: #60a5fa; border-color: rgba(66,133,244,0.3);">
                                <i class="fa-brands fa-google-drive"></i> Google Drive Album
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Photo Album Grid -->
                <div class="modal-gallery-sec">
                    <h4 class="modal-gallery-heading"><i class="fa-solid fa-images" style="color: #dc2626;"></i> Album Photos</h4>
                    <div class="modal-photos-grid">
                        <?php foreach ($galleryPhotos as $idx => $photoUrl): ?>
                            <div class="modal-photo-item" onclick="openPhotoLightbox('<?php echo htmlspecialchars($photoUrl); ?>')">
                                <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Photo <?php echo $idx; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Lightbox Modal -->
    <div id="photoLightboxModal" class="lightbox-modal" onclick="closePhotoLightbox()">
        <span class="lightbox-close">&times;</span>
        <img id="photoLightboxImg" src="" class="lightbox-img" onclick="event.stopPropagation()">
    </div>

    <script>
        function openPhotoLightbox(src) {
            document.getElementById('photoLightboxImg').src = src;
            document.getElementById('photoLightboxModal').style.display = 'flex';
        }
        function closePhotoLightbox() {
            document.getElementById('photoLightboxModal').style.display = 'none';
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
