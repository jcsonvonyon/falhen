<?php
// Subdomain Routing: Serve Client Gallery Portal when accessed via client.falhen.com
$incomingHost = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
if ($incomingHost === 'client.falhen.com' || str_starts_with($incomingHost, 'client.')) {
    require __DIR__ . '/client-portal.php';
    exit;
}

$pageTitle = "Falhen Video Production | Visual Storytelling";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$settings  = getSiteSettings();
$heroMedia = resolveHeroMediaConfig($settings);
$heroStats = resolveHeroStatsConfig($settings);

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
    } catch (Exception $e) {}
}
?>

<!-- Hero Section: Instant Poster Backup + Chromeless Autoplay Video Stage -->
<section class="hero-stage" id="heroStage">
    <!-- Layer 0: Instant Responsive Backup Picture (Offline & Zero-Latency LCP Layer) -->
    <div class="hero-poster-layer" id="heroPosterLayer" aria-hidden="true">
        <picture>
            <source media="(max-width: 768px)" srcset="<?php echo htmlspecialchars($heroMedia['poster_mobile']); ?>">
            <img
                src="<?php echo htmlspecialchars($heroMedia['poster_desktop']); ?>"
                alt=""
                class="hero-poster-img"
                fetchpriority="high"
                decoding="sync"
                onerror="this.onerror=null;this.src='/assets/img/hero.jpg';"
            >
        </picture>
    </div>

    <!-- Layer 1: Primary Chromeless Muted Video Layer (Crossfades in ONLY when frames are playing) -->
    <?php if ($heroMedia['bg_mode'] !== 'poster'): ?>
        <div
            class="hero-video-layer"
            id="heroVideoLayer"
            aria-hidden="true"
            data-bg-mode="<?php echo htmlspecialchars($heroMedia['bg_mode']); ?>"
            data-iframe-provider="<?php echo htmlspecialchars($heroMedia['iframe_provider']); ?>"
            data-iframe-id="<?php echo htmlspecialchars($heroMedia['iframe_video_id']); ?>"
            data-iframe-src="<?php echo htmlspecialchars($heroMedia['iframe_embed_url']); ?>"
            data-direct-desktop="<?php echo htmlspecialchars($heroMedia['direct_video_desktop']); ?>"
            data-direct-mobile="<?php echo htmlspecialchars($heroMedia['direct_video_mobile']); ?>"
        ></div>
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
                <a href="<?php echo htmlspecialchars($settings['hero_primary_cta_url'] ?? '/portfolio.php'); ?>" class="btn btn-hero-red">
                    <?php echo htmlspecialchars($settings['hero_primary_cta_text'] ?? 'Explore Our Projects'); ?>
                </a>
                <a href="#" class="btn btn-hero-glass trigger-video" data-video="<?php echo htmlspecialchars($heroMedia['showreel_modal_url']); ?>">
                    <span class="hero-play-icon"><i class="fa-solid fa-play"></i></span> <?php echo htmlspecialchars($settings['hero_secondary_cta_text'] ?? 'Watch Showreel'); ?>
                </a>
            </div>

            <?php if (!empty($heroStats['visible']) && $heroStats['placement'] === 'hero'): ?>
                <div class="hero-stats-dock" id="heroStatsDock" role="region" aria-label="Key Production Metrics">
                    <div class="hero-stats-grid">
                        <?php foreach ($heroStats['items'] as $sItem): ?>
                            <div class="hero-stat-item">
                                <div class="hero-stat-value">
                                    <?php if (!empty($sItem['prefix'])): ?><span class="hero-stat-prefix"><?php echo htmlspecialchars($sItem['prefix']); ?></span><?php endif; ?>
                                    <span class="stat-counter-val" data-target="<?php echo htmlspecialchars($sItem['number']); ?>"><?php echo htmlspecialchars($sItem['number']); ?></span>
                                    <?php if (!empty($sItem['suffix'])): ?><span class="hero-stat-suffix"><?php echo htmlspecialchars($sItem['suffix']); ?></span><?php endif; ?>
                                </div>
                                <div class="hero-stat-label"><?php echo htmlspecialchars($sItem['label']); ?></div>
                                <?php if (!empty($sItem['sublabel'])): ?>
                                    <div class="hero-stat-sublabel"><?php echo htmlspecialchars($sItem['sublabel']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Trusted Brands Section (Replicated from falhen.com) -->
<?php 
    $allBrandLogos = getBrandLogosRepo();
    $visibleBrands = array_values(array_filter($allBrandLogos, function($b) {
        return !empty($b['visible']);
    }));
    if (empty($visibleBrands)) {
        $visibleBrands = array_values($allBrandLogos);
    }
?>
<section class="brands-section">
    <div class="brands-title">TRUSTED BY WORLD-CLASS BRANDS</div>
    <div class="brands-ticker-wrapper">
        <div class="brands-ticker-track">
            <!-- First Set -->
            <?php foreach ($visibleBrands as $brand): ?>
                <div class="brand-card">
                    <?php if (!empty($brand['image'])): ?>
                        <img src="<?php echo htmlspecialchars(getCloudinaryUrl($brand['image'])); ?>" alt="<?php echo htmlspecialchars($brand['name']); ?>" style="max-height: 26px; max-width: 110px; object-fit: contain;">
                    <?php else: ?>
                        <i class="<?php echo htmlspecialchars(!empty($brand['icon']) ? $brand['icon'] : 'fa-solid fa-star'); ?>"></i> 
                        <?php echo htmlspecialchars($brand['name']); ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Duplicate Set for Seamless Infinite Ticker Loop -->
            <?php foreach ($visibleBrands as $brand): ?>
                <div class="brand-card">
                    <?php if (!empty($brand['image'])): ?>
                        <img src="<?php echo htmlspecialchars(getCloudinaryUrl($brand['image'])); ?>" alt="<?php echo htmlspecialchars($brand['name']); ?>" style="max-height: 26px; max-width: 110px; object-fit: contain;">
                    <?php else: ?>
                        <i class="<?php echo htmlspecialchars(!empty($brand['icon']) ? $brand['icon'] : 'fa-solid fa-star'); ?>"></i> 
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
            <h2 class="section-title" style="font-size: 2.75rem; font-weight: 700;">Our Services</h2>
            <p class="section-subtitle" style="max-width: 620px; margin: 0 auto; color: var(--text-muted); font-size: 1.05rem;">From concept to completion, we bring your vision to life with creativity and precision.</p>
        </div>
        
        <div class="services-grid">
            <?php 
            $homepageServices = getServicesRepo();
            foreach ($homepageServices as $sItem): 
            ?>
                <div class="service-card">
                    <div class="service-card-banner">
                        <img src="<?php echo htmlspecialchars($sItem['image']); ?>" alt="<?php echo htmlspecialchars($sItem['title']); ?>" onerror="this.src='/assets/img/hero.jpg';">
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
                        <a href="/service-single.php?slug=<?php echo htmlspecialchars($sItem['slug']); ?>" class="service-learn-link">Learn More <i class="fa-solid fa-arrow-right"></i></a>
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
                    <span class="services-dot <?php echo ($idx === 0) ? 'active' : ''; ?>" data-index="<?php echo $idx; ?>"></span>
                <?php endforeach; ?>
            </div>

            <button type="button" class="services-carousel-btn" id="servicesNextBtn" aria-label="Next Service">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>

<!-- Stats Counter / By the Numbers Section (Synced with Admin > Hero & Stats) -->
<?php
if (!empty($heroStats['visible']) && $heroStats['placement'] === 'section'):
    $statsBadge = $heroStats['badge_label'] ?? 'By the Numbers';
    $statsWhite = $heroStats['headline_white'] ?? 'A Decade of';
    $statsRed   = $heroStats['headline_red'] ?? 'Impact';
    $statsDesc  = $heroStats['description'] ?? '';
?>
<section class="section" id="impact-stats" style="padding-top: 20px;">
    <div class="container">
        <div class="section-title-wrapper" style="text-align: center;">
            <?php if (!empty($statsBadge)): ?>
                <div class="section-badge-pill" style="display: inline-block; margin-bottom: 12px;">
                    <?php echo htmlspecialchars($statsBadge); ?>
                </div>
            <?php endif; ?>
            <h2 class="section-title" style="font-size: clamp(2rem, 3.8vw, 2.85rem); font-weight: 800;">
                <?php echo htmlspecialchars($statsWhite); ?> <span style="color: #dc2626;"><?php echo htmlspecialchars($statsRed); ?></span>
            </h2>
            <?php if (!empty($statsDesc)): ?>
                <p class="section-subtitle" style="max-width: 640px; margin: 12px auto 0 auto; color: var(--text-muted); font-size: 1.02rem;">
                    <?php echo htmlspecialchars($statsDesc); ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="impact-grid">
            <?php foreach ($heroStats['items'] as $st): ?>
                <div class="impact-card">
                    <div class="impact-icon-box">
                        <i class="<?php echo htmlspecialchars(!empty($st['icon']) ? $st['icon'] : 'ri-film-line'); ?>"></i>
                    </div>
                    <div class="impact-number">
                        <?php if (!empty($st['prefix'])): ?><span><?php echo htmlspecialchars($st['prefix']); ?></span><?php endif; ?><span class="stat-counter-val" data-target="<?php echo htmlspecialchars($st['number']); ?>"><?php echo htmlspecialchars($st['number'] ?? '0'); ?></span><?php if (!empty($st['suffix'])): ?><span class="impact-plus"><?php echo htmlspecialchars($st['suffix']); ?></span><?php endif; ?>
                    </div>
                    <div class="impact-label"><?php echo htmlspecialchars($st['label'] ?? ''); ?></div>
                    <?php if (!empty($st['sublabel'])): ?>
                        <div class="impact-sublabel"><?php echo htmlspecialchars($st['sublabel']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Featured Work Section (Dynamically powered by Portfolio Showcase Video Reels) -->
<?php 
$allPortfolioRepo = getPortfolioRepo();
$portfolioVideos = array_values(array_filter($allPortfolioRepo, function($i) {
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
            <h2 class="section-title stories-heading" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 60px; font-weight: 800; letter-spacing: -1.5px; line-height: 1.1; text-align: left;">Stories We've <span style="color: #dc2626;">Told.</span></h2>
        </div>

        <!-- Dynamic Filter Pill Tabs -->
        <?php 
        $uniqueCategories = array_unique(array_filter(array_map(function($f) {
            return strtoupper($f['category']);
        }, $featuredItems)));
        ?>
        <div class="portfolio-filter-bar">
            <button class="filter-pill-btn active" data-filter="all">ALL</button>
            <?php foreach ($uniqueCategories as $catName): ?>
                <button class="filter-pill-btn" data-filter="<?php echo htmlspecialchars($catName); ?>"><?php echo htmlspecialchars($catName); ?></button>
            <?php endforeach; ?>
        </div>

        <!-- Main Featured Video Showcase Player -->
        <div class="featured-showcase-player">
            <div class="showcase-player-bg" id="showcase-player-bg" style="background-image: url('<?php echo htmlspecialchars($heroImg); ?>');">
                <div class="showcase-player-overlay"></div>
                <div class="showcase-counter-badge"><span id="showcase-current">1</span> / <span id="showcase-total"><?php echo count($featuredItems); ?></span></div>
                
                <div class="showcase-content">
                    <div class="showcase-meta">
                        <span class="showcase-badge" id="showcase-category"><?php echo htmlspecialchars($heroItem['category']); ?></span>
                        <span class="showcase-client" id="showcase-client"><?php echo htmlspecialchars($heroItem['client']); ?></span>
                        <span class="showcase-duration" id="showcase-time"><?php echo htmlspecialchars($heroItem['duration']); ?></span>
                    </div>
                    <h3 class="showcase-title" id="showcase-title"><?php echo htmlspecialchars($heroItem['project_name']); ?></h3>
                    <div class="showcase-actions">
                        <button class="btn btn-hero-red trigger-video" id="showcase-play-btn" data-video="<?php echo htmlspecialchars($heroVideoUrl); ?>">
                            <i class="fa-solid fa-play"></i> Watch Film
                        </button>
                        <button class="showcase-fullscreen-btn trigger-video" id="showcase-full-btn" data-video="<?php echo htmlspecialchars($heroVideoUrl); ?>">
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
                    $thumbImg = getCloudinaryUrl($itemThumb);
                    $videoEmbed = 'https://www.youtube.com/embed/' . $ytId;
                    $isActive = (!empty($heroItem['id']) && !empty($item['id'])) ? ($heroItem['id'] === $item['id']) : ($idx === 0);
                    ?>
                    <div class="thumb-card <?php echo $isActive ? 'active' : ''; ?>" 
                         data-index="<?php echo $idx; ?>" 
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
                            <img src="<?php echo htmlspecialchars($thumbImg); ?>" alt="<?php echo htmlspecialchars($item['project_name']); ?>" onerror="this.src='https://i.ytimg.com/vi/<?php echo htmlspecialchars($ytId); ?>/hqdefault.jpg';">
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
                <button class="nav-arrow-btn" id="showcase-prev-btn" aria-label="Previous Showcase"><i class="fa-solid fa-chevron-left"></i></button>
                <button class="nav-arrow-btn" id="showcase-next-btn" aria-label="Next Showcase"><i class="fa-solid fa-chevron-right"></i></button>
                <span class="auto-advance-label">Auto-advancing every 6s</span>
            </div>
            <a href="/services.php" class="explore-services-btn">Explore Services <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="section" id="testimonials" style="background: #09090b; border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container">
        <div class="section-title-wrapper" style="text-align: center; margin-bottom: 50px;">
            <h2 class="section-title testimonials-heading" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 50px; font-weight: 700; letter-spacing: -1.5px; line-height: 1.1;">What Our Clients <span style="color: #dc2626;">Say</span></h2>
            <p class="section-subtitle" style="max-width: 620px; margin: 0 auto; color: var(--text-muted); font-size: 1.05rem;">Don't take our word for it — here's what the brands we've worked with have to say.</p>
        </div>

        <div class="testimonials-wrapper">
            <div class="testimonials-grid" id="testimonials-grid">
                <?php 
                $dynamicTestimonials = getTestimonialsRepo();
                $initActive = 1; // Center card (Sprint Series Brand Film) active by default as shown in reference
                foreach ($dynamicTestimonials as $idx => $tCard):
                ?>
                    <div class="testimonial-card <?php echo ($idx === $initActive) ? 'active featured-card' : ''; ?>" data-real-index="<?php echo $idx; ?>">
                        <p class="testimonial-quote">"<?php echo htmlspecialchars($tCard['quote']); ?>"</p>
                        <?php if (!empty($tCard['project'])): ?>
                            <div class="testimonial-tag-pill"><?php echo htmlspecialchars($tCard['project']); ?></div>
                        <?php endif; ?>
                        <div class="testimonial-footer">
                            <div class="testimonial-author">
                                <img src="<?php echo htmlspecialchars(getCloudinaryUrl($tCard['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80')); ?>" alt="<?php echo htmlspecialchars($tCard['name']); ?>" class="author-img" onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80';">
                                <div>
                                    <div class="author-name"><?php echo htmlspecialchars($tCard['name']); ?></div>
                                    <div class="author-role"><?php echo htmlspecialchars(($tCard['role'] ?? '') . (!empty($tCard['company']) ? ', ' . $tCard['company'] : '')); ?></div>
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
                <button class="nav-arrow-btn" id="testi-prev-btn" aria-label="Previous Testimonial"><i class="fa-solid fa-chevron-left"></i></button>
                <div class="testimonial-dots" id="testimonial-dots">
                    <?php foreach ($dynamicTestimonials as $idx => $tCard): ?>
                        <span class="dot <?php echo ($idx === $initActive) ? 'active' : ''; ?>" data-index="<?php echo $idx; ?>"></span>
                    <?php endforeach; ?>
                </div>
                <button class="nav-arrow-btn" id="testi-next-btn" aria-label="Next Testimonial"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>
    </div>
</section>

<!-- BTS Section (Dynamic Admin Settings Integration) -->
<?php 
$btsWhite = !empty($settings['bts_headline_white']) ? $settings['bts_headline_white'] : 'Every Frame';
$btsRed = !empty($settings['bts_headline_red']) ? $settings['bts_headline_red'] : 'Speaks';
$btsDesc = !empty($settings['bts_description']) ? $settings['bts_description'] : 'A raw look at what it takes to create world-class content — from location scouts to post-production suites.';
$btsItems = $settings['bts_items'] ?? [
    ['id' => 1, 'title' => 'On set — RedBull Campaign, Dubai 2024', 'subtitle' => 'Director reviewing footage on set', 'image' => 'https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?auto=format&fit=crop&w=800&q=80', 'visible' => true],
    ['id' => 2, 'title' => 'ARRI ALEXA Mini LF — lens prep', 'subtitle' => 'Camera rig setup', 'image' => 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?auto=format&fit=crop&w=800&q=80', 'visible' => true],
    ['id' => 3, 'title' => 'Lighting rig — HBO Teaser, Cape Town', 'subtitle' => 'Lighting setup', 'image' => 'https://images.unsplash.com/photo-1598899134739-24c46f58b8c0?auto=format&fit=crop&w=800&q=80', 'visible' => true],
    ['id' => 4, 'title' => 'Aerial Drone Scout — Kenya Safari', 'subtitle' => '4K aerial footage capture', 'image' => 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?auto=format&fit=crop&w=800&q=80', 'visible' => true],
    ['id' => 5, 'title' => 'Post-Production Suite — Color Grading', 'subtitle' => 'DaVinci Resolve studio session', 'image' => 'https://images.unsplash.com/photo-1536240478700-b869070f9279?auto=format&fit=crop&w=800&q=80', 'visible' => true],
    ['id' => 6, 'title' => 'Wedding Cinema Shoot — Sunset', 'subtitle' => 'Romantic Golden Hour capture', 'image' => 'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=800&q=80', 'visible' => true]
];
?>
<section class="section" id="bts" style="background: #09090b; border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container">
        <!-- Section Header Flex -->
        <div class="team-header-flex" style="margin-bottom: 40px;">
            <div class="team-header-left">
                <h2 class="section-title" style="font-size: 2.75rem; font-weight: 700;">
                    <?php echo htmlspecialchars($btsWhite); ?> <span style="color: #dc2626;"><?php echo htmlspecialchars($btsRed); ?></span>
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
                $imgCdn = getCloudinaryUrl($bts['image']);
                $captionText = !empty($bts['title']) ? $bts['title'] : ($bts['subtitle'] ?? 'Production BTS');
                ?>
                <div class="bts-card trigger-lightbox" data-img="<?php echo htmlspecialchars($imgCdn); ?>" data-caption="<?php echo htmlspecialchars($captionText); ?>">
                    <img src="<?php echo htmlspecialchars($imgCdn); ?>" alt="<?php echo htmlspecialchars($captionText); ?>" onerror="this.src='/assets/img/hero.jpg';">
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
        <button class="bts-lightbox-close" id="btsLightboxClose"><i class="fa-solid fa-xmark"></i></button>
        <img src="" alt="BTS Lightbox Image" id="btsLightboxImg">
        <div class="bts-lightbox-caption" id="btsLightboxCaption"></div>
    </div>
</div>

<!-- Book a Discovery Call Section (Replicated from reference mockup) -->
<section class="section" id="lets-talk" style="background: #09090b; border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container">
        <!-- Section Header -->
        <div class="section-title-wrapper" style="text-align: center; margin-bottom: 50px;">
            <h2 class="section-title discovery-main-title">Book a <span style="color: #ef4444;">Discovery Call</span></h2>
            <p class="section-subtitle discovery-main-subtitle">30 minutes. No sales pitch. Just a candid conversation about your project.</p>
        </div>

        <!-- 2-Column Discovery Call Layout -->
        <div class="discovery-grid">
            <!-- Left Side: Hero Image & Feature Cards -->
            <div class="discovery-left">
                <!-- Top Hero Image Box with Available Badge -->
                <div class="discovery-hero-box">
                    <img src="/assets/img/discovery_call.jpg" alt="Book a Discovery Call - Falhen Media">
                    <div class="discovery-hero-overlay">
                        <span class="discovery-available-pill">
                            <span class="pulse-green-dot"></span> Available &mdash; Book your slot now
                        </span>
                    </div>
                </div>

                <!-- 2x2 Feature Cards Grid -->
                <div class="discovery-features-grid">
                    <!-- Feature 1 -->
                    <div class="discovery-feature-card">
                        <div class="discovery-feature-header">
                            <div class="discovery-icon-box"><i class="fa-regular fa-clock"></i></div>
                            <h4 class="discovery-feature-title">30-Min Deep Dive</h4>
                        </div>
                        <p class="discovery-feature-desc">No fluff &mdash; just a focused conversation about your project and goals.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="discovery-feature-card">
                        <div class="discovery-feature-header">
                            <div class="discovery-icon-box"><i class="fa-regular fa-lightbulb"></i></div>
                            <h4 class="discovery-feature-title">Creative Direction</h4>
                        </div>
                        <p class="discovery-feature-desc">We'll give you initial ideas and directional thoughts on the spot.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="discovery-feature-card">
                        <div class="discovery-feature-header">
                            <div class="discovery-icon-box"><i class="fa-regular fa-file-lines"></i></div>
                            <h4 class="discovery-feature-title">Proposal in 48hrs</h4>
                        </div>
                        <p class="discovery-feature-desc">A full scope, timeline, and budget proposal delivered fast after the call.</p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="discovery-feature-card">
                        <div class="discovery-feature-header">
                            <div class="discovery-icon-box"><i class="fa-solid fa-lock"></i></div>
                            <h4 class="discovery-feature-title">Zero Commitment</h4>
                        </div>
                        <p class="discovery-feature-desc">No pressure, no contracts &mdash; just a conversation between creators.</p>
                    </div>
                </div>

            </div>

            <!-- Right Side: Booking Form Card -->
            <div class="discovery-form-card">
                <div class="discovery-form-header">
                    <h3 class="discovery-form-title">Your Details</h3>
                    <p class="discovery-form-subtitle">We'll confirm your slot within 2 hours.</p>
                </div>

                <div id="formFeedback" style="display: none; margin-bottom: 20px;"></div>

                <form id="inquiryForm" class="discovery-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="discovery-label">FULL NAME *</label>
                            <input type="text" name="full_name" class="discovery-input" placeholder="Jane Smith" required>
                        </div>
                        <div class="form-group">
                            <label class="discovery-label">EMAIL ADDRESS *</label>
                            <input type="email" name="email" class="discovery-input" placeholder="jane@yourbrand.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="discovery-label">SERVICE</label>
                        <div class="discovery-select-wrapper">
                            <select name="service_type" class="discovery-input discovery-select" required>
                                <option value="" disabled selected>Select...</option>
                                <option value="Commercial Video & Ad Campaign">Commercial Video &amp; Ad Campaign</option>
                                <option value="Brand Story & Documentary">Brand Story &amp; Documentary</option>
                                <option value="Music Video & Creative Cinema">Music Video &amp; Creative Cinema</option>
                                <option value="Post-Production & Color Grading">Post-Production &amp; Color Grading</option>
                                <option value="Event Cinema & Live Coverage">Event Cinema &amp; Live Coverage</option>
                                <option value="Social Media Content Retainer">Social Media Content Retainer</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="position: relative;">
                        <label class="discovery-label">ABOUT YOUR PROJECT</label>
                        <textarea name="project_details" id="projectDetails" class="discovery-input discovery-textarea" maxlength="500" placeholder="Brief description of your project..."></textarea>
                        <div class="char-count-wrapper">
                            <span class="char-count" id="charCount">0/500</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-discovery-submit" id="submitInquiryBtn">
                        <i class="fa-regular fa-calendar-check"></i> Book My Discovery Call
                    </button>

                    <p class="discovery-form-footer-note">We'll confirm within 2 hours. No spam, ever.</p>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
