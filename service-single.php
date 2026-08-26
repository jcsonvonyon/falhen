<?php
// service-single.php - Dynamic Service details view
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$requestedSlug = isset($_GET['slug']) ? trim($_GET['slug']) : (isset($_GET['service']) ? trim($_GET['service']) : 'video-production');

// Master Services Repository dynamically fetched from Admin Settings
$servicesRepo = getServicesRepo();
$service = $servicesRepo[$requestedSlug] ?? (reset($servicesRepo) ?: []);
$pageTitle = $service['title'] . " — Services | Falhen Media";

// Other Services for explore section (excluding current)
$otherServices = array_filter($servicesRepo, function($item) use ($service) {
    return $item['slug'] !== $service['slug'];
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
            line-height: 1.6;
        }

        /* Nav Bar */
        .service-nav-bar {
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #030305;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .service-nav-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .service-back-link {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .service-back-link:hover {
            color: #ffffff;
        }

        .service-logo-center img {
            height: 28px;
            width: auto;
        }

        .btn-service-contact {
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

        .btn-service-contact:hover {
            background: #ef4444;
            transform: translateY(-1px);
        }

        /* Container */
        .service-main-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 30px 24px 80px 24px;
        }

        /* Breadcrumb */
        .service-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.86rem;
            color: #71717a;
            margin-bottom: 24px;
            font-weight: 500;
        }

        .service-breadcrumb a {
            color: #a1a1aa;
            text-decoration: none;
            transition: color 0.25s ease;
        }

        .service-breadcrumb a:hover {
            color: #ffffff;
        }

        .service-breadcrumb span.sep {
            color: #52525b;
            font-size: 0.75rem;
        }

        .service-breadcrumb span.current {
            color: #d4d4d8;
            font-weight: 600;
        }

        /* Hero Stage */
        .service-hero-stage {
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            background: linear-gradient(130deg, rgba(20, 20, 28, 0.95) 0%, rgba(10, 10, 14, 0.98) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 50px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 380px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
        }

        .service-hero-left {
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            z-index: 2;
        }

        .service-pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #dc2626;
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 800;
            padding: 6px 14px;
            border-radius: 50px;
            width: fit-content;
            margin-bottom: 20px;
            letter-spacing: 0.3px;
        }

        .service-hero-title {
            font-size: 3.2rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 16px 0;
            line-height: 1.1;
            letter-spacing: -1px;
        }

        .service-hero-desc {
            font-size: 1.05rem;
            color: #a1a1aa;
            margin: 0;
            line-height: 1.6;
            font-weight: 400;
        }

        .service-hero-right {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .service-hero-right img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .service-hero-right::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(10, 10, 14, 1) 0%, rgba(10, 10, 14, 0.4) 40%, transparent 100%);
            z-index: 1;
        }

        /* 2-Column Main Content Layout */
        .service-content-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 40px;
            margin-bottom: 70px;
        }

        .service-section-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 20px 0;
            letter-spacing: -0.5px;
        }

        .service-about-paragraph {
            font-size: 1.02rem;
            color: #a1a1aa;
            line-height: 1.75;
            margin-bottom: 50px;
        }

        /* What's Included Grid */
        .included-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 50px;
        }

        .included-item-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.3s ease;
        }

        .included-item-card:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(220, 38, 38, 0.4);
            transform: translateY(-2px);
        }

        .included-check-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            flex-shrink: 0;
        }

        .included-text {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
        }

        /* Specialisations Pills */
        .spec-pills-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .spec-pill {
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #ff4d4d;
            font-size: 0.88rem;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 50px;
            transition: all 0.25s ease;
        }

        .spec-pill:hover {
            background: rgba(220, 38, 38, 0.2);
            border-color: rgba(220, 38, 38, 0.5);
            color: #ffffff;
        }

        /* Right Sidebar Cards */
        .sidebar-sticky-wrap {
            display: flex;
            flex-direction: column;
            gap: 24px;
            position: sticky;
            top: 90px;
        }

        /* Action Card ("Ready to get started?") */
        .ready-action-card {
            background: linear-gradient(135deg, rgba(20, 20, 26, 0.95) 0%, rgba(12, 12, 16, 0.95) 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            padding: 30px 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        }

        .ready-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #dc2626;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.5);
        }

        .ready-card-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 10px 0;
            line-height: 1.2;
        }

        .ready-card-desc {
            font-size: 0.88rem;
            color: #a1a1aa;
            margin: 0 0 24px 0;
            line-height: 1.5;
        }

        .btn-action-primary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #dc2626;
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 18px rgba(220, 38, 38, 0.4);
            margin-bottom: 12px;
            width: 100%;
            box-sizing: border-box;
        }

        .btn-action-primary:hover {
            background: #ef4444;
            transform: translateY(-2px);
        }

        .btn-action-secondary {
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #a1a1aa;
            font-size: 0.88rem;
            font-weight: 600;
            padding: 11px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.25s ease;
            width: 100%;
            box-sizing: border-box;
        }

        .btn-action-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        /* Stats Card ("BY THE NUMBERS") */
        .stats-sidebar-card {
            background: linear-gradient(135deg, rgba(20, 20, 26, 0.95) 0%, rgba(12, 12, 16, 0.95) 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        }

        .stats-header-label {
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 1px;
            color: #71717a;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .stats-rows-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .stats-row-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.88rem;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .stats-row-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .stats-item-label {
            color: #a1a1aa;
            font-weight: 500;
        }

        .stats-item-value {
            color: #ffffff;
            font-weight: 800;
            font-size: 1.05rem;
        }

        /* Related Projects Section */
        .related-projects-section {
            margin-bottom: 70px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 50px;
        }

        .section-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .section-header-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .view-all-link {
            color: #dc2626;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: gap 0.25s ease;
        }

        .view-all-link:hover {
            gap: 10px;
            color: #ef4444;
        }

        .related-projects-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        .related-card {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            overflow: hidden;
            position: relative;
            height: 280px;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 24px;
            box-sizing: border-box;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .related-card:hover {
            border-color: rgba(220, 38, 38, 0.5);
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(220, 38, 38, 0.2);
        }

        .related-card img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
            z-index: 1;
        }

        .related-card:hover img {
            transform: scale(1.06);
        }

        .related-card-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(3, 3, 5, 0.2) 0%, rgba(3, 3, 5, 0.85) 70%, rgba(3, 3, 5, 0.98) 100%);
            z-index: 2;
        }

        .related-card-content {
            position: relative;
            z-index: 3;
        }

        .related-cat-pill {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 12px;
            width: fit-content;
        }

        .related-client-name {
            font-size: 0.82rem;
            color: #a1a1aa;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .related-project-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            line-height: 1.25;
        }

        /* Explore Other Services Section */
        .explore-services-section {
            margin-bottom: 70px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 50px;
        }

        .explore-services-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .other-service-btn {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 20px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            text-align: center;
        }

        .other-service-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(220, 38, 38, 0.4);
            color: #ef4444;
            transform: translateY(-2px);
        }

        .other-service-btn i {
            color: #dc2626;
            font-size: 1rem;
        }

        /* Call to Action Footer Banner */
        .cta-bottom-banner {
            background: linear-gradient(135deg, rgba(30, 10, 15, 0.95) 0%, rgba(14, 14, 18, 0.95) 100%);
            border: 1px solid rgba(220, 38, 38, 0.3);
            border-radius: 20px;
            padding: 48px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 60px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
        }

        .cta-banner-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 6px 0;
            letter-spacing: -0.5px;
        }

        .cta-banner-desc {
            color: #a1a1aa;
            font-size: 0.98rem;
            margin: 0;
        }

        .cta-banner-buttons {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .btn-cta-red {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 13px 26px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 18px rgba(220, 38, 38, 0.4);
            white-space: nowrap;
        }

        .btn-cta-red:hover {
            background: #ef4444;
            transform: translateY(-2px);
        }

        .btn-cta-outline {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-cta-outline:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.3);
        }

        /* Footer */
        .service-footer-bar {
            padding: 24px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.88rem;
            color: #71717a;
        }

        .service-footer-links {
            display: flex;
            gap: 24px;
        }

        .service-footer-links a {
            color: #a1a1aa;
            text-decoration: none;
            transition: color 0.25s ease;
        }

        .service-footer-links a:hover {
            color: #ffffff;
        }

        @media (max-width: 992px) {
            .service-hero-stage {
                grid-template-columns: 1fr;
            }
            .service-hero-right {
                height: 260px;
            }
            .service-content-grid {
                grid-template-columns: 1fr;
            }
            .sidebar-sticky-wrap {
                position: static;
            }
            .related-projects-grid {
                grid-template-columns: 1fr;
            }
            .explore-services-row {
                grid-template-columns: repeat(2, 1fr);
            }
            .cta-bottom-banner {
                flex-direction: column;
                align-items: flex-start;
                gap: 24px;
            }
        }

        @media (max-width: 600px) {
            .included-grid {
                grid-template-columns: 1fr;
            }
            .explore-services-row {
                grid-template-columns: 1fr;
            }
            .service-hero-title {
                font-size: 2.2rem;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation Bar -->
    <header class="service-nav-bar">
        <div class="service-nav-container">
            <a href="/services.php" class="service-back-link">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
            <a href="/" class="service-logo-center">
                <img src="/assets/img/icons/logo.png" alt="Falhen Logo">
            </a>
            <a href="/contact.php" class="btn-service-contact">
                <i class="fa-regular fa-paper-plane"></i> Contact Us
            </a>
        </div>
    </header>

    <main class="service-main-container">

        <!-- Breadcrumb Navigation -->
        <nav class="service-breadcrumb">
            <a href="/">Home</a>
            <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
            <a href="/services.php">Services</a>
            <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
            <span class="current"><?php echo htmlspecialchars($service['title']); ?></span>
        </nav>

        <!-- Hero Stage -->
        <div class="service-hero-stage">
            <div class="service-hero-left">
                <div class="service-pill-badge">
                    <i class="fa-solid fa-square"></i> Our Services
                </div>
                <h1 class="service-hero-title"><?php echo htmlspecialchars($service['title']); ?></h1>
                <p class="service-hero-desc"><?php echo htmlspecialchars($service['subtitle']); ?></p>
            </div>
            <div class="service-hero-right">
                <img src="<?php echo htmlspecialchars($service['hero_image']); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>">
            </div>
        </div>

        <!-- Main Content 2-Column Grid -->
        <div class="service-content-grid">
            <!-- Left Main Column -->
            <div>
                <!-- About Section -->
                <h2 class="service-section-title">About This Service</h2>
                <p class="service-about-paragraph"><?php echo htmlspecialchars($service['about']); ?></p>

                <!-- What's Included Section -->
                <h2 class="service-section-title">What's Included</h2>
                <div class="included-grid">
                    <?php foreach ($service['included'] as $item): ?>
                        <div class="included-item-card">
                            <div class="included-check-icon">
                                <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                            </div>
                            <span class="included-text"><?php echo htmlspecialchars($item['text']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Specialisations Section -->
                <h2 class="service-section-title" style="margin-top: 40px;">Specialisations</h2>
                <div class="spec-pills-wrap">
                    <?php foreach ($service['specialisations'] as $spec): ?>
                        <span class="spec-pill"><?php echo htmlspecialchars($spec); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Sidebar Column -->
            <div>
                <div class="sidebar-sticky-wrap">
                    <!-- Ready Action Card -->
                    <div class="ready-action-card">
                        <div class="ready-icon-box">
                            <i class="fa-solid fa-video"></i>
                        </div>
                        <h3 class="ready-card-title">Ready to get started?</h3>
                        <p class="ready-card-desc">Tell us about your project and we'll put together a tailored proposal within 24 hours.</p>
                        <a href="/contact.php" class="btn-action-primary">
                            <i class="fa-regular fa-envelope"></i> Contact Us
                        </a>
                        <a href="/services.php" class="btn-action-secondary">
                            View All Services
                        </a>
                    </div>

                    <!-- By The Numbers Stats Card -->
                    <div class="stats-sidebar-card">
                        <div class="stats-header-label">BY THE NUMBERS</div>
                        <div class="stats-rows-list">
                            <div class="stats-row-item">
                                <span class="stats-item-label">Projects Delivered</span>
                                <span class="stats-item-value">300+</span>
                            </div>
                            <div class="stats-row-item">
                                <span class="stats-item-label">Years of Experience</span>
                                <span class="stats-item-value">12</span>
                            </div>
                            <div class="stats-row-item">
                                <span class="stats-item-label">Industry Awards</span>
                                <span class="stats-item-value">47</span>
                            </div>
                            <div class="stats-row-item">
                                <span class="stats-item-label">Countries Served</span>
                                <span class="stats-item-value">40+</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Projects Section -->
        <?php if (!empty($service['related_projects'])): ?>
            <section class="related-projects-section">
                <div class="section-header-flex">
                    <h2 class="section-header-title">Related Projects</h2>
                    <a href="/portfolio.php" class="view-all-link">View All <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <div class="related-projects-grid">
                    <?php foreach ($service['related_projects'] as $proj): ?>
                        <a href="/portfolio.php" class="related-card">
                            <img src="<?php echo htmlspecialchars($proj['image']); ?>" alt="<?php echo htmlspecialchars($proj['title']); ?>">
                            <div class="related-card-overlay"></div>
                            <div class="related-card-content">
                                <span class="related-cat-pill"><?php echo htmlspecialchars($proj['category']); ?></span>
                                <div class="related-client-name"><?php echo htmlspecialchars($proj['client']); ?></div>
                                <h3 class="related-project-title"><?php echo htmlspecialchars($proj['title']); ?></h3>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Explore Other Services Section -->
        <section class="explore-services-section">
            <div class="section-header-flex">
                <h2 class="section-header-title">Explore Other Services</h2>
                <a href="/services.php" class="view-all-link">All Services <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="explore-services-row">
                <?php foreach (array_slice($otherServices, 0, 4) as $oService): ?>
                    <a href="/service-single.php?slug=<?php echo htmlspecialchars($oService['slug']); ?>" class="other-service-btn">
                        <i class="fa-solid fa-clapperboard"></i>
                        <span><?php echo htmlspecialchars($oService['title']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Call to Action Banner -->
        <div class="cta-bottom-banner">
            <div>
                <h2 class="cta-banner-title">Let's make something great together.</h2>
                <p class="cta-banner-desc">Our team is ready to bring your vision to life.</p>
            </div>
            <div class="cta-banner-buttons">
                <a href="/index.php#lets-talk" class="btn-cta-red">Start a Project</a>
                <a href="/portfolio.php" class="btn-cta-outline">See Our Work</a>
            </div>
        </div>

        <!-- Footer -->
        <footer class="service-footer-bar">
            <div>© 2026 Falhen Media. All rights reserved.</div>
            <div class="service-footer-links">
                <a href="/">Home</a>
                <a href="/about.php">About</a>
                <a href="/services.php">Services</a>
                <a href="/portfolio.php">Portfolio</a>
                <a href="/careers.php">Careers</a>
                <a href="/contact.php">Contact</a>
            </div>
        </footer>

    </main>

    <!-- Fixed Mobile Floating Bottom Dock Navigation Widget -->
    <nav class="mobile-bottom-dock">
        <a href="/" class="dock-item">
            <div class="dock-icon-box"><i class="fa-solid fa-house"></i></div>
            <span class="dock-label">HOME</span>
        </a>
        <a href="/services.php" class="dock-item active">
            <div class="dock-icon-box"><i class="fa-solid fa-briefcase"></i></div>
            <span class="dock-label">SERVICES</span>
        </a>
        <a href="/portfolio.php" class="dock-item">
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
