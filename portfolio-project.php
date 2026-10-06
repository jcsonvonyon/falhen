<?php
// portfolio-project.php - Single Project / Production Case Study Modal Overlay View
require_once __DIR__ . '/includes/functions.php';

$masterPortfolioItems = getPortfolioRepo();
$projectsRepo = [];

foreach ($masterPortfolioItems as $item) {
    if (($item['media_type'] ?? 'photo') === 'project') {
        $projectsRepo[$item['id']] = $item;
    }
}

if (empty($projectsRepo)) {
    foreach ($masterPortfolioItems as $item) {
        $projectsRepo[$item['id']] = $item;
    }
}

$projectId = isset($_GET['id']) ? (int)$_GET['id'] : key($projectsRepo);
$currentProject = reset($projectsRepo);
if (isset($projectsRepo[$projectId])) {
    $currentProject = $projectsRepo[$projectId];
} else {
    foreach ($masterPortfolioItems as $mIt) {
        if ((int)$mIt['id'] === $projectId) {
            $currentProject = $mIt;
            break;
        }
    }
}

$pageTitle = htmlspecialchars($currentProject['title']) . " — Case Study | Falhen Media";
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
            font-weight: 700;
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
            font-weight: 700;
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

        /* Project Card Styling (matching reference) */
        .project-card {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            text-decoration: none;
        }
        .project-card:hover {
            border-color: rgba(220, 38, 38, 0.6);
            transform: translateY(-4px);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.6), 0 0 20px rgba(220, 38, 38, 0.15);
        }
        .project-card-image-wrap {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 10;
            background: #000000;
            overflow: hidden;
        }
        .project-card-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transform: scale(1.0);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .project-card:hover .project-card-image-wrap img {
            transform: scale(1.05);
        }
        .project-card-info {
            padding: 14px 16px 16px 16px;
            background: #0e0e12;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .project-card-cat {
            color: #ef4444;
            font-size: 0.82rem;
            font-weight: 600;
            line-height: 1.2;
            margin: 0;
            text-transform: capitalize;
            letter-spacing: 0.2px;
        }
        .project-card-title {
            font-size: 0.2rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
            line-height: 1.35;
            transition: color 0.2s ease;
        }
        .project-card:hover .project-card-title {
            color: #ff4d4d;
        }
    </style>
</head>
<body>

    <!-- Full Modal Overlay Backdrop -->
    <div class="modal-page-backdrop">
        <div class="modal-card-box">
            
            <!-- Top Image Header with Overlay Buttons -->
            <div class="modal-image-header">
                <img src="<?php echo htmlspecialchars(getCloudinaryUrl($currentProject['image'])); ?>" alt="<?php echo htmlspecialchars($currentProject['title']); ?>">
                <div class="modal-header-gradient"></div>
                
                <a href="/portfolio.php?tab=project" class="modal-top-back-btn">
                    <i class="fa-solid fa-arrow-left"></i> Projects
                </a>

                <a href="/portfolio.php?tab=project" class="modal-top-close-btn" title="Close">
                    &times;
                </a>
            </div>

            <!-- Modal Content Body -->
            <div class="modal-body-content">
                <!-- Badges Row -->
                <div class="modal-badges-row">
                    <span class="cat-pill-red"><?php echo htmlspecialchars($currentProject['category'] ?? 'Corporate'); ?></span>
                    <?php if (!empty($currentProject['featured'])): ?>
                        <span class="star-badge-gold"><i class="fa-solid fa-star"></i> Featured</span>
                    <?php endif; ?>
                </div>

                <!-- Main Title -->
                <h1 class="modal-item-title"><?php echo htmlspecialchars($currentProject['title']); ?></h1>

                <!-- Description Text -->
                <p class="modal-item-desc">
                    <?php echo !empty($currentProject['desc']) ? htmlspecialchars($currentProject['desc']) : 'Full-scale video production for a major technology conference featuring keynote speeches, panel discussions, and product launches with multi-camera setup and live streaming capabilities.'; ?>
                </p>

                <!-- Footer Info Bar -->
                <div class="modal-footer-bar">
                    <!-- Client Name -->
                    <?php if (!empty($currentProject['client'])): ?>
                        <div class="modal-client-info">
                            <i class="fa-solid fa-user"></i>
                            <span><?php echo htmlspecialchars($currentProject['client']); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="modal-client-info">
                            <i class="fa-solid fa-building"></i>
                            <span>Corporate Client</span>
                        </div>
                    <?php endif; ?>

                    <!-- Service Pills / Drive Link -->
                    <div class="modal-tags-group">
                        <?php 
                        $rawServices = !empty($currentProject['services']) ? $currentProject['services'] : 'Video Production, Live Streaming, Post Production';
                        $servicesList = array_map('trim', explode(',', $rawServices));
                        foreach ($servicesList as $sTag):
                            if (!empty($sTag)):
                        ?>
                            <span class="modal-tag-pill"><?php echo htmlspecialchars($sTag); ?></span>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                        <?php if (!empty($currentProject['gdrive_url'])): ?>
                            <a href="<?php echo htmlspecialchars($currentProject['gdrive_url']); ?>" target="_blank" rel="noopener noreferrer" class="modal-tag-pill" style="background: rgba(66,133,244,0.15); color: #60a5fa; border-color: rgba(66,133,244,0.3);">
                                <i class="fa-brands fa-google-drive"></i> Production Folder
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3-Column Project Meta Stats (matching reference) -->
                <div class="project-detail-meta-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 28px;">
                    <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 14px 18px;">
                        <div style="font-size: 0.72rem; font-weight: 700; color: #71717a; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 4px;">CLIENT</div>
                        <div style="font-size: 0.95rem; font-weight: 700; color: #ffffff;"><?php echo htmlspecialchars(!empty($currentProject['client']) ? $currentProject['client'] : 'Falhen Client'); ?></div>
                    </div>
                    <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 14px 18px;">
                        <div style="font-size: 0.72rem; font-weight: 700; color: #71717a; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 4px;">CATEGORY</div>
                        <div style="font-size: 0.95rem; font-weight: 700; color: #ffffff;"><?php echo htmlspecialchars(!empty($currentProject['category']) ? $currentProject['category'] : 'Corporate'); ?></div>
                    </div>
                    <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 14px 18px;">
                        <div style="font-size: 0.72rem; font-weight: 700; color: #71717a; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 4px;">YEAR</div>
                        <div style="font-size: 0.95rem; font-weight: 700; color: #ffffff;"><?php echo htmlspecialchars(!empty($currentProject['year']) ? $currentProject['year'] : (!empty($currentProject['project_date']) ? $currentProject['project_date'] : '2026')); ?></div>
                    </div>
                </div>

                <!-- CTA Banner (matching reference) -->
                <div style="margin-top: 24px; background: rgba(220, 38, 38, 0.08); border: 1px solid rgba(220, 38, 38, 0.2); border-radius: 16px; padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(220, 38, 38, 0.2); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                            <i class="fa-solid fa-star"></i>
                        </div>
                        <div>
                            <div style="font-size: 1.05rem; font-weight: 700; color: #ffffff;">Want something like this?</div>
                            <div style="font-size: 0.86rem; color: #a1a1aa;">Let's craft a story your audience remembers.</div>
                        </div>
                    </div>
                    <a href="/contact.php" style="background: #dc2626; color: #ffffff; text-decoration: none; font-size: 0.88rem; font-weight: 700; padding: 11px 22px; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4); transition: background 0.2s;" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                        <i class="fa-regular fa-calendar-check"></i> Book a Discovery Call
                    </a>
                </div>

                <!-- More Projects Section (matching reference) -->
                <?php
                $moreProjects = array_values(array_filter($projectsRepo, function($p) use ($projectId) {
                    return (int)($p['id'] ?? 0) !== $projectId;
                }));
                if (empty($moreProjects)) {
                    $moreProjects = array_values($projectsRepo);
                }
                ?>
                <?php if (!empty($moreProjects)): ?>
                    <div style="margin-top: 36px; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 28px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                            <h3 style="font-size: 1.25rem; font-weight: 700; color: #ffffff; margin: 0;">More Projects</h3>
                            <a href="/portfolio.php?tab=project" style="color: #ef4444; font-size: 0.86rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                All Projects <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px;">
                            <?php foreach (array_slice($moreProjects, 0, 3) as $mProj): ?>
                                <a href="/portfolio-project.php?id=<?php echo $mProj['id']; ?>" class="project-card">
                                    <div class="project-card-image-wrap">
                                        <img src="<?php echo htmlspecialchars(getCloudinaryUrl($mProj['image'] ?? '/assets/img/portfolio/portfolio_halima.png')); ?>" alt="<?php echo htmlspecialchars($mProj['title']); ?>" onerror="this.src='/assets/img/portfolio/portfolio_halima.png';">
                                    </div>
                                    <div class="project-card-info">
                                        <div class="project-card-cat"><?php echo htmlspecialchars($mProj['category'] ?? 'Events'); ?></div>
                                        <h4 class="project-card-title"><?php echo htmlspecialchars($mProj['title']); ?></h4>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
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
    <?php require_once __DIR__ . '/includes/cookie-banner.php'; ?>
    <script src="/assets/js/main.js"></script>
</body>
</html>
