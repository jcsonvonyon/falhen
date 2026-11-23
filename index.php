<?php
// Subdomain Routing: Serve Client Gallery Portal when accessed via client.falhen.com
$incomingHost = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
if ($incomingHost === 'client.falhen.com' || str_starts_with($incomingHost, 'client.')) {
    require __DIR__ . '/client-portal.php';
    exit;
}

$pageTitle = "Falhen Productions | Video Production & Visual Storytelling";
$pageDescription = "Full-service video production company specializing in commercial film, concert cinematography, live streaming, and 3D motion graphics. Elevate your brand today.";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$settings = getSiteSettings();
$heroMedia = resolveHeroMediaConfig($settings);

require_once __DIR__ . '/includes/header.php';

// Fetch services from DB if available
$pdo = getDBConnection();
$services = [];
$portfolioItems = [];
$testimonials = [];

if ($pdo) {
    try {
        $services = $pdo->query("SELECT * FROM `services` ORDER BY `featured` DESC, `id` ASC LIMIT 6")->fetchAll();
        $portfolioItems = $pdo->query("SELECT * FROM `portfolio` ORDER BY `id` DESC LIMIT 6")->fetchAll();
        $testimonials = $pdo->query("SELECT * FROM `testimonials` ORDER BY `id` ASC LIMIT 3")->fetchAll();
    } catch (Exception $e) {
    }
}
?>

<!-- Hero Section: Instant Poster Backup + Chromeless Autoplay Video Stage -->
<section class="hero-stage" id="heroStage">
    <!-- Layer 0: Instant Responsive Backup Picture (Offline & Zero-Latency LCP Layer) -->
    <div class="hero-poster-layer" id="heroPosterLayer" aria-hidden="true">
        <picture>
            <source media="(max-width: 768px)" srcset="<?php echo htmlspecialchars($heroMedia['poster_mobile']); ?>"
                width="750" height="422">
            <img src="<?php echo htmlspecialchars($heroMedia['poster_desktop']); ?>"
                alt="Falhen Productions Hero Showreel" class="hero-poster-img" width="1920" height="1080"
                fetchpriority="high" decoding="sync" onerror="if(!this.dataset.failed){this.dataset.failed='1';this.src='https://res.cloudinary.com/pnabfi91/image/upload/v1786578372/falhen/uploads/r96mxsji9cdogqrkxwoe.jpg';}">
        </picture>
    </div>

    <!-- Layer 1: Primary Chromeless Muted Video Layer (Crossfades in ONLY when frames are playing) -->
    <?php if ($heroMedia['bg_mode'] !== 'poster'): ?>
        <div class="hero-video-layer" id="heroVideoLayer" aria-hidden="true"
            data-bg-mode="<?php echo htmlspecialchars($heroMedia['bg_mode']); ?>"
            data-iframe-provider="<?php echo htmlspecialchars($heroMedia['iframe_provider']); ?>"
            data-iframe-id="<?php echo htmlspecialchars($heroMedia['iframe_video_id']); ?>"
            data-iframe-src="<?php echo htmlspecialchars($heroMedia['iframe_embed_url']); ?>"
            data-direct-desktop="<?php echo htmlspecialchars($heroMedia['direct_video_desktop']); ?>"
            data-direct-mobile="<?php echo htmlspecialchars($heroMedia['direct_video_mobile']); ?>"></div>
    <?php endif; ?>

    <!-- Layer 2: Cinematic Readability Vignette -->
    <div class="hero-overlay" aria-hidden="true"></div>

    <!-- Layer 3: Foreground Copy & Actions -->
    <div class="container hero-container">
        <div class="hero-content">
            <?php if (!empty($settings['hero_badge_label'])): ?>
                <div class="section-badge-pill hero-badge-pill">
                    <?php echo htmlspecialchars($settings['hero_badge_label']); ?>
                </div>
            <?php endif; ?>
            <h1 class="hero-title">
                <?php
                $line1 = htmlspecialchars($settings['hero_headline_line1'] ?? 'Creating what the');
                $line2 = htmlspecialchars($settings['hero_headline_line2'] ?? 'World Watches');
                echo $line1 . '<br><span class="hero-gradient-text">' . $line2 . '</span>';
                ?>
            </h1>
            <p class="hero-subtitle">
                <?php echo htmlspecialchars($settings['hero_tagline'] ?? "From cinematic campaigns to viral content — we craft the visual stories your audience can't look away from."); ?>
            </p>
            <div class="hero-actions">
                <a href="<?php echo htmlspecialchars($settings['hero_primary_cta_url'] ?? '/portfolio.php'); ?>"
                    class="btn btn-hero-red">
                    <?php echo htmlspecialchars($settings['hero_primary_cta_text'] ?? 'Explore Our Projects'); ?>
                </a>
                <a href="#" class="btn btn-hero-glass trigger-video"
                    data-video="<?php echo htmlspecialchars($heroMedia['showreel_modal_url']); ?>">
                    <span class="hero-play-icon"><i class="fa-solid fa-play"></i></span>
                    <?php echo htmlspecialchars($settings['hero_secondary_cta_text'] ?? 'Watch Showreel'); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Trusted Brands Section (Replicated from falhen.com) -->
<?php
$allBrandLogos = getBrandLogosRepo();
$visibleBrands = array_values(array_filter($allBrandLogos, function ($b) {
    return !empty($b['visible']);
}));
if (empty($visibleBrands)) {
    $visibleBrands = array_values($allBrandLogos);
}
?>
<section class="brands-section">
    <div class="brands-title"><?php echo htmlspecialchars($settings['brands_section_title'] ?? 'TRUSTED BY WORLD-CLASS BRANDS'); ?></div>
    <div class="brands-ticker-wrapper">
        <div class="brands-ticker-track">
            <!-- First Set -->
            <?php foreach ($visibleBrands as $brand): ?>
                <div class="brand-card">
                    <?php if (!empty($brand['image'])): ?>
                        <img src="<?php echo htmlspecialchars(getCloudinaryUrl($brand['image'], 'f_auto,q_auto:good,w_220,h_52,c_fit')); ?>"
                            alt="<?php echo htmlspecialchars($brand['name']); ?>" width="110" height="26" loading="lazy"
                            decoding="async" style="max-height: 26px; max-width: 110px; object-fit: contain;">
                    <?php else: ?>
                        <i
                            class="<?php echo htmlspecialchars(!empty($brand['icon']) ? $brand['icon'] : 'fa-solid fa-star'); ?>"></i>
                        <?php echo htmlspecialchars($brand['name']); ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Duplicate Set for Seamless Infinite Ticker Loop -->
            <?php foreach ($visibleBrands as $brand): ?>
                <div class="brand-card">
                    <?php if (!empty($brand['image'])): ?>
                        <img src="<?php echo htmlspecialchars(getCloudinaryUrl($brand['image'], 'f_auto,q_auto:good,w_220,h_52,c_fit')); ?>"
                            alt="<?php echo htmlspecialchars($brand['name']); ?>" width="110" height="26" loading="lazy"
                            decoding="async" style="max-height: 26px; max-width: 110px; object-fit: contain;">
                    <?php else: ?>
                        <i
                            class="<?php echo htmlspecialchars(!empty($brand['icon']) ? $brand['icon'] : 'fa-solid fa-star'); ?>"></i>
                        <?php echo htmlspecialchars($brand['name']); ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Services Section (Replicated exactly from falhen.com screenshots) -->
<section class="section" id="services">
    <div class="container">
        <div class="section-title-wrapper" style="text-align: center;">
            <?php if (!empty($settings['services_badge_label'])): ?>
                <span class="section-badge" style="display: inline-block; font-size: 0.76rem; font-weight: 700; color: #dc2626; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 8px;"><?php echo htmlspecialchars($settings['services_badge_label']); ?></span>
            <?php endif; ?>
            <h2 class="section-title" style="font-size: 2.75rem; font-weight: 700;">
                <span><?php echo htmlspecialchars($settings['services_headline_white'] ?? 'Our'); ?></span>
                <span style="color: #dc2626;"><?php echo htmlspecialchars($settings['services_headline_red'] ?? 'Services'); ?></span>
            </h2>
            <p class="section-subtitle"
                style="max-width: 620px; margin: 0 auto; color: var(--text-muted); font-size: 1.05rem;">
                <?php echo htmlspecialchars($settings['services_subtitle'] ?? 'From concept to completion, we bring your vision to life with creativity and precision.'); ?>
            </p>
        </div>

        <div class="services-grid">
            <?php
            $homepageServices = getServicesRepo();
            foreach ($homepageServices as $sItem):
                $sBanner = function_exists('getCloudinaryUrl') ? getCloudinaryUrl($sItem['image'], 'f_auto,q_auto:good,w_600,h_340,c_fill') : $sItem['image'];
                ?>
                <div class="service-card">
                    <div class="service-card-banner">
                        <img src="<?php echo htmlspecialchars($sBanner); ?>"
                            alt="<?php echo htmlspecialchars($sItem['title']); ?>" width="600" height="340" loading="lazy"
                            decoding="async" onerror="this.src='/assets/img/hero.jpg';">
                        <div class="service-icon-box"><i class="<?php echo htmlspecialchars($sItem['icon']); ?>"></i></div>
                    </div>
                    <div class="service-card-body">
                        <h3 class="service-title"><?php echo htmlspecialchars($sItem['title']); ?></h3>
                        <p class="service-desc"><?php echo htmlspecialchars($sItem['short_description']); ?></p>
                        <ul class="service-checklist">
                            <?php foreach (array_slice($sItem['card_features'], 0, 3) as $feat): ?>
                                <li><span class="check-icon">✓</span> <?php echo htmlspecialchars($feat); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="/services/<?php echo htmlspecialchars($sItem['slug']); ?>" class="service-learn-link">Learn
                            More <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Mobile Carousel Navigation Controls -->
        <div class="services-mobile-controls">
            <button type="button" class="services-carousel-btn" id="servicesPrevBtn" aria-label="Previous Service">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <div class="services-carousel-dots" id="servicesDots">
                <?php foreach ($homepageServices as $idx => $sItem): ?>
                    <span class="services-dot <?php echo ($idx === 0) ? 'active' : ''; ?>"
                        data-index="<?php echo $idx; ?>"></span>
                <?php endforeach; ?>
            </div>

            <button type="button" class="services-carousel-btn" id="servicesNextBtn" aria-label="Next Service">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>

<!-- Featured Work Section (Dynamically powered by Portfolio Showcase Video Reels) -->
<?php
$allPortfolioRepo = getPortfolioRepo();
$portfolioVideos = array_values(array_filter($allPortfolioRepo, function ($i) {
    return ($i['media_type'] ?? 'photo') === 'video';
}));

if (empty($portfolioVideos)) {
    $portfolioVideos = $allPortfolioRepo;
}

$featuredItems = [];
foreach ($portfolioVideos as $idx => $item) {
    $ytId = extractYouTubeId(!empty($item['video_url']) ? $item['video_url'] : ($item['image'] ?? ''));
    $thumb = !empty($item['image']) ? getCloudinaryUrl($item['image']) : getYouTubeThumbnailUrl($ytId);
    $featuredItems[] = [
        'id' => $item['id'] ?? ($idx + 1),
        'project_name' => $item['title'] ?? 'Featured Reel',
        'client' => $item['client'] ?? 'Falhen',
        'category' => strtoupper($item['category'] ?? 'General'),
        'duration' => !empty($item['duration']) ? $item['duration'] : '02:30',
        'youtube_id' => $ytId,
        'video_url' => $item['video_url'] ?? '',
        'thumbnail' => $thumb,
        'is_hero_featured' => !empty($item['featured']),
        'status' => 'live'
    ];
}

$heroItem = $featuredItems[0];
foreach ($featuredItems as $f) {
    if (!empty($f['is_hero_featured'])) {
        $heroItem = $f;
        break;
    }
}

$heroYtId = extractYouTubeId($heroItem['youtube_id']);
$heroImg = !empty($heroItem['thumbnail']) ? getCloudinaryUrl($heroItem['thumbnail']) : getYouTubeThumbnailUrl($heroYtId);
$heroVideoUrl = 'https://www.youtube.com/embed/' . $heroYtId;
?>
<section class="section" id="portfolio" style="background: #000000; border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container">
        <!-- Section Header -->
        <div class="section-title-wrapper" style="margin-bottom: 30px; text-align: left;">
            <?php if (!empty($settings['featured_work_badge_label'])): ?>
                <span class="section-badge" style="display: inline-block; font-size: 0.76rem; font-weight: 700; color: #dc2626; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 8px;"><?php echo htmlspecialchars($settings['featured_work_badge_label']); ?></span>
            <?php endif; ?>
            <h2 class="section-title stories-heading"
                style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 60px; font-weight: 800; letter-spacing: -1.5px; line-height: 1.1; text-align: left;">
                <?php echo htmlspecialchars($settings['featured_work_headline_white'] ?? "Stories We've"); ?> <span style="color: #dc2626;"><?php echo htmlspecialchars($settings['featured_work_headline_red'] ?? 'Told.'); ?></span>
            </h2>
            <?php if (!empty($settings['featured_work_subtitle'])): ?>
                <p style="color: var(--text-muted); font-size: 1.05rem; margin-top: 8px; max-width: 680px;"><?php echo htmlspecialchars($settings['featured_work_subtitle']); ?></p>
            <?php endif; ?>
        </div>

        <!-- Dynamic Filter Pill Tabs -->
        <?php
        $uniqueCategories = array_unique(array_filter(array_map(function ($f) {
            return strtoupper($f['category']);
        }, $featuredItems)));
        ?>
        <div class="portfolio-filter-bar">
            <button class="filter-pill-btn active" data-filter="all">ALL</button>
            <?php foreach ($uniqueCategories as $catName): ?>
                <button class="filter-pill-btn"
                    data-filter="<?php echo htmlspecialchars($catName); ?>"><?php echo htmlspecialchars($catName); ?></button>
            <?php endforeach; ?>
        </div>

        <!-- Main Featured Video Showcase Player -->
        <div class="featured-showcase-player">
            <div class="showcase-player-bg" id="showcase-player-bg"
                style="background-image: url('<?php echo htmlspecialchars($heroImg); ?>');">
                <div class="showcase-player-overlay"></div>
                <div class="showcase-counter-badge"><span id="showcase-current">1</span> / <span
                        id="showcase-total"><?php echo count($featuredItems); ?></span></div>

                <div class="showcase-content">
                    <div class="showcase-meta">
                        <span class="showcase-badge"
                            id="showcase-category"><?php echo htmlspecialchars($heroItem['category']); ?></span>
                        <span class="showcase-client"
                            id="showcase-client"><?php echo htmlspecialchars($heroItem['client']); ?></span>
                        <span class="showcase-duration"
                            id="showcase-time"><?php echo htmlspecialchars($heroItem['duration']); ?></span>
                    </div>
                    <h3 class="showcase-title" id="showcase-title">
                        <?php echo htmlspecialchars($heroItem['project_name']); ?></h3>
                    <div class="showcase-actions">
                        <button class="btn btn-hero-red trigger-video" id="showcase-play-btn"
                            data-video="<?php echo htmlspecialchars($heroVideoUrl); ?>">
                            <i class="fa-solid fa-play"></i> Watch Film
                        </button>
                        <button class="showcase-fullscreen-btn trigger-video" id="showcase-full-btn"
                            data-video="<?php echo htmlspecialchars($heroVideoUrl); ?>">
                            <i class="fa-solid fa-expand"></i> Full screen
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thumbnail Carousel Track -->
        <div class="showcase-thumbs-wrapper">
            <div class="showcase-thumbs-track" id="showcase-thumbs-track">
                <?php foreach ($featuredItems as $idx => $item): ?>
                    <?php
                    $ytId = extractYouTubeId($item['youtube_id']);
                    $itemThumb = !empty($item['thumbnail']) ? $item['thumbnail'] : getYouTubeThumbnailUrl($ytId);
                    $thumbImg = getCloudinaryUrl($itemThumb, 'f_auto,q_auto:good,w_360,h_200,c_fill');
                    $videoEmbed = 'https://www.youtube.com/embed/' . $ytId;
                    $isActive = (!empty($heroItem['id']) && !empty($item['id'])) ? ($heroItem['id'] === $item['id']) : ($idx === 0);
                    ?>
                    <div class="thumb-card <?php echo $isActive ? 'active' : ''; ?>" data-index="<?php echo $idx; ?>"
                        data-category="<?php echo htmlspecialchars($item['category']); ?>"
                        data-client="<?php echo htmlspecialchars($item['client']); ?>"
                        data-time="<?php echo htmlspecialchars($item['duration']); ?>"
                        data-title="<?php echo htmlspecialchars($item['project_name']); ?>"
                        data-img="<?php echo htmlspecialchars($thumbImg); ?>"
                        data-video="<?php echo htmlspecialchars($videoEmbed); ?>">
                        <?php if ($isActive): ?>
                            <div class="thumb-badge-now">NOW</div>
                        <?php endif; ?>
                        <div class="thumb-img-wrapper">
                            <img src="<?php echo htmlspecialchars($thumbImg); ?>"
                                alt="<?php echo htmlspecialchars($item['project_name']); ?>" width="360" height="200"
                                loading="lazy" decoding="async"
                                onerror="this.src='https://i.ytimg.com/vi/<?php echo htmlspecialchars($ytId); ?>/hqdefault.jpg';">
                            <span class="thumb-time"><?php echo htmlspecialchars($item['duration']); ?></span>
                        </div>
                        <div class="thumb-info">
                            <span class="thumb-cat"><?php echo htmlspecialchars($item['category']); ?></span>
                            <h4 class="thumb-title"><?php echo htmlspecialchars($item['project_name']); ?></h4>
                            <small class="thumb-client"><?php echo htmlspecialchars($item['client']); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bottom Row Controls & Explore Action -->
        <div class="showcase-controls-bar">
            <div class="showcase-nav-btns">
                <button class="nav-arrow-btn" id="showcase-prev-btn" aria-label="Previous Showcase"><i
                        class="fa-solid fa-chevron-left"></i></button>
                <button class="nav-arrow-btn" id="showcase-next-btn" aria-label="Next Showcase"><i
                        class="fa-solid fa-chevron-right"></i></button>
                <span class="auto-advance-label">Auto-advancing every 6s</span>
            </div>
            <a href="<?php echo htmlspecialchars($settings['featured_work_cta_url'] ?? '/services.php'); ?>" class="explore-services-btn"><?php echo htmlspecialchars($settings['featured_work_cta_text'] ?? 'Explore Services'); ?> <i
                    class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="section" id="testimonials"
    style="background: #09090b; border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container">
        <div class="section-title-wrapper" style="text-align: center; margin-bottom: 50px;">
            <?php if (!empty($settings['testimonials_badge_label'])): ?>
                <span class="section-badge" style="display: inline-block; font-size: 0.76rem; font-weight: 700; color: #dc2626; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 8px;"><?php echo htmlspecialchars($settings['testimonials_badge_label']); ?></span>
            <?php endif; ?>
            <h2 class="section-title testimonials-heading"
                style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 50px; font-weight: 700; letter-spacing: -1.5px; line-height: 1.1;">
                <?php echo htmlspecialchars($settings['testimonials_headline_white'] ?? 'What Our Clients'); ?> <span style="color: #dc2626;"><?php echo htmlspecialchars($settings['testimonials_headline_red'] ?? 'Say'); ?></span></h2>
            <p class="section-subtitle"
                style="max-width: 620px; margin: 0 auto; color: var(--text-muted); font-size: 1.05rem;"><?php echo htmlspecialchars($settings['testimonials_subtitle'] ?? "Don't take our word for it — here's what the brands we've worked with have to say."); ?></p>
        </div>

        <div class="testimonials-wrapper">
            <div class="testimonials-grid" id="testimonials-grid">
                <?php
                $dynamicTestimonials = getTestimonialsRepo();
                $initActive = 1; // Center card (Sprint Series Brand Film) active by default as shown in reference
                foreach ($dynamicTestimonials as $idx => $tCard):
                    ?>
                    <div class="testimonial-card <?php echo ($idx === $initActive) ? 'active featured-card' : ''; ?>"
                        data-real-index="<?php echo $idx; ?>">
                        <p class="testimonial-quote">"<?php echo htmlspecialchars($tCard['quote']); ?>"</p>
                        <?php if (!empty($tCard['project'])): ?>
                            <div class="testimonial-tag-pill"><?php echo htmlspecialchars($tCard['project']); ?></div>
                        <?php endif; ?>
                        <div class="testimonial-footer">
                            <div class="testimonial-author">
                                <img src="<?php echo htmlspecialchars(getCloudinaryUrl($tCard['avatar'] ?? 'https://res.cloudinary.com/pnabfi91/image/upload/v1786712075/falhen/team/ctxjp9mymgqdh2ecg2hu.jpg', 'f_auto,q_auto:good,w_96,h_96,c_fill')); ?>"
                                    alt="<?php echo htmlspecialchars($tCard['name']); ?>" width="48" height="48"
                                    loading="lazy" decoding="async" class="author-img"
                                    onerror="this.onerror=null;this.src='/assets/img/icons/favicon.png';">
                                <div>
                                    <div class="author-name"><?php echo htmlspecialchars($tCard['name']); ?></div>
                                    <div class="author-role">
                                        <?php echo htmlspecialchars(($tCard['role'] ?? '') . (!empty($tCard['company']) ? ', ' . $tCard['company'] : '')); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Carousel Navigation Bar -->
        <div class="testimonial-controls-bar">
            <div class="testimonial-nav-btns">
                <button class="nav-arrow-btn" id="testi-prev-btn" aria-label="Previous Testimonial"><i
                        class="fa-solid fa-chevron-left"></i></button>
                <div class="testimonial-dots" id="testimonial-dots">
                    <?php foreach ($dynamicTestimonials as $idx => $tCard): ?>
                        <span class="dot <?php echo ($idx === $initActive) ? 'active' : ''; ?>"
                            data-index="<?php echo $idx; ?>"></span>
                    <?php endforeach; ?>
                </div>
                <button class="nav-arrow-btn" id="testi-next-btn" aria-label="Next Testimonial"><i
                        class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>
    </div>
</section>

<!-- BTS Section (Dynamic Admin Settings Integration) -->
<?php
$btsWhite = !empty($settings['bts_headline_white']) ? $settings['bts_headline_white'] : 'Every Frame';
$btsRed = !empty($settings['bts_headline_red']) ? $settings['bts_headline_red'] : 'Speaks';
$btsDesc = !empty($settings['bts_description']) ? $settings['bts_description'] : 'A raw look at what it takes to create world-class content — from location scouts to post-production suites.';
$rawBtsItems = getProductionBtsRepo();
$btsItems = array_values(array_filter($rawBtsItems, function ($b) {
    return !empty($b['visible']);
}));
if (empty($btsItems)) {
    $btsItems = $rawBtsItems;
}
?>
<section class="section" id="bts" style="background: #09090b; border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container">
        <!-- Section Header Flex -->
        <div class="team-header-flex" style="margin-bottom: 40px;">
            <div class="team-header-left">
                <h2 class="section-title" style="font-size: 2.75rem; font-weight: 700;">
                    <?php echo htmlspecialchars($btsWhite); ?> <span
                        style="color: #dc2626;"><?php echo htmlspecialchars($btsRed); ?></span>
                </h2>
            </div>
            <div class="team-header-right" style="max-width: 368px;">
                <p class="team-header-desc"><?php echo htmlspecialchars($btsDesc); ?></p>
            </div>
        </div>

        <!-- BTS Grid (Dynamic Loop) -->
        <div class="bts-grid">
            <?php foreach (array_slice($btsItems, 0, 6) as $bts): ?>
                <?php
                $imgCdn = getCloudinaryUrl($bts['image'], 'f_auto,q_auto:good,w_400,h_400,c_fill');
                $captionText = !empty($bts['title']) ? $bts['title'] : ($bts['subtitle'] ?? 'Production BTS');
                ?>
                <div class="bts-card trigger-lightbox" data-img="<?php echo htmlspecialchars($imgCdn); ?>"
                    data-caption="<?php echo htmlspecialchars($captionText); ?>">
                    <img src="<?php echo htmlspecialchars($imgCdn); ?>" alt="<?php echo htmlspecialchars($captionText); ?>"
                        width="400" height="400" loading="lazy" decoding="async" onerror="this.src='/assets/img/hero.jpg';">
                    <div class="bts-card-overlay">
                        <div class="bts-zoom-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <span class="bts-caption"><?php echo htmlspecialchars($captionText); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Lightbox Modal for BTS Gallery -->
<div class="bts-lightbox-modal" id="btsLightbox">
    <div class="bts-lightbox-overlay"></div>
    <div class="bts-lightbox-content">
        <button class="bts-lightbox-close" id="btsLightboxClose" type="button"
            aria-label="Close behind the scenes gallery"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        <img src="" alt="BTS Lightbox Image" id="btsLightboxImg">
        <div class="bts-lightbox-caption" id="btsLightboxCaption"></div>
    </div>
</div>

<!-- Book a Discovery Call Section (Replicated from reference mockup) -->
<section class="section" id="lets-talk" style="background: #09090b; border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container">
        <!-- Section Header -->
        <div class="section-title-wrapper" style="text-align: center; margin-bottom: 50px;">
            <?php if (!empty($settings['discovery_badge_label'])): ?>
                <span class="section-badge" style="display: inline-block; font-size: 0.76rem; font-weight: 700; color: #dc2626; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 8px;"><?php echo htmlspecialchars($settings['discovery_badge_label']); ?></span>
            <?php endif; ?>
            <h2 class="section-title discovery-main-title">
                <?php echo htmlspecialchars($settings['discovery_headline_white'] ?? 'Book a'); ?> <span style="color: #ef4444;"><?php echo htmlspecialchars($settings['discovery_headline_red'] ?? 'Discovery Call'); ?></span>
            </h2>
            <p class="section-subtitle discovery-main-subtitle">
                <?php echo htmlspecialchars($settings['discovery_subtitle'] ?? '30 minutes. No sales pitch. Just a candid conversation about your project.'); ?>
            </p>
        </div>

        <!-- 2-Column Discovery Call Layout -->
        <div class="discovery-grid">
            <!-- Left Side: Hero Image & Feature Cards -->
            <div class="discovery-left">
                <!-- Top Hero Image Box with Available Badge -->
                <div class="discovery-hero-box">
                    <?php
                    $discoveryImgRaw = $settings['discovery_hero_image'] ?? '/assets/img/discovery_call.jpg';
                    $discoveryImgCdn = function_exists('getCloudinaryUrl') ? getCloudinaryUrl($discoveryImgRaw, 'f_auto,q_auto:good,w_800,h_533,c_fill') : $discoveryImgRaw;
                    ?>
                    <img src="<?php echo htmlspecialchars($discoveryImgCdn); ?>" alt="Book a Discovery Call - Falhen Productions"
                        width="600" height="400" loading="lazy" decoding="async" onerror="this.src='/assets/img/discovery_call.jpg';">
                    <div class="discovery-hero-overlay">
                        <span class="discovery-available-pill">
                            <span class="pulse-green-dot"></span> <?php echo htmlspecialchars($settings['discovery_available_text'] ?? 'Available — Book your slot now'); ?>
                        </span>
                    </div>
                </div>

                <!-- 2x2 Feature Cards Grid -->
                <?php
                $discoveryFeatures = !empty($settings['discovery_features']) && is_array($settings['discovery_features'])
                    ? $settings['discovery_features']
                    : [
                        ['icon' => 'fa-regular fa-clock', 'title' => '30-Min Deep Dive', 'desc' => 'No fluff — just a focused conversation about your project and goals.'],
                        ['icon' => 'fa-regular fa-lightbulb', 'title' => 'Creative Direction', 'desc' => "We'll give you initial ideas and directional thoughts on the spot."],
                        ['icon' => 'fa-regular fa-file-lines', 'title' => 'Proposal in 48hrs', 'desc' => 'A full scope, timeline, and budget proposal delivered fast after the call.'],
                        ['icon' => 'fa-solid fa-lock', 'title' => 'Zero Commitment', 'desc' => 'No pressure, no contracts — just a conversation between creators.']
                    ];
                ?>
                <div class="discovery-features-grid">
                    <?php foreach ($discoveryFeatures as $df): ?>
                        <div class="discovery-feature-card">
                            <div class="discovery-feature-header">
                                <div class="discovery-icon-box"><i class="<?php echo htmlspecialchars($df['icon'] ?? 'fa-solid fa-check'); ?>"></i></div>
                                <h4 class="discovery-feature-title"><?php echo htmlspecialchars($df['title'] ?? ''); ?></h4>
                            </div>
                            <p class="discovery-feature-desc"><?php echo htmlspecialchars($df['desc'] ?? ''); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>

            <!-- Right Side: Booking Form Card -->
            <div class="discovery-form-card">
                <div class="discovery-form-header">
                    <h3 class="discovery-form-title"><?php echo htmlspecialchars($settings['discovery_form_title'] ?? 'Your Details'); ?></h3>
                    <p class="discovery-form-subtitle">We'll confirm your slot within 2 hours.</p>
                </div>

                <div id="formFeedback" style="display: none; margin-bottom: 20px;"></div>

                <form id="inquiryForm" class="discovery-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="discovery-label" for="discoveryFullName">FULL NAME *</label>
                            <input type="text" name="full_name" id="discoveryFullName" class="discovery-input"
                                placeholder="Jane Smith" autocomplete="name" required>
                        </div>
                        <div class="form-group">
                            <label class="discovery-label" for="discoveryEmail">EMAIL ADDRESS *</label>
                            <input type="email" name="email" id="discoveryEmail" class="discovery-input"
                                placeholder="jane@yourbrand.com" autocomplete="email" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="discovery-label" for="discoveryServiceType">SERVICE *</label>
                        <div class="discovery-select-wrapper">
                                <select name="service_type" id="discoveryServiceType"
                                    class="discovery-input discovery-select" required>
                                    <option value="" disabled selected>Select a service...</option>
                                    <option value="Commercial Video & Ad Campaign">Commercial Video &amp; Ad Campaign
                                    </option>
                                    <option value="Brand Story & Documentary">Brand Story &amp; Documentary</option>
                                    <option value="Music Video & Creative Cinema">Music Video &amp; Creative Cinema
                                    </option>
                                    <option value="Post-Production & Color Grading">Post-Production &amp; Color Grading
                                    </option>
                                    <option value="Event Cinema & Live Coverage">Event Cinema &amp; Live Coverage
                                    </option>
                                    <option value="Social Media Content Retainer">Social Media Content Retainer</option>
                                </select>
                        </div>
                    </div>

                    <div class="form-group" style="position: relative;">
                        <label class="discovery-label" for="projectDetails">ABOUT YOUR PROJECT</label>
                        <textarea name="project_details" id="projectDetails" class="discovery-input discovery-textarea"
                            maxlength="500" placeholder="Brief description of your project..."></textarea>
                        <div class="char-count-wrapper">
                            <span class="char-count" id="charCount">0/500</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-discovery-submit" id="submitInquiryBtn">
                        <i class="fa-regular fa-calendar-check"></i> <?php echo htmlspecialchars($settings['discovery_button_text'] ?? 'Book My Discovery Call'); ?>
                    </button>

                    <p class="discovery-form-footer-note">We'll confirm within 2 hours. No spam, ever.</p>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>