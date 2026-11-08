<?php
// portfolio-project.php - Dedicated Project / Production Case Study Showcase matching readdy.ai reference
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
$currentProject = null;
if (isset($projectsRepo[$projectId])) {
    $currentProject = $projectsRepo[$projectId];
} else {
    foreach ($masterPortfolioItems as $mIt) {
        if ((int)$mIt['id'] === $projectId) {
            $currentProject = $mIt;
            break;
        }
    }
    if (!$currentProject) {
        $currentProject = reset($projectsRepo);
    }
}

$pageTitle = htmlspecialchars($currentProject['title']) . " — Case Study | Falhen Media";

// Fetch related/more projects (excluding current project)
$relatedProjects = [];
foreach ($projectsRepo as $relId => $relItem) {
    if ((int)($relItem['id'] ?? $relId) !== (int)$currentProject['id']) {
        $relatedProjects[] = $relItem;
    }
}
if (empty($relatedProjects)) {
    foreach ($masterPortfolioItems as $mIt) {
        if ((int)($mIt['id'] ?? 0) !== (int)$currentProject['id']) {
            $relatedProjects[] = $mIt;
        }
    }
}
$relatedProjects = array_slice($relatedProjects, 0, 3);
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
    
    <?php 
    $servicesRaw = !empty($currentProject['services']) 
        ? $currentProject['services'] 
        : (!empty($currentProject['keywords']) ? $currentProject['keywords'] : 'Video Production, Photography, AV Production, Content Strategy, live production, creative services, event design, event management');
    $cleanDesc = !empty($currentProject['desc']) ? strip_tags($currentProject['desc']) : 'Step inside ' . ($currentProject['title'] ?? 'Falhen Media') . ' — Production and creative direction showcase by Falhen Media.';
    $cleanDesc = mb_substr(preg_replace('/\s+/', ' ', $cleanDesc), 0, 160);
    ?>
    <meta name="description" content="<?php echo htmlspecialchars($cleanDesc); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($servicesRaw); ?>">

    <!-- Schema.org JSON-LD Structured Data (Fed by Services & Keywords) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "CreativeWork",
      "name": <?php echo json_encode($currentProject['title']); ?>,
      "headline": <?php echo json_encode($currentProject['title']); ?>,
      "description": <?php echo json_encode($cleanDesc); ?>,
      "image": <?php echo json_encode(getCloudinaryUrl($currentProject['image'] ?? '')); ?>,
      "creator": {
        "@type": "Organization",
        "name": "Falhen Media",
        "url": "https://falhen.com"
      },
      "provider": {
        "@type": "Organization",
        "name": <?php echo json_encode(!empty($currentProject['client']) ? $currentProject['client'] : "Falhen Media"); ?>
      },
      "keywords": <?php echo json_encode($servicesRaw); ?>,
      "genre": <?php echo json_encode($currentProject['category'] ?? "Events"); ?>,
      "dateCreated": <?php echo json_encode(!empty($currentProject['year']) ? (string)$currentProject['year'] : "2026"); ?>
    }
    </script>
    
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
            background-color: #000000;
            color: #ffffff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* Top Sticky Nav Bar (matching reference) */
        .project-nav-bar {
            padding: 14px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(0, 0, 0, 0.85);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        .project-nav-container {
            max-width: 1120px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-back-projects {
            color: #d4d4d8;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s ease;
        }

        .btn-back-projects:hover {
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
            transition: all 0.2s ease;
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
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.35);
            transition: all 0.2s ease;
        }

        .btn-nav-cta:hover {
            background: #ef4444;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
        }

        /* Hero Banner Section (Screenshot 1) */
        .project-hero-section {
            position: relative;
            width: 100%;
            min-height: 440px;
            background: #000000;
            display: flex;
            align-items: flex-end;
            overflow: hidden;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .project-hero-bg {
            position: absolute;
            inset: 0;
            z-index: 1;
        }

        .project-hero-bg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            filter: brightness(0.65);
        }

        .project-hero-gradient {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0.5) 0%, rgba(0,0,0,0.4) 40%, #000000 100%),
                        linear-gradient(90deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.3) 60%, rgba(0,0,0,0.7) 100%);
        }

        .project-hero-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1120px;
            margin: 0 auto;
            padding: 40px 24px 44px 24px;
        }

        /* Breadcrumb Navigation */
        .project-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            color: #71717a;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .project-breadcrumb a {
            color: #a1a1aa;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .project-breadcrumb a:hover {
            color: #ffffff;
        }

        .project-breadcrumb .bc-sep {
            color: #52525b;
            font-size: 0.76rem;
        }

        .project-breadcrumb .bc-current {
            color: #d4d4d8;
            font-weight: 500;
        }

        /* Category & Featured Badges */
        .project-badge-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .project-hero-cat {
            color: #ef4444;
            font-size: 0.88rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .project-hero-featured {
            background: rgba(234, 179, 8, 0.15);
            color: #facc15;
            border: 1px solid rgba(234, 179, 8, 0.35);
            font-size: 0.76rem;
            font-weight: 700;
            padding: 3px 12px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Big Title */
        .project-hero-title {
            font-size: 3rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 16px 0;
            line-height: 1.15;
            letter-spacing: -0.5px;
            max-width: 900px;
        }

        /* Meta Pills: Client & Year */
        .project-hero-meta {
            display: flex;
            align-items: center;
            gap: 18px;
            font-size: 0.92rem;
            color: #d4d4d8;
            flex-wrap: wrap;
        }

        .project-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-weight: 600;
        }

        .project-meta-pill i {
            color: #ef4444;
            font-size: 0.88rem;
        }

        /* Main Content Wrapper (Screenshot 2 & 3) */
        .project-content-main {
            max-width: 1120px;
            margin: 0 auto;
            padding: 44px 24px 80px 24px;
        }

        .project-section-heading {
            font-size: 1.55rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 16px 0;
            letter-spacing: -0.3px;
        }

        .project-description-text {
            color: #a1a1aa;
            font-size: 1.05rem;
            line-height: 1.75;
            margin: 0 0 36px 0;
            max-width: 860px;
        }

        /* Services Delivered Section (Screenshot 2) */
        .services-delivered-block {
            margin-bottom: 40px;
        }

        .services-delivered-title {
            font-size: 0.78rem;
            font-weight: 800;
            color: #71717a;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin: 0 0 16px 0;
        }

        .services-pills-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .service-pill-item {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 50px;
            padding: 8px 18px;
            font-size: 0.88rem;
            font-weight: 600;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .service-pill-item:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.28);
        }

        .service-pill-item i {
            color: #ef4444;
            font-size: 0.78rem;
        }

        /* 3-Column Metadata Cards Grid (Screenshot 2) */
        .project-meta-cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 40px;
        }

        .project-meta-card {
            background: #0d0d12;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 22px 24px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            transition: all 0.25s ease;
        }

        .project-meta-card:hover {
            border-color: rgba(255, 255, 255, 0.18);
            background: #111117;
        }

        .meta-card-icon {
            color: #ef4444;
            font-size: 1.15rem;
            margin-bottom: 8px;
        }

        .meta-card-label {
            font-size: 0.74rem;
            font-weight: 800;
            color: #71717a;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .meta-card-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.3;
        }

        /* CTA Banner (Screenshot 2) */
        .project-cta-card {
            background: rgba(220, 38, 38, 0.08);
            border: 1px solid rgba(220, 38, 38, 0.22);
            border-radius: 20px;
            padding: 26px 32px;
            margin-bottom: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .project-cta-left {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .project-cta-star {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(220, 38, 38, 0.2);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            border: 1px solid rgba(220, 38, 38, 0.35);
        }

        .project-cta-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 4px 0;
            line-height: 1.3;
        }

        .project-cta-subtitle {
            font-size: 0.92rem;
            color: #a1a1aa;
            margin: 0;
        }

        .btn-project-cta {
            background: #dc2626;
            color: #ffffff;
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 13px 26px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            box-shadow: 0 4px 18px rgba(220, 38, 38, 0.45);
            transition: all 0.2s ease;
        }

        .btn-project-cta:hover {
            background: #ef4444;
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(239, 68, 68, 0.65);
        }

        /* More Projects Section (Screenshot 3) */
        .more-projects-section {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 48px;
            margin-bottom: 40px;
        }

        .more-projects-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .more-projects-title {
            font-size: 1.55rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            letter-spacing: -0.3px;
        }

        .more-projects-link {
            color: #ef4444;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .more-projects-link:hover {
            color: #ff6b6b;
            gap: 9px;
        }

        .more-projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 22px;
            margin-bottom: 48px;
        }

        /* Project Card (matching reference with Category & Title BELOW image) */
        .project-card {
            background: #0d0d12;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
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
            background: #0d0d12;
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
            font-size: 0.9rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
            line-height: 1.35;
            transition: color 0.2s ease;
        }

        .project-card:hover .project-card-title {
            color: #ff4d4d;
        }

        /* Bottom "Back to All Projects" Button (Screenshot 3) */
        .back-all-projects-wrap {
            display: flex;
            justify-content: center;
            margin-top: 36px;
        }

        .btn-back-all-projects {
            background: #0c0c10;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 11px 24px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
        }

        .btn-back-all-projects:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.3);
            color: #ffffff;
        }

        /* Footer Bar */
        .project-footer-bar {
            max-width: 1120px;
            margin: 0 auto;
            padding: 24px 24px 40px 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.82rem;
            color: #71717a;
            flex-wrap: wrap;
            gap: 16px;
        }

        .project-footer-links {
            display: flex;
            gap: 20px;
        }

        .project-footer-links a {
            color: #71717a;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .project-footer-links a:hover {
            color: #ffffff;
        }

        /* Share Toast Notification */
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

        /* Responsive Breakpoints */
        @media (max-width: 900px) {
            .project-hero-title {
                font-size: 2.3rem;
            }
            .project-meta-cards-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
                margin-bottom: 28px;
            }
            .project-meta-card {
                padding: 14px 16px;
                gap: 4px;
            }
            .meta-card-icon {
                font-size: 0.95rem;
                margin-bottom: 4px;
            }
            .meta-card-label {
                font-size: 0.65rem;
            }
            .meta-card-value {
                font-size: 0.88rem;
            }
            .project-cta-card {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
                gap: 18px;
            }
            .project-cta-title {
                font-size: 1.05rem;
            }
            .project-cta-subtitle {
                font-size: 0.84rem;
            }
            .btn-project-cta {
                width: 100%;
                justify-content: center;
                font-size: 0.85rem;
                padding: 11px 20px;
            }
        }

        @media (max-width: 600px) {
            .project-hero-title {
                font-size: 1.85rem;
            }
            .project-nav-container {
                padding: 0 16px;
            }
            .nav-right-actions .btn-nav-share {
                padding: 7px 12px;
            }
            .nav-right-actions .btn-nav-cta {
                padding: 7px 14px;
                font-size: 0.8rem;
            }
            .project-meta-cards-grid {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 8px !important;
                margin-bottom: 24px;
            }
            .project-meta-card {
                padding: 10px 10px !important;
                border-radius: 12px;
                gap: 2px !important;
                min-width: 0;
            }
            .meta-card-icon {
                font-size: 0.85rem !important;
                margin-bottom: 2px !important;
            }
            .meta-card-label {
                font-size: 0.58rem !important;
                letter-spacing: 0.4px;
            }
            .meta-card-value {
                font-size: 0.76rem !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .project-cta-card {
                padding: 16px;
                gap: 14px;
                border-radius: 14px;
            }
            .project-cta-left {
                gap: 12px;
            }
            .project-cta-star {
                width: 38px;
                height: 38px;
                font-size: 0.95rem;
            }
            .project-cta-title {
                font-size: 0.92rem;
                margin: 0 0 2px 0;
            }
            .project-cta-subtitle {
                font-size: 0.76rem;
                line-height: 1.4;
            }
            .btn-project-cta {
                font-size: 0.78rem;
                padding: 9px 16px;
                border-radius: 9px;
                gap: 6px;
            }
            .more-projects-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 12px !important;
            }
            .more-projects-grid .project-card-info {
                padding: 8px 10px 10px 10px;
            }
            .more-projects-grid .project-card-cat {
                font-size: 0.68rem;
            }
            .more-projects-grid .project-card-title {
                font-size: 0.78rem !important;
                line-height: 1.25;
            }
        }
    </style>
</head>
<body>

    <!-- Top Sticky Header Navigation Bar -->
    <nav class="project-nav-bar">
        <div class="project-nav-container">
            <a href="/portfolio.php?tab=project" class="btn-back-projects">
                <i class="fa-solid fa-arrow-left"></i> All Projects
            </a>
            
            <a href="/" class="nav-center-logo">
                <img src="/assets/img/logo/falhen_logo_white.png" alt="Falhen Media" onerror="this.src='/assets/img/icons/favicon.png';">
            </a>

            <div class="nav-right-actions">
                <button type="button" class="btn-nav-share" onclick="shareCurrentProject()">
                    <i class="fa-solid fa-share-nodes"></i> Share
                </button>
                <a href="/contact.php" class="btn-nav-cta">
                    <i class="fa-regular fa-paper-plane"></i> Start a Project
                </a>
            </div>
        </div>
    </nav>

    <!-- Project Hero Banner Area (Screenshot 1) -->
    <section class="project-hero-section">
        <div class="project-hero-bg">
            <img src="<?php echo htmlspecialchars(getCloudinaryUrl($currentProject['image'] ?? 'https://res.cloudinary.com/pnabfi91/image/upload/v1791224271/falhen/portfolio/ysfriy8jlhhkcuzl8j16.jpg')); ?>" alt="<?php echo htmlspecialchars($currentProject['title']); ?>">
            <div class="project-hero-gradient"></div>
        </div>

        <div class="project-hero-container">
            <!-- Breadcrumbs -->
            <div class="project-breadcrumb">
                <a href="/">Home</a>
                <span class="bc-sep">/</span>
                <a href="/portfolio.php?tab=project">Portfolio</a>
                <span class="bc-sep">/</span>
                <span class="bc-current"><?php echo htmlspecialchars($currentProject['title']); ?></span>
            </div>

            <!-- Category & Featured Badges -->
            <div class="project-badge-row">
                <span class="project-hero-cat"><?php echo strtoupper(htmlspecialchars($currentProject['category'] ?? 'Events')); ?></span>
                <?php if (!empty($currentProject['featured'])): ?>
                    <span class="project-hero-featured"><i class="fa-solid fa-star"></i> Featured</span>
                <?php endif; ?>
            </div>

            <!-- Big Main Title -->
            <h1 class="project-hero-title"><?php echo htmlspecialchars($currentProject['title']); ?></h1>

            <!-- Meta: Client & Year with Red Icons -->
            <div class="project-hero-meta">
                <?php if (!empty($currentProject['client'])): ?>
                    <span class="project-meta-pill">
                        <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($currentProject['client']); ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($currentProject['year']) || !empty($currentProject['project_date'])): ?>
                    <span class="project-meta-pill">
                        <i class="fa-regular fa-calendar"></i> <?php echo htmlspecialchars(!empty($currentProject['year']) ? $currentProject['year'] : $currentProject['project_date']); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Main Project Content (Screenshot 2 & 3) -->
    <main class="project-content-main">
        <!-- About This Project -->
        <h2 class="project-section-heading">About This Project</h2>
        <p class="project-description-text">
            <?php 
            echo !empty($currentProject['desc']) 
                ? nl2br(htmlspecialchars($currentProject['desc'])) 
                : 'Step inside ' . htmlspecialchars($currentProject['title']) . '—MC Fantasy’s signature annual spectacle, shaped and elevated under the creative direction of Falhen for three straight years. Serving as the visual architect behind the event\'s atmosphere, Falhen blends cutting-edge cinematography, bespoke visual design, and seamless production management to capture the heart of the experience. The result is a vibrant fusion of music, elegance, and raw performances.'; 
            ?>
        </p>

        <!-- Services Delivered -->
        <div class="services-delivered-block">
            <h3 class="services-delivered-title">SERVICES DELIVERED</h3>
            <div class="services-pills-wrap">
                <?php 
                $servicesRaw = !empty($currentProject['services']) 
                    ? $currentProject['services'] 
                    : (!empty($currentProject['keywords']) ? $currentProject['keywords'] : 'Video Production, Photography, AV Production, Content Strategy, live production, creative services, event design, event management');
                $servicesArr = array_filter(array_map('trim', explode(',', $servicesRaw)));
                foreach ($servicesArr as $sName):
                ?>
                    <div class="service-pill-item">
                        <i class="fa-solid fa-check"></i>
                        <span><?php echo htmlspecialchars($sName); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 3-Column Metadata Cards -->
        <div class="project-meta-cards-grid">
            <div class="project-meta-card">
                <div class="meta-card-icon"><i class="fa-solid fa-user"></i></div>
                <div class="meta-card-label">CLIENT</div>
                <div class="meta-card-value"><?php echo htmlspecialchars(!empty($currentProject['client']) ? $currentProject['client'] : 'ASA Heart Foundation'); ?></div>
            </div>

            <div class="project-meta-card">
                <div class="meta-card-icon"><i class="fa-solid fa-tag"></i></div>
                <div class="meta-card-label">CATEGORY</div>
                <div class="meta-card-value"><?php echo htmlspecialchars(!empty($currentProject['category']) ? $currentProject['category'] : 'Events'); ?></div>
            </div>

            <div class="project-meta-card">
                <div class="meta-card-icon"><i class="fa-regular fa-calendar"></i></div>
                <div class="meta-card-label">YEAR</div>
                <div class="meta-card-value"><?php echo htmlspecialchars(!empty($currentProject['year']) ? $currentProject['year'] : (!empty($currentProject['project_date']) ? $currentProject['project_date'] : '2026')); ?></div>
            </div>
        </div>

        <!-- Want something like this? (CTA Card) -->
        <div class="project-cta-card">
            <div class="project-cta-left">
                <div class="project-cta-star">
                    <i class="fa-solid fa-star"></i>
                </div>
                <div>
                    <h3 class="project-cta-title">Want something like this?</h3>
                    <p class="project-cta-subtitle">Let's craft a story your audience remembers.</p>
                </div>
            </div>
            <a href="/contact.php" class="btn-project-cta">
                <i class="fa-regular fa-calendar-check"></i> Book a Discovery Call
            </a>
        </div>

        <!-- More Projects Section (Screenshot 3) -->
        <?php if (!empty($relatedProjects)): ?>
            <section class="more-projects-section">
                <div class="more-projects-header">
                    <h2 class="more-projects-title">More Projects</h2>
                    <a href="/portfolio.php?tab=project" class="more-projects-link">
                        All Projects <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                <div class="more-projects-grid">
                    <?php foreach ($relatedProjects as $relProj): ?>
                        <a href="/portfolio-project.php?id=<?php echo $relProj['id']; ?>" class="project-card">
                            <div class="project-card-image-wrap">
                                <img src="<?php echo htmlspecialchars(getCloudinaryUrl($relProj['image'] ?? 'https://res.cloudinary.com/pnabfi91/image/upload/v1791224271/falhen/portfolio/ysfriy8jlhhkcuzl8j16.jpg')); ?>" alt="<?php echo htmlspecialchars($relProj['title']); ?>" onerror="this.src='https://res.cloudinary.com/pnabfi91/image/upload/v1791224271/falhen/portfolio/ysfriy8jlhhkcuzl8j16.jpg';">
                            </div>
                            <div class="project-card-info">
                                <div class="project-card-cat"><?php echo htmlspecialchars($relProj['category'] ?? 'Corporate'); ?></div>
                                <h4 class="project-card-title"><?php echo htmlspecialchars($relProj['title']); ?></h4>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Bottom "Back to All Projects" Button (Screenshot 3) -->
                <div class="back-all-projects-wrap">
                    <a href="/portfolio.php?tab=project" class="btn-back-all-projects">
                        <i class="fa-solid fa-arrow-left"></i> Back to All Projects
                    </a>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- Footer Bar -->
    <footer class="project-footer-bar">
        <div>&copy; <?php echo date('Y'); ?> Falhen Media. All rights reserved.</div>
        <div class="project-footer-links">
            <a href="/">Home</a>
            <a href="/about.php">About</a>
            <a href="/services.php">Services</a>
            <a href="/portfolio.php">Portfolio</a>
            <a href="/careers.php">Careers</a>
            <a href="/contact.php">Contact</a>
        </div>
    </footer>

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
        function shareCurrentProject() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(window.location.href).then(showToast).catch(function() {
                    fallbackShare();
                });
            } else {
                fallbackShare();
            }
        }

        function fallbackShare() {
            var temp = document.createElement('input');
            document.body.appendChild(temp);
            temp.value = window.location.href;
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            showToast();
        }

        function showToast() {
            var toast = document.getElementById('shareToast');
            if (toast) {
                toast.classList.add('show');
                setTimeout(function() {
                    toast.classList.remove('show');
                }, 3000);
            }
        }
    </script>
</body>
</html>
